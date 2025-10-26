@extends($activeTemplate.'layouts.authenticate')
@section('content')
@php
$content = getContent('sign_up.content', true);
@endphp
<!-- Account Section Starts Here -->
<section class="account-section bg_img" style="background: url({{getImage('assets/images/frontend/sign_up/'. @$content->data_values->background_image, "1920x1280") }}) bottom left;">
    <span class="spark"></span>
    <span class="spark2"></span>
    <div class="account-wrapper sign-up">
        <div class="account-form-wrapper">
            <div class="account-header">
                <div class="left-content">
                    <div class="logo mb-4">
                        <a href="{{ route('home') }}"><img src="{{ getImage(imagePath()['logoIcon']['path'].'/logo.png') }}" alt="Logo"></a>
                    </div>
                    <h3 class="title">{{ __(@$content->data_values->heading) }}</h3>
                    <span>{{ __(@$content->data_values->sub_heading) }}</span>
                </div>
            </div>

            <!-- Step 1: Mobile Number Input -->
            <div id="mobile-step" class="registration-step">
                <form class="account-form row" id="mobile-form">
                    @csrf
                    <div class="col-md-12">
                        <div class="form--group">
                            <label for="mobile_number">@lang('Mobile Number') <span>*</span></label>
                            <div class="input-group flex-nowrap">
                                <span class="input-group-text mobile-code border-0 h-40 text-primary">+91</span>
                                <input type="number" name="mobile_number" id="mobile_number" class="form--control ps-2 checkUser"
                                       placeholder="@lang('Enter your 10-digit mobile number')"
                                       pattern="[6-9][0-9]{9}"
                                       maxlength="10" required>
                            </div>
                            <small class="text-muted">@lang('We will send you an OTP for verification')</small>
                            <small class="text-danger mobileExist"></small>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form--group">
                            <button type="submit" class="account-button w-100" id="send-otp-btn">@lang('Send OTP')</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Step 2: OTP Verification -->
            <div id="otp-step" class="registration-step" style="display: none;">
                <form class="account-form row" id="otp-form">
                    @csrf
                    <div class="col-md-12">
                        <div class="text-center mb-3">
                            <p class="text-muted">@lang('OTP sent to') <strong id="display-mobile"></strong></p>
                            <button type="button" class="btn btn-link btn-sm" id="change-mobile">
                                @lang('Change Mobile Number')
                            </button>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form--group">
                            <label for="otp">@lang('Enter 6-digit OTP sent to your mobile number') <span>*</span></label>
                            <input type="text" name="otp" id="otp" class="form--control text-center"
                                   placeholder="@lang('Enter 6-digit OTP')"
                                   maxlength="6"
                                   pattern="[0-9]{6}"
                                   style="font-size: 1.5rem; letter-spacing: 0.5rem;" required>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="text-center mb-3">
                            <p class="text-muted">@lang("Didn't receive OTP?")
                                <button type="button" class="btn btn-link btn-sm" id="resend-otp" disabled>
                                    @lang('Resend OTP') (<span id="countdown">60</span>s)
                                </button>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form--group">
                            <button type="submit" class="account-button w-100" id="verify-otp-btn">@lang('Verify & Login')</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Step 3: Success Message -->
            <div id="success-step" class="registration-step text-center" style="display: none;">
                <div class="success-animation mb-4">
                    <i class="las la-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h4 class="text-success">@lang('Registration Successful!')</h4>
                <p class="text-muted">@lang('You have been logged in successfully.')</p>
                <div class="form--group">
                    <a href="{{ route('user.home') }}" class="account-button w-100">
                        @lang('Go to Dashboard')
                    </a>
                </div>
            </div>

            <!-- Business Registration Options -->
            <div class="business-registration-section mt-4">
                <div class="text-center mb-3">
                    <span class="or-divider">@lang('OR')</span>
                </div>

                <h4 class="text-center mb-4">@lang('Business Registration')</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="{{ route('user.operator.register') }}" class="business-option-link">
                            <div class="business-card">
                                <div class="business-icon">
                                    <i class="las la-bus"></i>
                                </div>
                                <h5>@lang('Register as Operator')</h5>
                                <p>@lang('Operate your own bus services and manage routes')</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="{{ route('agent.register') }}" class="business-option-link">
                            <div class="business-card">
                                <div class="business-icon">
                                    <i class="las la-user-tie"></i>
                                </div>
                                <h5>@lang('Register as Agent')</h5>
                                <p>@lang('Sell tickets and earn commissions')</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Login Link -->
            <div class="col-md-12 mt-4">
                <div class="account-page-link">
                    <p>@lang('Already have an account?') <a href="{{ route('user.login') }}">@lang('Sign In')</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Account Section Ends Here -->
