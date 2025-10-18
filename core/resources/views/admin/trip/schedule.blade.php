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
                                    <th>@lang('Start From')</th>
                                    <th>@lang('End At')</th>
                                    <th>@lang('Duration')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($schedules as $item)
                                @php
                                    $start   = Carbon\Carbon::parse($item->start_from);
                                    $end    = Carbon\Carbon::parse($item->end_at);
                                    $diff   = $start->diff($end);
                                @endphp
                                <tr>
                                    <td data-label="@lang('Start From')">
                                        {{ showDateTime($item->start_from, 'h:i a') }}
                                    </td>
                                    <td data-label="@lang('End At')">
                                        {{ showDateTime($item->end_at, 'h:i a') }}
                                    </td>
                                    <td data-label="@lang('Duration')">
                                        {{ __($diff->format('%h hours %i minutes')) }}
                                    </td>
                                    <td data-label="@lang('Status')">
                                        @if($item->status == 1)
                                        <span class="text--small badge font-weight-normal badge--success">@lang('Active')</span>
                                        @else
                                        <span class="text--small badge font-weight-normal badge--warning">@lang('Disabled')</span>
                                        @endif
                                    </td>
                                    <td data-label="@lang('Action')">
                                        <button type="button" class="icon-btn ml-1 editBtn"
                                                data-toggle="modal" data-target="#editModal"
                                                data-start_from="{{ showDateTime($item->start_from, 'H:i') }}"
                                                data-end_at ="{{ showDateTime($item->end_at, 'H:i') }}"
                                                data-action="{{ route('admin.trip.schedule.update', $item->id) }}"
                                                data-original-title="@lang('Update')">
                                            <i class="la la-pen"></i>
                                        </button>

                                        @if ($item->status != 1)
                                            <button type="button"
                                                class="icon-btn btn--success ml-1 activeBtn"
                                                data-toggle="modal" data-target="#activeModal"
                                                data-id="{{ $item->id }}"
                                                data-original-title="@lang('Active')">
                                                <i class="la la-eye"></i>
                                            </button>
                                        @else
                                            <button type="button"
                                                class="icon-btn btn--danger ml-1 disableBtn"
                                                data-toggle="modal" data-target="#disableModal"
                                                data-id="{{ $item->id }}"
                                                data-original-title="@lang('Disable')">
                                                <i class="la la-eye-slash"></i>
                                            </button>
                                        @endif
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
                    {{ paginateLinks($schedules) }}
                </div>
            </div>
        </div>
    </div>


    {{-- Add METHOD MODAL --}}
    <div id="addModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Add Schedule')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.trip.schedule.store')}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Start From')</label>
                            <div class="input-group clockpicker">
                                <input type="text" class="form-control" placeholder="--:--" name="start_from" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('End At')</label>
                            <div class="input-group clockpicker">
                                <input type="text" class="form-control" placeholder="--:--" name="end_at" autocomplete="off" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--primary">@lang('Save')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Update METHOD MODAL --}}
    <div id="editModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Update Schedule')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Start From')</label>
                            <div class="input-group clockpicker">
                                <input type="text" class="form-control" placeholder="--:--" name="start_from" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('End At')</label>
                            <div class="input-group clockpicker">
                                <input type="text" class="form-control" placeholder="--:--" name="end_at" autocomplete="off" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--primary">@lang('Update')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- active METHOD MODAL --}}
    <div id="activeModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Active Schedule')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.trip.schedule.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to active') <span class="font-weight-bold">@lang('this')</span> @lang('schedule')?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--primary">@lang('Active')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- disable METHOD MODAL --}}
    <div id="disableModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Disable Schedule')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.trip.schedule.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to disable') <span class="font-weight-bold">@lang('this')</span> @lang('schedule')?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--danger">@lang('Disable')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('breadcrumb-plugins')
    <a href="javascript:void(0)" class="btn btn-sm btn--primary box--shadow1 text--small addBtn"><i class="fa fa-fw fa-plus"></i>@lang('Add New')</a>
@endpush

@push('style-lib')
   <link rel="stylesheet" href="{{ asset('assets/admin/css/vendor/bootstrap-clockpicker.min.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/vendor/bootstrap-clockpicker.min.js') }}"></script>
@endpush

@push('script')
    <script>
        (function ($) {
            "use strict";

            $('#addModal').on('shown.bs.modal', function (e) {
                $(document).off('focusin.modal');
            });

            $('#editModal').on('shown.bs.modal', function (e) {
                $(document).off('focusin.modal');
            });

            $('.addBtn').on('click', function () {
                var modal = $('#addModal');
                modal.modal('show');
            });

            $('.addBtn').on('click', function () {
                var modal = $('#addModal');
                modal.modal('show');
            });

            $('.disableBtn').on('click', function () {
                var modal = $('#disableModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.modal('show');
            });

            $('.activeBtn').on('click', function () {
                var modal = $('#activeModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.modal('show');
            });

            $('.editBtn').on('click', function () {
                var modal = $('#editModal');
                var route = $(this).data('route');
                modal.find('form').attr('action' ,$(this).data('action'));
                modal.find('input[name=start_from]').val($(this).data('start_from'));
                modal.find('input[name=end_at]').val($(this).data('end_at'));
                modal.modal('show');
            });

            $('.clockpicker').clockpicker({
                placement: 'bottom',
                align: 'right',
                donetext: 'Done',
                autoclose:true,
            });
        })(jQuery);
    </script>

@endpush
