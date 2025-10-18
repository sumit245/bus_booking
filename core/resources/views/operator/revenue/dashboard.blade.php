@extends('operator.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">@lang('Revenue Dashboard')</h4>
                    </div>
                    <div class="card-body">
                        <!-- Revenue Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">
                                                    ₹{{ number_format($revenueSummary['summary']['total_revenue'], 2) }}
                                                </h4>
                                                <p class="mb-0">@lang('Total Revenue')</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="las la-money-bill-wave fs-1"></i>
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
                                                    ₹{{ number_format($revenueSummary['fees']['net_payable'], 2) }}</h4>
                                                <p class="mb-0">@lang('Net Payable')</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="las la-wallet fs-1"></i>
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
                                                    ₹{{ number_format($revenueSummary['fees']['platform_commission'], 2) }}
                                                </h4>
                                                <p class="mb-0">@lang('Platform Commission')</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="las la-percentage fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">
                                                    ₹{{ number_format($revenueSummary['pending_amount'], 2) }}</h4>
                                                <p class="mb-0">@lang('Pending Amount')</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="las la-clock fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Revenue Breakdown -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Revenue Breakdown')</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('User Bookings'):</span>
                                            <strong>₹{{ number_format($revenueSummary['summary']['user_bookings_revenue'], 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Operator Bookings'):</span>
                                            <strong>₹{{ number_format($revenueSummary['summary']['operator_bookings_revenue'], 2) }}</strong>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Total Tickets'):</span>
                                            <strong>{{ $revenueSummary['summary']['total_tickets'] }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>@lang('Agent Commission'):</span>
                                            <strong>₹{{ number_format($revenueSummary['summary']['agent_commission_total'], 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Fee Breakdown')</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Platform Commission'):</span>
                                            <strong>₹{{ number_format($revenueSummary['fees']['platform_commission'], 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Payment Gateway Fees'):</span>
                                            <strong>₹{{ number_format($revenueSummary['fees']['payment_gateway_fees'], 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('TDS Amount'):</span>
                                            <strong>₹{{ number_format($revenueSummary['fees']['tds_amount'], 2) }}</strong>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span><strong>@lang('Net Payable'):</strong></span>
                                            <strong>₹{{ number_format($revenueSummary['fees']['net_payable'], 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Revenue Chart -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Revenue Trend (Last 30 Days)')</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="revenueChart" height="100"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Reports and Payouts -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Recent Reports')</h5>
                                    </div>
                                    <div class="card-body">
                                        @if ($recentReports->count() > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>@lang('Date')</th>
                                                            <th>@lang('Revenue')</th>
                                                            <th>@lang('Net Payable')</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($recentReports->take(5) as $report)
                                                            <tr>
                                                                <td>{{ $report->report_date->format('M d, Y') }}</td>
                                                                <td>₹{{ number_format($report->total_revenue, 2) }}</td>
                                                                <td>₹{{ number_format($report->net_payable, 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-muted">@lang('No reports available')</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Recent Payouts')</h5>
                                    </div>
                                    <div class="card-body">
                                        @if ($recentPayouts->count() > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>@lang('Period')</th>
                                                            <th>@lang('Amount')</th>
                                                            <th>@lang('Status')</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($recentPayouts->take(5) as $payout)
                                                            <tr>
                                                                <td>{{ $payout->payout_period }}</td>
                                                                <td>₹{{ number_format($payout->amount_paid, 2) }}</td>
                                                                <td>
                                                                    <span class="badge badge-{{ $payout->status_badge }}">
                                                                        {{ ucfirst($payout->payment_status) }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-muted">@lang('No payouts available')</p>
                                        @endif
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
            // Load chart data
            $.get('{{ route('operator.revenue.chart-data') }}', function(data) {
                const ctx = document.getElementById('revenueChart').getContext('2d');
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
    </script>
@endpush
