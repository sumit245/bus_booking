@extends('agent.layouts.app')

@section('title', 'Dashboard')

@section('panel')
    <div class="container-fluid mt-0">
        <!-- Welcome Section with PWA Status -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">@lang('Welcome back,') {{ $agent->name }}!</h5>
                        <p class="text-muted mb-0">
                            <i class="las la-calendar"></i>
                            {{ now()->format('l, F j, Y') }}
                            <span id="connection-status" class="ms-2">
                                <i class="las la-wifi text-success"></i>
                            </span>
                        </p>
                    </div>
                    <div class="text-right">
                        <span
                            class="badge badge-{{ $agent->status === 'active' ? 'success' : ($agent->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($agent->status) }}
                        </span>
                        @if ($agent->status === 'pending')
                            <small class="d-block text-muted mt-1">@lang('Awaiting verification')</small>
                        @endif
                        <div id="pwa-install-container" class="mt-2 d-none">
                            <button id="pwa-install-btn" class="btn btn-sm btn-outline-primary">
                                <i class="las la-download"></i> @lang('Install App')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="las la-bolt text-warning"></i>
                    @lang('Quick Actions')
                </h6>
            </div>
            <div class="card-body">
                <div class="row quick-actions">
                    <div class="col-6 col-md-3 mb-2">
                        <a href="{{ route('agent.search') }}" class="btn btn-outline-primary btn-block"
                            data-action="search">
                            <i class="las la-search"></i>
                            <small>@lang('Search Buses')</small>
                            <div class="ripple-container"></div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <a href="{{ route('agent.bookings') }}" class="btn btn-outline-success btn-block"
                            data-action="bookings">
                            <i class="las la-ticket-alt"></i>
                            <small>@lang('My Bookings')</small>
                            <div class="ripple-container"></div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <a href="{{ route('agent.earnings') }}" class="btn btn-outline-info btn-block"
                            data-action="earnings">
                            <i class="las la-chart-line"></i>
                            <small>@lang('Earnings')</small>
                            <div class="ripple-container"></div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <a href="{{ route('agent.profile') }}" class="btn btn-outline-secondary btn-block"
                            data-action="profile">
                            <i class="las la-user"></i>
                            <small>@lang('Profile')</small>
                            <div class="ripple-container"></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-3">
            <div class="col-6 col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-2">
                            <i class="las la-ticket-alt text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <h3 class="text-primary" data-stat="total_bookings">{{ number_format($stats['total_bookings']) }}
                        </h3>
                        <p class="text-muted mb-0">@lang('Total Bookings')</p>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-2">
                            <i class="las la-calendar-check text-success" style="font-size: 2rem;"></i>
                        </div>
                        <h3 class="text-success" data-stat="today_bookings">{{ number_format($stats['today_bookings']) }}
                        </h3>
                        <p class="text-muted mb-0">@lang("Today's Bookings")</p>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-success" role="progressbar"
                                style="width: {{ $stats['total_bookings'] > 0 ? ($stats['today_bookings'] / $stats['total_bookings']) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-2">
                            <i class="las la-rupee-sign text-info" style="font-size: 2rem;"></i>
                        </div>
                        <h3 class="text-info" data-stat="total_earnings">₹{{ number_format($stats['total_earnings'], 0) }}
                        </h3>
                        <p class="text-muted mb-0">@lang('Total Earnings')</p>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-2">
                            <i class="las la-chart-line text-warning" style="font-size: 2rem;"></i>
                        </div>
                        <h3 class="text-warning" data-stat="monthly_earnings">
                            ₹{{ number_format($stats['monthly_earnings'], 0) }}</h3>
                        <p class="text-muted mb-0">@lang('This Month')</p>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-warning" role="progressbar"
                                style="width: {{ $stats['total_earnings'] > 0 ? ($stats['monthly_earnings'] / $stats['total_earnings']) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings Chart -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="las la-chart-area text-info"></i>
                    @lang('Monthly Earnings Trend')
                </h6>
            </div>
            <div class="card-body">
                <canvas id="earningsChart" height="250"></canvas>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="las la-history text-primary"></i>
                    @lang('Recent Bookings')
                </h6>
                <a href="{{ route('agent.bookings') }}" class="btn btn-sm btn-outline-primary">
                    @lang('View All')
                </a>
            </div>
            <div class="card-body p-0">
                @if ($recentBookings->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Ticket</th>
                                    <th>Route</th>
                                    <th>Date</th>
                                    <th>Commission</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentBookings as $booking)
                                    <tr>
                                        <td>
                                            <strong>{{ $booking->bookedTicket->ticket_no ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $booking->bookedTicket->seats ?? 'N/A' }}
                                                seats</small>
                                        </td>
                                        <td>
                                            {{ $booking->bookedTicket->trip->startFrom->name ?? 'N/A' }}
                                            <br>
                                            <small class="text-muted">to
                                                {{ $booking->bookedTicket->trip->endTo->name ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            {{ $booking->bookedTicket->date_of_journey ? \Carbon\Carbon::parse($booking->bookedTicket->date_of_journey)->format('M d, Y') : 'N/A' }}
                                            <br>
                                            <small
                                                class="text-muted">{{ $booking->created_at->format('M d, h:i A') }}</small>
                                        </td>
                                        <td>
                                            <strong
                                                class="text-success">₹{{ number_format($booking->total_commission_earned, 2) }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $booking->commission_type ?? 'Fixed' }}</small>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $booking->booking_status === 'confirmed' ? 'success' : ($booking->booking_status === 'pending' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($booking->booking_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="las la-ticket-alt text-muted" style="font-size: 3rem;"></i>
                        <h6 class="text-muted mt-2">No bookings yet</h6>
                        <p class="text-muted mb-3">Start by searching for buses to make your first booking</p>
                        <a href="{{ route('agent.search') }}" class="btn btn-primary">
                            <i class="las la-search"></i>
                            Search Buses
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Commission Information -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="las la-percentage text-success"></i>
                    @lang('Commission Structure')
                </h6>
            </div>
            <div class="card-body">
                @if ($commissionConfig)
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">@lang('Below')
                                ₹{{ number_format($commissionConfig['threshold_amount']) }}
                                (@lang('Fixed'))</h6>
                            @if (isset($commissionConfig['below_threshold']))
                                @foreach ($commissionConfig['below_threshold'] as $rule)
                                    <div class="d-flex justify-content-between">
                                        <span>₹{{ $rule['condition'] }}</span>
                                        <strong>₹{{ number_format($rule['amount'], 0) }}</strong>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">@lang('Above')
                                ₹{{ number_format($commissionConfig['threshold_amount']) }}
                                (@lang('Percentage'))</h6>
                            @if (isset($commissionConfig['above_threshold']))
                                @foreach ($commissionConfig['above_threshold'] as $rule)
                                    <div class="d-flex justify-content-between">
                                        <span>₹{{ $rule['condition'] }}</span>
                                        <strong>{{ $rule['percentage'] }}%</strong>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="las la-info-circle"></i>
                        @lang('Commission structure will be available once configured by admin.')
                    </div>
                @endif
            </div>
        </div>

        <!-- Performance Tips -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="las la-lightbulb text-warning"></i>
                    @lang('Performance Tips')
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-start">
                            <i class="las la-mobile text-primary mr-2 mt-1"></i>
                            <div>
                                <h6 class="mb-1">@lang('Install PWA')</h6>
                                <small class="text-muted">@lang('Add to home screen for faster access')</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-start">
                            <i class="las la-wifi text-success mr-2 mt-1"></i>
                            <div>
                                <h6 class="mb-1">@lang('Works Offline')</h6>
                                <small class="text-muted">@lang('Continue working even with poor internet')</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-start">
                            <i class="las la-chart-line text-info mr-2 mt-1"></i>
                            <div>
                                <h6 class="mb-1">@lang('Track Performance')</h6>
                                <small class="text-muted">@lang('Monitor your earnings and bookings')</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pwaInstallContainer = document.getElementById('pwa-install-container');
            const pwaInstallBtn = document.getElementById('pwa-install-btn');
            const connectionStatus = document.getElementById('connection-status');
            let deferredPrompt;

            // PWA Install Prompt
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                pwaInstallContainer.classList.remove('d-none');
            });

            if (pwaInstallBtn) {
                pwaInstallBtn.addEventListener('click', async () => {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        const {
                            outcome
                        } = await deferredPrompt.userChoice;
                        console.log(`User response to install prompt: ${outcome}`);
                        deferredPrompt = null;
                        pwaInstallContainer.classList.add('d-none');
                    }
                });
            }

            // Network Status
            function updateOnlineStatus() {
                if (navigator.onLine) {
                    connectionStatus.innerHTML = '<i class="las la-wifi text-success"></i>';
                    document.body.classList.remove('offline-mode');
                } else {
                    connectionStatus.innerHTML = '<i class="las la-wifi text-danger"></i>';
                    document.body.classList.add('offline-mode');
                    iziToast.warning({
                        title: 'Offline Mode',
                        message: 'You are currently offline. Some features may be limited.',
                        timeout: 5000
                    });
                }
            }

            window.addEventListener('online', updateOnlineStatus);
            window.addEventListener('offline', updateOnlineStatus);
            updateOnlineStatus();

            // Initialize Monthly Earnings Chart
            const monthlyData = @json($monthlyEarnings);
            if (monthlyData && document.getElementById('earningsChart')) {
                const ctx = document.getElementById('earningsChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: Object.keys(monthlyData),
                        datasets: [{
                            label: 'Daily Earnings',
                            data: Object.values(monthlyData),
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '₹' + context.parsed.y.toLocaleString('en-IN');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₹' + value.toLocaleString('en-IN');
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Auto-refresh stats
            function refreshStats() {
                fetch('{{ route('agent.dashboard') }}/data')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateStats(data.data);
                        }
                    })
                    .catch(error => console.error('Failed to refresh stats:', error));
            }

            function updateStats(stats) {
                document.querySelectorAll('[data-stat]').forEach(element => {
                    const statType = element.dataset.stat;
                    if (stats[statType] !== undefined) {
                        const value = stats[statType];
                        element.textContent = statType.includes('earnings') ?
                            '₹' + parseFloat(value).toLocaleString('en-IN') :
                            value.toLocaleString('en-IN');
                    }
                });
            }

            // Refresh stats every 30 seconds if online
            setInterval(() => {
                if (navigator.onLine) {
                    refreshStats();
                }
            }, 30000);

            // Show verification warning if pending
            @if ($agent->status === 'pending')
                iziToast.info({
                    title: 'Account Verification',
                    message: 'Your account is pending admin verification. Some features may be limited.',
                    timeout: 10000,
                    progressBar: true
                });
            @endif
        });
    </script>
@endpush

@push('style')
    <style>
        .offline-mode {
            filter: grayscale(0.3);
        }

        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border: none;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .quick-actions .btn {
            transition: all 0.3s ease;
            border-radius: 10px;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .quick-actions .btn i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .quick-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }

            .table td,
            .table th {
                padding: 0.5rem;
            }

            .quick-actions .btn {
                min-height: 60px;
                padding: 0.5rem;
            }

            .quick-actions .btn i {
                font-size: 1.2rem;
            }
        }
    </style>
@endpush