@endsection

@push('style')
<style>
    .registration-step {
        transition: all 0.3s ease;
    }

    .or-divider {
        position: relative;
        background: white;
        padding: 0 15px;
        color: #666;
        font-size: 14px;
    }

    .or-divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: -100px;
        right: calc(100% + 15px);
        height: 1px;
        background: #ddd;
    }

    .or-divider::after {
        content: '';
        position: absolute;
        top: 50%;
        right: -100px;
        left: calc(100% + 15px);
        height: 1px;
        background: #ddd;
    }

    .business-card {
        text-align: center;
        padding: 2rem 1rem;
        border: 2px solid #f0f0f0;
        border-radius: 8px;
        transition: all 0.3s ease;
        background: white;
        height: 100%;
    }

    .business-option-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .business-option-link:hover .business-card {
        border-color: var(--base-color);
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .business-icon {
        margin-bottom: 1rem;
    }

    .business-icon i {
        font-size: 3rem;
        color: var(--base-color);
        transition: all 0.3s ease;
    }

    .business-card h5 {
        margin-bottom: 1rem;
        color: #333;
    }

    .business-card p {
        color: #666;
        font-size: 14px;
        margin: 0;
    }

    .success-animation {
        animation: successPulse 1.5s ease-in-out;
    }

    @keyframes successPulse {
        0% {
            transform: scale(0.5);
            opacity: 0;
        }
        50% {
            transform: scale(1.1);
            opacity: 1;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .input-group-text {
        background: var(--base-color);
        color: white;
        border-color: var(--base-color);
    }

    #otp {
        font-family: 'Courier New', monospace;
    }

    @media (max-width: 768px) {
        .business-card {
            padding: 1.5rem 1rem;
        }
    }
</style>
@endpush

@push('script')
<script>
"use strict";
$(document).ready(function() {
    let currentMobile = '';
    let countdownInterval;

    // Step management
    function showStep(stepId) {
        $('.registration-step').hide();
        $('#' + stepId).show();
    }

    // Mobile form submission
    $('#mobile-form').on('submit', function(e) {
        e.preventDefault();

        const mobile = $('#mobile_number').val().trim();

        // Validate mobile number
        if (!mobile.match(/^[6-9][0-9]{9}$/)) {
            notify('error', '@lang("Please enter a valid 10-digit mobile number")');
            $('#mobile_number').focus();
            return;
        }

        currentMobile = mobile;
        sendOTP(mobile);
    });

    // OTP form submission
    $('#otp-form').on('submit', function(e) {
        e.preventDefault();

        const otp = $('#otp').val().trim();

        if (!otp.match(/^[0-9]{6}$/)) {
            notify('error', '@lang("Please enter a valid 6-digit OTP")');
            $('#otp').focus().select();
            return;
        }

        if (!currentMobile) {
            notify('error', '@lang("Session expired. Please restart the process.")');
            showStep('mobile-step');
            $('#mobile_number').focus();
            return;
        }

        verifyOTP(currentMobile, otp);
    });

    // Change mobile number
    $('#change-mobile').on('click', function() {
        showStep('mobile-step');
        clearInterval(countdownInterval);
        $('#mobile_number').focus();
        currentMobile = '';
    });

    // Resend OTP
    $('#resend-otp').on('click', function() {
        if (!$(this).prop('disabled')) {
            sendOTP(currentMobile);
        }
    });

    // Auto-submit OTP when 6 digits are entered
    $('#otp').on('input', function() {
        const otp = $(this).val();
        if (otp.length === 6 && otp.match(/^[0-9]{6}$/)) {
            setTimeout(() => {
                $('#otp-form').submit();
            }, 500);
        }
    });

    // Functions
    function sendOTP(mobile) {
        if (!mobile || !mobile.match(/^[6-9][0-9]{9}$/)) {
            notify('error', '@lang("Invalid mobile number")');
            return;
        }

        $('#send-otp-btn').prop('disabled', true).html('@lang("Sending...")');

        $.ajax({
            url: '{{ route("send.otp") }}',
            type: 'POST',
            data: {
                mobile_number: mobile,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.status === 200 || response.success === true) {
                    notify('success', response.message || '@lang("OTP sent successfully")');
                    $('#display-mobile').text('+91 ' + mobile);
                    showStep('otp-step');
                    setTimeout(function() {
                        $('#otp').focus();
                    }, 200);
                    startCountdown();
                } else {
                    notify('error', response.message || '@lang("Failed to send OTP")');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (xhr.status === 422) {
                    const errors = response?.errors;
                    if (errors) {
                        Object.values(errors).forEach(errorArray => {
                            errorArray.forEach(error => notify('error', error));
                        });
                    } else {
                        notify('error', response?.message || '@lang("Validation failed")');
                    }
                } else {
                    notify('error', response?.message || '@lang("Failed to send OTP. Please try again.")');
                }
            },
            complete: function() {
                $('#send-otp-btn').prop('disabled', false).html('@lang("Send OTP")');
            }
        });
    }

    function verifyOTP(mobile, otp) {
        if (!mobile || !otp || !otp.match(/^[0-9]{6}$/)) {
            notify('error', '@lang("Invalid mobile number or OTP")');
            return;
        }

        $('#verify-otp-btn').prop('disabled', true).html('@lang("Verifying...")');

        $.ajax({
            url: '{{ route("verify.otp") }}',
            type: 'POST',
            data: {
                mobile_number: mobile,
                otp: otp,
                user_name: 'User',
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.status === 200 || response.success === true) {
                    notify('success', response.message || '@lang("Login successful")');
                    showStep('success-step');

                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = '{{ route("user.home") }}';
                    }, 2000);
                } else {
                    notify('error', response.message || '@lang("Invalid OTP")');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (xhr.status === 422) {
                    const errors = response?.errors;
                    if (errors) {
                        Object.values(errors).forEach(errorArray => {
                            errorArray.forEach(error => notify('error', error));
                        });
                    } else {
                        notify('error', response?.message || '@lang("Validation failed")');
                    }
                } else {
                    notify('error', response?.message || '@lang("Invalid OTP. Please try again.")');
                }
                $('#otp').focus().select();
            },
            complete: function() {
                $('#verify-otp-btn').prop('disabled', false).html('@lang("Verify & Login")');
            }
        });
    }

    function startCountdown() {
        let seconds = 60;
        $('#resend-otp').prop('disabled', true);

        countdownInterval = setInterval(() => {
            seconds--;
            $('#countdown').text(seconds);

            if (seconds <= 0) {
                clearInterval(countdownInterval);
                $('#resend-otp').prop('disabled', false).html('@lang("Resend OTP")');
                $('#countdown').text('60');
            }
        }, 1000);
    }

    // Initialize the form
    showStep('mobile-step');
    $('#mobile_number').focus();

    // Format mobile number input (remove non-digits)
    $('#mobile_number').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Format OTP input (remove non-digits)
    $('#otp').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
</script>
@endpush
