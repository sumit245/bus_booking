<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\OperatorBus;
use App\Models\BusSchedule;
use App\Models\BookedTicket;
use App\Models\AgentBooking;
use App\Models\Counter;
use App\Services\AgentCommissionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    protected $commissionCalculator;

    public function __construct(AgentCommissionCalculator $commissionCalculator)
    {
        $this->commissionCalculator = $commissionCalculator;
    }

    public function showBusDetails($busId)
    {
        $bus = OperatorBus::with(['operator', 'fleetType', 'routes.cities'])
            ->findOrFail($busId);

        return view('agent.booking.bus_details', compact('bus'));
    }

    public function selectSeats(Request $request, $busId)
    {
        $request->validate([
            'schedule_id' => 'required|exists:bus_schedules,id',
            'date' => 'required|date|after_or_equal:today',
            'passengers' => 'required|integer|min:1|max:10',
            'price' => 'required|numeric|min:0'
        ]);

        $bus = OperatorBus::with(['operator', 'fleetType'])->findOrFail($busId);
        $schedule = BusSchedule::findOrFail($request->schedule_id);
        $passengers = $request->passengers;
        $dateOfJourney = $request->date;
        $basePrice = $request->price;

        // Get seat layout
        $seatLayout = $this->getSeatLayout($bus, $schedule, $dateOfJourney);

        // Calculate commission
        $totalAmount = $basePrice * $passengers;
        $commissionConfig = $this->commissionCalculator->getCommissionConfig();
        $commissionData = $this->commissionCalculator->calculate($totalAmount, $commissionConfig);

        return view('agent.booking.seat_selection', compact(
            'bus',
            'schedule',
            'passengers',
            'dateOfJourney',
            'basePrice',
            'totalAmount',
            'commissionData',
            'seatLayout'
        ));
    }

    public function create(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:bus_schedules,id',
            'bus_id' => 'required|exists:buses,id',
            'date_of_journey' => 'required|date',
            'passengers' => 'required|integer|min:1|max:10',
            'selected_seats' => 'required|array|min:1',
            'passenger_details' => 'required|array',
            'pickup_point' => 'required|exists:counters,id',
            'drop_point' => 'required|exists:counters,id'
        ]);

        try {
            DB::beginTransaction();

            $agent = Auth::guard('agent')->user();
            $schedule = BusSchedule::findOrFail($request->schedule_id);
            $bus = OperatorBus::findOrFail($request->bus_id);

            // Calculate amounts
            $basePrice = $schedule->price ?? 0;
            $totalAmount = $basePrice * count($request->selected_seats);

            // Calculate commission
            $commissionConfig = $this->commissionCalculator->getCommissionConfig();
            $commissionData = $this->commissionCalculator->calculate($totalAmount, $commissionConfig);

            $netAmountPaid = $totalAmount - $commissionData['commission_amount'];

            // Create booked ticket
            $bookedTicket = BookedTicket::create([
                'user_id' => null,
                'agent_id' => $agent->id,
                'operator_id' => $bus->operator_id,
                'booking_id' => 'AG' . time() . Str::random(6),
                'ticket_no' => 'TKT' . time() . Str::random(4),
                'gender' => 'male', // Default, will be updated with passenger details
                'trip_id' => $schedule->id,
                'source_destination' => $request->pickup_point . '-' . $request->drop_point,
                'pickup_point' => $request->pickup_point,
                'drop' => $request->drop_point,
                'seats' => json_encode($request->selected_seats),
                'date_of_journey' => $request->date_of_journey,
                'total_amount' => $totalAmount,
                'agent_commission_amount' => $commissionData['commission_amount'],
                'booking_source' => 'agent',
                'total_commission_charged' => $commissionData['commission_amount'],
                'status' => 1, // Confirmed
                'payment_status' => 'completed'
            ]);

            // Create agent booking record
            $agentBooking = AgentBooking::create([
                'agent_id' => $agent->id,
                'booked_ticket_id' => $bookedTicket->id,
                'commission_amount' => $commissionData['commission_amount'],
                'commission_type' => $commissionData['commission_type'],
                'base_amount_paid' => $netAmountPaid,
                'total_commission_earned' => $commissionData['commission_amount'],
                'booking_status' => 'confirmed'
            ]);

            // Update agent statistics
            $agent->increment('total_bookings');
            $agent->increment('total_earnings', $commissionData['commission_amount']);

            DB::commit();

            return redirect()->route('agent.booking.confirm', $bookedTicket->id)
                ->with('success', 'Booking created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Booking failed: ' . $e->getMessage());
        }
    }

    public function confirm($bookingId)
    {
        $bookedTicket = BookedTicket::with(['agent', 'agentBooking', 'pickup', 'drop'])
            ->where('id', $bookingId)
            ->where('agent_id', Auth::guard('agent')->id())
            ->firstOrFail();

        return view('agent.booking.confirm', compact('bookedTicket'));
    }

    public function showTicket($bookingId)
    {
        $bookedTicket = BookedTicket::with(['agent', 'agentBooking', 'pickup', 'drop'])
            ->where('id', $bookingId)
            ->where('agent_id', Auth::guard('agent')->id())
            ->firstOrFail();

        return view('agent.booking.ticket', compact('bookedTicket'));
    }

    public function index(Request $request)
    {
        $pageTitle = 'My Bookings';
        $agent = Auth::guard('agent')->user();

        // Fetch booked_tickets where agent_id is not null and agent_id matches the authenticated agent
        $query = BookedTicket::with(['pickup', 'drop', 'agentBooking'])
            ->whereNotNull('agent_id')
            ->where('agent_id', $agent->id);

        // Filter by tab: upcoming or past (based on date_of_journey)
        $tab = $request->get('tab', 'upcoming');
        if ($tab === 'past') {
            $query->whereDate('date_of_journey', '<', now()->toDateString());
        } else {
            $query->whereDate('date_of_journey', '>=', now()->toDateString());
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('date_of_journey', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date_of_journey', '<=', $request->get('date_to'));
        }

        // Search across booking id or ticket_no
        if ($request->filled('q')) {
            $qStr = $request->get('q');
            $query->where(function ($q) use ($qStr) {
                $q->where('booking_id', 'like', "%{$qStr}%")
                    ->orWhere('ticket_no', 'like', "%{$qStr}%");
            });
        }

        $query->latest();

        // Export as CSV if requested
        if ($request->boolean('export')) {
            $rows = $query->get();
            $filename = 'agent_bookings_' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $columns = ['Booking ID', 'Ticket No', 'Journey Date', 'Passenger Amount', 'Commission', 'Status', 'Payment Status'];

            $callback = function () use ($rows, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);
                foreach ($rows as $bt) {
                    fputcsv($file, [
                        $bt->booking_id ?? '',
                        $bt->ticket_no ?? '',
                        $bt->date_of_journey ?? '',
                        $bt->total_amount ?? '',
                        $bt->agent_commission_amount ?? '',
                        $bt->status == 1 ? 'Confirmed' : ($bt->status == 2 ? 'Cancelled' : 'Pending'),
                        $bt->payment_status ?? '',
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        $bookings = $query->paginate(20)->appends($request->query());

        return view('agent.booking.index', compact('pageTitle', 'bookings', 'tab'));
    }

    public function show($bookingId)
    {
        $agentBooking = AgentBooking::with(['bookedTicket.pickup', 'bookedTicket.drop'])
            ->where('id', $bookingId)
            ->where('agent_id', Auth::guard('agent')->id())
            ->firstOrFail();

        return view('agent.booking.show', compact('agentBooking'));
    }

    public function cancel($bookingId)
    {
        try {
            $agentBooking = AgentBooking::with('bookedTicket')
                ->where('id', $bookingId)
                ->where('agent_id', Auth::guard('agent')->id())
                ->firstOrFail();

            if ($agentBooking->booking_status === 'cancelled') {
                return redirect()->back()->with('warning', 'Booking is already cancelled.');
            }

            // Update booking status
            $agentBooking->update(['booking_status' => 'cancelled']);
            $agentBooking->bookedTicket->update(['status' => 2]); // Cancelled

            // Refund commission (optional business logic)
            // You might want to deduct from agent earnings

            return redirect()->back()->with('success', 'Booking cancelled successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to cancel booking: ' . $e->getMessage());
        }
    }

    public function print($bookingId)
    {
        $bookedTicket = BookedTicket::with(['agent', 'agentBooking', 'pickup', 'drop'])
            ->where('id', $bookingId)
            ->where('agent_id', Auth::guard('agent')->id())
            ->firstOrFail();

        return view('agent.booking.print', compact('bookedTicket'));
    }

    private function getSeatLayout($bus, $schedule, $dateOfJourney)
    {
        // Get existing seat layout HTML
        $seatLayout = $bus->html_layout ?? '<div class="alert alert-info">No seat layout available</div>';

        // Get booked seats for this date and schedule
        $bookedSeats = BookedTicket::where('trip_id', $schedule->id)
            ->where('date_of_journey', $dateOfJourney)
            ->whereIn('status', [0, 1, 2])
            ->get()
            ->pluck('seats')
            ->flatten()
            ->filter()
            ->toArray();

        // Mark booked seats as unavailable
        foreach ($bookedSeats as $seat) {
            $seatLayout = preg_replace(
                '/(<[^>]*class="[^"]*seat[^"]*"[^>]*data-seat="' . preg_quote($seat, '/') . '"[^>]*>)/',
                '$1 class="booked"',
                $seatLayout
            );
        }

        return $seatLayout;
    }
}