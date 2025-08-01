@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">@lang($pageTitle)</h4>
                </div>
                <form action="{{ route('trip.coupon.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="coupon_name">@lang('Coupon Name')</label>
                            <input type="text" name="coupon_name" id="coupon_name" class="form-control" value="{{ old('coupon_name', $currentCoupon->coupon_name) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="coupon_threshold">@lang('Coupon Threshold (Price up to which flat amount applies)')</label>
                            <div class="input-group">
                                <input type="number" step="any" name="coupon_threshold" id="coupon_threshold" class="form-control" value="{{ old('coupon_threshold', getAmount($currentCoupon->coupon_threshold)) }}" required>
                                <span class="input-group-text">{{ __($general->cur_sym) }}</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="flat_coupon_amount">@lang('Flat Coupon Amount (Applied if price <= threshold)')</label>
                            <div class="input-group">
                                <input type="number" step="any" name="flat_coupon_amount" id="flat_coupon_amount" class="form-control" value="{{ old('flat_coupon_amount', getAmount($currentCoupon->flat_coupon_amount)) }}" required>
                                <span class="input-group-text">{{ __($general->cur_sym) }}</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="percentage_coupon_amount">@lang('Percentage Coupon Amount (Applied if price > threshold)')</label>
                            <div class="input-group">
                                <input type="number" step="any" name="percentage_coupon_amount" id="percentage_coupon_amount" class="form-control" value="{{ old('percentage_coupon_amount', getAmount($currentCoupon->percentage_coupon_amount)) }}" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@push('style')
<style>
    .alert-secondary {
        background-color: #f4f6f9;
        border-color: #d6d8db;
        color: #333;
        border-radius: 5px;
    }
    
    .alert-info {
        background-color: #e8f4fd;
        border-color: #b8daff;
        color: #0c5460;
    }
    
    .btn--primary {
        background-color: #007bff;
        color: white;
    }
    
    .btn--primary:hover {
        background-color: #0056b3;
        color: white;
    }
    
    .btn--danger {
        background-color: #dc3545;
        color: white;
    }
    
    .btn--danger:hover {
        background-color: #c82333;
        color: white;
    }
    
    .d-flex .btn {
        margin-left: 5px;
    }
</style>
@endpush
