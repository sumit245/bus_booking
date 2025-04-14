@extends($activeTemplate.'layouts.master')
@section('content')
<div class="container padding-top padding-bottom">
    <div class="row justify-content-center gy-4">
        @foreach($gatewayCurrency as $data)
        <div class="col-lg-4 col-md-6 col-xl-3">
            <div class="card cmn--card card-deposit">
                <div class="card-header text-center">
                    <h5 class="title">{{__($data->name)}}</h5>
                </div>
                <div class="card-body card-body-deposit">
                    <img src="{{$data->methodImage()}}" class="card-img-top" alt="{{__($data->name)}}" class="w-100">
                </div>
                <div class="card-footer pt-0 pb-4">
                    <a href="javascript:void(0)" data-id="{{$data->id}}" data-name="{{$data->name}}" data-currency="{{$data->currency}}" data-method_code="{{$data->method_code}}" data-base_symbol="{{$data->baseSymbol()}}" data-percent_charge="{{showAmount($data->percent_charge)}}" data-booked_ticket="{{ $bookedTicket }}" class=" btn btn--base w-100 deposit" data-bs-toggle="modal" data-bs-target="#depositModal">
                        @lang('Pay Now')
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<div class="modal fade" id="depositModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title method-name" id="depositModalLabel"></h5>
                <a href="javascript:void(0)" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </a>
            </div>
            <form action="{{route('user.deposit.insert')}}" method="post">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <input type="hidden" name="currency" class="edit-currency">
                        <input type="hidden" name="method_code" class="edit-method-code">
                    </div>
                    <span>@lang('Are you sure, you want to payment via') <strong class="method-name font-weight-bold"></strong> ?</span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--danger w-auto btn--sm" data-bs-dismiss="modal">@lang('Close')</button>
                    <div class="prevent-double-click">
                        <button type="submit" class="btn btn--success confirm-btn btn--sm">@lang('Confirm')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function($) {
        "use strict";
        $('.deposit').on('click', function() {
            var bookedTicket = $(this).data('booked_ticket');
            var name = $(this).data('name');
            var currency = $(this).data('currency');
            var method_code = $(this).data('method_code');
            var baseSymbol = "{{$general->cur_text}}";

            $('.method-name').text(`@lang('Payment By ') ${name}`);
            $('.currency-addon').text(baseSymbol);
            $('.edit-currency').val(currency);
            $('.edit-method-code').val(method_code);
            $('#amount').val(parseFloat(bookedTicket.sub_total));
        });
    })(jQuery);
</script>
@endpush
