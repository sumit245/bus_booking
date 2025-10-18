@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Add New Operator')</h4>
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar" role="progressbar" style="width: 25%" id="progressBar"></div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Step Indicator -->
                    <div class="row mb-4">
                        <div class="col-3 text-center">
                            <div class="step-indicator active" data-step="1">
                                <div class="step-number">1</div>
                                <div class="step-title">@lang('Basic Details')</div>
                            </div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="step-indicator" data-step="2">
                                <div class="step-number">2</div>
                                <div class="step-title">@lang('Company Details')</div>
                            </div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="step-indicator" data-step="3">
                                <div class="step-number">3</div>
                                <div class="step-title">@lang('Documents')</div>
                            </div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="step-indicator" data-step="4">
                                <div class="step-number">4</div>
                                <div class="step-title">@lang('Bank Details')</div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.fleet.operators.store') }}" method="POST" enctype="multipart/form-data"
                        id="operatorForm">
                        @csrf

                        <!-- Step 1: Basic Details -->
                        <div class="step-content" id="step1">
                            <h5 class="mb-3">@lang('Basic Information')</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Full Name') <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            name="name" value="{{ old('name') }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Email') <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            name="email" value="{{ old('email') }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Mobile Number') <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('mobile') is-invalid @enderror"
                                            name="mobile" value="{{ old('mobile') }}" required>
                                        @error('mobile')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Password') <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                                            name="password" required>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Confirm Password') <span class="text-danger">*</span></label>
                                        <input type="password"
                                            class="form-control @error('password_confirmation') is-invalid @enderror"
                                            name="password_confirmation" required>
                                        @error('password_confirmation')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Address') <span class="text-danger">*</span></label>
                                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3" required>{{ old('address') }}</textarea>
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <button type="button" class="btn btn--primary"
                                    onclick="nextStep()">@lang('Next')</button>
                            </div>
                        </div>

                        <!-- Step 2: Company Details -->
                        <div class="step-content d-none" id="step2">
                            <h5 class="mb-3">@lang('Company Information')</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Company Name') <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('company_name') is-invalid @enderror"
                                            name="company_name" value="{{ old('company_name') }}" required>
                                        @error('company_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('City (Head Office)') <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('city') is-invalid @enderror"
                                            name="city" value="{{ old('city') }}" required>
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('State (Head Office)') <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('state') is-invalid @enderror"
                                            name="state" value="{{ old('state') }}" required>
                                        @error('state')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <button type="button" class="btn btn--secondary"
                                    onclick="prevStep()">@lang('Previous')</button>
                                <button type="button" class="btn btn--primary"
                                    onclick="nextStep()">@lang('Next')</button>
                            </div>
                        </div>

                        <!-- Step 3: Documents -->
                        <div class="step-content d-none" id="step3">
                            <h5 class="mb-3">@lang('Required Documents')</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Passport Size Photo') <span class="text-danger">*</span></label>
                                        <div class="image-upload">
                                            <div class="thumb">
                                                <div class="avatar-preview">
                                                    <div class="profilePicPreview"
                                                        style="background-image: url({{ getImage('', imagePath()['profile']['operator']['size']) }})">
                                                        <button type="button" class="remove-image"><i
                                                                class="fa fa-times"></i></button>
                                                    </div>
                                                </div>
                                                <div class="avatar-edit">
                                                    <input type="file"
                                                        class="profilePicUpload @error('photo') is-invalid @enderror"
                                                        name="photo" id="photoUpload" accept=".png, .jpg, .jpeg"
                                                        required>
                                                    <label for="photoUpload"
                                                        class="bg--primary">@lang('Upload Photo')</label>
                                                    <small class="mt-2 text-facebook">@lang('Supported files'):
                                                        <b>@lang('jpeg'), @lang('jpg'), @lang('png')</b>.
                                                        @lang('Image will be resized into')
                                                        {{ imagePath()['profile']['operator']['size'] }}@lang('px').</small>
                                                </div>
                                            </div>
                                        </div>
                                        @error('photo')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('PAN Card') <span class="text-danger">*</span></label>
                                        <div class="image-upload">
                                            <div class="thumb">
                                                <div class="avatar-preview">
                                                    <div class="profilePicPreview"
                                                        style="background-image: url({{ getImage('', imagePath()['profile']['operator']['size']) }})">
                                                        <button type="button" class="remove-image"><i
                                                                class="fa fa-times"></i></button>
                                                    </div>
                                                </div>
                                                <div class="avatar-edit">
                                                    <input type="file"
                                                        class="profilePicUpload @error('pan_card') is-invalid @enderror"
                                                        name="pan_card" id="panUpload" accept=".png, .jpg, .jpeg"
                                                        required>
                                                    <label for="panUpload" class="bg--primary">@lang('Upload PAN')</label>
                                                    <small class="mt-2 text-facebook">@lang('Supported files'):
                                                        <b>@lang('jpeg'), @lang('jpg'),
                                                            @lang('png')</b>.</small>
                                                </div>
                                            </div>
                                        </div>
                                        @error('pan_card')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Aadhaar Card Front') <span class="text-danger">*</span></label>
                                        <div class="image-upload">
                                            <div class="thumb">
                                                <div class="avatar-preview">
                                                    <div class="profilePicPreview"
                                                        style="background-image: url({{ getImage('', imagePath()['profile']['operator']['size']) }})">
                                                        <button type="button" class="remove-image"><i
                                                                class="fa fa-times"></i></button>
                                                    </div>
                                                </div>
                                                <div class="avatar-edit">
                                                    <input type="file"
                                                        class="profilePicUpload @error('aadhaar_card_front') is-invalid @enderror"
                                                        name="aadhaar_card_front" id="aadhaarFrontUpload"
                                                        accept=".png, .jpg, .jpeg" required>
                                                    <label for="aadhaarFrontUpload"
                                                        class="bg--primary">@lang('Upload Aadhaar Front')</label>
                                                    <small class="mt-2 text-facebook">@lang('Supported files'):
                                                        <b>@lang('jpeg'), @lang('jpg'),
                                                            @lang('png')</b>.</small>
                                                </div>
                                            </div>
                                        </div>
                                        @error('aadhaar_card_front')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Aadhaar Card Back') <span class="text-danger">*</span></label>
                                        <div class="image-upload">
                                            <div class="thumb">
                                                <div class="avatar-preview">
                                                    <div class="profilePicPreview"
                                                        style="background-image: url({{ getImage('', imagePath()['profile']['operator']['size']) }})">
                                                        <button type="button" class="remove-image"><i
                                                                class="fa fa-times"></i></button>
                                                    </div>
                                                </div>
                                                <div class="avatar-edit">
                                                    <input type="file"
                                                        class="profilePicUpload @error('aadhaar_card_back') is-invalid @enderror"
                                                        name="aadhaar_card_back" id="aadhaarBackUpload"
                                                        accept=".png, .jpg, .jpeg" required>
                                                    <label for="aadhaarBackUpload"
                                                        class="bg--primary">@lang('Upload Aadhaar Back')</label>
                                                    <small class="mt-2 text-facebook">@lang('Supported files'):
                                                        <b>@lang('jpeg'), @lang('jpg'),
                                                            @lang('png')</b>.</small>
                                                </div>
                                            </div>
                                        </div>
                                        @error('aadhaar_card_back')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Business License') <span class="text-danger">*</span></label>
                                        <div class="image-upload">
                                            <div class="thumb">
                                                <div class="avatar-preview">
                                                    <div class="profilePicPreview"
                                                        style="background-image: url({{ getImage('', imagePath()['profile']['operator']['size']) }})">
                                                        <button type="button" class="remove-image"><i
                                                                class="fa fa-times"></i></button>
                                                    </div>
                                                </div>
                                                <div class="avatar-edit">
                                                    <input type="file"
                                                        class="profilePicUpload @error('business_license') is-invalid @enderror"
                                                        name="business_license" id="businessLicenseUpload"
                                                        accept=".png, .jpg, .jpeg, .pdf" required>
                                                    <label for="businessLicenseUpload"
                                                        class="bg--primary">@lang('Upload Business License')</label>
                                                    <small class="mt-2 text-facebook">@lang('Supported files'):
                                                        <b>@lang('jpeg'), @lang('jpg'), @lang('png'),
                                                            @lang('pdf')</b>.</small>
                                                </div>
                                            </div>
                                        </div>
                                        @error('business_license')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <button type="button" class="btn btn--secondary"
                                    onclick="prevStep()">@lang('Previous')</button>
                                <button type="button" class="btn btn--primary"
                                    onclick="nextStep()">@lang('Next')</button>
                            </div>
                        </div>

                        <!-- Step 4: Bank Details -->
                        <div class="step-content d-none" id="step4">
                            <h5 class="mb-3">@lang('Banking Information')</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Account Holder Name') <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('account_holder_name') is-invalid @enderror"
                                            name="account_holder_name" value="{{ old('account_holder_name') }}" required>
                                        @error('account_holder_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Bank Account Number') <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('account_number') is-invalid @enderror"
                                            name="account_number" value="{{ old('account_number') }}" required>
                                        @error('account_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('IFSC Code') <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('ifsc_code') is-invalid @enderror"
                                            name="ifsc_code" value="{{ old('ifsc_code') }}" required>
                                        @error('ifsc_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Bank Name') <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('bank_name') is-invalid @enderror"
                                            name="bank_name" value="{{ old('bank_name') }}" required>
                                        @error('bank_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('GST Number') <span
                                                class="text-muted">(@lang('Optional'))</span></label>
                                        <input type="text"
                                            class="form-control @error('gst_number') is-invalid @enderror"
                                            name="gst_number" value="{{ old('gst_number') }}">
                                        @error('gst_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Cancelled Cheque') <span class="text-danger">*</span></label>
                                        <div class="image-upload">
                                            <div class="thumb">
                                                <div class="avatar-preview">
                                                    <div class="profilePicPreview"
                                                        style="background-image: url({{ getImage('', imagePath()['profile']['operator']['size']) }})">
                                                        <button type="button" class="remove-image"><i
                                                                class="fa fa-times"></i></button>
                                                    </div>
                                                </div>
                                                <div class="avatar-edit">
                                                    <input type="file"
                                                        class="profilePicUpload @error('cancelled_cheque') is-invalid @enderror"
                                                        name="cancelled_cheque" id="chequeUpload"
                                                        accept=".png, .jpg, .jpeg" required>
                                                    <label for="chequeUpload"
                                                        class="bg--primary">@lang('Upload Cheque')</label>
                                                    <small class="mt-2 text-facebook">@lang('Supported files'):
                                                        <b>@lang('jpeg'), @lang('jpg'),
                                                            @lang('png')</b>.</small>
                                                </div>
                                            </div>
                                        </div>
                                        @error('cancelled_cheque')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <button type="button" class="btn btn--secondary"
                                    onclick="prevStep()">@lang('Previous')</button>
                                <button type="submit" class="btn btn--success">@lang('Complete Registration')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .step-indicator {
            margin-bottom: 20px;
            cursor: pointer;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .step-indicator.active .step-number {
            background-color: #007bff;
            color: white;
        }

        .step-indicator.completed .step-number {
            background-color: #28a745;
            color: white;
        }

        .step-title {
            font-size: 12px;
            color: #6c757d;
            font-weight: 500;
        }

        .step-indicator.active .step-title {
            color: #007bff;
            font-weight: 600;
        }

        .step-indicator.completed .step-title {
            color: #28a745;
            font-weight: 600;
        }

        .progress {
            background-color: #e9ecef;
            border-radius: 4px;
        }

        .progress-bar {
            background-color: #007bff;
            transition: width 0.3s ease;
        }

        .step-content {
            min-height: 400px;
        }
    </style>
@endpush

@push('script')
    <script>
        let currentStep = 1;

        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < 4) {
                    currentStep++;
                    showStep(currentStep);
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }

        function showStep(step) {
            $('.step-content').addClass('d-none');
            $(`#step${step}`).removeClass('d-none');
            currentStep = step;

            const progress = (step / 4) * 100;
            $('#progressBar').css('width', progress + '%');
            updateStepIndicators();
        }

        function updateStepIndicators() {
            $('.step-indicator').each(function(index) {
                const stepNumber = index + 1;
                $(this).removeClass('active completed');
                if (stepNumber < currentStep) {
                    $(this).addClass('completed');
                } else if (stepNumber === currentStep) {
                    $(this).addClass('active');
                }
            });
        }

        function validateCurrentStep() {
            const currentStepContent = $(`.step-content:not(.d-none)`);
            let isValid = true;

            currentStepContent.find('.is-invalid').removeClass('is-invalid');
            currentStepContent.find('.invalid-feedback').hide();

            currentStepContent.find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('is-invalid');
                    $(this).siblings('.invalid-feedback').text('This field is required.').show();
                    isValid = false;
                }
            });

            // Validate email format
            const email = $('input[name="email"]').val();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $('input[name="email"]').addClass('is-invalid');
                $('input[name="email"]').siblings('.invalid-feedback').text('Please enter a valid email address.').show();
                isValid = false;
            }

            // Validate password confirmation
            const password = $('input[name="password"]').val();
            const confirmPassword = $('input[name="password_confirmation"]').val();
            if (password && confirmPassword && password !== confirmPassword) {
                $('input[name="password_confirmation"]').addClass('is-invalid');
                $('input[name="password_confirmation"]').siblings('.invalid-feedback').text('Passwords do not match.')
                .show();
                isValid = false;
            }

            return isValid;
        }

        // Initialize on page load
        $(document).ready(function() {
            updateStepIndicators();
        });
    </script>
@endpush

@push('breadcrumb-plugins')
    <a href="{{ route('admin.fleet.operators.index') }}" class="btn btn-sm btn--primary box--shadow1 text--small">
        <i class="las la-angle-double-left"></i>@lang('Go Back')
    </a>
@endpush
