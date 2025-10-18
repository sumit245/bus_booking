@extends('admin.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">@lang('Payout Details')</h4>
                            <div>
                                <a href="{{ route('admin.payouts.index') }}" class="btn btn-secondary">
                                    <i class="las la-arrow-left"></i> @lang('Back to Payouts')
                                </a>
                                @if (!$payout->isPaid())
                                    <a href="{{ route('admin.payouts.payment', $payout->id) }}" class="btn btn-success">
                                        <i class="las la-credit-card"></i> @lang('Record Payment')
                                    </a>
                                @endif
                                @if ($payout->isPending())
                                    <button type="button" class="btn btn-danger"
                                        onclick="cancelPayout({{ $payout->id }})">
                                        <i class="las la-times"></i> @lang('Cancel Payout')
                                    </button>
                                @endif
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

                        <!-- Operator Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Operator Information')</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Business Name'):</span>
                                                    <strong>{{ $payout->operator->company_name ?: $payout->operator->name ?? 'N/A' }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Contact Person'):</span>
                                                    <strong>{{ $payout->operator->contact_person ?? 'N/A' }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Email'):</span>
                                                    <strong>{{ $payout->operator->email ?? 'N/A' }}</strong>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Phone'):</span>
                                                    <strong>{{ $payout->operator->phone ?? 'N/A' }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>@lang('Address'):</span>
                                                    <strong>{{ $payout->operator->address ?? 'N/A' }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>@lang('Status'):</span>
                                                    <span
                                                        class="badge badge-{{ $payout->operator->status == 'active' ? 'success' : 'danger' }}">
                                                        {{ ucfirst($payout->operator->status ?? 'inactive') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Revenue and Fee Breakdown -->
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

                        <!-- Payout Period and Admin Notes -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Payout Period Information')</h5>
                                    </div>
                                    <div class="card-body">
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
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Created On'):</span>
                                            <strong>{{ $payout->created_at->format('M d, Y H:i') }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>@lang('Last Updated'):</span>
                                            <strong>{{ $payout->updated_at->format('M d, Y H:i') }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Admin Notes')</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST"
                                            action="{{ route('admin.payouts.update-notes', $payout->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-group">
                                                <textarea name="admin_notes" class="form-control" rows="4" placeholder="@lang('Add admin notes here...')">{{ $payout->admin_notes }}</textarea>
                                            </div>
                                            <button type="submit"
                                                class="btn btn-primary btn-sm">@lang('Update Notes')</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Payout Form -->
    <form id="cancelPayoutForm" method="POST" style="display: none;">
        @csrf
        @method('PATCH')
    </form>
@endsection

@push('script')
    <script>
        function cancelPayout(payoutId) {
            if (confirm('@lang('Are you sure you want to cancel this payout? This action cannot be undone.')')) {
                const form = document.getElementById('cancelPayoutForm');
                form.action = `/admin/payouts/${payoutId}/cancel`;
                form.submit();
            }
        }
    </script>
@endpush
