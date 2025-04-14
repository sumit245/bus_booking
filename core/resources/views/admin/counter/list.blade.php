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
                                    <th>@lang('Name')</th>
                                    <th>@lang('Mobile Number')</th>
                                    <th>@lang('City')</th>
                                    <th>@lang('Location')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($counters as $item)
                                <tr>
                                    <td data-label="@lang('Name')">
                                        {{ __($item->name) }}
                                    </td>
                                    <td data-label="@lang('Mobile Number')">
                                        {{ __($item->mobile) }}
                                    </td>
                                    <td data-label="@lang('City')">
                                        {{ __($item->city) }}
                                    </td>
                                    <td data-label="@lang('Location')">
                                        {{ __($item->location) }}
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
                                                data-counter="{{ $item }}"
                                                data-action="{{ route('admin.manage.counter.update', $item->id) }}"
                                                data-original-title="@lang('Update')">
                                            <i class="la la-pen"></i>
                                        </button>

                                        @if ($item->status != 1)
                                            <button type="button"
                                            class="icon-btn btn--success ml-1 activeBtn"
                                            data-toggle="modal" data-target="#activeModal"
                                            data-id="{{ $item->id }}"
                                            data-name="{{ $item->name }}"
                                            data-original-title="@lang('Active')">
                                            <i class="la la-eye"></i>
                                        </button>
                                        @else
                                            <button type="button"
                                                class="icon-btn btn--danger ml-1 disableBtn"
                                                data-toggle="modal" data-target="#disableModal"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
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
            </div>
        </div>
    </div>


    {{-- Add METHOD MODAL --}}
    <div id="addModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Add Counter')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.manage.counter.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Name')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Counter Name')" name="name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('City')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter City Name')" name="city" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Location')</label>
                            <textarea name="location" class="form-control" placeholder="@lang('Enter Location')"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Mobile')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Counter Contact Number')" name="mobile">
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
                    <h5 class="modal-title"> @lang('Update Counter')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Name')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Counter Name')" name="name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('City')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter City Name')" name="city" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Location')</label>
                            <textarea name="location" class="form-control" placeholder="@lang('Enter Location')"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Mobile')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Counter Contact Number')" name="mobile">
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
                    <h5 class="modal-title"> @lang('Active Counter')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.manage.counter.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to active') <span class="font-weight-bold name"></span> @lang('Counter')?</p>
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
                    <h5 class="modal-title">@lang('Disable Counter')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.manage.counter.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to disable') <span class="font-weight-bold name"></span> @lang('Counter')?</p>
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

@push('script')

    <script>
        (function ($) {
            "use strict";

            $('.disableBtn').on('click', function () {
                var modal = $('#disableModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.find('.name').text($(this).data('name'));
                modal.modal('show');
            });

            $('.activeBtn').on('click', function () {
                var modal = $('#activeModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.find('.name').text($(this).data('name'));
                modal.modal('show');
            });

            $('.addBtn').on('click', function () {
                var modal = $('#addModal');
                modal.modal('show');
            });

            $('.editBtn').on('click', function () {
                var modal = $('#editModal');
                var counter = $(this).data('counter');
                modal.find('form').attr('action' ,$(this).data('action'));
                modal.find('input[name=name]').val(counter.name);
                modal.find('input[name=city]').val(counter.city);
                modal.find('input[name=mobile]').val(counter.mobile);
                modal.find('textarea[name=location]').val(counter.location);
                modal.modal('show');
            });

        })(jQuery);

    </script>

@endpush
