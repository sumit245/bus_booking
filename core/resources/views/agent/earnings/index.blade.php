@extends('agent.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">@lang('Earnings Dashboard')</h4>
                    </div>
                    <div class="card-body">
                        <!-- Date Range Filter -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <form method="GET" action="{{ route('agent.earnings') }}" class="form-inline">
                                    <div class="form-group mr-3">
                                        <label class="mr-2">@lang('Date Range'):</label>
                                        <select name="period" class="form-control" id="periodSelect">
                                            <option value="all"
                                                {{ request('period', 'last30') == 'all' ? 'selected' : '' }}>
                                                @lang('All Time')</option>
                                            <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>
                                                @lang('Today')</option>
                                            <option value="last7" {{ request('period') == 'last7' ? 'selected' : '' }}>
                                                @lang('Last 7 Days')</option>
                                            <option value="last30"
                                                {{ request('period', 'last30') == 'last30' ? 'selected' : '' }}>
                                                @lang('Last 30 Days')</option>
                                            <option value="this_month"
                                                {{ request('period') == 'this_month' ? 'selected' : '' }}>
                                                @lang('This Month')</option>
                                            <option value="last_month"
                                                {{ request('period') == 'last_month' ? 'selected' : '' }}>
                                                @lang('Last Month')</option>
                                            <option value="custom" {{ request('period') == 'custom' ? 'selected' : '' }}>
                                                @lang('Custom Range')</option>
                                        </select>
                                    </div>
                                    <div class="form-group mr-3" id="customDateRange"
                                        style="display: {{ request('period') == 'custom' ? 'block' : 'none' }};">
                                        <input type="date" name="start_date" class="form-control mr-2"
                                            value="{{ request('start_date') }}" placeholder="Start Date">
                                        <input type="date" name="end_date" class="form-control"
                                            value="{{ request('end_date') }}" placeholder="End Date">
                                    </div>
                                    <button type="submit" class="btn btn-primary">@lang('Apply Filter')</button>
                                    <a href="{{ route('agent.earnings') }}"
                                        class="btn btn-secondary ml-2">@lang('Reset')</a>
                                    <a href="{{ route('agent.earnings.export', request()->query()) }}"
                                        class="btn btn-success ml-2">
                                        <i class="las la-file-excel"></i> @lang('Export to Excel')
                                    </a>
                                </form>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">
                                                    {{ $revenueSummary['summary']['total_tickets'] }}
                                                </h4>
                                                <p class="mb-0">@lang('Total Bookings')</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="las la-ticket-alt fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">
                                                    ₹{{ number_format($revenueSummary['summary']['total_commission'], 2) }}
                                                </h4>
                                                <p class="mb-0">@lang('Total Commission')</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="las la-money-bill-wave fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">
                                                    {{ $revenueSummary['summary']['confirmed_tickets'] }}
                                                </h4>
                                                <p class="mb-0">@lang('Confirmed Bookings')</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="las la-check-circle fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">
                                                    {{ $revenueSummary['summary']['cancelled_tickets'] }}
                                                </h4>
                                                <p class="mb-0">@lang('Cancelled Bookings')</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="las la-times-circle fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Breakdown -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Bookings Breakdown')</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Confirmed Bookings'):</span>
                                            <strong>{{ $revenueSummary['summary']['confirmed_tickets'] }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Pending Bookings'):</span>
                                            <strong>{{ $revenueSummary['summary']['pending_tickets'] }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Cancelled Bookings'):</span>
                                            <strong>{{ $revenueSummary['summary']['cancelled_tickets'] }}</strong>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span><strong>@lang('Total Bookings'):</strong></span>
                                            <strong>{{ $revenueSummary['summary']['total_tickets'] }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Commission Breakdown')</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Confirmed Commission'):</span>
                                            <strong>₹{{ number_format($revenueSummary['summary']['confirmed_commission'], 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Pending Commission'):</span>
                                            <strong>₹{{ number_format($revenueSummary['summary']['pending_commission'], 2) }}</strong>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span><strong>@lang('Total Commission'):</strong></span>
                                            <strong
                                                class="text-success">₹{{ number_format($revenueSummary['summary']['total_commission'], 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Commission Trend (Last 30 Days)')</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="earningsChart" height="100"></canvas>
                                    </div>
                                </div>
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
        $(document).ready(function() {
            // Toggle custom date range visibility
            $('#periodSelect').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#customDateRange').show();
                } else {
                    $('#customDateRange').hide();
                }
            });

            // Load chart data
            $.get('{{ route('agent.earnings.chart-data') }}', function(data) {
                const ctx = document.getElementById('earningsChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: data.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₹' + value.toLocaleString();
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ₹' + context.parsed.y
                                            .toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            });
        });

        // Show filter period notification
        @if (request()->has('period') || request()->has('start_date'))
            $(document).ready(function() {
                var period = "{{ request('period', 'last30') }}";
                var message = "";

                if (period === 'all') {
                    message = "@lang('Showing data for'): @lang('All Time')";
                } else if (period === 'today') {
                    message = "@lang('Showing data for'): @lang('Today')";
                } else if (period === 'last7') {
                    message = "@lang('Showing data for'): @lang('Last 7 Days')";
                } else if (period === 'last30') {
                    message = "@lang('Showing data for'): @lang('Last 30 Days')";
                } else if (period === 'this_month') {
                    message = "@lang('Showing data for'): @lang('This Month')";
                } else if (period === 'last_month') {
                    message = "@lang('Showing data for'): @lang('Last Month')";
                } else if (period === 'custom') {
                    message =
                        "@lang('Showing data for'): {{ request('start_date') }} @lang('to') {{ request('end_date') }}";
                }

                if (message && typeof notify === 'function') {
                    notify('info', message);
                }
            });
        @endif
    </script>
@endpush
