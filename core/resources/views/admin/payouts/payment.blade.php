@extends('admin.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">@lang('Record Payment')</h4>
                            <div>
                                <a href="{{ route('admin.payouts.show', $payout->id) }}" class="btn btn-secondary">
                                    <i class="las la-arrow-left"></i> @lang('Back to Payout')
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Payout Summary -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h4 class="mb-0">₹{{ number_format($payout->net_payable, 2) }}</h4>
                                        <p class="mb-0">@lang('Total Net Payable')</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h4 class="mb-0">₹{{ number_format($payout->amount_paid, 2) }}</h4>
                                        <p class="mb-0">@lang('Amount Already Paid')</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h4 class="mb-0">₹{{ number_format($payout->pending_amount, 2) }}</h4>
                                        <p class="mb-0">@lang('Pending Amount')</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Form -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Payment Details')</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST"
                                            action="{{ route('admin.payouts.record-payment', $payout->id) }}">
                                            @csrf

                                            <div class="form-group">
                                                <label>@lang('Payment Amount') *</label>
                                                <input type="number" name="amount"
                                                    class="form-control @error('amount') is-invalid @enderror"
                                                    value="{{ old('amount') }}" min="0.01"
                                                    max="{{ $payout->pending_amount }}" step="0.01" required>
                                                <small class="form-text text-muted">
                                                    @lang('Maximum amount: ₹'){{ number_format($payout->pending_amount, 2) }}
                                                </small>
                                                @error('amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <label>@lang('Payment Method') *</label>
                                                <select name="payment_method"
                                                    class="form-control @error('payment_method') is-invalid @enderror"
                                                    required>
                                                    <option value="">@lang('Select Payment Method')</option>
                                                    <option value="Bank Transfer"
                                                        {{ old('payment_method') == 'Bank Transfer' ? 'selected' : '' }}>
                                                        @lang('Bank Transfer')</option>
                                                    <option value="UPI"
                                                        {{ old('payment_method') == 'UPI' ? 'selected' : '' }}>
                                                        @lang('UPI')</option>
                                                    <option value="Cheque"
                                                        {{ old('payment_method') == 'Cheque' ? 'selected' : '' }}>
                                                        @lang('Cheque')</option>
                                                    <option value="Cash"
                                                        {{ old('payment_method') == 'Cash' ? 'selected' : '' }}>
                                                        @lang('Cash')</option>
                                                    <option value="Other"
                                                        {{ old('payment_method') == 'Other' ? 'selected' : '' }}>
                                                        @lang('Other')</option>
                                                </select>
                                                @error('payment_method')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <label>@lang('Transaction Reference')</label>
                                                <input type="text" name="transaction_reference"
                                                    class="form-control @error('transaction_reference') is-invalid @enderror"
                                                    value="{{ old('transaction_reference') }}"
                                                    placeholder="@lang('Enter transaction ID, UPI reference, etc.')">
                                                @error('transaction_reference')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <label>@lang('Payment Notes')</label>
                                                <textarea name="payment_notes" class="form-control @error('payment_notes') is-invalid @enderror" rows="3"
                                                    placeholder="@lang('Add any additional notes about this payment...')">{{ old('payment_notes') }}</textarea>
                                                @error('payment_notes')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="confirmPayment"
                                                        required>
                                                    <label class="custom-control-label" for="confirmPayment">
                                                        @lang('I confirm that this payment has been made to the operator')
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="las la-check"></i> @lang('Record Payment')
                                                </button>
                                                <a href="{{ route('admin.payouts.show', $payout->id) }}"
                                                    class="btn btn-secondary">
                                                    @lang('Cancel')
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Payout Information')</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Operator'):</span>
                                            <strong>{{ $payout->operator->company_name ?: $payout->operator->name }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Period'):</span>
                                            <strong>{{ $payout->payout_period_start->format('M d, Y') }} -
                                                {{ $payout->payout_period_end->format('M d, Y') }}</strong>
                                        </div>
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
                                            <span>@lang('Gateway Fee'):</span>
                                            <strong
                                                class="text-danger">-₹{{ number_format($payout->payment_gateway_fee, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('TDS'):</span>
                                            <strong
                                                class="text-danger">-₹{{ number_format($payout->tds_amount, 2) }}</strong>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><strong>@lang('Net Payable'):</strong></span>
                                            <strong>₹{{ number_format($payout->net_payable, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>@lang('Amount Paid'):</span>
                                            <strong
                                                class="text-success">₹{{ number_format($payout->amount_paid, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span><strong>@lang('Pending'):</strong></span>
                                            <strong
                                                class="text-warning">₹{{ number_format($payout->pending_amount, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Payment History')</h5>
                                    </div>
                                    <div class="card-body">
                                        @if ($payout->amount_paid > 0)
                                            <div class="alert alert-info">
                                                <i class="las la-info-circle"></i>
                                                @lang('Previous payments have been recorded. This will be an additional payment.')
                                            </div>
                                        @else
                                            <div class="alert alert-warning">
                                                <i class="las la-exclamation-triangle"></i>
                                                @lang('No previous payments recorded.')
                                            </div>
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
    <script>
        $(document).ready(function() {
            // Auto-calculate maximum amount
            const maxAmount = {{ $payout->pending_amount }};

            $('input[name="amount"]').on('input', function() {
                const value = parseFloat($(this).val());
                if (value > maxAmount) {
                    $(this).val(maxAmount);
                }
            });
        });
    </script>
@endpush
