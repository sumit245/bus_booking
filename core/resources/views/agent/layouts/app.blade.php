@extends('admin.layouts.master')

@section('content')
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="Bus Booking Agent Panel">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Agent Panel">
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-config" content="/assets/images/logoIcon/browserconfig.xml">
    <meta name="msapplication-TileColor" content="#007bff">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="theme-color" content="#007bff">

    <!-- PWA Links -->
    <link rel="apple-touch-icon" href="/assets/images/logoIcon/logo.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/logoIcon/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/logoIcon/favicon.png">
    <link rel="manifest" href="{{ route('agent.manifest') }}">
    <link rel="mask-icon" href="/assets/images/logoIcon/logo.png" color="#007bff">
    <link rel="shortcut icon" href="/assets/images/logoIcon/favicon.png">

    <!-- Agent Panel Custom Styles -->
    <style>
        /* Mobile-First PWA Enhancements */
        .agent-mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid #e9ecef;
            padding: 0.5rem 0;
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            display: none;
        }

        @media (max-width: 767.98px) {
            .agent-mobile-nav {
                display: block;
            }

            .body-wrapper {
                padding-bottom: 70px;
            }
        }

        .agent-mobile-nav .nav-item {
            flex: 1;
            text-align: center;
        }

        .agent-mobile-nav .nav-link {
            color: #6c757d;
            font-size: 0.75rem;
            padding: 0.5rem 0.25rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .agent-mobile-nav .nav-link:hover,
        .agent-mobile-nav .nav-link.active {
            color: #007bff;
        }

        .agent-mobile-nav .nav-link i {
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }

        /* PWA Installation Prompt */
        .pwa-install-prompt {
            background: #007bff;
            color: white;
            padding: 1rem;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        }

        .pwa-install-prompt.show {
            transform: translateY(0);
        }

        /* Offline Indicator */
        .offline-indicator {
            background: #ffc107;
            color: #343a40;
            padding: 0.5rem 1rem;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9998;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        }

        .offline-indicator.show {
            transform: translateY(0);
        }

        /* Logo styling */
        .logo-img {
            height: 40px;
            width: auto;
            max-width: 120px;
        }

        .logo-text {
            margin-left: 10px;
            font-weight: 600;
            color: #333;
        }

        /* Hide large logos/illustrations */
        .body-wrapper img[src*="bus"],
        .body-wrapper img[alt*="bus"],
        .body-wrapper img[src*="illustration"],
        .body-wrapper img[height*="200"],
        .body-wrapper img[width*="300"],
        .main-content img[src*="bus"],
        .main-content img[alt*="bus"],
        .panel img[src*="bus"],
        .panel img[alt*="bus"] {
            display: none !important;
        }

        /* Hide any large graphics in main content */
        .body-wrapper svg[width*="300"],
        .body-wrapper svg[height*="200"],
        .main-content svg[width*="300"],
        .main-content svg[height*="200"] {
            display: none !important;
        }

        /* Mobile-optimized cards */
        @media (max-width: 767.98px) {
            .card {
                margin-bottom: 1rem;
                border-radius: 8px;
            }

            .table-responsive {
                border-radius: 8px;
            }

            .logo-text {
                display: inline !important;
                font-size: 14px;
            }
        }

        @media (min-width: 768px) {
            .logo-text {
                display: inline !important;
            }
        }

        /* Stats grid for mobile */
        .stats-mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        @media (min-width: 768px) {
            .stats-mobile-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>

    <!-- page-wrapper start -->
    <div class="page-wrapper default-version">
        @include('agent.partials.sidenav')
        @include('agent.partials.topnav')

        <div class="body-wrapper">
            <div class="bodywrapper__inner">
                @include('agent.partials.breadcrumb')
                @yield('panel')
            </div><!-- bodywrapper__inner end -->
        </div><!-- body-wrapper end -->
    </div>

    <!-- PWA Install Prompt -->
    <div id="pwa-install-prompt" class="pwa-install-prompt">
        <div class="d-flex justify-content-center align-items-center">
            <div>
                <strong>Install Agent Panel</strong>
                <p class="mb-0">Add to home screen for quick access</p>
            </div>
            <button id="pwa-install-btn" class="btn btn-light ml-3">Install</button>
            <button id="pwa-install-dismiss" class="btn btn-link text-white ml-2">×</button>
        </div>
    </div>

    <!-- Offline Indicator -->
    <div id="offline-indicator" class="offline-indicator">
        <i class="las la-wifi"></i> You're offline. Some features may be limited.
    </div>

    <!-- Mobile Navigation -->
    @auth('agent')
        <nav class="agent-mobile-nav d-flex">
            <div class="nav-item">
                <a href="{{ route('agent.dashboard') }}"
                    class="nav-link {{ request()->routeIs('agent.dashboard') ? 'active' : '' }}">
                    <i class="las la-home"></i>
                    <span>Home</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('agent.search') }}"
                    class="nav-link {{ request()->routeIs('agent.search*') ? 'active' : '' }}">
                    <i class="las la-search"></i>
                    <span>Search</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('agent.bookings') }}"
                    class="nav-link {{ request()->routeIs('agent.bookings*') ? 'active' : '' }}">
                    <i class="las la-ticket-alt"></i>
                    <span>Bookings</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('agent.earnings') }}"
                    class="nav-link {{ request()->routeIs('agent.earnings*') ? 'active' : '' }}">
                    <i class="las la-chart-line"></i>
                    <span>Earnings</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('agent.profile') }}"
                    class="nav-link {{ request()->routeIs('agent.profile*') ? 'active' : '' }}">
                    <i class="las la-user"></i>
                    <span>Profile</span>
                </a>
            </div>
        </nav>
    @endauth

    <!-- PWA Registration Script -->
    <script>
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('{{ route('agent.sw') }}')
                    .then(function(registration) {
                        console.log('SW registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }

        // PWA Install Prompt
        let deferredPrompt;
        const installPrompt = document.getElementById('pwa-install-prompt');
        const installBtn = document.getElementById('pwa-install-btn');
        const dismissBtn = document.getElementById('pwa-install-dismiss');

        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA install prompt triggered');
            e.preventDefault();
            deferredPrompt = e;

            // Show install prompt after a delay
            setTimeout(() => {
                if (installPrompt) {
                    installPrompt.classList.add('show');
                }
            }, 3000);
        });

        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    try {
                        deferredPrompt.prompt();
                        const {
                            outcome
                        } = await deferredPrompt.userChoice;
                        console.log(`User response to the install prompt: ${outcome}`);
                        deferredPrompt = null;
                        if (installPrompt) {
                            installPrompt.classList.remove('show');
                        }
                    } catch (error) {
                        console.error('Install prompt error:', error);
                    }
                }
            });
        }

        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => {
                if (installPrompt) {
                    installPrompt.classList.remove('show');
                }
            });
        }

        // Check if app is already installed
        window.addEventListener('appinstalled', (evt) => {
            console.log('PWA was installed successfully');
            if (installPrompt) {
                installPrompt.classList.remove('show');
            }
        });

        // Offline Detection
        const offlineIndicator = document.getElementById('offline-indicator');

        window.addEventListener('online', () => {
            offlineIndicator.classList.remove('show');
            if (typeof iziToast !== 'undefined') {
                iziToast.success({
                    title: 'Connected',
                    message: 'You are back online'
                });
            }
        });

        window.addEventListener('offline', () => {
            offlineIndicator.classList.add('show');
            if (typeof iziToast !== 'undefined') {
                iziToast.warning({
                    title: 'Offline',
                    message: 'You are currently offline'
                });
            }
        });

        // CSRF Token for AJAX - wait for jQuery to be available
        if (typeof jQuery !== 'undefined') {
            jQuery.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
                }
            });
        }
    </script>

    @stack('script')
@endsection
