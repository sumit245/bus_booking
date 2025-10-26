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
                    <h3 class="title">@lang('Operator Registration')</h3>
                    <span>@lang('Join as a bus operator and manage your fleet')</span>
                </div>
            </div>

            <!-- Coming Soon Message -->
            <div class="coming-soon-container text-center">
                <div class="coming-soon-icon mb-4">
                    <i class="las la-construction text-warning" style="font-size: 4rem;"></i>
                </div>

                <h4 class="text-primary mb-3">@lang('Operator Registration')</h4>
                <h5 class="text-secondary mb-4">@lang('Coming Soon!')</h5>

                <div class="alert alert-info mb-4">
                    <i class="las la-info-circle"></i>
                    @lang('We are currently developing the operator registration system. This will allow bus operators to register and manage their fleet, routes, and bookings.')
                </div>

                <div class="features-list text-left mb-4">
                    <h6 class="text-primary mb-3">@lang('What you\'ll be able to do:')</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="las la-check-circle text-success me-2"></i>
                            @lang('Register and manage your bus fleet')
                        </li>
                        <li class="mb-2">
                            <i class="las la-check-circle text-success me-2"></i>
                            @lang('Create and manage routes')
                        </li>
                        <li class="mb-2">
                            <i class="las la-check-circle text-success me-2"></i>
                            @lang('Set up schedules and pricing')
                        </li>
                        <li class="mb-2">
                            <i class="las la-check-circle text-success me-2"></i>
                            @lang('Track bookings and revenue')
                        </li>
                        <li class="mb-2">
                            <i class="las la-check-circle text-success me-2"></i>
                            @lang('Manage staff and crew assignments')
                        </li>
                        <li class="mb-2">
                            <i class="las la-check-circle text-success me-2"></i>
                            @lang('Access detailed analytics and reports')
                        </li>
                    </ul>
                </div>

                <div class="contact-info bg-light p-4 rounded mb-4">
                    <h6 class="text-primary mb-3">@lang('Interested in becoming an operator?')</h6>
                    <p class="text-muted mb-3">@lang('Contact our team to get early access and learn more about our operator program.')</p>

                    <div class="contact-details">
                        <p class="mb-2">
                            <i class="las la-envelope text-primary me-2"></i>
                            <a href="mailto:operators@ghumantoo.com" class="text-decoration-none">operators@ghumantoo.com</a>
                        </p>
                        <p class="mb-0">
                            <i class="las la-phone text-primary me-2"></i>
                            <a href="tel:+911234567890" class="text-decoration-none">+91 12345 67890</a>
                        </p>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="{{ route('home') }}" class="account-button me-3">
                        <i class="las la-home"></i> @lang('Back to Home')
                    </a>
                    <a href="{{ route('user.register') }}" class="btn btn-outline-primary">
                        <i class="las la-user"></i> @lang('Register as Customer')
                    </a>
                </div>
            </div>

            <!-- Login Link -->
            <div class="col-md-12 mt-4">
                <div class="account-page-link text-center">
                    <p>@lang('Already have an operator account?') <a href="{{ route('operator.login') }}">@lang('Login Here')</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Account Section Ends Here -->
@endsection

@push('style')
<style>
    .coming-soon-container {
        padding: 2rem 1rem;
    }

    .coming-soon-icon {
        animation: bounce 2s infinite;
    }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        60% {
            transform: translateY(-5px);
        }
    }

    .features-list ul li {
        padding: 0.25rem 0;
        font-size: 0.95rem;
    }

    .contact-info {
        border: 1px solid #e3e6ea;
    }

    .action-buttons .account-button {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        text-decoration: none;
    }

    .action-buttons .btn {
        padding: 0.75rem 1.5rem;
    }

    @media (max-width: 768px) {
        .action-buttons .account-button,
        .action-buttons .btn {
            display: block;
            width: 100%;
            margin-bottom: 0.5rem;
        }

        .action-buttons .account-button {
            margin-right: 0 !important;
        }
    }
</style>
@endpush
