<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA Meta Tags -->
    <meta name="application-name" content="Bus Booking Agent Panel">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Agent Panel">
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#007bff">

    <!-- PWA Links -->
    <link rel="apple-touch-icon" href="/assets/images/logoIcon/logo.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/logoIcon/favicon.png">
    <link rel="manifest" href="{{ route('agent.manifest') }}">

    <title>@lang('Agent Registration') - {{ $general->sitename ?? 'Bus Booking' }}</title>

    <!-- Inherit Admin Theme -->
    <link rel="shortcut icon" type="image/png" href="{{ getImage(imagePath()['logoIcon']['path'] . '/favicon.png') }}">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/vendor/grid.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/vendor/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/app.css') }}">

    <style>
        /* Agent Registration Specific Styles */
        .agent-register-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .agent-register-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .agent-register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .agent-register-header {
            background: linear-gradient(135deg, #1a252f 0%, #2c3e50 100%);
            color: #ffffff;
            padding: 2rem;
            text-align: center;
            border-radius: 8px 8px 0 0;
            position: relative;
        }

        .agent-register-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.2);
            border-radius: 8px 8px 0 0;
        }

        .agent-register-header > * {
            position: relative;
            z-index: 1;
        }

        .agent-register-header h3 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            color: #ffffff;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .agent-register-header p {
            margin: 0.5rem 0 0;
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 500;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            opacity: 1;
        }

        .agent-register-body {
            padding: 2rem;
        }

        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: #495057;
            background-color: #fff;
        }

        .form-control::placeholder {
            color: #6c757d;
            opacity: 1;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-register {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .agent-benefits {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            border: 1px solid #e9ecef;
        }

        .agent-benefits h6 {
            margin-bottom: 1rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .benefit-item i {
            color: #28a745;
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }

        .benefit-item span {
            color: #495057;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .commission-preview {
            background: #f8f9fa;
            color: #2c3e50;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            border: 2px solid #3498db;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .commission-preview h6 {
            margin-bottom: 1rem;
            font-weight: 700;
            color: #1a252f;
            font-size: 1.2rem;
        }

        .commission-preview ul {
            margin: 0;
            padding-left: 1.2rem;
        }

        .commission-preview li {
            margin-bottom: 0.5rem;
            color: #2c3e50;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .commission-preview li strong {
            color: #1a252f;
            font-weight: 700;
        }

        .commission-preview p {
            color: #495057;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .commission-preview small {
            color: #495057;
            font-size: 0.85rem;
            font-weight: 400;
        }

        .form-check-label {
            color: #495057;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-left: 0.5rem;
        }

        .form-check-label a {
            color: #007bff;
            font-weight: 600;
            text-decoration: none;
        }

        .form-check-label a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .login-link p {
            color: #495057;
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #007bff;
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
            color: #0056b3;
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .agent-register-wrapper {
                padding: 10px;
            }

            .agent-register-header,
            .agent-register-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="agent-register-wrapper">
        <div class="agent-register-container">
            <div class="agent-register-card">
                <div class="agent-register-header">
                    <h3><i class="las la-user-plus"></i> @lang('Become an Agent')</h3>
                    <p>@lang('Join our agent network and start earning')</p>
                </div>

                <div class="agent-register-body">
                    <form method="POST" action="{{ route('agent.register.submit') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">@lang('Full Name') *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name') }}" required
                                        autocomplete="name" autofocus placeholder="@lang('Enter your full name')">
                                    @error('name')
                                        <div class="invalid-feedback" style="color: #dc3545; font-weight: 500;">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">@lang('Email Address') *</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="email" name="email" value="{{ old('email') }}" required
                                        autocomplete="email" placeholder="@lang('Enter your email')">
                                    @error('email')
                                        <div class="invalid-feedback" style="color: #dc3545; font-weight: 500;">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">@lang('Phone Number') *</label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                        id="phone" name="phone" value="{{ old('phone') }}" required
                                        autocomplete="tel" placeholder="@lang('Enter your phone number')">
                                    @error('phone')
                                        <div class="invalid-feedback" style="color: #dc3545; font-weight: 500;">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pan_number">@lang('PAN Number')</label>
                                    <input type="text" class="form-control @error('pan_number') is-invalid @enderror"
                                        id="pan_number" name="pan_number" value="{{ old('pan_number') }}"
                                        autocomplete="off" placeholder="@lang('Enter PAN number')">
                                    @error('pan_number')
                                        <div class="invalid-feedback" style="color: #dc3545; font-weight: 500;">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">@lang('Address')</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3"
                                placeholder="@lang('Enter your address')">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback" style="color: #dc3545; font-weight: 500;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">@lang('Password') *</label>
                                    <div class="input-group">
                                        <input type="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            id="password" name="password" required autocomplete="new-password"
                                            placeholder="@lang('Create a password')">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button"
                                                id="togglePassword">
                                                <i class="las la-eye" id="toggleIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="password-strength" id="passwordStrength"></div>
                                    @error('password')
                                        <div class="invalid-feedback" style="color: #dc3545; font-weight: 500;">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password_confirmation">@lang('Confirm Password') *</label>
                                    <input type="password" class="form-control" id="password_confirmation"
                                        name="password_confirmation" required autocomplete="new-password"
                                        placeholder="@lang('Confirm your password')">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox"
                                    name="terms" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    @lang('I agree to the') <a href="#" class="text-primary">@lang('Terms and Conditions')</a>
                                    @lang('and') <a href="#" class="text-primary">@lang('Privacy Policy')</a>
                                </label>
                                @error('terms')
                                    <div class="invalid-feedback" style="color: #dc3545; font-weight: 500;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-register">
                            <i class="las la-user-plus"></i>
                            @lang('Create Agent Account')
                        </button>
                    </form>

                    <div class="agent-benefits">
                        <h6><i class="las la-gift"></i> @lang('Agent Benefits')</h6>
                        <div class="benefit-item">
                            <i class="las la-percentage"></i>
                            <span>@lang('Earn commission on every successful booking')</span>
                        </div>
                        <div class="benefit-item">
                            <i class="las la-mobile"></i>
                            <span>@lang('Mobile-first PWA experience')</span>
                        </div>
                        <div class="benefit-item">
                            <i class="las la-clock"></i>
                            <span>@lang('24/7 access to booking system')</span>
                        </div>
                        <div class="benefit-item">
                            <i class="las la-chart-line"></i>
                            <span>@lang('Track your earnings and performance')</span>
                        </div>
                    </div>

                    <div class="commission-preview">
                        <h6><i class="las la-info-circle"></i> @lang('Commission Structure')</h6>
                        <p class="mb-2">@lang('How Commission Works:')</p>
                        <ul>
                            <li><strong>@lang('Fixed Commission:')</strong> @lang('Earn fixed amounts for smaller bookings')</li>
                            <li><strong>@lang('Percentage Commission:')</strong> @lang('Earn percentage for larger bookings')</li>
                            <li><strong>@lang('Real-time Calculation:')</strong> @lang('See commission before booking')</li>
                            <li><strong>@lang('Transparent Pricing:')</strong> @lang('All fees shown upfront')</li>
                        </ul>
                        <p class="mb-0 mt-2">
                            <small>@lang('Commission rates are configured by admin and may vary based on booking amounts and other factors.')</small>
                        </p>
                    </div>

                    <div class="login-link">
                        <p class="mb-0">
                            @lang('Already have an agent account?')
                            <a href="{{ route('agent.login') }}">@lang('Login here')</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/global/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/bootstrap.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('#togglePassword').click(function() {
                const password = $('#password');
                const toggleIcon = $('#toggleIcon');

                if (password.attr('type') === 'password') {
                    password.attr('type', 'text');
                    toggleIcon.removeClass('la-eye').addClass('la-eye-slash');
                } else {
                    password.attr('type', 'password');
                    toggleIcon.removeClass('la-eye-slash').addClass('la-eye');
                }
            });

            // Password strength indicator
            $('#password').on('input', function() {
                const password = $(this).val();
                const strength = getPasswordStrength(password);
                updatePasswordStrength(strength);
            });

            // Phone number formatting
            $('#phone').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                $(this).val(value);
            });

            // PAN number formatting
            $('#pan_number').on('input', function() {
                let value = $(this).val().toUpperCase();
                $(this).val(value);
            });

            // Form validation
            $('form').on('submit', function(e) {
                const password = $('#password').val();
                const confirmPassword = $('#password_confirmation').val();

                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('@lang('Passwords do not match')');
                    return false;
                }

                if (!document.getElementById('terms').checked) {
                    e.preventDefault();
                    alert('@lang('Please accept the terms and conditions')');
                    return false;
                }

                // Show loading state
                $('button[type="submit"]').html('<i class="las la-spinner la-spin"></i> @lang('Creating Account...')')
                    .prop('disabled', true);
            });

            // Auto-focus name field
            $('#name').focus();
        });

        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }

        function updatePasswordStrength(strength) {
            const strengthText = ['@lang('Very Weak')', '@lang('Weak')', '@lang('Fair')', '@lang('Good')',
                '@lang('Strong')'
            ][strength] || '@lang('Very Weak')';
            const strengthColor = ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#28a745'][strength] || '#dc3545';

            $('#passwordStrength').html(`<span style="color: ${strengthColor};">${strengthText}</span>`);
        }
    </script>
</body>

</html>
