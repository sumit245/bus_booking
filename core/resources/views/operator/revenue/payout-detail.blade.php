@extends('operator.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">@lang('Payout Details')</h4>
                            <div>
                                <a href="{{ route('operator.revenue.payouts') }}" class="btn btn-secondary">
                                    <i class="las la-arrow-left"></i> @lang('Back to Payouts')
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Payout Summary -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h4 class="mb-0">₹{{ number_format($payout->total_revenue, 2) }}</h4>
                                        <p class="mb-0">@lang('Total Revenue')</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h4 class="mb-0">₹{{ number_format($payout->net_payable, 2) }}</h4>
                                        <p class="mb-0">@lang('Net Payable')</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h4 class="mb-0">₹{{ number_format($payout->amount_paid, 2) }}</h4>
                                        <p class="mb-0">@lang('Amount Paid')</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h4 class="mb-0">₹{{ number_format($payout->pending_amount, 2) }}</h4>
                                        <p class="mb-0">@lang('Pending Amount')</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payout Details -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Revenue Breakdown')</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Total Revenue'):</span>
                                            <strong>₹{{ number_format($payout->total_revenue, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Platform Fee'):</span>
                                            <strong
                                                class="text-danger">-₹{{ number_format($payout->platform_fee, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Payment Gateway Fee'):</span>
                                            <strong
                                                class="text-danger">-₹{{ number_format($payout->payment_gateway_fee, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Other Deductions'):</span>
                                            <strong
                                                class="text-danger">-₹{{ number_format($payout->other_deductions, 2) }}</strong>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><strong>@lang('Net Before TDS'):</strong></span>
                                            <strong>₹{{ number_format($payout->total_revenue - $payout->platform_fee - $payout->payment_gateway_fee - $payout->other_deductions, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>@lang('TDS Amount'):</span>
                                            <strong
                                                class="text-danger">-₹{{ number_format($payout->tds_amount, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Payment Information')</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Payment Status'):</span>
                                            <span class="badge badge-{{ $payout->status_badge }}">
                                                {{ ucfirst($payout->payment_status) }}
                                            </span>
                                        </div>
                                        @if ($payout->paid_date)
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>@lang('Paid Date'):</span>
                                                <strong>{{ $payout->paid_date->format('M d, Y') }}</strong>
                                            </div>
                                        @endif
                                        @if ($payout->payment_method)
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>@lang('Payment Method'):</span>
                                                <strong>{{ $payout->payment_method }}</strong>
                                            </div>
                                        @endif
                                        @if ($payout->transaction_reference)
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>@lang('Transaction Reference'):</span>
                                                <strong>{{ $payout->transaction_reference }}</strong>
                                            </div>
                                        @endif
                                        @if ($payout->payment_notes)
                                            <div class="mb-2">
                                                <span>@lang('Payment Notes'):</span>
                                                <p class="mt-1 text-muted">{{ $payout->payment_notes }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payout Period Information -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Payout Period Information')</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Payout Period'):</span>
                                                    <strong>{{ $payout->payout_period }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Period Start'):</span>
                                                    <strong>{{ $payout->payout_period_start->format('M d, Y') }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Period End'):</span>
                                                    <strong>{{ $payout->payout_period_end->format('M d, Y') }}</strong>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Created On'):</span>
                                                    <strong>{{ $payout->created_at->format('M d, Y H:i') }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Last Updated'):</span>
                                                    <strong>{{ $payout->updated_at->format('M d, Y H:i') }}</strong>
                                                </div>
                                                @if ($payout->createdByAdmin)
                                                    <div class="d-flex justify-content-between">
                                                        <span>@lang('Created By'):</span>
                                                        <strong>{{ $payout->createdByAdmin->name ?? 'Admin' }}</strong>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Notes -->
                        @if ($payout->admin_notes)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">@lang('Admin Notes')</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted">{{ $payout->admin_notes }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
