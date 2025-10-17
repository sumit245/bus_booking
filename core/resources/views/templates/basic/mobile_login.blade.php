@extends($activeTemplate . $layout)

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="mb-0 text-light">@lang('Login with your mobile number')</h4>
                        <p class="text-light">@lang('Quick & secure access via WhatsApp OTP')</p>
                    </div>
                    <div class="card-body">
                        <form id="mobileLoginForm">
                            @csrf
                            <div class="form-group mb-4">
                                <label for="mobile" class="form-label">@lang('Mobile Number')</label>
                                <div class="input-group">
                                    <span class="input-group-text country-code">+91</span>
                                    <input type="tel" class="form-control mobile-input" id="mobile" name="mobile"
                                        placeholder="Enter your mobile number" pattern="[6-9][0-9]{9}" maxlength="10"
                                        required>
                                </div>
                                <small class="text-muted">@lang('Enter your 10-digit mobile number')</small>
                            </div>

                            <div class="form-group mb-3" id="otpSection" style="display: none;">
                                <label for="otp" class="form-label">@lang('OTP Code')</label>
                                <input type="text" class="form-control" id="otp" name="otp"
                                    placeholder="Enter 6-digit OTP" pattern="[0-9]{6}" maxlength="6">
                                <small class="text-muted">@lang('Enter the 6-digit OTP sent to your WhatsApp')</small>

                                <div class="resend-section mt-2 text-center">
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" id="resendOtpBtn"
                                        disabled>
                                        <span id="resendText" class="text-primary fw-bold">Resend OTP</span>
                                        <span id="countdownText" style="display: none;">
                                            <span class="text-muted">Resend in <span id="countdown"
                                                    class="fw-bold text-primary">60</span>s</span>
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid gap-3">
                                <button type="button" class="btn btn-primary whatsapp-btn" id="sendOtpBtn">
                                    <i class="fab fa-whatsapp"></i> @lang('Send OTP to WhatsApp')
                                </button>
                                <button type="button" class="btn btn-success" id="verifyOtpBtn" style="display: none;">
                                    <i class="las la-check"></i> @lang('Verify OTP & Login')
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted mb-3">@lang('Don\'t have an account?') <br>@lang('We\'ll create one for you automatically!')</p>

                            <div class="login-options">
                                <a href="{{ route('user.login') }}" class="btn btn-outline-secondary btn-sm me-2">
                                    <i class="fas fa-envelope"></i> @lang('Email Login')
                                </a>
                                <a href="{{ route('operator.login') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-bus"></i> @lang('Vendor Login')
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .card {
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-radius: 16px;
            margin: 2rem 0;
        }

        .card-header {
            background: linear-gradient(135deg, #D63942 0%, #B02A35 100%);
            color: white;
            border-radius: 16px 16px 0 0 !important;
            padding: 2.5rem 2rem 2rem;
            margin: 0;
        }

        .card-header h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card-header p {
            opacity: 0.9;
            font-size: 0.95rem;
            margin: 0;
        }

        .card-body {
            padding: 2.5rem 2rem;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 0.875rem 1rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: #D63942;
            box-shadow: 0 0 0 0.2rem rgba(214, 57, 66, 0.25);
        }

        .mobile-input {
            height: 50px;
        }

        .country-code {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            color: #6c757d;
            font-weight: 600;
            height: 50px;
            display: flex;
            align-items: center;
            border-radius: 10px 0 0 10px;
        }

        .btn {
            border-radius: 10px;
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #D63942 0%, #B02A35 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(214, 57, 66, 0.4);
        }

        .whatsapp-btn {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            border: none;
        }

        .whatsapp-btn:hover {
            background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .login-options {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-outline-primary {
            border-color: #D63942;
            color: #D63942;
        }

        .btn-outline-primary:hover {
            background-color: #D63942;
            border-color: #D63942;
        }

        .resend-section {
            margin-top: 0.5rem;
        }

        #resendOtpBtn {
            border: none;
            background: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        #resendOtpBtn:hover:not(:disabled) {
            text-decoration: underline !important;
        }

        #resendOtpBtn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #resendText {
            color: #D63942;
            font-weight: 600;
        }

        #countdownText {
            font-size: 0.9rem;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
    </style>
@endpush

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileInput = document.getElementById('mobile');
            const otpInput = document.getElementById('otp');
            const otpSection = document.getElementById('otpSection');
            const sendOtpBtn = document.getElementById('sendOtpBtn');
            const verifyOtpBtn = document.getElementById('verifyOtpBtn');
            const resendOtpBtn = document.getElementById('resendOtpBtn');
            const resendText = document.getElementById('resendText');
            const countdownText = document.getElementById('countdownText');
            const countdown = document.getElementById('countdown');
            const form = document.getElementById('mobileLoginForm');

            let countdownTimer = null;

            // Mobile number validation
            mobileInput.addEventListener('input', function() {
                const value = this.value.replace(/\D/g, '');
                this.value = value;

                if (value.length === 10) {
                    sendOtpBtn.disabled = false;
                } else {
                    sendOtpBtn.disabled = true;
                }
            });

            // OTP input validation
            otpInput.addEventListener('input', function() {
                const value = this.value.replace(/\D/g, '');
                this.value = value;

                if (value.length === 6) {
                    verifyOtpBtn.disabled = false;
                } else {
                    verifyOtpBtn.disabled = true;
                }
            });

            // Send OTP
            sendOtpBtn.addEventListener('click', function() {
                const mobile = mobileInput.value;

                if (mobile.length !== 10) {
                    showAlert('Please enter a valid 10-digit mobile number', 'danger');
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="las la-spinner la-spin"></i> Sending OTP...';

                fetch('{{ route('mobile.send.otp') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            mobile: mobile
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showAlert(data.message, 'success');
                            otpSection.style.display = 'block';
                            verifyOtpBtn.style.display = 'block';
                            sendOtpBtn.style.display = 'none';
                            otpInput.focus();
                            startCountdown();
                        } else {
                            showAlert(data.message || 'Failed to send OTP', 'danger');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fab fa-whatsapp"></i> Send OTP to WhatsApp';
                        }
                    })
                    .catch(error => {
                        showAlert('An error occurred. Please try again.', 'danger');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Send OTP to WhatsApp';
                    });
            });

            // Verify OTP
            verifyOtpBtn.addEventListener('click', function() {
                const mobile = mobileInput.value;
                const otp = otpInput.value;

                if (otp.length !== 6) {
                    showAlert('Please enter a valid 6-digit OTP', 'danger');
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="las la-spinner la-spin"></i> Verifying...';

                fetch('{{ route('mobile.verify.otp') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            mobile: mobile,
                            otp: otp
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showAlert(data.message, 'success');
                            setTimeout(() => {
                                window.location.href = data.redirect ||
                                    '{{ route('user.dashboard') }}';
                            }, 1000);
                        } else {
                            showAlert(data.message || 'Invalid OTP', 'danger');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="las la-check"></i> Verify OTP & Login';
                        }
                    })
                    .catch(error => {
                        showAlert('An error occurred. Please try again.', 'danger');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="las la-check"></i> Verify OTP & Login';
                    });
            });

            // Resend OTP functionality
            resendOtpBtn.addEventListener('click', function() {
                const mobile = mobileInput.value;
                if (mobile.length !== 10) {
                    showAlert('Please enter a valid 10-digit mobile number', 'danger');
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="las la-spinner la-spin"></i> Resending...';

                fetch('{{ route('mobile.send.otp') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            mobile: mobile
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showAlert('OTP resent successfully', 'success');
                            startCountdown();
                        } else {
                            showAlert(data.message || 'Failed to resend OTP', 'danger');
                            btn.disabled = false;
                            btn.innerHTML = '<span id="resendText">Resend OTP</span>';
                        }
                    })
                    .catch(error => {
                        showAlert('An error occurred. Please try again.', 'danger');
                        btn.disabled = false;
                        btn.innerHTML = '<span id="resendText">Resend OTP</span>';
                    });
            });

            // Countdown function
            function startCountdown() {
                let timeLeft = 60;
                resendOtpBtn.disabled = true;
                resendText.style.display = 'none';
                countdownText.style.display = 'inline';

                countdownTimer = setInterval(() => {
                    timeLeft--;
                    countdown.textContent = timeLeft;

                    if (timeLeft <= 0) {
                        clearInterval(countdownTimer);
                        resendOtpBtn.disabled = false;
                        resendText.style.display = 'inline';
                        countdownText.style.display = 'none';
                    }
                }, 1000);
            }

            function showAlert(message, type) {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
                alertDiv.innerHTML = `
             ${message}
             <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
         `;

                const cardBody = document.querySelector('.card-body');
                cardBody.insertBefore(alertDiv, cardBody.firstChild);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }
        });
    </script>
@endpush
