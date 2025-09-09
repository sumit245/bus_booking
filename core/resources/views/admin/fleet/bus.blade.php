@extends('admin.layouts.app')

@php
    $search = $search ?? '';
    $emptyMessage = $emptyMessage ?? 'No buses found';
    $initialBuses = $initialBuses ?? [
        (object)[
            'id' => 1,
            'bus_name' => 'Volvo AC Sleeper',
            'bus_number' => 'DL01AB1234',
            'route' => 'Delhi - Mumbai',
            'capacity' => 40,
            'status' => 1
        ],
        (object)[
            'id' => 2,
            'bus_name' => 'Scania Non-AC',
            'bus_number' => 'MH02XY5678',
            'route' => 'Mumbai - Pune',
            'capacity' => 45,
            'status' => 0
        ],
    ];
@endphp

@section('panel')
<div class="row mb-3">
    <div class="col-md-8"><h4 class="mb-0"></h4></div>
    <div class="col-md-4 text-right">
        <a href="javascript:void(0)" class="btn btn--primary box--shadow1 addBtn" data-toggle="modal" data-target="#addModal">
            <i class="fa fa-fw fa-plus"></i>@lang('Add New Bus')
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card b-radius--10">
            <div class="card-body">
                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two" id="busesTable">
                        <thead>
                            <tr>
                                <th>@lang('Bus Name')</th>
                                <th>@lang('Bus Number')</th>
                                <th>@lang('Route')</th>
                                <th>@lang('Capacity')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody id="busesTbody">
                            @foreach($initialBuses as $bus)
                            <tr data-id="{{ $bus->id }}">
                                <td>{{ $bus->bus_name }}</td>
                                <td>{{ $bus->bus_number }}</td>
                                <td>{{ $bus->route }}</td>
                                <td>{{ $bus->capacity }}</td>
                                <td>
                                    @if($bus->status == 1)
                                        <span class="badge badge--success">@lang('Active')</span>
                                    @else
                                        <span class="badge badge--warning">@lang('Inactive')</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="icon-btn editRowBtn" title="@lang('Edit')"><i class="la la-pen"></i></button>
                                </td>
                            </tr>
                            @endforeach
                            @if(count($initialBuses) == 0)
                                <tr><td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer py-4"></div>
        </div>
    </div>
</div>

{{-- Add Modal --}}
<div id="addModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addBusForm">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Add Bus')</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>@lang('Bus Name')</label><input type="text" id="add_bus_name" class="form-control" required></div>
                    <div class="form-group"><label>@lang('Bus Number')</label><input type="text" id="add_bus_number" class="form-control" required></div>
                    <div class="form-group"><label>@lang('Route')</label><input type="text" id="add_route" class="form-control" required></div>
                    <div class="form-group"><label>@lang('Capacity')</label><input type="number" id="add_capacity" class="form-control" required></div>
                    <div class="form-group">
                        <label>@lang('Status')</label>
                        <select id="add_status" class="form-control">
                            <option value="1">@lang('Active')</option>
                            <option value="0">@lang('Inactive')</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn--primary">@lang('Save Bus')</button></div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editBusForm">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Edit Bus')</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_row_id">
                    <div class="form-group"><label>@lang('Bus Name')</label><input type="text" id="edit_bus_name" class="form-control" required></div>
                    <div class="form-group"><label>@lang('Bus Number')</label><input type="text" id="edit_bus_number" class="form-control" required></div>
                    <div class="form-group"><label>@lang('Route')</label><input type="text" id="edit_route" class="form-control" required></div>
                    <div class="form-group"><label>@lang('Capacity')</label><input type="number" id="edit_capacity" class="form-control" required></div>
                    <div class="form-group">
                        <label>@lang('Status')</label>
                        <select id="edit_status" class="form-control">
                            <option value="1">@lang('Active')</option>
                            <option value="0">@lang('Inactive')</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn--primary">@lang('Save Changes')</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(function ($) {
    "use strict";

    // Add bus
    $('#addBusForm').on('submit', function(e){
        e.preventDefault();
        let name = $('#add_bus_name').val();
        let number = $('#add_bus_number').val();
        let route = $('#add_route').val();
        let capacity = $('#add_capacity').val();
        let status = $('#add_status').val();
        let statusText = status == 1 ? '<span class="badge badge--success">Active</span>' : '<span class="badge badge--warning">Inactive</span>';

        let newRow = `<tr>
            <td>${name}</td>
            <td>${number}</td>
            <td>${route}</td>
            <td>${capacity}</td>
            <td>${statusText}</td>
            <td><button type="button" class="icon-btn editRowBtn"><i class="la la-pen"></i></button></td>
        </tr>`;

        $('#busesTbody').append(newRow);
        $('#addModal').modal('hide');
        $('#addBusForm')[0].reset();
    });

    // Edit bus
    $(document).on('click', '.editRowBtn', function(){
        let row = $(this).closest('tr');
        $('#edit_row_id').val(row.index());
        $('#edit_bus_name').val(row.find('td:eq(0)').text());
        $('#edit_bus_number').val(row.find('td:eq(1)').text());
        $('#edit_route').val(row.find('td:eq(2)').text());
        $('#edit_capacity').val(row.find('td:eq(3)').text());
        $('#edit_status').val(row.find('td:eq(4)').text().trim() === 'Active' ? 1 : 0);
        $('#editModal').modal('show');
    });

    $('#editBusForm').on('submit', function(e){
        e.preventDefault();
        let rowIndex = $('#edit_row_id').val();
        let row = $('#busesTbody tr').eq(rowIndex);

        row.find('td:eq(0)').text($('#edit_bus_name').val());
        row.find('td:eq(1)').text($('#edit_bus_number').val());
        row.find('td:eq(2)').text($('#edit_route').val());
        row.find('td:eq(3)').text($('#edit_capacity').val());

        let status = $('#edit_status').val();
        let statusText = status == 1 ? '<span class="badge badge--success">Active</span>' : '<span class="badge badge--warning">Inactive</span>';
        row.find('td:eq(4)').html(statusText);

        $('#editModal').modal('hide');
    });

})(jQuery);
</script>
@endpush
