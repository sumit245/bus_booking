@extends('operator.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-0">Staff Details - {{ $staff->full_name }}
                                <span class="badge badge-pill badge-{{ $staff->is_active ? 'success' : 'danger' }}"
                                    style="width: 10px; height: 10px; padding: 0; border-radius: 50%; display: inline-block;"
                                    title="{{ $staff->is_active ? 'Active' : 'Inactive' }}"></span>
                            </h4>
                            <a href="{{ route('operator.staff.index') }}" class="btn btn-secondary btn-sm mt-2">
                                <i class="las la-arrow-left"></i> @lang('Back')
                            </a>
                        </div>
                        <div>
                            <a href="{{ route('operator.staff.edit', $staff->id) }}" class="btn btn-warning btn-sm">
                                <i class="las la-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Personal Information -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-primary mb-3">Personal Information</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Employee ID:</strong><br>{{ $staff->employee_id }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Name:</strong><br>{{ $staff->full_name }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Email:</strong><br>{{ $staff->email }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Phone:</strong><br>{{ $staff->phone }}</p>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>WhatsApp:</strong><br>
                                            @if ($staff->whatsapp_number)
                                                {{ $staff->whatsapp_number }}
                                                @if ($staff->whatsapp_notifications_enabled)
                                                    <span class="badge badge-success ml-1">Enabled</span>
                                                @else
                                                    <span class="badge badge-warning ml-1">Disabled</span>
                                                @endif
                                            @else
                                                <span class="text-muted">Not provided</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Role:</strong><br><span
                                                class="badge badge-info">{{ ucfirst($staff->role) }}</span></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Gender:</strong><br>{{ ucfirst($staff->gender) }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Date of
                                                Birth:</strong><br>{{ $staff->date_of_birth->format('d M Y') }}
                                            ({{ $staff->age }} years)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employment Information -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h5 class="text-primary mb-3">Employment Information</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Joining
                                                Date:</strong><br>{{ $staff->joining_date->format('d M Y') }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Experience:</strong><br>{{ $staff->experience }} years
                                        </p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Employment
                                                Type:</strong><br>{{ ucfirst(str_replace('_', ' ', $staff->employment_type)) }}
                                        </p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Salary
                                                Frequency:</strong><br>{{ ucfirst($staff->salary_frequency) }}</p>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Basic
                                                Salary:</strong><br>₹{{ number_format($staff->basic_salary, 2) }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2">
                                            <strong>Allowances:</strong><br>₹{{ number_format($staff->allowances, 2) }}
                                        </p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>Total
                                                Salary:</strong><br>₹{{ number_format($staff->total_salary, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h5 class="text-primary mb-3">Address Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Address:</strong><br>{{ $staff->address }}</p>
                                    </div>
                                    <div class="col-md-2">
                                        <p class="mb-2"><strong>City:</strong><br>{{ $staff->city }}</p>
                                    </div>
                                    <div class="col-md-2">
                                        <p class="mb-2"><strong>State:</strong><br>{{ $staff->state }}</p>
                                    </div>
                                    <div class="col-md-2">
                                        <p class="mb-2"><strong>Pincode:</strong><br>{{ $staff->pincode }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h5 class="text-primary mb-3">Emergency Contact</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <p class="mb-2"><strong>Name:</strong><br>{{ $staff->emergency_contact_name }}
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-2"><strong>Phone:</strong><br>{{ $staff->emergency_contact_phone }}
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-2">
                                            <strong>Relation:</strong><br>{{ $staff->emergency_contact_relation }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents -->
                        @if ($staff->aadhar_number || $staff->pan_number || $staff->driving_license_number)
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5 class="text-primary mb-3">Documents</h5>
                                    <div class="row">
                                        @if ($staff->aadhar_number)
                                            <div class="col-md-4">
                                                <p class="mb-2"><strong>Aadhar
                                                        Number:</strong><br>{{ $staff->aadhar_number }}</p>
                                            </div>
                                        @endif
                                        @if ($staff->pan_number)
                                            <div class="col-md-4">
                                                <p class="mb-2"><strong>PAN Number:</strong><br>{{ $staff->pan_number }}
                                                </p>
                                            </div>
                                        @endif
                                        @if ($staff->driving_license_number)
                                            <div class="col-md-4">
                                                <p class="mb-2"><strong>Driving
                                                        License:</strong><br>{{ $staff->driving_license_number }}
                                                    @if ($staff->driving_license_expiry)
                                                        <br><small class="text-muted">Expires:
                                                            {{ $staff->driving_license_expiry->format('d M Y') }}</small>
                                                    @endif
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Notes -->
                        @if ($staff->notes)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">Notes</h5>
                                    <div class="alert alert-info">
                                        {{ $staff->notes }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Current Assignments -->
                        @if ($staff->crewAssignments->count() > 0)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">Current Assignments</h5>
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
                                                        <td>{{ $assignment->operatorBus->travel_name ?? 'N/A' }}</td>
                                                        <td><span
                                                                class="badge badge-info">{{ ucfirst($assignment->role) }}</span>
                                                        </td>
                                                        <td>{{ $assignment->assignment_date->format('d M Y') }}</td>
                                                        <td>
                                                            @if ($assignment->status === 'active')
                                                                <span class="badge badge-success">Active</span>
                                                            @else
                                                                <span
                                                                    class="badge badge-secondary">{{ ucfirst($assignment->status) }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Recent Attendance -->
                        @if ($attendanceStats)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">Attendance Summary ({{ now()->format('F Y') }})</h5>
                                    <div class="row text-center">
                                        <div class="col-md-2">
                                            <h4 class="text-primary">{{ $attendanceStats['present_days'] }}</h4>
                                            <small>Present</small>
                                        </div>
                                        <div class="col-md-2">
                                            <h4 class="text-danger">{{ $attendanceStats['absent_days'] }}</h4>
                                            <small>Absent</small>
                                        </div>
                                        <div class="col-md-2">
                                            <h4 class="text-warning">{{ $attendanceStats['late_days'] }}</h4>
                                            <small>Late</small>
                                        </div>
                                        <div class="col-md-2">
                                            <h4 class="text-info">{{ $attendanceStats['leave_days'] }}</h4>
                                            <small>Leave</small>
                                        </div>
                                        <div class="col-md-2">
                                            <h4 class="text-success">{{ $attendanceStats['total_hours'] }}</h4>
                                            <small>Total Hours</small>
                                        </div>
                                        <div class="col-md-2">
                                            <h4 class="text-secondary">{{ $attendanceStats['overtime_hours'] }}</h4>
                                            <small>Overtime</small>
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
