<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\CrewAssignment;
use App\Models\Staff;
use App\Models\OperatorBus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CrewAssignmentController extends Controller
{
    public function __construct()
    {
        // Middleware is already applied at route level
    }

    /**
     * Display a listing of crew assignments
     */
    public function index(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        $query = CrewAssignment::where('operator_id', $operator->id)
            ->with(['staff', 'operatorBus']);

        // Filter by bus
        if ($request->filled('bus_id')) {
            $query->where('operator_bus_id', $request->bus_id);
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assignments = $query->orderBy('operator_bus_id')
            ->orderBy('role')
            ->paginate(20);

        // Get buses for filter dropdown
        $buses = OperatorBus::where('operator_id', $operator->id)
            ->where('status', 1)
            ->select('id', 'travel_name', 'bus_type')
            ->get();

        return view('operator.crew.index', compact('assignments', 'buses'));
    }

    /**
     * Show the form for creating a new crew assignment
     */
    public function create(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        $buses = OperatorBus::where('operator_id', $operator->id)
            ->where('status', 1)
            ->with('currentRoute')
            ->get();

        $staff = Staff::where('operator_id', $operator->id)
            ->where('is_active', true)
            ->get();

        $drivers = $staff->where('role', 'driver');
        $conductors = $staff->where('role', 'conductor');
        $attendants = $staff->where('role', 'attendant');

        $selectedBus = $request->get('bus_id');

        return view('operator.crew.create', compact('buses', 'drivers', 'conductors', 'attendants', 'selectedBus'));
    }

    /**
     * Store a newly created crew assignment
     */
    public function store(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        $validator = Validator::make($request->all(), [
            'operator_bus_id' => 'required|exists:operator_buses,id',
            'shift_start_time' => 'nullable|date_format:H:i',
            'shift_end_time' => 'nullable|date_format:H:i|after:shift_start_time',
            'driver_id' => 'nullable|exists:staff,id',
            'conductor_id' => 'nullable|exists:staff,id',
            'attendant_id' => 'nullable|exists:staff,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check if at least one crew member is assigned
        if (!$request->driver_id && !$request->conductor_id && !$request->attendant_id) {
            return redirect()->back()
                ->with('error', 'Please assign at least one crew member.')
                ->withInput();
        }

        $assignments = [];
        $errors = [];

        // Process driver assignment
        if ($request->driver_id) {
            // Check if driver is already assigned to another bus
            $existingAssignment = CrewAssignment::where('staff_id', $request->driver_id)
                ->where('status', 'active')
                ->first();

            if ($existingAssignment) {
                $errors[] = 'Driver is already assigned to another bus. Please deactivate the existing assignment first.';
            } else {
                // Check if this bus already has a driver
                $existingRoleAssignment = CrewAssignment::where('operator_bus_id', $request->operator_bus_id)
                    ->where('role', 'driver')
                    ->where('status', 'active')
                    ->first();

                if ($existingRoleAssignment) {
                    $errors[] = 'A driver is already assigned to this bus. Please deactivate the existing assignment first.';
                } else {
                    $assignments[] = [
                        'operator_id' => $operator->id,
                        'operator_bus_id' => $request->operator_bus_id,
                        'staff_id' => $request->driver_id,
                        'role' => 'driver',
                        'assignment_date' => now()->toDateString(), // Set assignment date to today
                        'start_date' => now()->toDateString(),
                        'shift_start_time' => $request->shift_start_time,
                        'shift_end_time' => $request->shift_end_time,
                        'notes' => $request->notes,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Process conductor assignment
        if ($request->conductor_id) {
            // Check if conductor is already assigned to another bus
            $existingAssignment = CrewAssignment::where('staff_id', $request->conductor_id)
                ->where('status', 'active')
                ->first();

            if ($existingAssignment) {
                $errors[] = 'Conductor is already assigned to another bus. Please deactivate the existing assignment first.';
            } else {
                // Check if this bus already has a conductor
                $existingRoleAssignment = CrewAssignment::where('operator_bus_id', $request->operator_bus_id)
                    ->where('role', 'conductor')
                    ->where('status', 'active')
                    ->first();

                if ($existingRoleAssignment) {
                    $errors[] = 'A conductor is already assigned to this bus. Please deactivate the existing assignment first.';
                } else {
                    $assignments[] = [
                        'operator_id' => $operator->id,
                        'operator_bus_id' => $request->operator_bus_id,
                        'staff_id' => $request->conductor_id,
                        'role' => 'conductor',
                        'assignment_date' => now()->toDateString(), // Set assignment date to today
                        'start_date' => now()->toDateString(),
                        'shift_start_time' => $request->shift_start_time,
                        'shift_end_time' => $request->shift_end_time,
                        'notes' => $request->notes,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Process attendant assignment
        if ($request->attendant_id) {
            // Check if attendant is already assigned to another bus
            $existingAssignment = CrewAssignment::where('staff_id', $request->attendant_id)
                ->where('status', 'active')
                ->first();

            if ($existingAssignment) {
                $errors[] = 'Attendant is already assigned to another bus. Please deactivate the existing assignment first.';
            } else {
                // Check if this bus already has an attendant
                $existingRoleAssignment = CrewAssignment::where('operator_bus_id', $request->operator_bus_id)
                    ->where('role', 'attendant')
                    ->where('status', 'active')
                    ->first();

                if ($existingRoleAssignment) {
                    $errors[] = 'An attendant is already assigned to this bus. Please deactivate the existing assignment first.';
                } else {
                    $assignments[] = [
                        'operator_id' => $operator->id,
                        'operator_bus_id' => $request->operator_bus_id,
                        'staff_id' => $request->attendant_id,
                        'role' => 'attendant',
                        'assignment_date' => now()->toDateString(), // Set assignment date to today
                        'start_date' => now()->toDateString(),
                        'shift_start_time' => $request->shift_start_time,
                        'shift_end_time' => $request->shift_end_time,
                        'notes' => $request->notes,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // If there are errors, return them
        if (!empty($errors)) {
            return redirect()->back()
                ->with('error', implode(' ', $errors))
                ->withInput();
        }

        // Create all assignments
        if (!empty($assignments)) {
            CrewAssignment::insert($assignments);
            $count = count($assignments);
            return redirect()->route('operator.crew.index')
                ->with('success', "Crew assignment created successfully! {$count} crew member(s) assigned.");
        }

        return redirect()->back()
            ->with('error', 'No valid assignments to create.')
            ->withInput();
    }

    /**
     * Display the specified crew assignment
     */
    public function show($id)
    {
        $operator = Auth::guard('operator')->user();
        $assignment = CrewAssignment::where('operator_id', $operator->id)
            ->with(['staff', 'operatorBus.currentRoute'])
            ->findOrFail($id);

        return view('operator.crew.show', compact('assignment'))->with('crewAssignment', $assignment);
    }

    /**
     * Show the form for editing the specified crew assignment
     */
    public function edit($id)
    {
        $operator = Auth::guard('operator')->user();
        $assignment = CrewAssignment::where('operator_id', $operator->id)->findOrFail($id);

        $buses = OperatorBus::where('operator_id', $operator->id)
            ->where('status', 1)
            ->get();

        $staff = Staff::where('operator_id', $operator->id)
            ->where('is_active', true)
            ->get();

        $drivers = $staff->where('role', 'driver');
        $conductors = $staff->where('role', 'conductor');
        $attendants = $staff->where('role', 'attendant');

        return view('operator.crew.edit', compact('assignment', 'buses', 'drivers', 'conductors', 'attendants'))->with('crewAssignment', $assignment);
    }

    /**
     * Update the specified crew assignment
     */
    public function update(Request $request, $id)
    {
        $operator = Auth::guard('operator')->user();
        $assignment = CrewAssignment::where('operator_id', $operator->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'operator_bus_id' => 'required|exists:operator_buses,id',
            'staff_id' => 'required|exists:staff,id',
            'role' => 'required|in:driver,conductor,attendant',
            'assignment_date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'shift_start_time' => 'nullable|date_format:H:i',
            'shift_end_time' => 'nullable|date_format:H:i|after:shift_start_time',
            'status' => 'required|in:active,inactive,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check for conflicts only if changing staff or date
        if ($request->staff_id != $assignment->staff_id || $request->assignment_date != $assignment->assignment_date) {
            $existingAssignment = CrewAssignment::where('staff_id', $request->staff_id)
                ->where('assignment_date', $request->assignment_date)
                ->where('status', 'active')
                ->where('id', '!=', $id)
                ->first();

            if ($existingAssignment) {
                return redirect()->back()
                    ->with('error', 'This staff member is already assigned to another bus on this date.')
                    ->withInput();
            }
        }

        // Check for role conflicts only if changing bus, role, or date
        if (
            $request->operator_bus_id != $assignment->operator_bus_id ||
            $request->role != $assignment->role ||
            $request->assignment_date != $assignment->assignment_date
        ) {

            $existingRoleAssignment = CrewAssignment::where('operator_bus_id', $request->operator_bus_id)
                ->where('role', $request->role)
                ->where('assignment_date', $request->assignment_date)
                ->where('status', 'active')
                ->where('id', '!=', $id)
                ->first();

            if ($existingRoleAssignment) {
                return redirect()->back()
                    ->with('error', "A {$request->role} is already assigned to this bus on this date.")
                    ->withInput();
            }
        }

        $assignment->update($request->all());

        return redirect()->route('operator.crew.index')
            ->with('success', 'Crew assignment updated successfully!');
    }

    /**
     * Remove the specified crew assignment
     */
    public function destroy($id)
    {
        $operator = Auth::guard('operator')->user();
        $assignment = CrewAssignment::where('operator_id', $operator->id)->findOrFail($id);

        $assignment->delete();

        return redirect()->route('operator.crew.index')
            ->with('success', 'Crew assignment deleted successfully!');
    }

    /**
     * Get crew assignments for a specific bus (AJAX)
     */
    public function getBusCrew(Request $request)
    {
        $operator = Auth::guard('operator')->user();
        $busId = $request->bus_id;

        $assignments = CrewAssignment::where('operator_id', $operator->id)
            ->where('operator_bus_id', $busId)
            ->where('status', 'active')
            ->with('staff')
            ->get();

        return response()->json($assignments);
    }

    /**
     * Get available staff for a specific role (AJAX)
     */
    public function getAvailableStaff(Request $request)
    {
        $operator = Auth::guard('operator')->user();
        $role = $request->role;

        // Get staff of the specified role
        $staff = Staff::where('operator_id', $operator->id)
            ->where('role', $role)
            ->where('is_active', true)
            ->get();

        // Filter out staff who are already assigned to any bus
        $assignedStaffIds = CrewAssignment::where('operator_id', $operator->id)
            ->where('status', 'active')
            ->pluck('staff_id')
            ->toArray();

        $availableStaff = $staff->reject(function ($member) use ($assignedStaffIds) {
            return in_array($member->id, $assignedStaffIds);
        });

        return response()->json($availableStaff);
    }

    /**
     * Bulk assign crew for multiple dates
     */
    public function bulkAssign(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        $validator = Validator::make($request->all(), [
            'operator_bus_id' => 'required|exists:operator_buses,id',
            'assignments' => 'required|array',
            'assignments.*.staff_id' => 'required|exists:staff,id',
            'assignments.*.role' => 'required|in:driver,conductor,attendant',
            'assignments.*.date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $busId = $request->operator_bus_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $assignments = $request->assignments;

        $created = 0;
        $errors = [];

        foreach ($assignments as $assignment) {
            try {
                // Check for conflicts
                $existingAssignment = CrewAssignment::where('staff_id', $assignment['staff_id'])
                    ->where('assignment_date', $assignment['date'])
                    ->where('status', 'active')
                    ->first();

                if ($existingAssignment) {
                    $errors[] = "Staff member already assigned on {$assignment['date']}";
                    continue;
                }

                $existingRoleAssignment = CrewAssignment::where('operator_bus_id', $busId)
                    ->where('role', $assignment['role'])
                    ->where('assignment_date', $assignment['date'])
                    ->where('status', 'active')
                    ->first();

                if ($existingRoleAssignment) {
                    $errors[] = "{$assignment['role']} already assigned to this bus on {$assignment['date']}";
                    continue;
                }

                CrewAssignment::create([
                    'operator_id' => $operator->id,
                    'operator_bus_id' => $busId,
                    'staff_id' => $assignment['staff_id'],
                    'role' => $assignment['role'],
                    'assignment_date' => $assignment['date'],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'active',
                ]);

                $created++;
            } catch (\Exception $e) {
                $errors[] = "Error creating assignment for {$assignment['date']}: " . $e->getMessage();
            }
        }

        return response()->json([
            'created' => $created,
            'errors' => $errors,
            'message' => "Successfully created {$created} assignments"
        ]);
    }
}