@extends('admin.layouts.app')

@section('panel')
<div class="row mb-none-30">
    <div class="col-xl-12 col-lg-12 col-md-12 mb-30">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title border-bottom pb-2">@lang('Information About Ticket Price') </h5>

                <form action="{{ route('admin.trip.ticket.price.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-control-label font-weight-bold"> @lang('Fleet Type')</label>
                                <select name="fleet_type" class="select2-basic" required>
                                    <option value="">@lang('Select an option')</option>
                                    @foreach ($fleetTypes as $item)
                                        <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-control-label font-weight-bold"> @lang('Route')</label>
                                <select name="route" class="select2-basic" required>
                                    <option value="">@lang('Select an option')</option>
                                    @foreach ($routes as $item)
                                        <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-control-label font-weight-bold"> @lang('Price For Source To Destination')</label>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="btn--light input-group-text">{{ $general->cur_sym }}</span>
                                    </div>
                                    <input type="text" name="main_price" class="form-control" placeholder="@lang('Enter a price')" required />
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 price-error-message">

                        </div>

                        <div class="price-wrapper col-md-12">

                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn--primary btn-block btn-lg submit-button">@lang('Save')
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@push('breadcrumb-plugins')
    <a href="{{ route('admin.trip.ticket.price') }}" class="btn btn-sm btn--primary box--shadow1 text--small addBtn"><i class="la la-fw la-backward"></i>@lang('Go Back')</a>
@endpush

@push('script')
<script>
     "use strict";

     (function($){
        $('.select2-basic').select2({
            dropdownParent: $('.card-body')
        });

        $(document).on('change', 'select[name=fleet_type] , select[name=route]', function(){
            var routeId  = $('select[name="route"]').find("option:selected").val();
            var fleetTypeId  = $('select[name="fleet_type"]').find("option:selected").val();

            if(routeId && fleetTypeId){
                var data = {
                    'vehicle_route_id'      : routeId,
                    'fleet_type_id' : fleetTypeId
                }
                $.ajax({
                    url: "{{ route('admin.trip.ticket.get_route_data') }}",
                    method: "get",
                    data: data,
                    success: function(result){
                        if(result.error){
                            $('.price-error-message').html(`<h5 class="text--danger">${result.error}</h5>`);
                            $('.price-wrapper').html('');
                            $('.submit-button').attr('disabled', 'disabled');
                        }else{
                            $('.price-error-message').html(``);
                            $('.submit-button').removeAttr('disabled');
                            $('.price-wrapper').html(`<h5>${result}</h5>`);
                        }
                    }
                });
            }else{
                $('.price-wrapper').html('');
            }
        })

     })(jQuery)
</script>
@endpush
