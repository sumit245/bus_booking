@extends('operator.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">@lang('Payout History')</h4>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="row mb-4">
                            <div class="col-md-3">
                                <label>@lang('Start Date')</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label>@lang('End Date')</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-3">
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
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">@lang('Filter')</button>
                                    <a href="{{ route('operator.revenue.payouts') }}"
                                        class="btn btn-secondary">@lang('Reset')</a>
                                </div>
                            </div>
                        </form>

                        <!-- Payouts Table -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('Payout Period')</th>
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
                                                <a href="{{ route('operator.revenue.payouts.show', $payout->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="las la-eye"></i> @lang('View')
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center">
                                                <div class="py-4">
                                                    <i class="las la-wallet fs-1 text-muted"></i>
                                                    <p class="mt-2 text-muted">@lang('No payouts found')</p>
                                                    <p class="text-muted">@lang('Payouts will appear here once they are generated by the admin')</p>
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
@endsection
