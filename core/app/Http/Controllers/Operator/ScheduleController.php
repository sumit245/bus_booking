<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\BusSchedule;
use App\Models\OperatorBus;
use App\Models\OperatorRoute;
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

        $query = BusSchedule::with(['operatorBus', 'operatorRoute.originCity', 'operatorRoute.destinationCity'])
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

        $schedule->load(['operatorBus', 'operatorRoute.originCity', 'operatorRoute.destinationCity']);

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
    public function toggleStatus(BusSchedule $schedule)
    {
        // Check if the schedule belongs to the authenticated operator
        $operator = auth('operator')->user();
        if ($schedule->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        $schedule->update([
            'is_active' => !$schedule->is_active,
            'status' => $schedule->is_active ? 'inactive' : 'active'
        ]);

        $status = $schedule->is_active ? 'activated' : 'deactivated';
        $notify[] = ['success', "Schedule {$status} successfully."];

        return back()->withNotify($notify);
    }

    /**
     * Get schedules for a specific date.
     */
    public function getSchedulesForDate(Request $request)
    {
        $operator = auth('operator')->user();
        $date = $request->date ?? now()->toDateString();

        $schedules = BusSchedule::with(['operatorBus', 'operatorRoute.originCity', 'operatorRoute.destinationCity'])
            ->byOperator($operator->id)
            ->forDate($date)
            ->active()
            ->ordered()
            ->get();

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
}