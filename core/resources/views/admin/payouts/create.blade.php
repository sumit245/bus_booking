@extends('admin.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">@lang('Generate Payout')</h4>
                            <div>
                                <a href="{{ route('admin.payouts.index') }}" class="btn btn-secondary">
                                    <i class="las la-arrow-left"></i> @lang('Back to Payouts')
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">@lang('Payout Generation Form')</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="{{ route('admin.payouts.generate') }}">
                                            @csrf

                                            <div class="form-group">
                                                <label>@lang('Select Operator') *</label>
                                                <select name="operator_id"
                                                    class="form-control @error('operator_id') is-invalid @enderror"
                                                    required>
                                                    <option value="">@lang('Select Operator')</option>
                                                    @foreach ($operators as $operator)
                                                        <option value="{{ $operator->id }}"
                                                            {{ old('operator_id') == $operator->id || $operatorId == $operator->id ? 'selected' : '' }}>
                                                            {{ $operator->company_name ?: $operator->name }}
                                                            ({{ $operator->name ?? 'N/A' }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('operator_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <label>@lang('Start Date') *</label>
                                                <input type="date" name="start_date"
                                                    class="form-control @error('start_date') is-invalid @enderror"
                                                    value="{{ old('start_date', date('Y-m-01')) }}" required>
                                                @error('start_date')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <label>@lang('End Date') *</label>
                                                <input type="date" name="end_date"
                                                    class="form-control @error('end_date') is-invalid @enderror"
                                                    value="{{ old('end_date', date('Y-m-d')) }}" required>
                                                @error('end_date')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="alert alert-info">
                                                <i class="las la-info-circle"></i>
                                                @lang('This will calculate the total revenue for the selected operator during the specified period and generate a payout record.')
                                            </div>

                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="las la-calculator"></i> @lang('Generate Payout')
                                                </button>
                                                <a href="{{ route('admin.payouts.index') }}" class="btn btn-secondary">
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
                                        <div class="alert alert-warning">
                                            <h6><i class="las la-exclamation-triangle"></i> @lang('Important Notes')</h6>
                                            <ul class="mb-0">
                                                <li>@lang('Platform commission is 5% of total revenue')</li>
                                                <li>@lang('Payment gateway fees are 2% of user bookings')</li>
                                                <li>@lang('TDS is 10% of net amount')</li>
                                                <li>@lang('Payouts are generated for confirmed bookings only')</li>
                                            </ul>
                                        </div>

                                        <div class="alert alert-info">
                                            <h6><i class="las la-info-circle"></i> @lang('What happens next?')</h6>
                                            <ol class="mb-0">
                                                <li>@lang('Revenue will be calculated for the period')</li>
                                                <li>@lang('Fees and deductions will be applied')</li>
                                                <li>@lang('A payout record will be created')</li>
                                                <li>@lang('You can then record payments made to the operator')</li>
                                            </ol>
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

@push('script')
    <script>
        $(document).ready(function() {
            // Set default date range to current month
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

            $('input[name="start_date"]').val(firstDay.toISOString().split('T')[0]);
            $('input[name="end_date"]').val(today.toISOString().split('T')[0]);
        });
    </script>
@endpush
