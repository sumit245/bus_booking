@extends('agent.layouts.app')

@section('title', 'Dashboard')

@section('panel')
    <div class="container-fluid">
        <!-- Welcome Section -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">@lang('Welcome back,') {{ $agent->name }}!</h5>
                        <p class="text-muted mb-0">
                            <i class="las la-calendar"></i>
                            {{ now()->format('l, F j, Y') }}
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
                <div class="row">
                    <div class="col-6 col-md-3 mb-2">
                        <a href="{{ route('agent.search') }}" class="btn btn-outline-primary btn-block">
                            <i class="las la-search d-block mb-1"></i>
                            <small>@lang('Search Buses')</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <a href="{{ route('agent.bookings') }}" class="btn btn-outline-success btn-block">
                            <i class="las la-ticket-alt d-block mb-1"></i>
                            <small>@lang('My Bookings')</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <a href="{{ route('agent.earnings') }}" class="btn btn-outline-info btn-block">
                            <i class="las la-chart-line d-block mb-1"></i>
                            <small>@lang('Earnings')</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <a href="{{ route('agent.profile') }}" class="btn btn-outline-secondary btn-block">
                            <i class="las la-user d-block mb-1"></i>
                            <small>@lang('Profile')</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-3">
            <div class="col-6 col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary">{{ number_format($stats['total_bookings']) }}</h3>
                        <p class="text-muted mb-0">@lang('Total Bookings')</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success">{{ number_format($stats['today_bookings']) }}</h3>
                        <p class="text-muted mb-0">@lang("Today's Bookings")</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-info">₹{{ number_format($stats['total_earnings'], 0) }}</h3>
                        <p class="text-muted mb-0">@lang('Total Earnings')</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-warning">₹{{ number_format($stats['monthly_earnings'], 0) }}</h3>
                        <p class="text-muted mb-0">@lang('This Month')</p>
                    </div>
                </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if jQuery is available
            if (typeof jQuery === 'undefined') {
                console.error('jQuery not loaded on dashboard');
                return;
            }

            jQuery(document).ready(function($) {
                // Auto-refresh stats every 30 seconds
                setInterval(function() {
                    $.get('{{ route('agent.dashboard') }}/data')
                        .done(function(data) {
                            if (data.success) {
                                updateStats(data.data);
                            }
                        })
                        .fail(function() {
                            console.log('Failed to refresh stats');
                        });
                }, 30000);

                // Update stats display
                function updateStats(stats) {
                    $('.stat-card').eq(0).find('.stat-number').text(stats.total_bookings);
                    $('.stat-card').eq(1).find('.stat-number').text(stats.today_bookings);
                    $('.stat-card').eq(2).find('.stat-number').text('₹' + stats.total_earnings);
                    $('.stat-card').eq(3).find('.stat-number').text('₹' + stats.monthly_earnings);
                }

                // Show offline indicator if needed
                if (!navigator.onLine) {
                    iziToast.warning({
                        title: 'Offline Mode',
                        message: 'You are currently offline. Some features may be limited.'
                    });
                }

                // Check for pending verification
                @if ($agent->status === 'pending')
                    iziToast.info({
                        title: 'Account Verification',
                        message: 'Your account is pending admin verification. Some features may be limited.',
                        timeout: 10000
                    });
                @endif
            });
        });
    </script>
@endpush
