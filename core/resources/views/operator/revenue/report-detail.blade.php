@extends('operator.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">@lang('Revenue Report Details')</h4>
                            <div>
                                <a href="{{ route('operator.revenue.reports') }}" class="btn btn-secondary">
                                    <i class="las la-arrow-left"></i> @lang('Back to Reports')
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Report Summary -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h4 class="mb-0">{{ $report->total_tickets }}</h4>
                                        <p class="mb-0">@lang('Total Tickets')</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h4 class="mb-0">₹{{ number_format($report->total_revenue, 2) }}</h4>
                                        <p class="mb-0">@lang('Total Revenue')</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h4 class="mb-0">₹{{ number_format($report->platform_commission, 2) }}</h4>
                                        <p class="mb-0">@lang('Platform Commission')</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h4 class="mb-0">₹{{ number_format($report->net_payable, 2) }}</h4>
                                        <p class="mb-0">@lang('Net Payable')</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Details -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Revenue Breakdown')</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('User Bookings Revenue'):</span>
                                            <strong>₹{{ number_format($report->user_bookings_revenue, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Operator Bookings Revenue'):</span>
                                            <strong>₹{{ number_format($report->operator_bookings_revenue, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Unit Price Total'):</span>
                                            <strong>₹{{ number_format($report->unit_price_total, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Sub Total'):</span>
                                            <strong>₹{{ number_format($report->sub_total_total, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>@lang('Agent Commission'):</span>
                                            <strong>₹{{ number_format($report->agent_commission_total, 2) }}</strong>
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
                                            <strong>₹{{ number_format($report->platform_commission, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Payment Gateway Fees'):</span>
                                            <strong>₹{{ number_format($report->payment_gateway_fees, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('TDS Amount'):</span>
                                            <strong>₹{{ number_format($report->tds_amount, 2) }}</strong>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span><strong>@lang('Net Payable'):</strong></span>
                                            <strong>₹{{ number_format($report->net_payable, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Breakdown -->
                        @if ($report->detailed_breakdown)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">@lang('Detailed Breakdown')</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>@lang('User Bookings')</h6>
                                                    @if (isset($report->detailed_breakdown['user_bookings']))
                                                        @php
                                                            // Handle both old and new structure
                                                            $userBookings =
                                                                $report->detailed_breakdown['user_bookings'];
                                                            if (isset($userBookings['total'])) {
                                                                // New structure with 'total' wrapper
                                                                $count = $userBookings['total']['count'];
                                                                $revenue = $userBookings['total']['revenue'];
                                                                $avgTicketValue =
                                                                    $userBookings['total']['avg_ticket_value'] ?? 0;
                                                            } else {
                                                                // Old structure without 'total' wrapper
                                                                $count = $userBookings['count'] ?? 0;
                                                                $revenue = $userBookings['revenue'] ?? 0;
                                                                $avgTicketValue = $count > 0 ? $revenue / $count : 0;
                                                            }
                                                        @endphp
                                                        <div class="mb-3">
                                                            <div class="d-flex justify-content-between">
                                                                <span>@lang('Total Count'):</span>
                                                                <strong>{{ $count }}</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between">
                                                                <span>@lang('Total Revenue'):</span>
                                                                <strong>₹{{ number_format($revenue, 2) }}</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between">
                                                                <span>@lang('Average Ticket Value'):</span>
                                                                <strong>₹{{ number_format($avgTicketValue, 2) }}</strong>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>@lang('Operator Bookings')</h6>
                                                    @if (isset($report->detailed_breakdown['operator_bookings']))
                                                        @php
                                                            // Handle both old and new structure
                                                            $operatorBookings =
                                                                $report->detailed_breakdown['operator_bookings'];
                                                            if (isset($operatorBookings['total'])) {
                                                                // New structure with 'total' wrapper
                                                                $count = $operatorBookings['total']['count'];
                                                                $revenue = $operatorBookings['total']['revenue'];
                                                                $avgBookingValue =
                                                                    $operatorBookings['total']['avg_booking_value'] ??
                                                                    0;
                                                            } else {
                                                                // Old structure without 'total' wrapper
                                                                $count = $operatorBookings['count'] ?? 0;
                                                                $revenue = $operatorBookings['revenue'] ?? 0;
                                                                $avgBookingValue = $count > 0 ? $revenue / $count : 0;
                                                            }
                                                        @endphp
                                                        <div class="mb-3">
                                                            <div class="d-flex justify-content-between">
                                                                <span>@lang('Total Count'):</span>
                                                                <strong>{{ $count }}</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between">
                                                                <span>@lang('Total Revenue'):</span>
                                                                <strong>₹{{ number_format($revenue, 2) }}</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between">
                                                                <span>@lang('Average Booking Value'):</span>
                                                                <strong>₹{{ number_format($avgBookingValue, 2) }}</strong>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Report Metadata -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Report Information')</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Report Date'):</span>
                                                    <strong>{{ $report->report_date->format('M d, Y') }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Report Type'):</span>
                                                    <strong>{{ ucfirst($report->report_type) }}</strong>
                                                </div>
                                                @if ($report->period_start)
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>@lang('Period Start'):</span>
                                                        <strong>{{ $report->period_start->format('M d, Y') }}</strong>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col-md-6">
                                                @if ($report->period_end)
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>@lang('Period End'):</span>
                                                        <strong>{{ $report->period_end->format('M d, Y') }}</strong>
                                                    </div>
                                                @endif
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Generated On'):</span>
                                                    <strong>{{ $report->created_at->format('M d, Y H:i') }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>@lang('Last Updated'):</span>
                                                    <strong>{{ $report->updated_at->format('M d, Y H:i') }}</strong>
                                                </div>
                                            </div>
                                        </div>
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
