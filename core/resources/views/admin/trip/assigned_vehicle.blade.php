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
                                    <th>@lang('Trip')</th>
                                    <th>@lang('Vehicle\'s Nick Name')</th>
                                    <th>@lang('Reg. No.')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($assignedVehicles as $item)
                                <tr>
                                    <td data-label="@lang('Trip')">
                                        {{ __($item->trip->title) }}
                                    </td>
                                    <td data-label="@lang('Vehicle\'s Nick Name')">
                                        {{ __($item->vehicle->nick_name) }}
                                    </td>
                                    <td data-label="@lang('Reg. No.')">
                                        {{ __($item->vehicle->register_no) }}
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
                                                data-assigned_vehicle = "{{ $item }}"
                                                data-action="{{ route('admin.trip.assigned.vehicle.update', $item->id) }}"
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
                    {{ paginateLinks($assignedVehicles) }}
                </div>
            </div>
        </div>
    </div>


    {{-- Add METHOD MODAL --}}
    <div id="addModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Assign Vehicle')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.trip.vehicle.assign')}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Trip')</label>
                            <div class="input-group">
                                <select class="select2-basic" name="trip">
                                    <option value="">@lang('Select an option')</option>
                                    @foreach ($trips as $item)
                                        <option value="{{ $item->id }}" data-vehicles="{{ $item->fleetType->activeVehicles }}">{{ __($item->title) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Vehicle')</label>
                            <select class="select2-basic" name="vehicle">
                                <option value="">@lang('Select an option')</option>
                            </select>
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
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Update Assigned Vehicle')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Trip')</label>
                            <div class="input-group">
                                <select class="select2-basic" name="trip">
                                    <option value="">@lang('Select an option')</option>
                                    @foreach ($trips as $item)
                                        <option value="{{ $item->id }}" data-vehicles="{{ $item->fleetType->activeVehicles }}">{{ __($item->title) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Vehicle')</label>
                            <select class="select2-basic" name="vehicle">
                                <option value="">@lang('Select an option')</option>
                            </select>
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
                    <h5 class="modal-title"> @lang('Active Assigned Vehicle')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.trip.assigned.vehicle.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to active') <span class="font-weight-bold">@lang('this')</span> @lang('assigned vehicle?')?</p>
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
                    <h5 class="modal-title"> @lang('Disable Assigned Vehicle')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.trip.assigned.vehicle.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to disable') <span class="font-weight-bold">@lang('this')</span> @lang('assigned vehicle?')?</p>
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
                var data = $(this).data('assigned_vehicle');
                modal.find('form').attr('action' ,$(this).data('action'));
                modal.find('select[name=trip]').val(data.trip_id);

                var vehicles  = modal.find('select[name=trip]').find("option:selected").data('vehicles');
                var options = `<option selected value="">@lang('Select One')</option>`

                $.each(vehicles, function (i, v) {
                    options += `<option value="${v.id}" data-name="${v.register_no}"> ${v.nick_name} (${v.register_no}) </option>`
                });

                modal.find('select[name=vehicle]').html(options);
                modal.find('select[name=vehicle]').val(data.vehicle_id);
                modal.find('.select2-basic').select2();
                modal.modal('show');
            });

            $(document).on('change','select[name="trip"]', function () {
                var vehicles   = $(this).parents('.modal-body').find('select[name="trip"]').find("option:selected").data('vehicles');
                var options = `<option selected value="">@lang('Select an option')</option>`

                $.each(vehicles, function (i, v) {
                    options += `<option value="${v.id}" data-name="${v.register_no}"> ${v.nick_name} (${v.register_no}) </option>`
                });

                $(this).parents('.modal-body').find('select[name=vehicle]').html(options);

            });
        })(jQuery);
    </script>

@endpush
