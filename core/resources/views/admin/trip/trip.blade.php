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
                                    <th>@lang('Title')</th>
                                    <th>@lang('AC / Non-AC')</th>
                                    <th>@lang('Day Off')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($trips as $item)
                                <tr>
                                    <td data-label="@lang('Title')">
                                        {{ __($item->title) }}
                                    </td>

                                    <td data-label="@lang('AC / Non-AC')">
                                          hello
                                    </td>

                                    <td data-label="@lang('Day Off')">
                                        @if($item->day_off)
                                            @foreach ($item->day_off as $day)
                                                {{ __(showDayOff($day)) }} @if(!$loop->last) , @endif
                                            @endforeach
                                        @else
                                            @lang('No Off Day')
                                        @endif
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
                                                data-trip="{{ $item }}"
                                                data-action="{{ route('admin.trip.update', $item->id) }}"
                                                data-original-title="@lang('Update')">
                                            <i class="la la-pen"></i>
                                        </button>

                                        @if ($item->status != 1)
                                            <button type="button"
                                                class="icon-btn btn--success ml-1 activeBtn"
                                                data-toggle="modal" data-target="#activeModal"
                                                data-id="{{ $item->id }}"
                                                data-trip_title = "{{ $item->title }}"
                                                data-original-title="@lang('Active')">
                                                <i class="la la-eye"></i>
                                            </button>
                                        @else
                                            <button type="button"
                                                class="icon-btn btn--danger ml-1 disableBtn"
                                                data-toggle="modal" data-target="#disableModal"
                                                data-id="{{ $item->id }}"
                                                data-trip_title = "{{ $item->title }}"
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
                    {{ paginateLinks($trips) }}
                </div>
            </div>
        </div>
    </div>


    {{-- Add METHOD MODAL --}}
    <div id="addModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Add Trip')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.trip.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Title')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Title')" name="title" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Fleet Type')</label>
                            <select name="fleet_type" class="select2-basic fleet_type1" required>
                                <option value="">@lang('Select an option')</option>
                                @foreach ($fleetTypes as $item)
                                    <option value="{{ $item->id }}" data-name="{{$item->name}}">{{ __($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Route')</label>
                            <select name="route" class="select2-basic route1" required>
                                <option value="">@lang('Select an option')</option>
                                @foreach ($routes as $item)
                                    <option value="{{ $item->id }}"  data-name="{{$item->name}}">{{ __($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Schedule')</label>
                            <select name="schedule" class="select2-basic schedule1" required>
                                <option value="">@lang('Select an option')</option>
                                @foreach ($schedules as $item)
                                    <option value="{{ $item->id }}" data-name="{{ showDateTime($item->start_from, 'h:i a').' - '. showDateTime($item->end_to, 'h:i a') }}">{{ __(showDateTime($item->start_from, 'h:i a').' - '. showDateTime($item->end_to, 'h:i a')) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Start From')</label>
                            <select name="start_from" class="select2-basic start_form1" required>
                                <option value="">@lang('Select an option')</option>
                                @foreach ($stoppages as $item)
                                    <option value="{{ $item->id }}" data-name="{{$item->name}}">{{ __($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('End To')</label>
                            <select name="end_to" class="select2-basic end_to1" required>
                                <option value="">@lang('Select an option')</option>
                                @foreach ($stoppages as $item)
                                    <option value="{{ $item->id }}" data-name="{{$item->name}}">{{ __($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold" for="day_off">@lang('Day Off')</label>
                            <select class="select2-basic" name="day_off[]" id="day_off"  multiple="multiple">
                                <option value="0">@lang('Sunday')</option>
                                <option value="1">@lang('Monday')</option>
                                <option value="2">@lang('Tuesday')</option>
                                <option value="3">@lang('Wednesday')</option>
                                <option value="4">@lang('Thursday')</option>
                                <option value="5">@lang('Friday')</option>
                                <option value="6">@lang('Saturday')</option>
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
                    <h5 class="modal-title"> @lang('Update Trip')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Title')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Title')" name="title" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Fleet Type')</label>
                            <select name="fleet_type" class="select2-basic fleet_type2" required>
                                <option value="">@lang('Select an option')</option>
                                @foreach ($fleetTypes as $item)
                                    <option value="{{ $item->id }}" data-name="{{$item->name}}">{{ __($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Route')</label>
                            <select name="route" class="select2-basic route2" required>
                                <option value="">@lang('Select an option')</option>
                                @foreach ($routes as $item)
                                    <option value="{{ $item->id }}"  data-name="{{$item->name}}">{{ __($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Schedule')</label>
                            <select name="schedule" class="select2-basic schedule2" required>
                                <option value="">@lang('Select an option')</option>
                                @foreach ($schedules as $item)
                                    <option value="{{ $item->id }}" data-name="{{ showDateTime($item->start_from, 'h:i a').' - '. showDateTime($item->end_to, 'h:i a') }}">{{ __(showDateTime($item->start_from, 'h:i a').' - '. showDateTime($item->end_to, 'h:i a')) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Start From')</label>
                            <select name="start_from" class="select2-basic start_from2" required>
                                <option value="">@lang('Select an option')</option>
                                @foreach ($stoppages as $item)
                                    <option value="{{ $item->id }}" data-name="{{$item->name}}">{{ __($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('End To')</label>
                            <select name="end_to" class="select2-basic end_to2" required>
                                <option value="">@lang('Select an option')</option>
                                @foreach ($stoppages as $item)
                                    <option value="{{ $item->id }}" data-name="{{$item->name}}">{{ __($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label font-weight-bold" for="day_off_update">@lang('Day Off')</label>
                            <select class="select2-basic" name="day_off[]" id="day_off_update" multiple="multiple">
                                <option value="0">@lang('Sunday')</option>
                                <option value="1">@lang('Monday')</option>
                                <option value="2">@lang('Tuesday')</option>
                                <option value="3">@lang('Wednesday')</option>
                                <option value="4">@lang('Thursday')</option>
                                <option value="5">@lang('Friday')</option>
                                <option value="6">@lang('Saturday')</option>
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
                    <h5 class="modal-title"> @lang('Active Trip')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.trip.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to active') <span class="font-weight-bold trip_title"></span> @lang('trip')?</p>
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
                    <h5 class="modal-title"> @lang('Disable Trip')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.trip.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to disable') <span class="font-weight-bold trip_title"></span> @lang('trip')?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--danger">@lang('Disable')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!--Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Help Message')</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                <div class="modal-body">
                    <div class="container-fluid">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                </div>
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

            $('#addModal').on('shown.bs.modal', function (e) {
                $(document).off('focusin.modal');
            });

            $('#editModal').on('shown.bs.modal', function (e) {
                $(document).off('focusin.modal');
            });

            $('.disableBtn').on('click', function () {
                var modal = $('#disableModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.find('.trip_title').text($(this).data('trip_title'));
                modal.modal('show');
            });

            $('.activeBtn').on('click', function () {
                var modal = $('#activeModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.find('.trip_title').text($(this).data('trip_title'));
                modal.modal('show');
            });

            $('.addBtn').on('click', function () {
                $('input[name=title]').text('')
                var modal = $('#addModal');
                modal.modal('show');
            });

            $('.editBtn').on('click', function () {
                var modal = $('#editModal');
                var trip = $(this).data('trip');
                modal.find('select[name=fleet_type]').data('name', trip.fleet_type.name);
                modal.find('select[name=route]').data('name',trip.route.name);
                modal.find('select[name=schedule]').data('name',trip.schedule.name);
                modal.find('select[name=start_from]').data('name',trip.start_from.name);
                modal.find('select[name=end_to]').data('name',trip.end_to.name);

                modal.find('form').attr('action' ,$(this).data('action'));
                modal.find('select[name=fleet_type]').val(trip.fleet_type_id);
                modal.find('select[name=route]').val(trip.vehicle_route_id);
                modal.find('select[name=schedule]').val(trip.schedule_id);
                modal.find('select[name=start_from]').val(trip.start_from);
                modal.find('select[name=end_to]').val(trip.end_to);
                modal.find('select[name="day_off[]"]').val(trip.day_off).select2();
                $('.select2-basic').select2();
                makeTitle('editModal');
                modal.modal('show');
            });

            $(document).on('change', '.start_from1, .end_to1, .fleet_type1', function () {
                makeTitle('addModal');
            });

            $(document).on('change', '.start_from2, .end_to2, .fleet_type2', function () {
                makeTitle('editModal');
            });


            function makeTitle(modalName){
                var modal = $('#'+ modalName);
                var data1 = modal.find('select[name="fleet_type"]').find("option:selected").data('name');
                var data2 = modal.find('select[name="start_from"]').find("option:selected").data('name');
                var data3 = modal.find('select[name="end_to"]').find("option:selected").data('name');
                var data  = [];
                var title = '';
                if(data1 != undefined){
                    data.push(data1);
                }
                if(data2 != undefined)
                    data.push(data2);
                if(data3 != undefined)
                    data.push(data3);
                if(data1 != undefined && data2 != undefined && data3 != undefined) {
                    var fleet_type_id = modal.find('select[name="fleet_type"]').val();
                    var vehicle_route_id = modal.find('select[name="route"]').val();

                    $.ajax({
                        type: "get",
                        url: "{{ route('admin.trip.ticket.check_price') }}",
                        data: {
                            "fleet_type_id" : fleet_type_id,
                            "vehicle_route_id" : vehicle_route_id
                        },
                        success: function (response) {
                            if(response.error){
                                modal.find('input').val('');
                                modal.find('select').val('').trigger('change');
                                modal.modal('hide');
                                var alertModal = $('#alertModal');
                                alertModal.find('.container-fluid').text(response.error);
                                alertModal.modal('show');
                            }
                        }
                    });
                }

                $.each(data, function (index, value) {
                    if(index > 0){
                        if(index > 3)
                            title += ' to ';
                        else
                            title += ' - ';
                    }
                    title += value;
                });
                $('input[name="title"]').val(title);
            }
        })(jQuery);
    </script>

@endpush
