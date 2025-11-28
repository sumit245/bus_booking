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

    <title>@lang('Agent Login') - {{ $general->sitename ?? 'Bus Booking' }}</title>

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
        /* Agent Login Specific Styles */
        .agent-login-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .agent-login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }

        .agent-login-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .agent-login-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .agent-login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }

        .agent-login-body {
            padding: 2rem;
        }

        .form-group label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .password-toggle-wrapper {
            position: relative;
        }

        .password-toggle-wrapper .form-control {
            padding-right: 3rem;
        }

        .password-toggle-btn {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            border: none;
            background: transparent;
            padding: 0 1rem;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            transition: color 0.3s ease;
        }

        .password-toggle-btn:hover {
            color: #007bff;
        }

        .password-toggle-btn:focus {
            outline: none;
        }

        .password-toggle-btn i {
            font-size: 1.25rem;
        }

        .btn-login {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }

        .agent-benefits {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }

        .agent-benefits h6 {
            color: #007bff;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .benefit-item i {
            color: #007bff;
            margin-right: 0.75rem;
            width: 20px;
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .register-link a {
            color: #007bff;
            font-weight: 500;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 576px) {
            .agent-login-wrapper {
                padding: 10px;
            }

            .agent-login-header,
            .agent-login-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="agent-login-wrapper">
        <div class="agent-login-card">
            <div class="agent-login-header">
                <h3 class="text-light"><i class="las la-user-tie"></i> @lang('Agent Login')</h3>
                <p class="text-light">@lang('Access your agent panel')</p>
            </div>

            <div class="agent-login-body">
                <form method="POST" action="{{ route('agent.login.submit') }}">
                    @csrf

                    <div class="form-group">
                        <label for="email">@lang('Email Address')</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                            name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                            placeholder="@lang('Enter your email')">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">@lang('Password')</label>
                        <div class="password-toggle-wrapper">
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="password" name="password" required autocomplete="current-password"
                                placeholder="@lang('Enter your password')">
                            <button class="password-toggle-btn" type="button" id="togglePassword" tabindex="-1">
                                <i class="las la-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">
                                @lang('Remember me')
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login text-light">
                        <i class="las la-sign-in-alt"></i>
                        @lang('Login to Agent Panel')
                    </button>
                </form>

                <div class="agent-benefits">
                    <h6><i class="las la-star"></i> @lang('Why Become an Agent?')</h6>
                    <div class="benefit-item">
                        <i class="las la-percentage"></i>
                        <span>@lang('Earn Commission on every booking')</span>
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

                <div class="register-link">
                    <p class="mb-0">
                        @lang("Don't have an agent account?")
                        <a href="{{ route('agent.register') }}">@lang('Register here')</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/global/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/bootstrap.min.js') }}"></script>

    <script>
        // Vanilla JavaScript - More reliable
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            const emailInput = document.getElementById('email');
            const loginForm = document.querySelector('form');
            const submitButton = document.querySelector('button[type="submit"]');

            // Toggle password visibility
            if (togglePassword) {
                togglePassword.addEventListener('click', function(e) {
                    e.preventDefault();

                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        toggleIcon.classList.remove('la-eye');
                        toggleIcon.classList.add('la-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        toggleIcon.classList.remove('la-eye-slash');
                        toggleIcon.classList.add('la-eye');
                    }
                });
            }

            // Form validation
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const email = emailInput.value.trim();
                    const password = passwordInput.value;

                    if (!email || !password) {
                        e.preventDefault();
                        alert('@lang('Please fill in all required fields')');
                        return false;
                    }

                    // Show loading state
                    submitButton.innerHTML = '<i class="las la-spinner la-spin"></i> @lang('Logging in...')';
                    submitButton.disabled = true;
                });
            }

            // Auto-focus email field
            if (emailInput) {
                emailInput.focus();
            }
        });
    </script>
</body>

</html>
