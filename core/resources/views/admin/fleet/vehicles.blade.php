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
                                    <th>@lang('Nick Name')</th>
                                    <th>@lang('Reg. No.')</th>
                                    <th>@lang('Engine No.')</th>
                                    <th>@lang('Chasis No.')</th>
                                    <th>@lang('Model No.')</th>
                                    <th>@lang('Fleet Type')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($vehicles as $item)
                                <tr>
                                    <td data-label="@lang('Nick Name')">
                                        {{ __($item->nick_name) }}
                                    </td>
                                    <td data-label="@lang('Reg. No.')">
                                        {{ __($item->register_no) }}
                                    </td>
                                    <td data-label="@lang('Engine No.')">
                                        {{ __($item->engine_no) }}
                                    </td>
                                    <td data-label="@lang('Chasis No.')">
                                        {{ __($item->chasis_no) }}
                                    </td>
                                    <td data-label="@lang('Model No.')">
                                        {{ __($item->model_no) }}
                                    </td>
                                    <td data-label="@lang('Fleet Type')">
                                        {{ __($item->fleetType->name) }}
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
                                                data-vehicle="{{ $item }}"
                                                data-action="{{ route('admin.fleet.vehicles.update', $item->id) }}"
                                                data-original-title="@lang('Update')">
                                            <i class="la la-pen"></i>
                                        </button>
                                        @if ($item->status != 1)
                                            <button type="button"
                                            class="icon-btn btn--success ml-1 activeBtn"
                                            data-toggle="modal" data-target="#activeModal"
                                            data-id="{{ $item->id }}"
                                            data-nick_name="{{ $item->nick_name }}"
                                            data-register_no="{{ $item->register_no }}"
                                            data-original-title="@lang('Active')">
                                            <i class="la la-eye"></i>
                                        </button>
                                        @else
                                            <button type="button"
                                                class="icon-btn btn--danger ml-1 disableBtn"
                                                data-toggle="modal" data-target="#disableModal"
                                                data-id="{{ $item->id }}"
                                                data-nick_name="{{ $item->nick_name }}"
                                                data-register_no="{{ $item->register_no }}"
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
                    {{ paginateLinks($vehicles) }}
                </div>
            </div>
        </div>
    </div>


    {{-- Add METHOD MODAL --}}
    <div id="addModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Add Vehicle')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.vehicles.store')}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Nick Name')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter nick name')" name="nick_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Fleet Type')</label>
                            <select name="fleet_type" class="form-control">
                                <option value="">@lang('Select an option')</option>
                                @foreach ($fleetType as $item)
                                    <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Register No.')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Reg. No.')" name="register_no" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Engine No.')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Eng. No.')" name="engine_no" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Chasis No.')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Chasis No.')" name="chasis_no" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Model No.')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Model No.')" name="model_no" required>
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


    {{-- Edit METHOD MODAL --}}
    <div id="editModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Update Vehicle')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Nick Name')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter nick name')" name="nick_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Fleet Type')</label>
                            <select name="fleet_type" class="form-control">
                                <option value="">@lang('Select an option')</option>
                                @foreach ($fleetType as $item)
                                    <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Register No.')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Reg. No.')" name="register_no" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Engine No.')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Eng. No.')" name="engine_no" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Chasis No.')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Chasis No.')" name="chasis_no" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Model No.')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Model No.')" name="model_no" required>
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
                    <h5 class="modal-title"> @lang('Active Vehicle')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.vehicles.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to active') <span class="font-weight-bold nick_name"></span> @lang('Vehicle')?</p>
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
                    <h5 class="modal-title"> @lang('Disable Vehicle')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.vehicles.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to disable') <span class="font-weight-bold nick_name"></span> @lang('Vehicle')?</p>
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
    <a href="javascript:void(0)" class="btn btn--primary box--shadow1 addBtn"><i class="fa fa-fw fa-plus"></i>@lang('Add New')</a>
    <form action="{{route('admin.fleet.vehicles.search') }}" method="GET" class="form-inline float-sm-right bg--white mb-2 ml-0 ml-xl-2 ml-lg-0">
        <div class="input-group has_append  ">
            <input type="text" name="search" class="form-control" placeholder="@lang('Reg. No.')" value="{{ $search ?? '' }}">
            <div class="input-group-append">
                <button class="btn btn--primary" type="submit"><i class="fa fa-search"></i></button>
            </div>
        </div>
    </form>
@endpush

@push('script')
<script>
    (function ($) {
        "use strict";

        $('.disableBtn').on('click', function () {
            var modal = $('#disableModal');
            modal.find('input[name=id]').val($(this).data('id'));
            modal.find('.nick_name').text($(this).data('nick_name') + "(" + $(this).data('register_no') + ")");
            modal.modal('show');
        });

        $('.activeBtn').on('click', function () {
            var modal = $('#activeModal');
            modal.find('input[name=id]').val($(this).data('id'));
            modal.find('.nick_name').text($(this).data('nick_name') + "(" + $(this).data('register_no') + ")");
            modal.modal('show');
        });
        $('.addBtn').on('click', function () {
            var modal = $('#addModal');
            modal.modal('show');
        });

        $('.editBtn').on('click', function () {
            var modal = $('#editModal');
            modal.find('form').attr('action' ,$(this).data('action'));
            var vehicle = $(this).data('vehicle');
            modal.find('input[name=nick_name]').val(vehicle.nick_name);
            modal.find('select[name=fleet_type]').val(vehicle.fleet_type_id);
            modal.find('input[name=register_no]').val(vehicle.register_no);
            modal.find('input[name=engine_no]').val(vehicle.engine_no);
            modal.find('input[name=chasis_no]').val(vehicle.chasis_no);
            modal.find('input[name=model_no]').val(vehicle.model_no);
            modal.modal('show');
        });

    })(jQuery);

</script>
@endpush
