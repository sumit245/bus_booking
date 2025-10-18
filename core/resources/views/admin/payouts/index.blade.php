@extends('admin.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">@lang('Operator Payouts')</h4>
                            <div>
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                    data-target="#generatePayoutModal">
                                    <i class="las la-plus"></i> @lang('Generate Payout')
                                </button>
                                <button type="button" class="btn btn-success" data-toggle="modal"
                                    data-target="#bulkGenerateModal">
                                    <i class="las la-bolt"></i> @lang('Bulk Generate')
                                </button>
                                <a href="{{ route('admin.payouts.export') }}" class="btn btn-info">
                                    <i class="las la-download"></i> @lang('Export')
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Admin Earnings Summary -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card text-white"
                                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="las la-chart-line"></i> @lang('Admin Earnings Summary')
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="text-center">
                                                    <h3 class="mb-1">
                                                        ₹{{ number_format($adminEarnings['total_pending_amount'], 2) }}</h3>
                                                    <p class="mb-0 text-warning">
                                                        <i class="las la-clock"></i> @lang('Pending to Pay')
                                                    </p>
                                                    <small class="text-light">{{ $adminEarnings['pending_payouts_count'] }}
                                                        @lang('payouts')</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="text-center">
                                                    <h3 class="mb-1">
                                                        ₹{{ number_format($adminEarnings['total_paid_amount'], 2) }}</h3>
                                                    <p class="mb-0 text-success">
                                                        <i class="las la-check-circle"></i> @lang('Already Paid')
                                                    </p>
                                                    <small class="text-light">{{ $adminEarnings['paid_payouts_count'] }}
                                                        @lang('payouts')</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="text-center">
                                                    <h3 class="mb-1">
                                                        ₹{{ number_format($adminEarnings['total_platform_fees'], 2) }}</h3>
                                                    <p class="mb-0 text-info">
                                                        <i class="las la-percentage"></i> @lang('Platform Fees Earned')
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="text-center">
                                                    <h3 class="mb-1">
                                                        ₹{{ number_format($adminEarnings['total_payment_gateway_fees'], 2) }}
                                                    </h3>
                                                    <p class="mb-0 text-info">
                                                        <i class="las la-credit-card"></i> @lang('Gateway Fees')
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="text-center">
                                                    <h3 class="mb-1">
                                                        ₹{{ number_format($adminEarnings['total_tds_collected'], 2) }}</h3>
                                                    <p class="mb-0 text-info">
                                                        <i class="las la-receipt"></i> @lang('TDS Collected')
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="text-center">
                                                    <h3 class="mb-1">
                                                        ₹{{ number_format($adminEarnings['total_platform_fees'] + $adminEarnings['total_payment_gateway_fees'] + $adminEarnings['total_tds_collected'], 2) }}
                                                    </h3>
                                                    <p class="mb-0 text-success">
                                                        <i class="las la-wallet"></i> @lang('Total Admin Revenue')
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row mb-4">
                            <div class="col-md-3">
                                <label>@lang('Operator')</label>
                                <select name="operator_id" class="form-control">
                                    <option value="">@lang('All Operators')</option>
                                    @foreach ($operators as $operator)
                                        <option value="{{ $operator->id }}"
                                            {{ request('operator_id') == $operator->id ? 'selected' : '' }}>
                                            {{ $operator->company_name ?: $operator->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>@lang('Status')</label>
                                <select name="status" class="form-control">
                                    <option value="">@lang('All Statuses')</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                                        @lang('Pending')</option>
                                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>
                                        @lang('Partial')</option>
                                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>
                                        @lang('Paid')</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                        @lang('Cancelled')</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>@lang('Start Date')</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label>@lang('End Date')</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">@lang('Filter')</button>
                                    <a href="{{ route('admin.payouts.index') }}"
                                        class="btn btn-secondary">@lang('Reset')</a>
                                </div>
                            </div>
                        </form>

                        <!-- Payouts Table -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('Operator')</th>
                                        <th>@lang('Period')</th>
                                        <th>@lang('Total Revenue')</th>
                                        <th>@lang('Platform Fee')</th>
                                        <th>@lang('Gateway Fee')</th>
                                        <th>@lang('TDS')</th>
                                        <th>@lang('Net Payable')</th>
                                        <th>@lang('Amount Paid')</th>
                                        <th>@lang('Pending')</th>
                                        <th>@lang('Status')</th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payouts as $payout)
                                        <tr>
                                            <td>
                                                <strong>{{ $payout->operator->company_name ?: $payout->operator->name ?? 'N/A' }}</strong>
                                            </td>
                                            <td>{{ $payout->payout_period }}</td>
                                            <td>₹{{ number_format($payout->total_revenue, 2) }}</td>
                                            <td>₹{{ number_format($payout->platform_fee, 2) }}</td>
                                            <td>₹{{ number_format($payout->payment_gateway_fee, 2) }}</td>
                                            <td>₹{{ number_format($payout->tds_amount, 2) }}</td>
                                            <td><strong>₹{{ number_format($payout->net_payable, 2) }}</strong></td>
                                            <td>₹{{ number_format($payout->amount_paid, 2) }}</td>
                                            <td>
                                                @if ($payout->pending_amount > 0)
                                                    <span
                                                        class="text-warning">₹{{ number_format($payout->pending_amount, 2) }}</span>
                                                @else
                                                    <span class="text-success">₹0.00</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $payout->status_badge }}">
                                                    {{ ucfirst($payout->payment_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.payouts.show', $payout->id) }}"
                                                        class="btn btn-sm btn-primary">
                                                        <i class="las la-eye"></i>
                                                    </a>
                                                    @if (!$payout->isPaid())
                                                        <a href="{{ route('admin.payouts.payment', $payout->id) }}"
                                                            class="btn btn-sm btn-success">
                                                            <i class="las la-credit-card"></i>
                                                        </a>
                                                    @endif
                                                    @if ($payout->isPending())
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                            onclick="cancelPayout({{ $payout->id }})">
                                                            <i class="las la-times"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center">
                                                <div class="py-4">
                                                    <i class="las la-wallet fs-1 text-muted"></i>
                                                    <p class="mt-2 text-muted">@lang('No payouts found')</p>
                                                    <button type="button" class="btn btn-primary" data-toggle="modal"
                                                        data-target="#generatePayoutModal">
                                                        @lang('Generate Your First Payout')
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if ($payouts->hasPages())
                            {{ $payouts->links() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Payout Modal -->
    <div class="modal fade" id="generatePayoutModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.payouts.generate') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Generate Payout')</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Operator')</label>
                            <select name="operator_id" class="form-control" required>
                                <option value="">@lang('Select Operator')</option>
                                @foreach ($operators as $operator)
                                    <option value="{{ $operator->id }}">{{ $operator->company_name ?: $operator->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>@lang('Start Date')</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('End Date')</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn-primary">@lang('Generate Payout')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Generate Modal -->
    <div class="modal fade" id="bulkGenerateModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.payouts.bulk-generate') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Bulk Generate Payouts')</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="las la-info-circle"></i>
                            @lang('This will generate payouts for all operators for the specified period.')
                        </div>
                        <div class="form-group">
                            <label>@lang('Start Date')</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('End Date')</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn-primary">@lang('Generate All Payouts')</button>
                    </div>
                </form>
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
            if (confirm('@lang('Are you sure you want to cancel this payout?')')) {
                const form = document.getElementById('cancelPayoutForm');
                form.action = `/admin/payouts/${payoutId}/cancel`;
                form.submit();
            }
        }
    </script>
@endpush
