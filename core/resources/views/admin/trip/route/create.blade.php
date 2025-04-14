@extends('admin.layouts.app')

@section('panel')
<div class="row mb-none-30">
    <div class="col-xl-12 col-lg-12 col-md-12 mb-30">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title border-bottom pb-2">@lang('Information of Route') </h5>

                <form action="{{ route('admin.trip.route.store')}}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-control-label font-weight-bold"> @lang('Name')</label>
                                <input type="text" class="form-control" placeholder="@lang('Enter Name')" name="name" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label font-weight-bold"> @lang('Start From')</label>
                                <select name="start_from" class="select2-basic" required>
                                    <option value="">@lang('Select an option')</option>
                                    @foreach ($stoppages as $item)
                                        <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox form-check-primary">
                                    <input type="checkbox" class="custom-control-input" id="has-stoppage">
                                    <label class="custom-control-label" for="has-stoppage">@lang('Has More Stoppage')</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label font-weight-bold"> @lang('End To')</label>
                                <select name="end_to" class="select2-basic" required>
                                    <option value="">@lang('Select an option')</option>
                                    @foreach ($stoppages as $item)
                                        <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="stoppages-wrapper col-md-12">
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-control-label font-weight-bold"> @lang('Time')</label>
                                <input type="text" class="form-control" name="time" placeholder="@lang('Enter Approximate Time')" required>
                                <small class="text-danger">@lang('Keep space between value & unit')</small>
                            </div>

                            <div class="form-group">
                                <label class="form-control-label font-weight-bold"> @lang('Distance')</label>
                                <input type="text" class="form-control" placeholder="@lang('Enter Distance')" name="distance" required>
                                <small class="text-danger">@lang('Keep space between value & unit')</small>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn--primary btn-block btn-lg">@lang('Save')
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
    <a href="{{ route('admin.trip.route') }}" class="btn btn-sm btn--primary box--shadow1 text--small addBtn"><i class="la la-fw la-backward"></i>@lang('Go Back')</a>
@endpush

@push('style')
    <style>
        .input-group > .select2-container--default {
            width: auto !important;
            flex: 1 1 auto !important;
        }

        .input-group > .select2-container--default .select2-selection--single {
            height: 100% !important;
            line-height: inherit !important;
        }
    </style>
@endpush

@push('script')
<script>
     "use strict";

     (function($){
        $('.select2-basic').select2({
            dropdownParent: $('.card-body')
        });

        $('#has-stoppage').on('click', function() {
            if(this.checked){
                var stps =
                        `<div class="row stoppages-row">
                            <div class="col-md-3">
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">@lang('1')</span>
                                    </div>
                                    <select class="select2-basic form-control w-auto" name="stoppages[1]" required >
                                        <option value="" selected>@lang('Select Stoppage')</option>
                                        @foreach ($stoppages as $stoppage)
                                        <option value="{{$stoppage->id}}">{{$stoppage->name}}</option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-danger remove-stoppage"><i class="las la-times"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn--success add-stoppage-btn mb-1"><i class="la la-plus"></i>@lang('Next Stoppage')</button> <span class="text--danger"> @lang('Make sure that you are adding stoppages serially followed by the starting point')</span>

                        `;
                $('.stoppages-wrapper').prepend(stps);
                $('.select2-basic').select2({
                    dropdownParent: $('.stoppages-wrapper')
                    
                });
            }else{
                itr = 2;
                $('.stoppages-wrapper').html('');
            }
        });

        var itr = 2;
        $(document).on('click', '.add-stoppage-btn', function(){
            var stps = `<div class="col-md-3">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">${itr}</span>
                                </div>
                                <select class="select2-basic form-control w-auto" name="stoppages[${itr}]">
                                    <option value="" selected>@lang('Select Stoppage')</option>
                                    @foreach ($stoppages as $stoppage)
                                    <option value="{{$stoppage->id}}">{{$stoppage->name}}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-danger remove-stoppage"><i class="las la-times"></i></button>
                                </div>
                            </div>
                        </div>`;

            $('.stoppages-row').append(stps);

            $('.select2-basic').select2({
                dropdownParent: $('.stoppages-wrapper'),
            });
            itr++;
        });

        $(document).on('click', '.remove-stoppage', function() {
            $(this).closest('.col-md-3').remove();
            var elements = $('.stoppages-row .col-md-3').find();

            $($('.stoppages-row .col-md-3')).each(function (index, element) {

                $(element).find('.input-group-prepend > .input-group-text').text(index+1);
                $(element).find('.select2-basic').attr('name',`stoppages[${index+1}]`);

            });
        });
     })(jQuery)
</script>
@endpush
