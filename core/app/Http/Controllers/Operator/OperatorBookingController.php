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

        $query = OperatorBooking::with(['operatorBus', 'operatorRoute.originCity', 'operatorRoute.destinationCity', 'busSchedule'])
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
     * Show the form for creating a new booking.
     */
    public function create(Request $request)
    {
        $operator = auth('operator')->user();

        $buses = OperatorBus::where('operator_id', $operator->id)->get();
        $routes = OperatorRoute::with(['originCity', 'destinationCity'])->where('operator_id', $operator->id)->get();

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
     * Create a booked ticket entry for operator booking (like user booking but without payment)
     */
    private function createOperatorBookedTicket($operatorBooking, $bus, $route)
    {
        // Generate PNR number like in SiteController and ApiTicketController
        $pnrNumber = 'OP' . strtoupper(Str::random(8));

        // Prepare passenger names
        $passengerNames = [];
        foreach ($operatorBooking->blocked_seats as $seat) {
            $passengerNames[] = "Operator Blocked - {$seat}";
        }

        // Get schedule details - load the relationship if not already loaded
        $schedule = $operatorBooking->busSchedule;
        if (!$schedule && $operatorBooking->bus_schedule_id) {
            $schedule = \App\Models\BusSchedule::find($operatorBooking->bus_schedule_id);
        }
        $departureTime = $schedule ? $schedule->departure_time : '00:00:00';
        $arrivalTime = $schedule ? $schedule->arrival_time : '00:00:00';

        \Log::info('createOperatorBookedTicket values', [
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime,
            'journey_date' => $operatorBooking->journey_date ? $operatorBooking->journey_date->format('Y-m-d') : null,
            'operator_phone' => $operatorBooking->operator->phone ?? 'NULL'
        ]);

        // Get boarding and dropping points
        $boardingPoint = $route->boardingPoints->first();
        $droppingPoint = $route->droppingPoints->first();

        // Prepare boarding point details
        $boardingPointDetails = $boardingPoint ? [
            [
                'CityPointIndex' => $boardingPoint->id,
                'CityPointLocation' => $boardingPoint->point_address ?: $boardingPoint->point_location ?: $boardingPoint->point_name,
                'CityPointName' => $boardingPoint->point_name,
                'CityPointTime' => $boardingPoint->point_time ?: $departureTime
            ]
        ] : [];

        // Prepare dropping point details
        $droppingPointDetails = $droppingPoint ? [
            [
                'CityPointIndex' => $droppingPoint->id,
                'CityPointLocation' => $droppingPoint->point_address ?: $droppingPoint->point_location ?: $droppingPoint->point_name,
                'CityPointName' => $droppingPoint->point_name,
                'CityPointTime' => $droppingPoint->point_time ?: $arrivalTime
            ]
        ] : [];

        // Prepare bus details
        $busDetails = [
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime,
            'bus_type' => $bus->bus_type,
            'travel_name' => $bus->travel_name
        ];

        // Generate search token ID (similar to API format)
        $searchTokenId = 'OP_TOKEN_' . time() . '_' . $operatorBooking->id;

        // Create the booked ticket with all required fields
        \App\Models\BookedTicket::create([
            'user_id' => $operatorBooking->operator_id, // Use operator ID as user_id
            'operator_id' => $operatorBooking->operator_id,
            'operator_booking_id' => $operatorBooking->id,
            'booking_id' => 'OP-' . $operatorBooking->id,
            'ticket_no' => 'OP-' . $operatorBooking->id,
            'pnr_number' => $pnrNumber,
            'operator_pnr' => $pnrNumber, // Add operator_pnr field
            'bus_id' => $bus->id,
            'route_id' => $route->id,
            'schedule_id' => $operatorBooking->bus_schedule_id,
            'bus_type' => $bus->bus_type,
            'travel_name' => $bus->travel_name,
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime,
            'seat_numbers' => implode(',', $operatorBooking->blocked_seats),
            'seats' => json_encode($operatorBooking->blocked_seats), // Convert array to JSON
            'ticket_count' => count($operatorBooking->blocked_seats),
            'passenger_names' => json_encode($passengerNames), // Convert array to JSON
            'passenger_phones' => json_encode([$operatorBooking->operator->phone]), // Convert array to JSON
            'passenger_emails' => json_encode([$operatorBooking->operator->email]), // Convert array to JSON
            'passenger_phone' => $operatorBooking->operator->phone, // Add single phone field
            'passenger_email' => $operatorBooking->operator->email, // Add single email field
            'passenger_name' => $operatorBooking->operator->company_name, // Add passenger name
            'passenger_age' => 0, // Default age for operator bookings
            'passenger_address' => $operatorBooking->operator->address ?? '', // Add address
            'gender' => 'Male', // Default gender for operator bookings
            'source_destination' => json_encode([$route->origin_city_id, $route->destination_city_id]), // Convert array to JSON
            'pickup_point' => $boardingPoint ? $boardingPoint->id : null,
            'boarding_point' => $boardingPoint ? $boardingPoint->point_name : 'Main Terminal',
            'boarding_point_details' => json_encode($boardingPointDetails), // Convert array to JSON
            'dropping_point' => $droppingPoint ? $droppingPoint->point_name : 'Main Terminal',
            'dropping_point_details' => json_encode($droppingPointDetails), // Convert array to JSON
            'date_of_journey' => $operatorBooking->journey_date ? $operatorBooking->journey_date->format('Y-m-d') : null,
            'origin_city' => $route->originCity->city_name,
            'destination_city' => $route->destinationCity->city_name,
            'unit_price' => 0,
            'sub_total' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,
            'status' => 0, // Blocked but not paid
            'payment_status' => 'pending',
            'booking_type' => 'operator_blocking',
            'booking_reason' => $operatorBooking->booking_reason,
            'notes' => $operatorBooking->notes,
            'bus_details' => json_encode($busDetails), // Convert array to JSON
            'search_token_id' => $searchTokenId,
            'api_invoice' => null, // No API invoice for operator bookings
            'api_booking_id' => null, // No API booking ID for operator bookings
            'api_invoice_date' => null, // No API invoice date for operator bookings
            'api_ticket_no' => null, // No API ticket number for operator bookings
            'agent_commission' => 0,
            'api_invoice_amount' => 0,
            'cancellation_policy' => null, // No cancellation policy for operator bookings
            'tds_from_api' => null, // No TDS for operator bookings
            'api_response' => json_encode([
                'operator_booking_id' => $operatorBooking->id,
                'blocked_seats' => $operatorBooking->blocked_seats,
                'booking_reason' => $operatorBooking->booking_reason,
                'notes' => $operatorBooking->notes
            ])
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

        $booking->delete();

        $notify[] = ['success', 'Booking cancelled successfully.'];
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
        // Skip authentication for testing - use operator ID 41 directly
        $operatorId = 41; // Sutra Seva operator

        $request->validate([
            'bus_id' => 'required|exists:operator_buses,id',
            'journey_date' => 'required|date',
            'journey_date_end' => 'nullable|date|after_or_equal:journey_date',
            'is_date_range' => 'boolean'
        ]);

        $bus = OperatorBus::where('operator_id', $operatorId)
            ->where('id', $request->bus_id)
            ->firstOrFail();

        // Get seat layout
        $seatLayout = $bus->activeSeatLayout;
        if (!$seatLayout) {
            return response()->json(['error' => 'No seat layout found for this bus'], 400);
        }

        // Get blocked seats for the date range
        $blockedSeats = $this->getBlockedSeats($request->bus_id, $request->journey_date, $request->journey_date_end, $request->is_date_range);

        // Return the existing HTML layout
        $seatLayoutHtml = $seatLayout->html_layout;

        return response()->json([
            'seat_layout_html' => $seatLayoutHtml,
            'blocked_seats' => $blockedSeats,
            'total_seats' => $bus->total_seats
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
        $blockedSeats = [];

        foreach ($bookings as $booking) {
            $seats = is_array($booking->blocked_seats) ? $booking->blocked_seats : [$booking->blocked_seats];
            $blockedSeats = array_merge($blockedSeats, $seats);
        }

        return array_unique($blockedSeats);
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

}