@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card b-radius--10 ">
                <div class="card-body">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Fleet Type')</th>
                                    <th>@lang('Route')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($prices as $item)
                                <tr>
                                    <td data-label="@lang('Fleet Type')">
                                        {{ __($item->fleetType->name) }}
                                    </td>
                                    <td data-label="@lang('Route')">
                                        {{ __($item->route->name) }}
                                    </td>
                                    <td data-label="@lang('Price')">
                                        <span class="font-weight-bold text-muted">{{ __(showAmount($item->price)) }} {{ __($general->cur_text) }}</span>
                                    </td>
                                    <td data-label="@lang('Action')">
                                        <a href="{{ route('admin.trip.ticket.price.edit', $item->id) }}" class="icon-btn ml-1" data-toggle="tooltip" data-original-title="@lang('Edit')"><i class="la la-pen"></i></a>

                                        <button type="button" class="ml-1 icon-btn btn--danger removeBtn"
                                                data-toggle="modal" data-target="#removeModal"
                                                data-id="{{ $item->id }}"
                                                data-original-title="@lang('Delete')">
                                                <i class="la la-trash"></i>
                                        </button>
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

                <div class="card-footer py-4">
                    {{ paginateLinks($prices) }}
                </div>
            </div>
        </div>
    </div>

    {{-- remove METHOD MODAL --}}
    <div id="removeModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Confirmation Alert')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.trip.ticket.price.delete')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to delete') <span class="font-weight-bold">@lang('this ')</span>@lang('ticket price')?</p>

                        <p class="text-danger">
                            <i class="las la-exclamation-triangle"></i>
                            @lang('Caution: If you delete this all prices for stoppage to stoppage will also be removed')
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--danger">@lang('Delete')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('breadcrumb-plugins')
    <a href="{{ route('admin.trip.ticket.price.create') }}" class="btn btn-sm btn--primary box--shadow1 text--small"><i class="fa fa-fw fa-plus"></i>@lang('Add New')</a>
@endpush

@push('script')
    <script>
        (function ($) {
            "use strict";

            $('.disableBtn').on('click', function () {
                var modal = $('#disableModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.find('.route_name').text($(this).data('route_name'));
                modal.modal('show');
            });

            $('.activeBtn').on('click', function () {
                var modal = $('#activeModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.find('.route_name').text($(this).data('route_name'));
                modal.modal('show');
            });

            $('.removeBtn').on('click', function () {
                var modal = $('#removeModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.modal('show');
            });
        })(jQuery);
    </script>

@endpush
