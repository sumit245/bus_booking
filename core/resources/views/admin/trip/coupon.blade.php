@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">@lang('Create/Update Coupon')</h4>
                </div>
                <form action="{{ route('trip.coupon.update') }}" method="POST">
                    @csrf
                    @method('POST')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="coupon_name">@lang('Coupon Name')</label>
                            <input type="text" name="coupon_name" id="coupon_name" class="form-control" 
                                   value="{{ old('coupon_name', $couponToEdit->coupon_name ?? '') }}" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="coupon_threshold">@lang('Coupon Threshold (Price above which discount applies)')</label>
                            <div class="input-group">
                                <input type="number" step="any" name="coupon_threshold" id="coupon_threshold" class="form-control" 
                                       value="{{ old('coupon_threshold', getAmount($couponToEdit->coupon_threshold ?? 0)) }}" required>
                                <span class="input-group-text">{{ __($general->cur_sym) }}</span>
                            </div>
                            <small class="text-muted">@lang('No discount will be applied if the price is less than or equal to this threshold.')</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="discount_type">@lang('Discount Type')</label>
                            <select name="discount_type" id="discount_type" class="form-control" required>
                                <option value="fixed" @if(old('discount_type', $couponToEdit->discount_type ?? 'fixed') == 'fixed') selected @endif>@lang('Fixed Amount')</option>
                                <option value="percentage" @if(old('discount_type', $couponToEdit->discount_type ?? 'fixed') == 'percentage') selected @endif>@lang('Percentage')</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="coupon_value">@lang('Coupon Value')</label>
                            <div class="input-group">
                                <input type="number" step="any" name="coupon_value" id="coupon_value" class="form-control" 
                                       value="{{ old('coupon_value', getAmount($couponToEdit->coupon_value ?? 0)) }}" required>
                                <span class="input-group-text" id="coupon_value_symbol">
                                    {{ old('discount_type', $couponToEdit->discount_type ?? 'fixed') == 'percentage' ? '%' : __($general->cur_sym) }}
                                </span>
                            </div>
                            <small class="text-muted percentage-note @if(old('discount_type', $couponToEdit->discount_type ?? 'fixed') != 'percentage') d-none @endif">
                                @lang('Enter value between 0 and 100 for percentage discount.')
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="expiry_date">@lang('Expiry Date')</label>
                            <input type="text" name="expiry_date" id="expiry_date" class="form-control datepicker-here" 
                                   data-language='en' data-date-format="yyyy-mm-dd" 
                                   value="{{ old('expiry_date', $couponToEdit && $couponToEdit->expiry_date ? $couponToEdit->expiry_date->format('Y-m-d') : '') }}" 
                                   autocomplete="off" required>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Save & Activate Coupon')</button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">@lang('All Coupons')</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Coupon Name')</th>
                                    <th>@lang('Threshold')</th>
                                    <th>@lang('Discount Type')</th>
                                    <th>@lang('Value')</th>
                                    <th>@lang('Expiry Date')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allCoupons as $coupon)
                                    <tr>
                                        <td>{{ __($coupon->coupon_name) }}</td>
                                        <td>{{ showAmount($coupon->coupon_threshold) }} {{ __($general->cur_sym) }}</td>
                                        <td>
                                            @if($coupon->discount_type == 'fixed')
                                                <span class="badge badge--primary">@lang('Fixed')</span>
                                            @else
                                                <span class="badge badge--info">@lang('Percentage')</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ showAmount($coupon->coupon_value) }}
                                            @if($coupon->discount_type == 'percentage')%@else{{ __($general->cur_sym) }}@endif
                                        </td>
                                        <td>{{ showDateTime($coupon->expiry_date, 'Y-m-d') }}</td>
                                        <td>
                                            @if($coupon->status && $coupon->expiry_date->isFuture())
                                                <span class="badge badge--success">@lang('Active')</span>
                                            @elseif($coupon->status && $coupon->expiry_date->isPast())
                                                <span class="badge badge--warning">@lang('Expired')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Inactive')</span>
                                            @endif
                                        </td>
                                        <td>
                                            
                        <div class="button--group">
                            {{-- ✅ Activate Button --}}
                            @if(!$coupon->status || $coupon->expiry_date->isPast())
                                <form method="POST" action="{{ route('trip.coupon.activate', $coupon->id) }}" style="display:inline-block;">
                                    @csrf
                                    <input type="hidden" name="expiry_date" value="{{ now()->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-sm btn--success">
                                        <i class="las la-check"></i> @lang('Activate')
                                    </button>
                                </form>
                            @else
                                {{-- ✅ Deactivate Button --}}
                                <form method="POST" action="{{ route('trip.coupon.deactivate', $coupon->id) }}" style="display:inline-block;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn--danger">
                                        <i class="las la-times"></i> @lang('Deactivate')
                                    </button>
                                </form>
                            @endif

                            {{-- ✅ Edit Link --}}
                            <a href="{{ route('trip.coupon', ['edit_id' => $coupon->id]) }}" class="btn btn-sm btn--primary">
                                <i class="las la-edit"></i> @lang('Edit')
                            </a>

                            {{-- ✅ Delete Button --}}
                            <form method="POST" action="{{ route('trip.coupon.delete', $coupon->id) }}" style="display:inline-block;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn--danger" onclick="return confirm('Are you sure you want to delete this coupon?');">
                                    <i class="las la-trash"></i> @lang('Delete')
                                </button>
                            </form>
                        </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Activate MODAL --}}
    <div id="activateModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Activate Coupon Confirmation')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form id="activateForm" action="" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="activate_coupon_id">
                    <div class="modal-body">
                        <p>@lang('Are you sure you want to activate') <span class="fw-bold coupon-name"></span>?</p>
                        <div class="form-group">
                            <label for="new_expiry_date">@lang('Set New Expiry Date')</label>
                            <input type="text" name="expiry_date" id="new_expiry_date" class="form-control datepicker-here" 
                                   data-language='en' data-date-format="yyyy-mm-dd" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary h-45 w-100">@lang('Activate')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Deactivate MODAL --}}
    <div id="deactivateModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Deactivate Coupon Confirmation')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form id="deactivateForm" action="" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="deactivate_coupon_id">
                    <div class="modal-body">
                        <p>@lang('Are you sure you want to deactivate') <span class="fw-bold coupon-name"></span>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--danger h-45 w-100">@lang('Deactivate')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete MODAL --}}
    <div id="deleteModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Delete Coupon Confirmation')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form id="deleteForm" action="" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="id" id="delete_coupon_id">
                    <div class="modal-body">
                        <p>@lang('Are you sure you want to delete') <span class="fw-bold coupon-name"></span>?</p>
                        <p class="text-danger">@lang('This action cannot be undone.')</p>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--danger h-45 w-100">@lang('Delete')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function ($) {
            "use strict";
            
            // Initialize datepicker for the main form
            $('#expiry_date').datepicker({
                minDate: new Date(),
                autoClose: true,
            });

            // Initialize datepicker for the activate modal
            $('#new_expiry_date').datepicker({
                minDate: new Date(),
                autoClose: true,
            });

            // Handle discount type change to update symbol
            function updateCouponValueSymbol() {
                const selectedType = $('#discount_type').val();
                const currencySymbol = "{{ __($general->cur_sym) }}";
                
                if (selectedType === 'percentage') {
                    $('#coupon_value_symbol').text('%');
                    $('.percentage-note').removeClass('d-none');
                } else {
                    $('#coupon_value_symbol').text(currencySymbol);
                    $('.percentage-note').addClass('d-none');
                }
            }

            // Bind change event and trigger on load
            $(document).on('change', '#discount_type', updateCouponValueSymbol);
            
            // Trigger on page load to set initial symbol
            $(document).ready(function() {
                updateCouponValueSymbol();
            });

            // Activate button click handler
            $(document).on('click', '.activateBtn', function () {
                var couponId = $(this).data('id');
                var couponName = $(this).data('coupon_name');
                var currentExpiry = $(this).data('expiry_date');
                
                var modal = $('#activateModal');
                modal.find('#activate_coupon_id').val(couponId);
                modal.find('.coupon-name').text(couponName);
                
                // Set default new expiry date
                let today = new Date().toISOString().slice(0, 10);
                if (currentExpiry && new Date(currentExpiry) > new Date()) {
                    modal.find('#new_expiry_date').val(currentExpiry);
                } else {
                    modal.find('#new_expiry_date').val(today);
                }
                
                // Set form action
                var actionUrl = "{{ route('trip.coupon.activate', ':id') }}".replace(':id', couponId);
                modal.find('#activateForm').attr('action', actionUrl);
                
                modal.modal('show');
            });

            // Deactivate button click handler
            $(document).on('click', '.deactivateBtn', function () {
                var couponId = $(this).data('id');
                var couponName = $(this).data('coupon_name');
                
                var modal = $('#deactivateModal');
                modal.find('#deactivate_coupon_id').val(couponId);
                modal.find('.coupon-name').text(couponName);
                
                // Set form action
                var actionUrl = "{{ route('trip.coupon.deactivate', ':id') }}".replace(':id', couponId);
                modal.find('#deactivateForm').attr('action', actionUrl);
                
                modal.modal('show');
            });

            // Delete button click handler
            $(document).on('click', '.deleteBtn', function () {
                var couponId = $(this).data('id');
                var couponName = $(this).data('coupon_name');
                
                var modal = $('#deleteModal');
                modal.find('#delete_coupon_id').val(couponId);
                modal.find('.coupon-name').text(couponName);
                
                // Set form action
                var actionUrl = "{{ route('trip.coupon.delete', ':id') }}".replace(':id', couponId);
                modal.find('#deleteForm').attr('action', actionUrl);
                
                modal.modal('show');
            });

            // Pre-fill form if edit_id is present in URL
            @if(isset($couponToEdit))
                $(document).ready(function() {
                    // Ensure the symbol updates correctly if editing
                    updateCouponValueSymbol();
                });
            @endif

        })(jQuery);
    </script>
@endpush

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
    
    .btn--success {
        background-color: #28a745;
        color: white;
    }
    
    .btn--success:hover {
        background-color: #218838;
        color: white;
    }
    
    .button--group .btn {
        margin-right: 5px;
        margin-bottom: 5px;
    }
    
    .d-none {
        display: none !important;
    }
    
    .percentage-note {
        font-size: 0.875em;
        color: #6c757d;
    }
</style>
@endpush
