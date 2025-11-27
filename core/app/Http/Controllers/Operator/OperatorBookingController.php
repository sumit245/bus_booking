<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\OperatorBooking;
use App\Models\OperatorBus;
use App\Models\OperatorRoute;
use App\Models\BusSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\BookedTicket;

class OperatorBookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:operator');
    }

    /**
     * Display a listing of operator bookings.
     */
    public function index(Request $request)
    {
        $operator = auth('operator')->user();

        $query = OperatorBooking::query()
            ->with([
                'operatorBus',
                'operatorRoute.originCity:id,city_name',
                'operatorRoute.destinationCity:id,city_name',
                'busSchedule'
            ])
            ->where('operator_id', $operator->id);

        // Apply filters
        if ($request->filled('bus_id')) {
            $query->where('operator_bus_id', $request->bus_id);
        }

        if ($request->filled('route_id')) {
            $query->where('operator_route_id', $request->route_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('journey_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('journey_date', '<=', $request->date_to);
        }

        $bookings = $query->orderBy('journey_date', 'desc')->paginate(15);

        // Get filter options
        $buses = OperatorBus::where('operator_id', $operator->id)->get();
        $routes = OperatorRoute::with(['originCity', 'destinationCity'])->where('operator_id', $operator->id)->get();

        return view('operator.bookings.index', compact('bookings', 'buses', 'routes'));
    }

    /**
     * Display user bookings (actual revenue) for this operator.
     */
    public function userBookings(\Illuminate\Http\Request $request)
    {
        $operator = auth('operator')->user();

        // Handle Excel export
        if ($request->has('export')) {
            return $this->exportUserBookings($request, $operator);
        }

        $query = \App\Models\BookedTicket::query()
            ->where('operator_id', $operator->id)
            ->whereIn('booking_source', ['user', 'agent', 'admin']);

        if ($request->filled('bus_id')) {
            $query->where('bus_id', $request->bus_id);
        }
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('date_of_journey', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date_of_journey', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'date_of_journey');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination with custom per page
        $perPage = $request->get('per_page', getPaginate());
        $bookings = $query->paginate($perPage)->appends($request->except('page'));

        $buses = OperatorBus::where('operator_id', $operator->id)->get(['id', 'travel_name', 'bus_type']);
        $routes = OperatorRoute::with(['originCity:city_id,city_name', 'destinationCity:city_id,city_name'])
            ->where('operator_id', $operator->id)
            ->get(['id', 'origin_city_id', 'destination_city_id', 'route_name']);

        return view('operator.bookings.user_bookings', compact('bookings', 'buses', 'routes'));
    }

    /**
     * Export user bookings to Excel.
     */
    private function exportUserBookings($request, $operator)
    {
        $query = BookedTicket::query()
            ->where('operator_id', $operator->id)
            ->whereIn('booking_source', ['user', 'agent', 'admin']);

        // Apply same filters as view
        if ($request->filled('bus_id')) {
            $query->where('bus_id', $request->bus_id);
        }
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('date_of_journey', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date_of_journey', '<=', $request->date_to);
        }

        $bookings = $query->orderByDesc('date_of_journey')->get();

        $filename = 'user_bookings_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($bookings) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Booking ID', 'Bus', 'Route', 'Boarding Point', 'Dropping Point', 'Seats Booked', 'Journey Date', 'Amount', 'Paid', 'Status', 'Created']);

            foreach ($bookings as $booking) {
                $boardingDetails = json_decode($booking->boarding_point_details, true);
                $droppingDetails = json_decode($booking->dropping_point_details, true);

                $boardingPoint = isset($boardingDetails[0]['PointLocation']) ? $boardingDetails[0]['PointLocation'] : 'N/A';
                $droppingPoint = isset($droppingDetails[0]['PointLocation']) ? $droppingDetails[0]['PointLocation'] : 'N/A';

                fputcsv($file, [
                    $booking->id,
                    $booking->travel_name . ' (' . $booking->bus_type . ')',
                    $booking->origin_city . ' → ' . $booking->destination_city,
                    $boardingPoint,
                    $droppingPoint,
                    $booking->ticket_count ?? 0,
                    $booking->date_of_journey,
                    number_format((float) ($booking->total_amount ?? 0), 2),
                    number_format((float) ($booking->paid_amount ?? 0), 2),
                    $booking->status == 1 ? 'Booked' : 'Cancelled',
                    optional($booking->created_at)->format('M d, Y')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show the form for creating a new booking.
     */
    public function create(Request $request)
    {
        $operator = auth('operator')->user();

        $buses = OperatorBus::where('operator_id', $operator->id)->get();
        $routes = OperatorRoute::with(['originCity', 'destinationCity'])
            ->where('operator_id', $operator->id)
            ->get();

        // Log for debugging
        \Log::info('Block Seats Create Page', [
            'buses_count' => $buses->count(),
            'routes_count' => $routes->count(),
            'routes' => $routes->map(function ($route) {
                return [
                    'id' => $route->id,
                    'route_name' => $route->route_name,
                    'origin_city_id' => $route->origin_city_id,
                    'destination_city_id' => $route->destination_city_id,
                    'origin_city' => $route->originCity ? $route->originCity->city_name : 'NULL',
                    'destination_city' => $route->destinationCity ? $route->destinationCity->city_name : 'NULL'
                ];
            })->toArray()
        ]);

        // Pre-select bus if provided
        $selectedBus = null;
        if ($request->filled('bus_id')) {
            $selectedBus = OperatorBus::where('operator_id', $operator->id)
                ->where('id', $request->bus_id)
                ->first();
        }

        return view('operator.bookings.create', compact('buses', 'routes', 'selectedBus'));
    }

    /**
     * Store a newly created booking.
     */
    public function store(Request $request)
    {
        $operator = auth('operator')->user();

        // Custom validation based on date range mode
        $isDateRange = $request->boolean('is_date_range');

        if ($isDateRange) {
            $request->validate([
                'operator_bus_id' => 'required|exists:operator_buses,id',
                'operator_route_id' => 'required|exists:operator_routes,id',
                'bus_schedule_id' => 'nullable|exists:bus_schedules,id',
                'blocked_seats' => 'required|array|min:1',
                'blocked_seats.*' => 'required|string',
                'journey_date_start' => 'required|date|after_or_equal:2025-10-17',
                'journey_date_end' => 'required|date|after_or_equal:journey_date_start',
                'is_date_range' => 'boolean',
                'booking_reason' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:1000'
            ]);
        } else {
            $request->validate([
                'operator_bus_id' => 'required|exists:operator_buses,id',
                'operator_route_id' => 'required|exists:operator_routes,id',
                'bus_schedule_id' => 'nullable|exists:bus_schedules,id',
                'blocked_seats' => 'required|array|min:1',
                'blocked_seats.*' => 'required|string',
                'journey_date' => 'required|date|after_or_equal:2025-10-17',
                'is_date_range' => 'boolean',
                'booking_reason' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:1000'
            ]);
        }

        // Verify bus belongs to operator
        $bus = OperatorBus::where('operator_id', $operator->id)
            ->where('id', $request->operator_bus_id)
            ->firstOrFail();

        // Verify route belongs to operator
        $route = OperatorRoute::where('operator_id', $operator->id)
            ->where('id', $request->operator_route_id)
            ->firstOrFail();

        // Determine journey date (single date or start date for range)
        $journeyDate = $request->journey_date ?: $request->journey_date_start;

        // Create operator booking
        $booking = OperatorBooking::create([
            'operator_id' => $operator->id,
            'operator_bus_id' => $request->operator_bus_id,
            'operator_route_id' => $request->operator_route_id,
            'bus_schedule_id' => $request->bus_schedule_id,
            'blocked_seats' => $request->blocked_seats,
            'total_seats_blocked' => count($request->blocked_seats),
            'journey_date' => $journeyDate,
            'journey_date_end' => $request->journey_date_end,
            'is_date_range' => $request->boolean('is_date_range'),
            'booking_reason' => $request->booking_reason,
            'notes' => $request->notes,
            'status' => 'active',
            'blocked_amount' => 0 // No payment for operator bookings
        ]);

        // Also create a regular booking in booked_tickets table (like user booking but without payment)
        try {
            $this->createOperatorBookedTicket($booking, $bus, $route);
            \Log::info('createOperatorBookedTicket completed successfully', ['booking_id' => $booking->id]);
        } catch (\Exception $e) {
            \Log::error('createOperatorBookedTicket failed', ['error' => $e->getMessage(), 'booking_id' => $booking->id]);
        }

        $notify[] = ['success', 'Seats blocked successfully.'];
        return redirect()->route('operator.bookings.index')->withNotify($notify);
    }

    /**
     * Create a booked ticket entry for operator booking to prevent double-booking by users
     */
    private function createOperatorBookedTicket($operatorBooking, $bus, $route)
    {
        $operator = $operatorBooking->operator;

        // Get schedule details
        $schedule = $operatorBooking->busSchedule;
        if (!$schedule && $operatorBooking->bus_schedule_id) {
            $schedule = \App\Models\BusSchedule::find($operatorBooking->bus_schedule_id);
        }

        if (!$schedule) {
            \Log::error('Cannot create booked ticket: schedule not found', [
                'operator_booking_id' => $operatorBooking->id,
                'bus_schedule_id' => $operatorBooking->bus_schedule_id
            ]);
            return;
        }

        $departureTime = $schedule->departure_time;
        $arrivalTime = $schedule->arrival_time;

        // Get first boarding and dropping points for this schedule's route
        $boardingPoint = \App\Models\BoardingPoint::where('operator_route_id', $route->id)
            ->active()
            ->ordered()
            ->first();

        $droppingPoint = \App\Models\DroppingPoint::where('operator_route_id', $route->id)
            ->active()
            ->ordered()
            ->first();

        // Prepare boarding point details in format expected by SeatAvailabilityService
        $boardingPointDetails = $boardingPoint ? [
            'CityPointIndex' => $boardingPoint->point_index,
            'CityPointLocation' => $boardingPoint->point_location ?? $boardingPoint->point_name,
            'CityPointName' => $boardingPoint->point_name,
            'CityPointTime' => $boardingPoint->point_time ?? $departureTime
        ] : null;

        // Prepare dropping point details in format expected by SeatAvailabilityService
        $droppingPointDetails = $droppingPoint ? [
            'CityPointIndex' => $droppingPoint->point_index,
            'CityPointLocation' => $droppingPoint->point_location ?? $droppingPoint->point_name,
            'CityPointName' => $droppingPoint->point_name,
            'CityPointTime' => $droppingPoint->point_time ?? $arrivalTime
        ] : null;

        // Generate unique PNR
        $pnrNumber = 'OP' . strtoupper(Str::random(8));

        // IMPORTANT: Ensure blocked_seats is an array (Laravel casts handle this)
        // If $operatorBooking->blocked_seats is already an array, don't json_encode it again
        $seatsArray = $operatorBooking->blocked_seats;
        if (!is_array($seatsArray)) {
            $seatsArray = json_decode($seatsArray, true) ?: [];
        }

        \Log::info('Seats data before saving to booked_tickets', [
            'seats_type' => gettype($seatsArray),
            'seats_value' => $seatsArray,
            'is_array' => is_array($seatsArray)
        ]);

        // Create the booked ticket entry - CRITICAL for blocking seats from user bookings
        \App\Models\BookedTicket::create([
            // Operator details (as passenger)
            'passenger_name' => $operator->company_name ?? 'Operator Block',
            'passenger_phone' => $operator->mobile,
            'passenger_email' => $operator->email,
            'gender' => 0, // As requested

            // IDs and references
            'user_id' => null, // As requested - this is operator booking, not user
            'operator_id' => $operator->id,
            'operator_booking_id' => $operatorBooking->id,
            'booking_id' => 'OP-' . $operatorBooking->id,
            'ticket_no' => 'OP-' . $operatorBooking->id,
            'pnr_number' => $pnrNumber,
            'operator_pnr' => $pnrNumber,

            // Bus and route details
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'schedule_id' => $schedule->id,
            'bus_type' => $bus->bus_type,
            'travel_name' => $bus->travel_name,
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime,

            // Journey details
            'source_destination' => $route->originCity->city_name . ' to ' . $route->destinationCity->city_name,
            'origin_city' => $route->originCity->city_name,
            'destination_city' => $route->destinationCity->city_name,
            'date_of_journey' => $operatorBooking->journey_date->format('Y-m-d'),

            // Boarding and dropping points
            'boarding_point' => $boardingPoint ? $boardingPoint->id : null,
            'boarding_point_details' => $boardingPointDetails ? json_encode($boardingPointDetails) : null,
            'dropping_point' => $droppingPoint ? $droppingPoint->id : null,
            'dropping_point_details' => $droppingPointDetails ? json_encode($droppingPointDetails) : null,

            // Seat details - CRITICAL: This is what SeatAvailabilityService checks
            // Pass as array - Laravel's cast will handle JSON encoding
            'seats' => $seatsArray,
            'seat_numbers' => implode(',', $seatsArray),
            'ticket_count' => count($seatsArray),

            // Financial details - all zero as requested
            'unit_price' => 0,
            'sub_total' => 0,
            'service_charge' => 0,
            'service_charge_percentage' => 0,
            'platform_fee' => 0,
            'gst' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,

            // Booking source and status
            'booking_source' => 'operator', // As requested
            'status' => 0, // Pending status - will be included in SeatAvailabilityService query
            'payment_status' => 'unpaid',

            // Additional metadata
            'booking_type' => 'operator_block',
            'notes' => 'Operator blocked seats: ' . ($operatorBooking->booking_reason ?? 'No reason provided')
        ]);

        \Log::info('Created booked_ticket entry for operator booking', [
            'operator_booking_id' => $operatorBooking->id,
            'seats' => $operatorBooking->blocked_seats,
            'schedule_id' => $schedule->id,
            'date' => $operatorBooking->journey_date->format('Y-m-d')
        ]);
    }

    /**
     * Display the specified booking.
     */
    public function show(OperatorBooking $booking)
    {
        // Check if booking belongs to operator
        $operator = auth('operator')->user();
        if ($booking->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        $booking->load(['operatorBus', 'operatorRoute.originCity', 'operatorRoute.destinationCity', 'busSchedule']);
        return view('operator.bookings.show', compact('booking'));
    }

    /**
     * Show the form for editing the specified booking.
     */
    public function edit(OperatorBooking $booking)
    {
        // Check if booking belongs to operator
        $operator = auth('operator')->user();
        if ($booking->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        $buses = OperatorBus::where('operator_id', $operator->id)->get();
        $routes = OperatorRoute::with(['originCity', 'destinationCity'])->where('operator_id', $operator->id)->get();

        return view('operator.bookings.edit', compact('booking', 'buses', 'routes'));
    }

    /**
     * Update the specified booking.
     */
    public function update(Request $request, OperatorBooking $booking)
    {
        // Check if booking belongs to operator
        $operator = auth('operator')->user();
        if ($booking->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        $request->validate([
            'blocked_seats' => 'required|array|min:1',
            'blocked_seats.*' => 'required|string',
            'journey_date' => 'required|date|after_or_equal:today',
            'journey_date_end' => 'nullable|date|after_or_equal:journey_date',
            'is_date_range' => 'boolean',
            'booking_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000'
        ]);

        // Check for existing bookings on same dates/seats (excluding current booking)
        $existingBookings = $this->checkSeatAvailability(
            $booking->operator_bus_id,
            $request->blocked_seats,
            $request->journey_date,
            $request->journey_date_end,
            $request->is_date_range,
            $booking->id
        );

        if (!empty($existingBookings)) {
            return back()->withErrors([
                'blocked_seats' => 'Some seats are already blocked for the selected dates: ' . implode(', ', $existingBookings)
            ])->withInput();
        }

        $booking->update([
            'blocked_seats' => $request->blocked_seats,
            'total_seats_blocked' => count($request->blocked_seats),
            'journey_date' => $request->journey_date,
            'journey_date_end' => $request->journey_date_end,
            'is_date_range' => $request->boolean('is_date_range'),
            'booking_reason' => $request->booking_reason,
            'notes' => $request->notes
        ]);

        $notify[] = ['success', 'Booking updated successfully.'];
        return redirect()->route('operator.bookings.index')->withNotify($notify);
    }

    /**
     * Remove the specified booking.
     */
    public function destroy(OperatorBooking $booking)
    {
        // Check if booking belongs to operator
        $operator = auth('operator')->user();
        if ($booking->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        // Delete corresponding booked_ticket entry to free up seats
        \App\Models\BookedTicket::where('operator_booking_id', $booking->id)->delete();

        $booking->delete();

        $notify[] = ['success', 'Booking cancelled successfully. Seats are now available for users.'];
        return redirect()->route('operator.bookings.index')->withNotify($notify);
    }

    /**
     * Toggle booking status.
     */
    public function toggleStatus(OperatorBooking $booking)
    {
        // Check if booking belongs to operator
        $operator = auth('operator')->user();
        if ($booking->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        $newStatus = $booking->status === 'active' ? 'cancelled' : 'active';
        $booking->update(['status' => $newStatus]);

        // Update corresponding booked_ticket status
        // Active = status 0 (pending, blocks seats), Cancelled = status 3 (cancelled, frees seats)
        $ticketStatus = $newStatus === 'active' ? 0 : 3;
        \App\Models\BookedTicket::where('operator_booking_id', $booking->id)
            ->update(['status' => $ticketStatus]);

        $statusText = $newStatus === 'active' ? 'activated' : 'cancelled';
        $notify[] = ['success', "Booking {$statusText} successfully."];

        return redirect()->route('operator.bookings.index')->withNotify($notify);
    }

    /**
     * Get available seats for a bus on specific dates.
     */
    public function getAvailableSeats(Request $request)
    {
        $operator = auth('operator')->user();

        $request->validate([
            'bus_id' => 'required|exists:operator_buses,id',
            'journey_date' => 'required|date',
            'journey_date_end' => 'nullable|date|after_or_equal:journey_date',
            'is_date_range' => 'boolean'
        ]);

        $bus = OperatorBus::where('operator_id', $operator->id)
            ->where('id', $request->bus_id)
            ->firstOrFail();

        // Get seat layout
        $seatLayout = $bus->activeSeatLayout;
        if (!$seatLayout) {
            return response()->json(['error' => 'No seat layout found for this bus'], 400);
        }

        // Parse seat layout to get all available seats
        $allSeats = $this->parseSeatLayout($seatLayout->html_layout);

        // Get blocked seats for the date range
        $blockedSeats = $this->getBlockedSeats(
            $request->bus_id,
            $request->journey_date,
            $request->journey_date_end,
            $request->is_date_range
        );

        // Filter available seats
        $availableSeats = array_diff($allSeats, $blockedSeats);

        return response()->json([
            'all_seats' => $allSeats,
            'blocked_seats' => $blockedSeats,
            'available_seats' => array_values($availableSeats)
        ]);
    }

    /**
     * Get seat layout with blocked seats for a bus on a specific date
     */
    public function getSeatLayout(Request $request)
    {
        $operator = auth('operator')->user();

        $request->validate([
            'bus_id' => 'required|exists:operator_buses,id',
            'schedule_id' => 'required|exists:bus_schedules,id',
            'journey_date' => 'required|date',
            'journey_date_end' => 'nullable|date|after_or_equal:journey_date',
            'is_date_range' => 'boolean'
        ]);

        $bus = OperatorBus::where('operator_id', $operator->id)
            ->where('id', $request->bus_id)
            ->firstOrFail();

        // Get seat layout
        $seatLayout = $bus->activeSeatLayout;
        if (!$seatLayout) {
            return response()->json(['error' => 'No seat layout found for this bus'], 400);
        }

        // Get blocked seats from BOTH sources:
        // 1. Customer bookings (booked_tickets table) - using SeatAvailabilityService
        // 2. Operator blocked seats (operator_bookings table) - using getBlockedSeats method

        // Get customer bookings using the same service as SiteController/ApiTicketController
        $availabilityService = new \App\Services\SeatAvailabilityService();
        $customerBookedSeats = $availabilityService->getBookedSeats(
            $request->bus_id,
            $request->schedule_id,
            $request->journey_date,
            null, // boardingPointIndex - get all bookings
            null  // droppingPointIndex - get all bookings
        );

        // Get operator blocked seats (for date range if applicable)
        $operatorBlockedSeats = $this->getBlockedSeats(
            $request->bus_id,
            $request->journey_date,
            $request->journey_date_end,
            $request->is_date_range
        );

        // Combine both sources and remove duplicates
        $allBlockedSeats = array_unique(array_merge($customerBookedSeats, $operatorBlockedSeats));

        \Log::info('OperatorBookingController@getSeatLayout: Retrieved data', [
            'bus_id' => $request->bus_id,
            'schedule_id' => $request->schedule_id,
            'journey_date' => $request->journey_date,
            'customer_booked_seats' => $customerBookedSeats,
            'customer_booked_count' => count($customerBookedSeats),
            'operator_blocked_seats' => $operatorBlockedSeats,
            'operator_blocked_count' => count($operatorBlockedSeats),
            'all_blocked_seats' => $allBlockedSeats,
            'total_blocked_count' => count($allBlockedSeats)
        ]);

        // Modify HTML on-the-fly: change nseat→bseat, hseat→bhseat, vseat→bvseat for blocked seats
        // This matches the approach used in SiteController and ApiTicketController
        $modifiedHtml = $this->modifyHtmlLayoutForBookedSeats($seatLayout->html_layout, $allBlockedSeats);

        // Parse the modified HTML to get structured seat data
        $parsedLayout = parseSeatHtmlToJson($modifiedHtml);

        return response()->json([
            'html' => $parsedLayout,
            'blocked_seats' => $allBlockedSeats,
            'customer_booked_seats' => $customerBookedSeats,
            'operator_blocked_seats' => $operatorBlockedSeats,
            'total_seats' => $bus->total_seats,
            'available_seats' => max(0, $bus->total_seats - count($allBlockedSeats))
        ]);
    }

    /**
     * Convert seat layout array to HTML
     */
    private function convertSeatLayoutToHtml($seatLayout, $blockedSeats = [])
    {
        $html = '<div class="bus-layout">';

        foreach ($seatLayout as $deckName => $deck) {
            $html .= '<div class="deck ' . $deckName . '">';
            $html .= '<h5>' . ucfirst(str_replace('_', ' ', $deckName)) . '</h5>';
            $html .= '<div class="seats-container">';

            foreach ($deck['seats'] as $seat) {
                $seatId = $seat['seat_id'];
                $isBlocked = in_array($seatId, $blockedSeats);
                $seatClass = $seat['type'] . ' ' . $seat['category'];
                $blockedClass = $isBlocked ? ' blocked' : '';

                $html .= '<div class="seat ' . $seatClass . $blockedClass . '" id="' . $seatId . '" ';
                $html .= 'style="left: ' . $seat['left'] . 'px; top: ' . $seat['position'] . 'px; ';
                $html .= 'width: ' . ($seat['width'] * 40) . 'px; height: ' . ($seat['height'] * 40) . 'px;" ';
                $html .= 'data-price="' . $seat['price'] . '" ';
                $html .= 'data-type="' . $seat['type'] . '">';
                $html .= $seatId;
                $html .= '</div>';
            }

            $html .= '</div></div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Check seat availability for booking.
     */
    private function checkSeatAvailability($busId, $seats, $startDate, $endDate = null, $isDateRange = false, $excludeBookingId = null)
    {
        $query = OperatorBooking::active()
            ->where('operator_bus_id', $busId)
            ->where(function ($q) use ($startDate, $endDate, $isDateRange) {
                if ($isDateRange && $endDate) {
                    $q->where(function ($q2) use ($startDate, $endDate) {
                        $q2->whereBetween('journey_date', [$startDate, $endDate])
                            ->orWhereBetween('journey_date_end', [$startDate, $endDate])
                            ->orWhere(function ($q3) use ($startDate, $endDate) {
                                $q3->where('journey_date', '<=', $startDate)
                                    ->where('journey_date_end', '>=', $endDate);
                            });
                    });
                } else {
                    $q->where('journey_date', $startDate);
                }
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        $existingBookings = $query->get();
        $conflictingSeats = [];

        foreach ($existingBookings as $booking) {
            $bookingSeats = is_array($booking->blocked_seats) ? $booking->blocked_seats : [$booking->blocked_seats];
            $conflictingSeats = array_merge($conflictingSeats, array_intersect($seats, $bookingSeats));
        }

        return array_unique($conflictingSeats);
    }

    /**
     * Get blocked seats for a bus on specific dates.
     */
    private function getBlockedSeats($busId, $startDate, $endDate = null, $isDateRange = false)
    {
        \Log::info('OperatorBookingController@getBlockedSeats: Starting', [
            'bus_id' => $busId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_date_range' => $isDateRange
        ]);

        $query = OperatorBooking::active()->where('operator_bus_id', $busId);

        if ($isDateRange && $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('journey_date', [$startDate, $endDate])
                    ->orWhereBetween('journey_date_end', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('journey_date', '<=', $startDate)
                            ->where('journey_date_end', '>=', $endDate);
                    });
            });
        } else {
            $query->where('journey_date', $startDate);
        }

        $bookings = $query->get();

        \Log::info('OperatorBookingController@getBlockedSeats: Query results', [
            'bookings_count' => $bookings->count(),
            'query_sql' => $query->toSql(),
            'query_bindings' => $query->getBindings()
        ]);

        $blockedSeats = [];

        foreach ($bookings as $booking) {
            \Log::info('OperatorBookingController@getBlockedSeats: Processing booking', [
                'booking_id' => $booking->id,
                'blocked_seats_raw' => $booking->blocked_seats,
                'blocked_seats_type' => gettype($booking->blocked_seats)
            ]);

            $seats = is_array($booking->blocked_seats) ? $booking->blocked_seats : [$booking->blocked_seats];
            $blockedSeats = array_merge($blockedSeats, $seats);
        }

        $result = array_unique($blockedSeats);

        \Log::info('OperatorBookingController@getBlockedSeats: Final result', [
            'blocked_seats' => $result,
            'count' => count($result)
        ]);

        return $result;
    }

    /**
     * Parse seat layout HTML to extract seat numbers.
     */
    private function parseSeatLayout($htmlLayout)
    {
        $seats = [];

        if (empty($htmlLayout)) {
            return $seats;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlLayout);
        $xpath = new \DOMXPath($dom);

        // Find all seat elements with id attributes (U1, L1, etc.)
        $seatElements = $xpath->query('//div[@id and (contains(@class, "hseat") or contains(@class, "nseat") or contains(@class, "vseat"))]');

        foreach ($seatElements as $seat) {
            $seatId = $seat->getAttribute('id');

            // Only add if it looks like a seat number (starts with letter + number)
            if (!empty($seatId) && preg_match('/^[A-Z]\d+$/', $seatId)) {
                $seats[] = $seatId;
            }
        }

        return array_unique($seats);
    }

    /**
     * Modify HTML layout to mark booked/blocked seats
     * This matches the implementation used in SiteController and ApiTicketController
     */
    private function modifyHtmlLayoutForBookedSeats(string $htmlLayout, array $blockedSeats): string
    {
        if (empty($blockedSeats)) {
            return $htmlLayout; // No modifications needed
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $htmlLayout, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($dom);

        foreach ($blockedSeats as $seatName) {
            // CRITICAL FIX: Match by @id attribute, not text content or onclick
            // This prevents "1" from matching "U1", "11", "21", etc.
            // Seat IDs are stored in the id attribute: <div id="U1" class="nseat"> or <div id="1" class="nseat">
            $nodes = $xpath->query("//*[@id='{$seatName}' and (contains(@class, 'nseat') or contains(@class, 'hseat') or contains(@class, 'vseat'))]");

            foreach ($nodes as $node) {
                $class = $node->getAttribute('class');
                // Replace nseat with bseat, hseat with bhseat, vseat with bvseat
                $class = str_replace(['nseat', 'hseat', 'vseat'], ['bseat', 'bhseat', 'bvseat'], $class);
                $node->setAttribute('class', $class);
            }
        }

        return $dom->saveHTML();
    }

}