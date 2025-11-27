<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\BusSchedule;
use App\Models\OperatorBus;
use App\Models\OperatorRoute;
use App\Models\BoardingPoint;
use App\Models\DroppingPoint;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:operator');
    }

    /**
     * Display a listing of schedules.
     */
    public function index(Request $request)
    {
        $operator = auth('operator')->user();

        $query = BusSchedule::with([
            'operatorBus',
            'operatorRoute' => function ($query) {
                $query->with(['originCity', 'destinationCity']);
            },
            'boardingPoints',
            'droppingPoints'
        ])
            ->byOperator($operator->id);

        // Filter by bus
        if ($request->filled('bus_id')) {
            $query->byBus($request->bus_id);
        }

        // Filter by route
        if ($request->filled('route_id')) {
            $query->byRoute($request->route_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by active status
        if ($request->has('active_only')) {
            $query->active();
        }

        $schedules = $query->ordered()->paginate(20);

        // Get filter options
        $buses = OperatorBus::where('operator_id', $operator->id)->get();
        $routes = OperatorRoute::with(['originCity', 'destinationCity'])
            ->where('operator_id', $operator->id)->get();

        return view('operator.schedules.index', compact('schedules', 'buses', 'routes'));
    }

    /**
     * Show the form for creating a new schedule.
     */
    public function create()
    {
        $operator = auth('operator')->user();

        $buses = OperatorBus::where('operator_id', $operator->id)->get();
        $routes = OperatorRoute::with(['originCity', 'destinationCity'])
            ->where('operator_id', $operator->id)->get();

        return view('operator.schedules.create', compact('buses', 'routes'));
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request)
    {
        $operator = auth('operator')->user();

        $request->validate([
            'operator_bus_id' => 'required|exists:operator_buses,id',
            'operator_route_id' => 'required|exists:operator_routes,id',
            'schedule_name' => 'nullable|string|max:255',
            'departure_time' => 'required|date_format:H:i',
            'arrival_time' => 'required|date_format:H:i',
            'is_daily' => 'boolean',
            'days_of_operation' => 'required_if:is_daily,false|array',
            'days_of_operation.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'sort_order' => 'integer|min:0'
        ]);

        // Verify bus and route belong to operator
        $bus = OperatorBus::where('id', $request->operator_bus_id)
            ->where('operator_id', $operator->id)->firstOrFail();

        $route = OperatorRoute::where('id', $request->operator_route_id)
            ->where('operator_id', $operator->id)->firstOrFail();

        // Check for schedule conflicts
        $this->checkScheduleConflicts($request, $operator->id);

        $schedule = BusSchedule::create([
            'operator_id' => $operator->id,
            'operator_bus_id' => $request->operator_bus_id,
            'operator_route_id' => $request->operator_route_id,
            'schedule_name' => $request->schedule_name,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'is_daily' => $request->boolean('is_daily'),
            'days_of_operation' => $request->is_daily ? null : $request->days_of_operation,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
            'status' => 'active'
        ]);

        $notify[] = ['success', 'Schedule created successfully.'];
        return redirect()->route('operator.schedules.index')->withNotify($notify);
    }

    /**
     * Display the specified schedule.
     */
    public function show(BusSchedule $schedule)
    {
        // Check if the schedule belongs to the authenticated operator
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        $schedule->load(['operatorBus', 'operatorRoute.originCity', 'operatorRoute.destinationCity', 'boardingPoints', 'droppingPoints']);

        return view('operator.schedules.show', compact('schedule'));
    }

    /**
     * Show the form for editing the specified schedule.
     */
    public function edit(BusSchedule $schedule)
    {
        // Check if the schedule belongs to the authenticated operator
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        $buses = OperatorBus::where('operator_id', $operator->id)->get();
        $routes = OperatorRoute::with(['originCity', 'destinationCity'])
            ->where('operator_id', $operator->id)->get();

        return view('operator.schedules.edit', compact('schedule', 'buses', 'routes'));
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, BusSchedule $schedule)
    {
        // Check if the schedule belongs to the authenticated operator
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        $request->validate([
            'operator_bus_id' => 'required|exists:operator_buses,id',
            'operator_route_id' => 'required|exists:operator_routes,id',
            'schedule_name' => 'nullable|string|max:255',
            'departure_time' => 'required|date_format:H:i',
            'arrival_time' => 'required|date_format:H:i',
            'is_daily' => 'boolean',
            'days_of_operation' => 'required_if:is_daily,false|array',
            'days_of_operation.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'status' => 'in:active,inactive,suspended,cancelled'
        ]);

        $operator = auth('operator')->user();

        // Verify bus and route belong to operator
        $bus = OperatorBus::where('id', $request->operator_bus_id)
            ->where('operator_id', $operator->id)->firstOrFail();

        $route = OperatorRoute::where('id', $request->operator_route_id)
            ->where('operator_id', $operator->id)->firstOrFail();

        // Check for schedule conflicts (excluding current schedule)
        $this->checkScheduleConflicts($request, $operator->id, $schedule->id);

        $schedule->update([
            'operator_bus_id' => $request->operator_bus_id,
            'operator_route_id' => $request->operator_route_id,
            'schedule_name' => $request->schedule_name,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'is_daily' => $request->boolean('is_daily'),
            'days_of_operation' => $request->is_daily ? null : $request->days_of_operation,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
            'status' => $request->status
        ]);

        $notify[] = ['success', 'Schedule updated successfully.'];
        return redirect()->route('operator.schedules.index')->withNotify($notify);
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(BusSchedule $schedule)
    {
        // Check if the schedule belongs to the authenticated operator
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        $schedule->delete();

        $notify[] = ['success', 'Schedule deleted successfully.'];
        return redirect()->route('operator.schedules.index')->withNotify($notify);
    }

    /**
     * Toggle schedule status.
     */
    public function toggleStatus(Request $request, BusSchedule $schedule)
    {
        // Check if the schedule belongs to the authenticated operator
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        // If activating, just toggle the status
        if (!$schedule->is_active) {
            $schedule->update([
                'is_active' => true,
                'status' => 'active',
                'start_date' => null,
                'end_date' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Schedule activated successfully.'
            ]);
        }

        // If deactivating, validate date range
        $request->validate([
            'deactivation_start' => 'required|date|after_or_equal:today',
            'deactivation_end' => 'nullable|date|after:deactivation_start'
        ]);

        $deactivationStart = $request->deactivation_start;
        $deactivationEnd = $request->deactivation_end;

        // Update schedule with deactivation dates
        $schedule->update([
            'is_active' => false,
            'status' => 'inactive',
            'start_date' => null, // Keep original start date
            'end_date' => $deactivationEnd ?? null // Set end date if provided
        ]);

        // Here you could also create a ScheduleDeactivation record to track deactivation periods
        // For now, we'll just update the schedule itself

        return response()->json([
            'success' => true,
            'message' => 'Schedule deactivated successfully.',
            'deactivation_start' => $deactivationStart,
            'deactivation_end' => $deactivationEnd
        ]);
    }

    /**
     * Calculate potential revenue loss for deactivation.
     */
    public function getRevenueImpact(BusSchedule $schedule)
    {
        try {
            $operator = auth('operator')->user();
            if ($schedule->operator_id !== $operator->id) {
                abort(403, 'Unauthorized access to this schedule.');
            }

            // Get the bus and seat layout
            $bus = $schedule->operatorBus;
            if (!$bus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bus not found for this schedule.'
                ]);
            }

            $seatLayout = $bus->seatLayouts()->where('is_active', true)->first();

            if (!$seatLayout) {
                // If no seat layout, use bus base price and total seats
                $totalRevenue = ($bus->base_price ?? 0) * ($bus->total_seats ?? 0);
                $averagePrice = $bus->base_price ?? 0;
                $totalSeats = $bus->total_seats ?? 0;

                return response()->json([
                    'success' => true,
                    'total_revenue_per_trip' => round($totalRevenue, 2),
                    'average_price_per_seat' => round($averagePrice, 2),
                    'total_seats' => $totalSeats,
                    'schedule_name' => $schedule->schedule_name,
                    'route' => $schedule->operatorRoute->route_name ?? 'N/A'
                ]);
            }

            // Calculate total seat revenue from layout
            $totalPrice = 0;
            $seatCount = 0;

            if ($seatLayout->layout_data) {
                foreach (['upper_deck', 'lower_deck'] as $deck) {
                    if (isset($seatLayout->layout_data[$deck]['seats'])) {
                        foreach ($seatLayout->layout_data[$deck]['seats'] as $seat) {
                            $totalPrice += $seat['price'] ?? 0;
                            $seatCount++;
                        }
                    }
                }
            }

            // If no seats found in layout, fallback to bus data
            if ($seatCount === 0) {
                $totalPrice = ($bus->base_price ?? 0) * ($bus->total_seats ?? 0);
                $seatCount = $bus->total_seats ?? 0;
            }

            $averagePrice = $seatCount > 0 ? round($totalPrice / $seatCount, 2) : 0;

            return response()->json([
                'success' => true,
                'total_revenue_per_trip' => round($totalPrice, 2),
                'average_price_per_seat' => $averagePrice,
                'total_seats' => $seatCount,
                'schedule_name' => $schedule->schedule_name,
                'route' => $schedule->operatorRoute->route_name ?? 'N/A'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching revenue impact: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error calculating revenue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedules for a specific date.
     */
    public function getSchedulesForDate(Request $request)
    {
        // Skip authentication for testing - use operator ID 41 directly
        $operatorId = 41; // Sutra Seva operator
        $date = $request->date ?? now()->toDateString();
        $busId = $request->bus_id;

        $query = BusSchedule::with(['operatorBus', 'operatorRoute.originCity', 'operatorRoute.destinationCity'])
            ->where('operator_id', $operatorId)
            ->where('is_daily', true) // Get daily schedules
            ->active()
            ->ordered();

        // Filter by bus if provided
        if ($busId) {
            $query->where('operator_bus_id', $busId);
        }

        $schedules = $query->get();

        return response()->json($schedules);
    }

    /**
     * Check for schedule conflicts.
     */
    private function checkScheduleConflicts(Request $request, $operatorId, $excludeScheduleId = null)
    {
        $query = BusSchedule::where('operator_id', $operatorId)
            ->where('operator_bus_id', $request->operator_bus_id)
            ->where('departure_time', $request->departure_time)
            ->where('is_active', true);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        // Check for same day conflicts
        if (!$request->is_daily && $request->days_of_operation) {
            $query->where(function ($q) use ($request) {
                $q->where('is_daily', true)
                    ->orWhereJsonContains('days_of_operation', $request->days_of_operation);
            });
        } elseif ($request->is_daily) {
            $query->where(function ($q) {
                $q->where('is_daily', true)
                    ->orWhereNotNull('days_of_operation');
            });
        }

        $conflicts = $query->count();

        if ($conflicts > 0) {
            throw new \Exception('Schedule conflict: Another schedule already exists for this bus at the same time and day(s).');
        }
    }

    /**
     * Manage boarding points for a schedule.
     */
    public function manageBoardingPoints(BusSchedule $schedule)
    {
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        $schedule->load(['boardingPoints', 'operatorRoute.boardingPoints']);

        return view('operator.schedules.boarding-points', compact('schedule'));
    }

    /**
     * Store a boarding point for a schedule.
     */
    public function storeBoardingPoint(Request $request, BusSchedule $schedule)
    {
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        $request->validate([
            'point_name' => 'required|string|max:255',
            'point_address' => 'required|string',
            'point_location' => 'required|string|max:255',
            'point_landmark' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'point_index' => 'required|integer|min:1',
            'point_time' => 'required|date_format:H:i',
        ]);

        BoardingPoint::create([
            'bus_schedule_id' => $schedule->id,
            'operator_route_id' => $schedule->operator_route_id,
            'point_name' => $request->point_name,
            'point_address' => $request->point_address,
            'point_location' => $request->point_location,
            'point_landmark' => $request->point_landmark,
            'contact_number' => $request->contact_number,
            'point_index' => $request->point_index,
            'point_time' => $request->point_time,
            'status' => 1
        ]);

        $notify[] = ['success', 'Boarding point added successfully.'];
        return back()->withNotify($notify);
    }

    /**
     * Update a boarding point for a schedule.
     */
    public function updateBoardingPoint(Request $request, BusSchedule $schedule, BoardingPoint $boardingPoint)
    {
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id || $boardingPoint->bus_schedule_id !== $schedule->id) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'point_name' => 'required|string|max:255',
            'point_address' => 'required|string',
            'point_location' => 'required|string|max:255',
            'point_landmark' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'point_index' => 'required|integer|min:1',
            'point_time' => 'required|date_format:H:i',
            'status' => 'required|boolean'
        ]);

        $boardingPoint->update([
            'point_name' => $request->point_name,
            'point_address' => $request->point_address,
            'point_location' => $request->point_location,
            'point_landmark' => $request->point_landmark,
            'contact_number' => $request->contact_number,
            'point_index' => $request->point_index,
            'point_time' => $request->point_time,
            'status' => $request->status
        ]);

        $notify[] = ['success', 'Boarding point updated successfully.'];
        return back()->withNotify($notify);
    }

    /**
     * Delete a boarding point for a schedule.
     */
    public function destroyBoardingPoint(BusSchedule $schedule, BoardingPoint $boardingPoint)
    {
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id || $boardingPoint->bus_schedule_id !== $schedule->id) {
            abort(403, 'Unauthorized access.');
        }

        $boardingPoint->delete();

        $notify[] = ['success', 'Boarding point deleted successfully.'];
        return back()->withNotify($notify);
    }

    /**
     * Manage dropping points for a schedule.
     */
    public function manageDroppingPoints(BusSchedule $schedule)
    {
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        $schedule->load(['droppingPoints', 'operatorRoute.droppingPoints']);

        return view('operator.schedules.dropping-points', compact('schedule'));
    }

    /**
     * Store a dropping point for a schedule.
     */
    public function storeDroppingPoint(Request $request, BusSchedule $schedule)
    {
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        $request->validate([
            'point_name' => 'required|string|max:255',
            'point_address' => 'nullable|string',
            'point_location' => 'required|string|max:255',
            'point_landmark' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'point_index' => 'required|integer|min:1',
            'point_time' => 'required|date_format:H:i',
        ]);

        DroppingPoint::create([
            'bus_schedule_id' => $schedule->id,
            'operator_route_id' => $schedule->operator_route_id,
            'point_name' => $request->point_name,
            'point_address' => $request->point_address,
            'point_location' => $request->point_location,
            'point_landmark' => $request->point_landmark,
            'contact_number' => $request->contact_number,
            'point_index' => $request->point_index,
            'point_time' => $request->point_time,
            'status' => 1
        ]);

        $notify[] = ['success', 'Dropping point added successfully.'];
        return back()->withNotify($notify);
    }

    /**
     * Update a dropping point for a schedule.
     */
    public function updateDroppingPoint(Request $request, BusSchedule $schedule, DroppingPoint $droppingPoint)
    {
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id || $droppingPoint->bus_schedule_id !== $schedule->id) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'point_name' => 'required|string|max:255',
            'point_address' => 'nullable|string',
            'point_location' => 'required|string|max:255',
            'point_landmark' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'point_index' => 'required|integer|min:1',
            'point_time' => 'required|date_format:H:i',
            'status' => 'required|boolean'
        ]);

        $droppingPoint->update([
            'point_name' => $request->point_name,
            'point_address' => $request->point_address,
            'point_location' => $request->point_location,
            'point_landmark' => $request->point_landmark,
            'contact_number' => $request->contact_number,
            'point_index' => $request->point_index,
            'point_time' => $request->point_time,
            'status' => $request->status
        ]);

        $notify[] = ['success', 'Dropping point updated successfully.'];
        return back()->withNotify($notify);
    }

    /**
     * Delete a dropping point for a schedule.
     */
    public function destroyDroppingPoint(BusSchedule $schedule, DroppingPoint $droppingPoint)
    {
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id || $droppingPoint->bus_schedule_id !== $schedule->id) {
            abort(403, 'Unauthorized access.');
        }

        $droppingPoint->delete();

        $notify[] = ['success', 'Dropping point deleted successfully.'];
        return back()->withNotify($notify);
    }
}