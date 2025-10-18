<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\CrewAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function __construct()
    {
        // Middleware is already applied at route level
    }

    /**
     * Display a listing of attendance records
     */
    public function index(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        // Get current month or requested month
        $currentMonth = $request->get('month', now()->format('Y-m'));
        $year = substr($currentMonth, 0, 4);
        $month = substr($currentMonth, 5, 2);

        // Get all active staff with pagination
        $staff = Staff::where('operator_id', $operator->id)
            ->where('is_active', true)
            ->select('id', 'first_name', 'last_name', 'role', 'employee_id')
            ->paginate(50);

        // Get attendance data for the month
        $attendanceData = Attendance::where('operator_id', $operator->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get()
            ->keyBy(function ($item) {
                return $item->staff_id . '_' . $item->attendance_date->format('Y-m-d');
            });

        // Generate calendar days for the month
        $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;
        $calendarDays = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $calendarDays[] = [
                'day' => $day,
                'date' => \Carbon\Carbon::create($year, $month, $day)->format('Y-m-d'),
                'isWeekend' => \Carbon\Carbon::create($year, $month, $day)->isWeekend()
            ];
        }

        return view('operator.attendance.index', compact('staff', 'attendanceData', 'calendarDays', 'currentMonth', 'year', 'month'));
    }

    /**
     * Show the form for creating a new attendance record
     */
    public function create(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        $staff = Staff::where('operator_id', $operator->id)
            ->where('is_active', true)
            ->get();

        $selectedDate = $request->get('date', now()->toDateString());
        $selectedStaff = $request->get('staff_id');

        return view('operator.attendance.create', compact('staff', 'selectedDate', 'selectedStaff'));
    }

    /**
     * Store a newly created attendance record
     */
    public function store(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staff,id',
            'attendance_date' => 'required|date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'status' => 'required|in:present,absent,late,half_day,on_leave,sick_leave,emergency_leave',
            'hours_worked' => 'nullable|numeric|min:0|max:24',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'notes' => 'nullable|string',
            'check_in_location' => 'nullable|string',
            'check_out_location' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check if attendance already exists for this staff on this date
        $existingAttendance = Attendance::where('staff_id', $request->staff_id)
            ->where('attendance_date', $request->attendance_date)
            ->first();

        if ($existingAttendance) {
            return redirect()->back()
                ->with('error', 'Attendance record already exists for this staff member on this date.')
                ->withInput();
        }

        $data = $request->all();
        $data['operator_id'] = $operator->id;

        // Get crew assignment for this staff and date
        $crewAssignment = CrewAssignment::where('staff_id', $request->staff_id)
            ->where('assignment_date', $request->attendance_date)
            ->where('status', 'active')
            ->first();

        if ($crewAssignment) {
            $data['crew_assignment_id'] = $crewAssignment->id;
        }

        // Auto-calculate hours if check-in and check-out times are provided
        if ($request->check_in_time && $request->check_out_time) {
            $checkIn = \Carbon\Carbon::parse($request->check_in_time);
            $checkOut = \Carbon\Carbon::parse($request->check_out_time);

            $totalMinutes = $checkOut->diffInMinutes($checkIn);
            $data['hours_worked'] = round($totalMinutes / 60, 2);

            // Calculate overtime (assuming 8 hours is standard)
            if ($data['hours_worked'] > 8) {
                $data['overtime_hours'] = $data['hours_worked'] - 8;
                $data['hours_worked'] = 8;
            } else {
                $data['overtime_hours'] = 0;
            }
        }

        Attendance::create($data);

        return redirect()->route('operator.attendance.index')
            ->with('success', 'Attendance record created successfully!');
    }

    /**
     * Update attendance status via AJAX
     */
    public function updateStatus(Request $request)
    {
        try {
            $operator = Auth::guard('operator')->user();

            \Log::info('Attendance update request', [
                'operator_id' => $operator->id,
                'request_data' => $request->all()
            ]);

            $request->validate([
                'staff_id' => 'required|exists:staff,id',
                'date' => 'required|date',
                'status' => 'nullable|in:A,P,H'
            ]);

            // Check if staff belongs to operator
            $staff = Staff::where('operator_id', $operator->id)
                ->where('id', $request->staff_id)
                ->firstOrFail();

            // Handle status clearing (null) or status setting
            if ($request->status === null) {
                // Delete existing attendance record
                Attendance::where('operator_id', $operator->id)
                    ->where('staff_id', $request->staff_id)
                    ->where('attendance_date', $request->date)
                    ->delete();

                $attendance = null;
            } else {
                // Convert status codes
                $statusMap = [
                    'A' => 'absent',
                    'P' => 'present',
                    'H' => 'half_day'
                ];

                $attendance = Attendance::updateOrCreate(
                    [
                        'operator_id' => $operator->id,
                        'staff_id' => $request->staff_id,
                        'attendance_date' => $request->date
                    ],
                    [
                        'status' => $statusMap[$request->status],
                        'is_approved' => true,
                        'approved_by' => null,
                        'approved_at' => now()
                    ]
                );
            }

            \Log::info('Attendance updated successfully', [
                'attendance_id' => $attendance ? $attendance->id : null,
                'status' => $request->status,
                'action' => $request->status === null ? 'deleted' : 'updated'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'status' => $request->status
            ]);
        } catch (\Exception $e) {
            \Log::error('Attendance update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export attendance to Excel
     */
    public function export(Request $request)
    {
        try {
            $operator = Auth::guard('operator')->user();

            \Log::info('Export request', [
                'operator_id' => $operator->id,
                'request_data' => $request->all()
            ]);

            $request->validate([
                'month' => 'required|date_format:Y-m'
            ]);

            $year = substr($request->month, 0, 4);
            $month = substr($request->month, 5, 2);

            // Get all active staff
            $staff = Staff::where('operator_id', $operator->id)
                ->where('is_active', true)
                ->select('id', 'first_name', 'last_name', 'role', 'employee_id')
                ->get();

            // Get attendance data for the month
            $attendanceData = Attendance::where('operator_id', $operator->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->get()
                ->keyBy(function ($item) {
                    return $item->staff_id . '_' . $item->attendance_date->format('Y-m-d');
                });

            // Generate calendar days for the month
            $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;
            $calendarDays = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $calendarDays[] = [
                    'day' => $day,
                    'date' => \Carbon\Carbon::create($year, $month, $day)->format('Y-m-d')
                ];
            }

            // Create CSV content
            $csvContent = "Employee ID,Name,Role,";
            foreach ($calendarDays as $day) {
                $csvContent .= $day['day'] . ",";
            }
            $csvContent .= "\n";

            foreach ($staff as $member) {
                $csvContent .= $member->employee_id . ",";
                $csvContent .= $member->first_name . " " . $member->last_name . ",";
                $csvContent .= ucfirst($member->role) . ",";

                foreach ($calendarDays as $day) {
                    $key = $member->id . '_' . $day['date'];
                    if (isset($attendanceData[$key])) {
                        $status = $attendanceData[$key]->status;
                        if ($status === 'absent')
                            $csvContent .= "A,";
                        elseif ($status === 'present')
                            $csvContent .= "P,";
                        elseif ($status === 'half_day')
                            $csvContent .= "H,";
                        else
                            $csvContent .= ",";
                    } else {
                        $csvContent .= ",";
                    }
                }
                $csvContent .= "\n";
            }

            $filename = "attendance_" . $request->month . ".csv";

            \Log::info('Export completed successfully', [
                'filename' => $filename,
                'staff_count' => $staff->count(),
                'attendance_records' => $attendanceData->count()
            ]);

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            \Log::error('Export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified attendance record
     */
    public function show($id)
    {
        $operator = Auth::guard('operator')->user();
        $attendance = Attendance::where('operator_id', $operator->id)
            ->with(['staff', 'crewAssignment.operatorBus', 'approvedBy'])
            ->findOrFail($id);

        return view('operator.attendance.show', compact('attendance'));
    }

    /**
     * Show the form for editing the specified attendance record
     */
    public function edit($id)
    {
        $operator = Auth::guard('operator')->user();
        $attendance = Attendance::where('operator_id', $operator->id)->findOrFail($id);

        $staff = Staff::where('operator_id', $operator->id)
            ->where('is_active', true)
            ->get();

        return view('operator.attendance.edit', compact('attendance', 'staff'));
    }

    /**
     * Update the specified attendance record
     */
    public function update(Request $request, $id)
    {
        $operator = Auth::guard('operator')->user();
        $attendance = Attendance::where('operator_id', $operator->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staff,id',
            'attendance_date' => 'required|date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'status' => 'required|in:present,absent,late,half_day,on_leave,sick_leave,emergency_leave',
            'hours_worked' => 'nullable|numeric|min:0|max:24',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'notes' => 'nullable|string',
            'check_in_location' => 'nullable|string',
            'check_out_location' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check for duplicate attendance if changing staff or date
        if ($request->staff_id != $attendance->staff_id || $request->attendance_date != $attendance->attendance_date) {
            $existingAttendance = Attendance::where('staff_id', $request->staff_id)
                ->where('attendance_date', $request->attendance_date)
                ->where('id', '!=', $id)
                ->first();

            if ($existingAttendance) {
                return redirect()->back()
                    ->with('error', 'Attendance record already exists for this staff member on this date.')
                    ->withInput();
            }
        }

        $data = $request->all();

        // Get crew assignment for this staff and date
        $crewAssignment = CrewAssignment::where('staff_id', $request->staff_id)
            ->where('assignment_date', $request->attendance_date)
            ->where('status', 'active')
            ->first();

        if ($crewAssignment) {
            $data['crew_assignment_id'] = $crewAssignment->id;
        }

        // Auto-calculate hours if check-in and check-out times are provided
        if ($request->check_in_time && $request->check_out_time) {
            $checkIn = \Carbon\Carbon::parse($request->check_in_time);
            $checkOut = \Carbon\Carbon::parse($request->check_out_time);

            $totalMinutes = $checkOut->diffInMinutes($checkIn);
            $data['hours_worked'] = round($totalMinutes / 60, 2);

            // Calculate overtime (assuming 8 hours is standard)
            if ($data['hours_worked'] > 8) {
                $data['overtime_hours'] = $data['hours_worked'] - 8;
                $data['hours_worked'] = 8;
            } else {
                $data['overtime_hours'] = 0;
            }
        }

        $attendance->update($data);

        return redirect()->route('operator.attendance.index')
            ->with('success', 'Attendance record updated successfully!');
    }

    /**
     * Remove the specified attendance record
     */
    public function destroy($id)
    {
        $operator = Auth::guard('operator')->user();
        $attendance = Attendance::where('operator_id', $operator->id)->findOrFail($id);

        $attendance->delete();

        return redirect()->route('operator.attendance.index')
            ->with('success', 'Attendance record deleted successfully!');
    }

    /**
     * Approve attendance record
     */
    public function approve($id)
    {
        $operator = Auth::guard('operator')->user();
        $attendance = Attendance::where('operator_id', $operator->id)->findOrFail($id);

        $attendance->approve($operator->id);

        return redirect()->back()
            ->with('success', 'Attendance record approved successfully!');
    }

    /**
     * Bulk approve attendance records
     */
    public function bulkApprove(Request $request)
    {
        $operator = Auth::guard('operator')->user();
        $attendanceIds = $request->attendance_ids;

        if (empty($attendanceIds)) {
            return redirect()->back()
                ->with('error', 'No attendance records selected.');
        }

        $updated = Attendance::where('operator_id', $operator->id)
            ->whereIn('id', $attendanceIds)
            ->update([
                'is_approved' => true,
                'approved_by' => $operator->id,
                'approved_at' => now(),
            ]);

        return redirect()->back()
            ->with('success', "Successfully approved {$updated} attendance records!");
    }

    /**
     * Get attendance summary for a staff member
     */
    public function getStaffSummary(Request $request)
    {
        $operator = Auth::guard('operator')->user();
        $staffId = $request->staff_id;
        $year = $request->year ?: now()->year;
        $month = $request->month ?: now()->month;

        $staff = Staff::where('operator_id', $operator->id)
            ->findOrFail($staffId);

        $stats = $staff->getAttendanceStats($year, $month);

        return response()->json($stats);
    }

    /**
     * Mark attendance for today (quick action)
     */
    public function markToday(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staff,id',
            'status' => 'required|in:present,absent,late,half_day,on_leave,sick_leave,emergency_leave',
            'check_in_time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $today = now()->toDateString();

        // Check if attendance already exists
        $existingAttendance = Attendance::where('staff_id', $request->staff_id)
            ->where('attendance_date', $today)
            ->first();

        if ($existingAttendance) {
            return response()->json(['error' => 'Attendance already marked for today'], 422);
        }

        $data = [
            'operator_id' => $operator->id,
            'staff_id' => $request->staff_id,
            'attendance_date' => $today,
            'status' => $request->status,
            'check_in_time' => $request->check_in_time,
        ];

        // Get crew assignment
        $crewAssignment = CrewAssignment::where('staff_id', $request->staff_id)
            ->where('assignment_date', $today)
            ->where('status', 'active')
            ->first();

        if ($crewAssignment) {
            $data['crew_assignment_id'] = $crewAssignment->id;
        }

        $attendance = Attendance::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'attendance' => $attendance
        ]);
    }

    /**
     * Get attendance calendar data
     */
    public function getCalendarData(Request $request)
    {
        $operator = Auth::guard('operator')->user();
        $staffId = $request->staff_id;
        $year = $request->year ?: now()->year;
        $month = $request->month ?: now()->month;

        $attendance = Attendance::where('operator_id', $operator->id)
            ->where('staff_id', $staffId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get()
            ->keyBy(function ($item) {
                return $item->attendance_date->format('Y-m-d');
            });

        return response()->json($attendance);
    }
}