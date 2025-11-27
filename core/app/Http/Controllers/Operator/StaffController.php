<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function __construct()
    {
        // Middleware is already applied at route level
    }

    /**
     * Display a listing of staff members
     */
    public function index(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        $query = Staff::where('operator_id', $operator->id)
            ->with([
                'crewAssignments' => function ($q) {
                    $q->where('status', 'active')->with('operatorBus');
                }
            ]);

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $staff = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('operator.staff.index', compact('staff'));
    }

    /**
     * Show the form for creating a new staff member
     */
    public function create()
    {
        $operator = Auth::guard('operator')->user();
        return view('operator.staff.create', compact('operator'));
    }

    /**
     * Store a newly created staff member
     */
    public function store(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:staff,email',
            'phone' => 'required|string|unique:staff,phone',
            'whatsapp_number' => 'nullable|string',
            'role' => 'required|in:driver,conductor,attendant,manager,other',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date|before:today',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'pincode' => 'required|string|max:10',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string',
            'emergency_contact_relation' => 'required|string|max:255',
            'joining_date' => 'required|date',
            'employment_type' => 'required|in:full_time,part_time,contract,temporary',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'salary_frequency' => 'required|in:monthly,weekly,daily',
            'aadhar_number' => 'nullable|string|unique:staff,aadhar_number',
            'pan_number' => 'nullable|string|unique:staff,pan_number',
            'driving_license_number' => 'nullable|string|unique:staff,driving_license_number',
            'driving_license_expiry' => 'nullable|date|after:today',
            'passport_number' => 'nullable|string|unique:staff,passport_number',
            'passport_expiry' => 'nullable|date|after:today',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'whatsapp_notifications_enabled' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->all();
        $data['operator_id'] = $operator->id;

        // Generate employee ID
        $data['employee_id'] = Staff::generateEmployeeId($operator->id, $data['role']);

        // Calculate total salary
        $data['total_salary'] = $data['basic_salary'] + ($data['allowances'] ?? 0);

        // Handle file uploads
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('staff/photos', 'public');
        }

        $staff = Staff::create($data);

        return redirect()->route('operator.staff.index')
            ->with('success', 'Staff member created successfully!');
    }

    /**
     * Display the specified staff member
     */
    public function show($id)
    {
        $operator = Auth::guard('operator')->user();
        $staff = Staff::where('operator_id', $operator->id)
            ->with([
                'crewAssignments.operatorBus',
                'attendance' => function ($q) {
                    $q->orderBy('attendance_date', 'desc')->limit(30);
                },
                'salaryRecords' => function ($q) {
                    $q->orderBy('salary_period', 'desc')->limit(12);
                }
            ])
            ->findOrFail($id);

        // Get attendance stats for current month
        $currentMonth = now();
        $attendanceStats = $staff->getAttendanceStats($currentMonth->year, $currentMonth->month);

        return view('operator.staff.show', compact('staff', 'attendanceStats'));
    }

    /**
     * Show the form for editing the specified staff member
     */
    public function edit($id)
    {
        $operator = Auth::guard('operator')->user();
        $staff = Staff::where('operator_id', $operator->id)->findOrFail($id);

        return view('operator.staff.edit', compact('staff'));
    }

    /**
     * Update the specified staff member
     */
    public function update(Request $request, $id)
    {
        $operator = Auth::guard('operator')->user();
        $staff = Staff::where('operator_id', $operator->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('staff', 'email')->ignore($staff->id)],
            'phone' => ['required', 'string', Rule::unique('staff', 'phone')->ignore($staff->id)],
            'whatsapp_number' => 'nullable|string',
            'role' => 'required|in:driver,conductor,attendant,manager,other',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date|before:today',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'pincode' => 'required|string|max:10',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string',
            'emergency_contact_relation' => 'required|string|max:255',
            'joining_date' => 'required|date',
            'employment_type' => 'required|in:full_time,part_time,contract,temporary',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'salary_frequency' => 'required|in:monthly,weekly,daily',
            'aadhar_number' => ['nullable', 'string', Rule::unique('staff', 'aadhar_number')->ignore($staff->id)],
            'pan_number' => ['nullable', 'string', Rule::unique('staff', 'pan_number')->ignore($staff->id)],
            'driving_license_number' => ['nullable', 'string', Rule::unique('staff', 'driving_license_number')->ignore($staff->id)],
            'driving_license_expiry' => 'nullable|date|after:today',
            'passport_number' => ['nullable', 'string', Rule::unique('staff', 'passport_number')->ignore($staff->id)],
            'passport_expiry' => 'nullable|date|after:today',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'whatsapp_notifications_enabled' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->all();

        // Handle checkbox fields (unchecked checkboxes don't send values)
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['whatsapp_notifications_enabled'] = $request->has('whatsapp_notifications_enabled') ? 1 : 0;

        // Calculate total salary
        $data['total_salary'] = $data['basic_salary'] + ($data['allowances'] ?? 0);

        // Handle file uploads
        if ($request->hasFile('profile_photo')) {
            // Delete old photo
            if ($staff->profile_photo) {
                Storage::disk('public')->delete($staff->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('staff/photos', 'public');
        }

        $staff->update($data);

        $notify[] = ['success', 'Staff member updated successfully!'];
        return redirect()->route('operator.staff.index')->withNotify($notify);
    }

    /**
     * Remove the specified staff member
     */
    public function destroy($id)
    {
        $operator = Auth::guard('operator')->user();
        $staff = Staff::where('operator_id', $operator->id)->findOrFail($id);

        // Check if staff has active assignments
        $activeAssignments = $staff->crewAssignments()->where('status', 'active')->count();
        if ($activeAssignments > 0) {
            $notify[] = ['error', 'Cannot delete staff member with active assignments. Please deactivate assignments first.'];
            return redirect()->back()->withNotify($notify);
        }

        // Delete profile photo
        if ($staff->profile_photo) {
            Storage::disk('public')->delete($staff->profile_photo);
        }

        $staff->delete();

        $notify[] = ['success', 'Staff member deleted successfully!'];
        return redirect()->route('operator.staff.index')->withNotify($notify);
    }

    /**
     * Toggle staff active status
     */
    public function toggleStatus($id)
    {
        $operator = Auth::guard('operator')->user();
        $staff = Staff::where('operator_id', $operator->id)->findOrFail($id);

        $staff->update(['is_active' => !$staff->is_active]);

        $status = $staff->is_active ? 'activated' : 'deactivated';
        $notify[] = ['success', "Staff member {$status} successfully!"];
        return redirect()->back()->withNotify($notify);
    }

    /**
     * Get staff by role (AJAX)
     */
    public function getByRole(Request $request)
    {
        $operator = Auth::guard('operator')->user();
        $role = $request->role;

        $staff = Staff::where('operator_id', $operator->id)
            ->where('role', $role)
            ->where('is_active', true)
            ->select('id', 'first_name', 'last_name', 'employee_id')
            ->get();

        return response()->json($staff);
    }
}