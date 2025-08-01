@extends('admin.layouts.app')

@section('panel')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card b-radius--10">
            <div class="card-body">
                <!-- Title -->
                <h5 class="mb-3">@lang('Coupon Settings')</h5>
                
                <!-- Current Settings Summary -->
                <div class="alert alert-secondary text-center font-weight-bold mb-4">
                    <p><strong>@lang('Current Coupon Name'):</strong> {{ $currentCoupon->coupon_name ?? 'No Active Coupon' }}</p>
                    <p><strong>@lang('Current Coupon Discount'):</strong> ₹{{ number_format($currentCoupon->coupon_amount ?? 0, 2) }}</p>
                    @if($currentCoupon)
                        <p><small class="text-muted">Last updated: {{ $currentCoupon->updated_at->format('d M Y, h:i A') }}</small></p>
                    @endif
                </div>

                <!-- Update Form -->
                <form action="{{ route('trip.coupon.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label class="font-weight-bold">@lang('Coupon Name') <span class="text-danger">*</span></label>
                        <input type="text" name="coupon_name" class="form-control"
                            value="{{ old('coupon_name', $currentCoupon->coupon_name ?? '') }}" 
                            placeholder="e.g., WELCOME50, SAVE100" required>
                        <small class="text-muted">Enter a descriptive name for the coupon</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">@lang('Coupon Discount Amount') <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="coupon_amount" class="form-control"
                            value="{{ old('coupon_amount', $currentCoupon->coupon_amount ?? '') }}" 
                            min="0" placeholder="0.00" required>
                        <small class="text-muted">Fixed amount to be deducted from the price (after markup)</small>
                    </div>

                    <div class="alert alert-info">
                        <strong>@lang('Note:'):</strong> 
                        <ul class="mb-0 mt-2">
                            <li>The coupon discount will be applied automatically to all bookings</li>
                            <li>Discount is applied after markup calculation</li>
                            <li>Final Price = (Original Price + Markup) - Coupon Discount</li>
                            <li>Creating a new coupon will replace the current active coupon</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn--primary mr-3">@lang('Update Coupon')</button>
                        <a href="{{ url()->previous() }}" class="btn btn--danger">@lang('Cancel')</a>
                    </div>
                </form>
            </div>
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
