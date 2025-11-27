@extends('operator.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Edit Staff Member - {{ $staff->full_name }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('operator.staff.update', $staff->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <!-- Personal Information -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary">Personal Information</h5>
                                    {{-- <hr> --}}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                            id="first_name" name="first_name"
                                            value="{{ old('first_name', $staff->first_name) }}" required>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                            id="last_name" name="last_name"
                                            value="{{ old('last_name', $staff->last_name) }}" required>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            id="email" name="email" value="{{ old('email', $staff->email) }}"
                                            required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                            id="phone" name="phone" value="{{ old('phone', $staff->phone) }}"
                                            required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="whatsapp_number">WhatsApp Number</label>
                                        <input type="text"
                                            class="form-control @error('whatsapp_number') is-invalid @enderror"
                                            id="whatsapp_number" name="whatsapp_number"
                                            value="{{ old('whatsapp_number', $staff->whatsapp_number) }}">
                                        @error('whatsapp_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role">Role <span class="text-danger">*</span></label>
                                        <select class="form-control @error('role') is-invalid @enderror" id="role"
                                            name="role" required>
                                            <option value="">Select Role</option>
                                            <option value="driver"
                                                {{ old('role', $staff->role) == 'driver' ? 'selected' : '' }}>Driver
                                            </option>
                                            <option value="conductor"
                                                {{ old('role', $staff->role) == 'conductor' ? 'selected' : '' }}>Conductor
                                            </option>
                                            <option value="attendant"
                                                {{ old('role', $staff->role) == 'attendant' ? 'selected' : '' }}>Attendant
                                            </option>
                                            <option value="manager"
                                                {{ old('role', $staff->role) == 'manager' ? 'selected' : '' }}>Manager
                                            </option>
                                            <option value="other"
                                                {{ old('role', $staff->role) == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('role')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gender">Gender <span class="text-danger">*</span></label>
                                        <select class="form-control @error('gender') is-invalid @enderror" id="gender"
                                            name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male"
                                                {{ old('gender', $staff->gender) == 'male' ? 'selected' : '' }}>Male
                                            </option>
                                            <option value="female"
                                                {{ old('gender', $staff->gender) == 'female' ? 'selected' : '' }}>Female
                                            </option>
                                            <option value="other"
                                                {{ old('gender', $staff->gender) == 'other' ? 'selected' : '' }}>Other
                                            </option>
                                        </select>
                                        @error('gender')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="date_of_birth">Date of Birth <span class="text-danger">*</span></label>
                                        <input type="date"
                                            class="form-control @error('date_of_birth') is-invalid @enderror"
                                            id="date_of_birth" name="date_of_birth"
                                            value="{{ old('date_of_birth', $staff->date_of_birth->format('Y-m-d')) }}"
                                            required>
                                        @error('date_of_birth')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Address Information -->
                            <div class="row mb-4 mt-4">
                                <div class="col-12">
                                    <h5 class="text-primary">Address Information</h5>
                                    <hr>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="address">Address <span class="text-danger">*</span></label>
                                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2"
                                            required>{{ old('address', $staff->address) }}</textarea>
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="city">City <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('city') is-invalid @enderror"
                                            id="city" name="city" value="{{ old('city', $staff->city) }}"
                                            required>
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="state">State <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('state') is-invalid @enderror"
                                            id="state" name="state" value="{{ old('state', $staff->state) }}"
                                            required>
                                        @error('state')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pincode">Pincode <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('pincode') is-invalid @enderror"
                                            id="pincode" name="pincode" value="{{ old('pincode', $staff->pincode) }}"
                                            required>
                                        @error('pincode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="row mb-4 mt-4">
                                <div class="col-12">
                                    <h5 class="text-primary">Emergency Contact</h5>
                                    <hr>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="emergency_contact_name">Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('emergency_contact_name') is-invalid @enderror"
                                            id="emergency_contact_name" name="emergency_contact_name"
                                            value="{{ old('emergency_contact_name', $staff->emergency_contact_name) }}"
                                            required>
                                        @error('emergency_contact_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="emergency_contact_phone">Phone <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                                            id="emergency_contact_phone" name="emergency_contact_phone"
                                            value="{{ old('emergency_contact_phone', $staff->emergency_contact_phone) }}"
                                            required>
                                        @error('emergency_contact_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="emergency_contact_relation">Relation <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('emergency_contact_relation') is-invalid @enderror"
                                            id="emergency_contact_relation" name="emergency_contact_relation"
                                            value="{{ old('emergency_contact_relation', $staff->emergency_contact_relation) }}"
                                            required>
                                        @error('emergency_contact_relation')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Employment Information -->
                            <div class="row mb-4 mt-4">
                                <div class="col-12">
                                    <h5 class="text-primary">Employment Information</h5>
                                    <hr>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="joining_date">Joining Date <span class="text-danger">*</span></label>
                                        <input type="date"
                                            class="form-control @error('joining_date') is-invalid @enderror"
                                            id="joining_date" name="joining_date"
                                            value="{{ old('joining_date', $staff->joining_date->format('Y-m-d')) }}"
                                            required>
                                        @error('joining_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="employment_type">Employment Type <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control @error('employment_type') is-invalid @enderror"
                                            id="employment_type" name="employment_type" required>
                                            <option value="">Select Type</option>
                                            <option value="full_time"
                                                {{ old('employment_type', $staff->employment_type) == 'full_time' ? 'selected' : '' }}>
                                                Full Time</option>
                                            <option value="part_time"
                                                {{ old('employment_type', $staff->employment_type) == 'part_time' ? 'selected' : '' }}>
                                                Part Time</option>
                                            <option value="contract"
                                                {{ old('employment_type', $staff->employment_type) == 'contract' ? 'selected' : '' }}>
                                                Contract</option>
                                            <option value="temporary"
                                                {{ old('employment_type', $staff->employment_type) == 'temporary' ? 'selected' : '' }}>
                                                Temporary</option>
                                        </select>
                                        @error('employment_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="basic_salary">Basic Salary <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01"
                                            class="form-control @error('basic_salary') is-invalid @enderror"
                                            id="basic_salary" name="basic_salary"
                                            value="{{ old('basic_salary', $staff->basic_salary) }}" required>
                                        @error('basic_salary')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="allowances">Allowances</label>
                                        <input type="number" step="0.01"
                                            class="form-control @error('allowances') is-invalid @enderror"
                                            id="allowances" name="allowances"
                                            value="{{ old('allowances', $staff->allowances) }}">
                                        @error('allowances')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="salary_frequency">Salary Frequency <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control @error('salary_frequency') is-invalid @enderror"
                                            id="salary_frequency" name="salary_frequency" required>
                                            <option value="">Select Frequency</option>
                                            <option value="monthly"
                                                {{ old('salary_frequency', $staff->salary_frequency) == 'monthly' ? 'selected' : '' }}>
                                                Monthly</option>
                                            <option value="weekly"
                                                {{ old('salary_frequency', $staff->salary_frequency) == 'weekly' ? 'selected' : '' }}>
                                                Weekly</option>
                                            <option value="daily"
                                                {{ old('salary_frequency', $staff->salary_frequency) == 'daily' ? 'selected' : '' }}>
                                                Daily</option>
                                        </select>
                                        @error('salary_frequency')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Status and Preferences -->
                            <div class="row mb-4 mt-4">
                                <div class="col-12">
                                    <h5 class="text-primary">Status and Preferences</h5>
                                    <hr>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="is_active"
                                                name="is_active" value="1"
                                                {{ old('is_active', $staff->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">
                                                Active Status
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input"
                                                id="whatsapp_notifications_enabled" name="whatsapp_notifications_enabled"
                                                value="1"
                                                {{ old('whatsapp_notifications_enabled', $staff->whatsapp_notifications_enabled) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="whatsapp_notifications_enabled">
                                                Enable WhatsApp Notifications
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="notes">Notes</label>
                                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $staff->notes) }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="las la-save"></i> Update Staff Member
                                        </button>
                                        <a href="{{ route('operator.staff.show', $staff->id) }}"
                                            class="btn btn-secondary">
                                            <i class="las la-times"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
