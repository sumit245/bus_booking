
@extends('admin.layouts.app')

@section('panel')
<div class="row mb-none-30">
    <div class="col-xl-12 col-lg-12 col-md-12 mb-30">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    @foreach ($stoppages as $item)
                        @php
                            $inserted = false;
                        @endphp
                        @if($item[0] != $item [1])
                            @php $sd = getStoppageInfo($item) @endphp
                            @foreach ($ticketPrice->prices as $ticket)
                            @if($item[0] == $ticket->source_destination[0] && $item[1] == $ticket->source_destination[1])
                            @php
                                $inserted = true;
                            @endphp
                            <div class="col-lg-4 col-md-6 col-sm-6">
                                <form action="{{ route('admin.trip.ticket.price.update', $ticket->id) }}" class="update-form">
                                    @csrf
                                    <label for="point-{{$loop->iteration}}">{{$sd[0]->name}} - {{$sd[1]->name}}</label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="btn--light input-group-text">{{ $general->cur_sym }}</span>
                                        </div>
                                        <input type="text" name="price" value=" {{ $ticket->price }}" id="point-{{$loop->iteration}}" class="form-control prices-auto numeric-validation" placeholder="@lang('Enter a price')" required />
                                        <div class="input-group-append">
                                            <button type="submit" class="btn--primary input-group-text update-price">@lang('Update')</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            @endif
                            @endforeach
                            @if ($inserted == false)
                            <div class="col-lg-4 col-md-6 col-sm-6">
                                <form action="{{ route('admin.trip.ticket.price.update', 0) }}" class="update-form">
                                    @csrf
                                    <label for="point-{{$loop->iteration}}">{{$sd[0]->name}} - {{$sd[1]->name}}</label>
                                    <div class="input-group mb-3">
                                        <input type="text" name="ticket_price" value="{{ $ticketPrice->id }}" hidden>
                                        <input type="text" name="source" value="{{ $item[0] }}" hidden>
                                        <input type="text" name="destination" value="{{ $item[1] }}" hidden>
                                        <div class="input-group-prepend">
                                            <span class="btn--light input-group-text">{{ $general->cur_sym }}</span>
                                        </div>
                                        <input type="text" name="price" id="point-{{$loop->iteration}}" class="form-control prices-auto numeric-validation" placeholder="@lang('Enter a price')" required />
                                        <div class="input-group-append">
                                            <button type="submit" class="btn--primary input-group-text update-price">@lang('Update')</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            @endif
                        @endif
                    @endforeach
                </div>
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
    'use strict';
    (function($){
        $(".numeric-validation").keypress(function(e){
        var unicode = e.charCode ? e.charCode : e.keyCode
            if (unicode != 8 && e.key != '.' && unicode != 45) {
                if ((unicode < 2534 || unicode > 2543) && (unicode < 48 || unicode > 57)) {
                    return false;
                }
            }
        });

        $(document).on('click', '.update-price', function(e){
            e.preventDefault();
            var form = $(this).parents('.update-form');
            var data = form.serialize();

            $.ajax({
                url: form.attr('action'),
                method:"POST",
                data: data,
                success:function(response){
                    if(response.success) {
                        notify('success', response.message);
                    }else{
                        notify('error', response.message);
                    }
                }
            });
        });
    })(jQuery)
</script>
@endpush
