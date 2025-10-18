@extends('operator.layouts.app')

@section('panel')
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-0">@lang('Add New Staff Member')</h4>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('operator.staff.index') }}" class="btn btn--secondary box--shadow1">
                <i class="fa fa-fw fa-arrow-left"></i>@lang('Back to Staff')
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <form action="{{ route('operator.staff.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Personal Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary">Personal Information</h5>
                                <hr>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                        id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                        id="last_name" name="last_name" value="{{ old('last_name') }}" required>
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
                                        id="email" name="email" value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                        id="phone" name="phone" value="{{ old('phone') }}" required>
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
                                        id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number') }}">
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
                                        <option value="driver" {{ old('role') == 'driver' ? 'selected' : '' }}>Driver
                                        </option>
                                        <option value="conductor" {{ old('role') == 'conductor' ? 'selected' : '' }}>
                                            Conductor</option>
                                        <option value="attendant" {{ old('role') == 'attendant' ? 'selected' : '' }}>
                                            Attendant</option>
                                        <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>
                                            Manager</option>
                                        <option value="other" {{ old('role') == 'other' ? 'selected' : '' }}>Other
                                        </option>
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
                                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male
                                        </option>
                                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female
                                        </option>
                                        <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other
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
                                        id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}"
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
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="address">Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3"
                                        required>{{ old('address') }}</textarea>
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
                                        id="city" name="city" value="{{ old('city') }}" required>
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="state">State <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('state') is-invalid @enderror"
                                        id="state" name="state" value="{{ old('state') }}" required>
                                    @error('state')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pincode">Pincode <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('pincode') is-invalid @enderror"
                                        id="pincode" name="pincode" value="{{ old('pincode') }}" required>
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
                                    <label for="emergency_contact_name">Contact Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control @error('emergency_contact_name') is-invalid @enderror"
                                        id="emergency_contact_name" name="emergency_contact_name"
                                        value="{{ old('emergency_contact_name') }}" required>
                                    @error('emergency_contact_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emergency_contact_phone">Contact Phone <span
                                            class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                                        id="emergency_contact_phone" name="emergency_contact_phone"
                                        value="{{ old('emergency_contact_phone') }}" required>
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
                                        value="{{ old('emergency_contact_relation') }}" required>
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
                                        id="joining_date" name="joining_date" value="{{ old('joining_date') }}"
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
                                            {{ old('employment_type') == 'full_time' ? 'selected' : '' }}>Full Time
                                        </option>
                                        <option value="part_time"
                                            {{ old('employment_type') == 'part_time' ? 'selected' : '' }}>Part Time
                                        </option>
                                        <option value="contract"
                                            {{ old('employment_type') == 'contract' ? 'selected' : '' }}>Contract
                                        </option>
                                        <option value="temporary"
                                            {{ old('employment_type') == 'temporary' ? 'selected' : '' }}>Temporary
                                        </option>
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
                                        id="basic_salary" name="basic_salary" value="{{ old('basic_salary') }}"
                                        required>
                                    @error('basic_salary')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="allowances">Allowances</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('allowances') is-invalid @enderror" id="allowances"
                                        name="allowances" value="{{ old('allowances', 0) }}">
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
                                            {{ old('salary_frequency') == 'monthly' ? 'selected' : '' }}>Monthly
                                        </option>
                                        <option value="weekly"
                                            {{ old('salary_frequency') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        <option value="daily" {{ old('salary_frequency') == 'daily' ? 'selected' : '' }}>
                                            Daily</option>
                                    </select>
                                    @error('salary_frequency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Documents -->
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary">Documents</h5>
                                <hr>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="aadhar_number">Aadhar Number</label>
                                    <input type="text"
                                        class="form-control @error('aadhar_number') is-invalid @enderror"
                                        id="aadhar_number" name="aadhar_number" value="{{ old('aadhar_number') }}">
                                    @error('aadhar_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pan_number">PAN Number</label>
                                    <input type="text" class="form-control @error('pan_number') is-invalid @enderror"
                                        id="pan_number" name="pan_number" value="{{ old('pan_number') }}">
                                    @error('pan_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="driving_license_number">Driving License Number</label>
                                    <input type="text"
                                        class="form-control @error('driving_license_number') is-invalid @enderror"
                                        id="driving_license_number" name="driving_license_number"
                                        value="{{ old('driving_license_number') }}">
                                    @error('driving_license_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="driving_license_expiry">License Expiry Date</label>
                                    <input type="date"
                                        class="form-control @error('driving_license_expiry') is-invalid @enderror"
                                        id="driving_license_expiry" name="driving_license_expiry"
                                        value="{{ old('driving_license_expiry') }}">
                                    @error('driving_license_expiry')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Profile Photo -->
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary">Profile Photo</h5>
                                <hr>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="profile_photo">Profile Photo</label>
                                    <input type="file"
                                        class="form-control @error('profile_photo') is-invalid @enderror"
                                        id="profile_photo" name="profile_photo" accept="image/*">
                                    @error('profile_photo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input"
                                            id="whatsapp_notifications_enabled" name="whatsapp_notifications_enabled"
                                            value="1" {{ old('whatsapp_notifications_enabled') ? 'checked' : '' }}>
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
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
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
                                    <button type="submit" class="btn btn--primary box--shadow1">
                                        <i class="fa fa-fw fa-save"></i>@lang('Create Staff Member')
                                    </button>
                                    <a href="{{ route('operator.staff.index') }}"
                                        class="btn btn--secondary box--shadow1">
                                        <i class="fa fa-fw fa-times"></i>@lang('Cancel')
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
