@extends('operator.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title">Staff Details - {{ $staff->full_name }}</h4>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('operator.staff.edit', $staff->id) }}" class="btn btn-warning">
                                    <i class="las la-edit"></i> Edit
                                </a>
                                <a href="{{ route('operator.staff.index') }}" class="btn btn-secondary">
                                    <i class="las la-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Personal Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Employee ID:</strong></div>
                                            <div class="col-8">{{ $staff->employee_id }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Name:</strong></div>
                                            <div class="col-8">{{ $staff->full_name }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Email:</strong></div>
                                            <div class="col-8">{{ $staff->email }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Phone:</strong></div>
                                            <div class="col-8">{{ $staff->phone }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>WhatsApp:</strong></div>
                                            <div class="col-8">
                                                @if ($staff->whatsapp_number)
                                                    {{ $staff->whatsapp_number }}
                                                    @if ($staff->whatsapp_notifications_enabled)
                                                        <span class="badge bg-success ms-2">Enabled</span>
                                                    @else
                                                        <span class="badge bg-warning ms-2">Disabled</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Not provided</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Role:</strong></div>
                                            <div class="col-8">
                                                <span class="badge bg-info">{{ ucfirst($staff->role) }}</span>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Gender:</strong></div>
                                            <div class="col-8">{{ ucfirst($staff->gender) }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Date of Birth:</strong></div>
                                            <div class="col-8">{{ $staff->date_of_birth->format('d M Y') }}
                                                ({{ $staff->age }} years)</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Status:</strong></div>
                                            <div class="col-8">
                                                @if ($staff->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Employment Information -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Employment Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Joining Date:</strong></div>
                                            <div class="col-8">{{ $staff->joining_date->format('d M Y') }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Experience:</strong></div>
                                            <div class="col-8">{{ $staff->experience }} years</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Employment Type:</strong></div>
                                            <div class="col-8">
                                                {{ ucfirst(str_replace('_', ' ', $staff->employment_type)) }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Basic Salary:</strong></div>
                                            <div class="col-8">₹{{ number_format($staff->basic_salary, 2) }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Allowances:</strong></div>
                                            <div class="col-8">₹{{ number_format($staff->allowances, 2) }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Total Salary:</strong></div>
                                            <div class="col-8">₹{{ number_format($staff->total_salary, 2) }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-4"><strong>Salary Frequency:</strong></div>
                                            <div class="col-8">{{ ucfirst($staff->salary_frequency) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Address Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-2"><strong>Address:</strong></div>
                                            <div class="col-10">{{ $staff->address }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-2"><strong>City:</strong></div>
                                            <div class="col-4">{{ $staff->city }}</div>
                                            <div class="col-2"><strong>State:</strong></div>
                                            <div class="col-4">{{ $staff->state }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-2"><strong>Pincode:</strong></div>
                                            <div class="col-4">{{ $staff->pincode }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Emergency Contact</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-2"><strong>Name:</strong></div>
                                            <div class="col-4">{{ $staff->emergency_contact_name }}</div>
                                            <div class="col-2"><strong>Phone:</strong></div>
                                            <div class="col-4">{{ $staff->emergency_contact_phone }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-2"><strong>Relation:</strong></div>
                                            <div class="col-4">{{ $staff->emergency_contact_relation }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents -->
                        @if ($staff->aadhar_number || $staff->pan_number || $staff->driving_license_number)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Documents</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                @if ($staff->aadhar_number)
                                                    <div class="col-md-6 mb-3">
                                                        <strong>Aadhar Number:</strong> {{ $staff->aadhar_number }}
                                                    </div>
                                                @endif
                                                @if ($staff->pan_number)
                                                    <div class="col-md-6 mb-3">
                                                        <strong>PAN Number:</strong> {{ $staff->pan_number }}
                                                    </div>
                                                @endif
                                                @if ($staff->driving_license_number)
                                                    <div class="col-md-6 mb-3">
                                                        <strong>Driving License:</strong>
                                                        {{ $staff->driving_license_number }}
                                                        @if ($staff->driving_license_expiry)
                                                            <br><small class="text-muted">Expires:
                                                                {{ $staff->driving_license_expiry->format('d M Y') }}</small>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Notes -->
                        @if ($staff->notes)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Notes</h5>
                                        </div>
                                        <div class="card-body">
                                            <p>{{ $staff->notes }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Current Assignments -->
                        @if ($staff->crewAssignments->count() > 0)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Current Assignments</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Bus</th>
                                                            <th>Role</th>
                                                            <th>Assignment Date</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($staff->crewAssignments as $assignment)
                                                            <tr>
                                                                <td>{{ $assignment->operatorBus->travel_name ?? 'N/A' }}
                                                                </td>
                                                                <td><span
                                                                        class="badge bg-info">{{ ucfirst($assignment->role) }}</span>
                                                                </td>
                                                                <td>{{ $assignment->assignment_date->format('d M Y') }}
                                                                </td>
                                                                <td>
                                                                    @if ($assignment->status === 'active')
                                                                        <span class="badge bg-success">Active</span>
                                                                    @else
                                                                        <span
                                                                            class="badge bg-secondary">{{ ucfirst($assignment->status) }}</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Recent Attendance -->
                        @if ($attendanceStats)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Attendance Summary ({{ now()->format('F Y') }})</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <div class="text-center">
                                                        <h4 class="text-primary">{{ $attendanceStats['present_days'] }}
                                                        </h4>
                                                        <small>Present</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="text-center">
                                                        <h4 class="text-danger">{{ $attendanceStats['absent_days'] }}</h4>
                                                        <small>Absent</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="text-center">
                                                        <h4 class="text-warning">{{ $attendanceStats['late_days'] }}</h4>
                                                        <small>Late</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="text-center">
                                                        <h4 class="text-info">{{ $attendanceStats['leave_days'] }}</h4>
                                                        <small>Leave</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="text-center">
                                                        <h4 class="text-success">{{ $attendanceStats['total_hours'] }}
                                                        </h4>
                                                        <small>Total Hours</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="text-center">
                                                        <h4 class="text-secondary">
                                                            {{ $attendanceStats['overtime_hours'] }}</h4>
                                                        <small>Overtime</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
