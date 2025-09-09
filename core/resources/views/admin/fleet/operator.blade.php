@extends('admin.layouts.app')

@php
    $search = $search ?? '';
    $emptyMessage = $emptyMessage ?? 'No operators found';
    $initialOperators = $initialOperators ?? [
        (object)[
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+91 9876543210',
            'company_name' => 'ABC Travels',
            'address' => 'New Delhi',
            'status' => 1,
            'license_no' => 'LIC-12345',
            'pan_no' => 'PAN1111A'
        ],
        (object)[
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+91 9123456780',
            'company_name' => 'XYZ Tours',
            'address' => 'Mumbai',
            'status' => 0,
            'license_no' => 'LIC-67890',
            'pan_no' => 'PAN2222B'
        ],
    ];
@endphp

@section('panel')
<div class="row mb-3">
    <div class="col-md-8"><h4 class="mb-0"></h4></div>
    <div class="col-md-4 text-right">
        <a href="javascript:void(0)" class="btn btn--primary box--shadow1 addBtn"><i class="fa fa-fw fa-plus"></i>@lang('Add New')</a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card b-radius--10">
            <div class="card-body">
                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two" id="operatorsTable">
                        <thead>
                            <tr>
                                <th>@lang('Operator Name')</th>
                                <th>@lang('Email')</th>
                                <th>@lang('Phone')</th>
                                <th>@lang('Company Name')</th>
                                <th>@lang('Address')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody id="operatorsTbody">
                            @foreach($initialOperators as $op)
                            <tr data-operator='@json($op)'>
                                <td data-label="@lang('Operator Name')">{{ $op->name }}</td>
                                <td data-label="@lang('Email')">{{ $op->email }}</td>
                                <td data-label="@lang('Phone')">{{ $op->phone }}</td>
                                <td data-label="@lang('Company Name')">{{ $op->company_name }}</td>
                                <td data-label="@lang('Address')">{{ $op->address }}</td>
                                <td data-label="@lang('Status')">
                                    @if($op->status == 1)
                                        <span class="text--small badge font-weight-normal badge--success">@lang('Active')</span>
                                    @else
                                        <span class="text--small badge font-weight-normal badge--warning">@lang('Disabled')</span>
                                    @endif
                                </td>
                                <td data-label="@lang('Action')">
                                    <button type="button" class="icon-btn editRowBtn" title="@lang('Edit')"><i class="la la-pen"></i></button>
                                    @if ($op->status != 1)
                                        <button type="button" class="icon-btn toggleStatusBtn btn--success" title="@lang('Activate')"><i class="la la-eye"></i></button>
                                    @else
                                        <button type="button" class="icon-btn toggleStatusBtn btn--danger" title="@lang('Disable')"><i class="la la-eye-slash"></i></button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            @if(count($initialOperators) == 0)
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

<div id="addModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="addOperatorForm" action="#" method="POST" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Add Operator') &nbsp; <small id="addStepLabel" class="text-muted">Step 1 of 3</small></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="operatorTab" role="tablist">
                        <li class="nav-item"><a class="nav-link active" id="tab-basic-btn" data-toggle="tab" href="#tab-basic" role="tab">1. Basic</a></li>
                        <li class="nav-item"><a class="nav-link" id="tab-company-btn" data-toggle="tab" href="#tab-company" role="tab">2. Company</a></li>
                        <li class="nav-item"><a class="nav-link" id="tab-details-btn" data-toggle="tab" href="#tab-details" role="tab">3. Details</a></li>
                    </ul>
                    <div class="tab-content mt-3">
                        <div class="tab-pane fade show active" id="tab-basic" role="tabpanel">
                            <div class="form-group">
                                <label class="font-weight-bold">@lang('Operator Name') <small class="text-danger">*</small></label>
                                <input type="text" id="add_name" class="form-control" name="name" required placeholder="@lang('Enter operator name')">
                                <div class="invalid-feedback">Please enter a name.</div>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">@lang('Email') <small class="text-danger">*</small></label>
                                <input type="email" id="add_email" class="form-control" name="email" required placeholder="@lang('Enter email')">
                                <div class="invalid-feedback">Please enter a valid email.</div>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">@lang('Phone') <small class="text-danger">*</small></label>
                                <input type="text" id="add_phone" class="form-control" name="phone" required placeholder="@lang('Enter phone')">
                                <div class="invalid-feedback">Please enter a phone number.</div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-company" role="tabpanel">
                            <div class="form-group">
                                <label class="font-weight-bold">@lang('Company Name') <small class="text-danger">*</small></label>
                                <input type="text" id="add_company_name" class="form-control" name="company_name" required placeholder="@lang('Enter company name')">
                                <div class="invalid-feedback">Please enter a company name.</div>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">@lang('Address') <small class="text-danger">*</small></label>
                                <textarea id="add_address" class="form-control" name="address" rows="3" required placeholder="@lang('Enter address')"></textarea>
                                <div class="invalid-feedback">Please enter an address.</div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="font-weight-bold">@lang('City')</label>
                                    <input type="text" id="add_city" class="form-control" name="city" placeholder="@lang('City')">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="font-weight-bold">@lang('State')</label>
                                    <input type="text" id="add_state" class="form-control" name="state" placeholder="@lang('State')">
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-details" role="tabpanel">
                            <div class="form-group">
                                <label class="font-weight-bold">@lang('License No.')</label>
                                <input type="text" id="add_license_no" class="form-control" name="license_no" placeholder="@lang('License number')">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">@lang('PAN No.')</label>
                                <input type="text" id="add_pan_no" class="form-control" name="pan_no" placeholder="@lang('PAN number')">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">@lang('Status')</label>
                                <select id="add_status" class="form-control" name="status">
                                    <option value="1">@lang('Active')</option>
                                    <option value="0">@lang('Disabled')</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">@lang('Notes')</label>
                                <textarea id="add_notes" class="form-control" name="notes" rows="2" placeholder="@lang('Any extra notes')"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark prevBtn" style="display:none;">@lang('Previous')</button>
                    <button type="button" class="btn btn--secondary nextBtn">@lang('Next')</button>
                    <button type="submit" class="btn btn--primary submitBtn" style="display:none;">@lang('Add Operator')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editOperatorForm" action="#" method="POST" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Edit Operator')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_row_id">
                    <div class="form-group">
                        <label class="font-weight-bold">@lang('Operator Name') <small class="text-danger">*</small></label>
                        <input type="text" id="edit_name" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">@lang('Email') <small class="text-danger">*</small></label>
                        <input type="email" id="edit_email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">@lang('Phone') <small class="text-danger">*</small></label>
                        <input type="text" id="edit_phone" class="form-control" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">@lang('Company Name')</label>
                        <input type="text" id="edit_company_name" class="form-control" name="company_name">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">@lang('Address')</label>
                        <textarea id="edit_address" class="form-control" name="address" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">@lang('Status')</label>
                        <select id="edit_status" class="form-control" name="status">
                            <option value="1">@lang('Active')</option>
                            <option value="0">@lang('Disabled')</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                    <button type="submit" class="btn btn--primary">@lang('Save Changes')</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
<form action="#" method="GET" class="form-inline float-sm-right bg--white mb-2 ml-0 ml-xl-2 ml-lg-0">
    <div class="input-group has_append">
        <input type="text" name="search" class="form-control" placeholder="@lang('Search by name or email')" value="{{ $search }}">
        <div class="input-group-append"><button class="btn btn--primary" type="submit"><i class="fa fa-search"></i></button></div>
    </div>
</form>
@endpush

@push('script')
<script>
(function ($) {
    "use strict";
    function showStepLabel(i, total) { $('#addStepLabel').text('Step ' + i + ' of ' + total); }
    function goToTab(index) {
        var tabs = $('#operatorTab .nav-link'), total = tabs.length;
        tabs.removeClass('active').eq(index - 1).addClass('active').tab('show');
        $('.prevBtn').toggle(index !== 1);
        $('.nextBtn').toggle(index !== total);
        $('.submitBtn').toggle(index === total);
        showStepLabel(index, total);
    }
    $('.nextBtn').on('click', function () {
        var activePane = $('.tab-pane.show.active'), invalid = false;
        activePane.find('[required]').each(function () {
            var val = $(this).val() ? $(this).val().trim() : '';
            $(this).removeClass('is-invalid');
            if (!val) { $(this).addClass('is-invalid'); invalid = true; }
            else if ($(this).attr('type') === 'email' && !/\S+@\S+\.\S+/.test(val)) { $(this).addClass('is-invalid'); invalid = true; }
        });
        if (invalid) { activePane.find('.is-invalid').first().focus(); return; }
        goToTab($('#operatorTab .nav-link').index($('#operatorTab .nav-link.active')) + 2);
    });
    $('.prevBtn').on('click', function () { goToTab($('#operatorTab .nav-link').index($('#operatorTab .nav-link.active'))); });
    $('.addBtn').on('click', function () {
        $('#addOperatorForm')[0].reset();
        $('#addOperatorForm').find('.is-invalid').removeClass('is-invalid');
        goToTab(1);
        $('#addModal').modal('show');
    });
    $('#addOperatorForm').on('submit', function (e) {
        e.preventDefault();
        var invalid = false;
        $(this).find('[required]').each(function () {
            var val = $(this).val() ? $(this).val().trim() : '';
            $(this).removeClass('is-invalid');
            if (!val) { $(this).addClass('is-invalid'); invalid = true; }
        });
        if (invalid) { 
            var inv = $(this).find('.is-invalid').first();
            goToTab($('.tab-pane').index(inv.closest('.tab-pane')) + 1);
            return;
        }
        var operator = {
            id: Date.now(),
            name: $('#add_name').val().trim(),
            email: $('#add_email').val().trim(),
            phone: $('#add_phone').val().trim(),
            company_name: $('#add_company_name').val().trim(),
            address: $('#add_address').val().trim(),
            city: $('#add_city').val().trim(),
            state: $('#add_state').val().trim(),
            license_no: $('#add_license_no').val().trim(),
            pan_no: $('#add_pan_no').val().trim(),
            status: parseInt($('#add_status').val()) || 0,
            notes: $('#add_notes').val().trim()
        };
        var statusBadge = operator.status === 1 ? '<span class="text--small badge font-weight-normal badge--success">Active</span>' : '<span class="text--small badge font-weight-normal badge--warning">Disabled</span>';
        var toggleBtn = operator.status === 1 ? '<button type="button" class="icon-btn toggleStatusBtn btn--danger" title="Disable"><i class="la la-eye-slash"></i></button>' : '<button type="button" class="icon-btn toggleStatusBtn btn--success" title="Activate"><i class="la la-eye"></i></button>';
        var row = $('<tr>', { 'data-operator': JSON.stringify(operator) });
        row.append($('<td>').attr('data-label', 'Operator Name').text(operator.name));
        row.append($('<td>').attr('data-label', 'Email').text(operator.email));
        row.append($('<td>').attr('data-label', 'Phone').text(operator.phone));
        row.append($('<td>').attr('data-label', 'Company Name').text(operator.company_name));
        row.append($('<td>').attr('data-label', 'Address').text(operator.address));
        row.append($('<td>').attr('data-label', 'Status').html(statusBadge));
        var actions = $('<td>').attr('data-label', 'Action');
        actions.append('<button type="button" class="icon-btn editRowBtn" title="Edit"><i class="la la-pen"></i></button> ');
        actions.append(toggleBtn);
        row.append(actions);
        $('#operatorsTbody').append(row);
        $('#addModal').modal('hide');
        alert('Operator added (frontend only).');
    });
    $('#operatorsTbody').on('click', '.editRowBtn', function () {
        var tr = $(this).closest('tr'), operator = tr.attr('data-operator');
        if (typeof operator === 'string') { try { operator = JSON.parse(operator); } catch (e) { operator = {}; } }
        $('#edit_row_id').val(tr.index());
        $('#edit_name').val(operator.name || '');
        $('#edit_email').val(operator.email || '');
        $('#edit_phone').val(operator.phone || '');
        $('#edit_company_name').val(operator.company_name || '');
        $('#edit_address').val(operator.address || '');
        $('#edit_status').val(operator.status != null ? operator.status : 0);
        $('#editModal').modal('show');
    });
    $('#editOperatorForm').on('submit', function (e) {
        e.preventDefault();
        var idx = parseInt($('#edit_row_id').val()), tr = $('#operatorsTbody tr').eq(idx);
        if (!tr || tr.length === 0) {
            var email = $('#edit_email').val().trim();
            tr = $('#operatorsTbody tr').filter(function () { return $(this).find('td').eq(1).text().trim() === email; }).first();
            if (!tr || tr.length === 0) { alert('Row not found for update (client-side).'); return; }
        }
        var updated = {
            id: Date.now(),
            name: $('#edit_name').val().trim(),
            email: $('#edit_email').val().trim(),
            phone: $('#edit_phone').val().trim(),
            company_name: $('#edit_company_name').val().trim(),
            address: $('#edit_address').val().trim(),
            status: parseInt($('#edit_status').val()) || 0
        };
        tr.attr('data-operator', JSON.stringify(updated));
        tr.find('td').eq(0).text(updated.name);
        tr.find('td').eq(1).text(updated.email);
        tr.find('td').eq(2).text(updated.phone);
        tr.find('td').eq(3).text(updated.company_name);
        tr.find('td').eq(4).text(updated.address);
        tr.find('td').eq(5).html(updated.status === 1 ? '<span class="text--small badge font-weight-normal badge--success">Active</span>' : '<span class="text--small badge font-weight-normal badge--warning">Disabled</span>');
        var toggleBtn = updated.status === 1 ? '<button type="button" class="icon-btn toggleStatusBtn btn--danger" title="Disable"><i class="la la-eye-slash"></i></button>' : '<button type="button" class="icon-btn toggleStatusBtn btn--success" title="Activate"><i class="la la-eye"></i></button>';
        tr.find('td').eq(6).find('.toggleStatusBtn').remove();
        tr.find('td').eq(6).append(toggleBtn);
        $('#editModal').modal('hide');
        alert('Operator updated (frontend only).');
    });
    $('#operatorsTbody').on('click', '.toggleStatusBtn', function () {
        var tr = $(this).closest('tr'), operator = tr.attr('data-operator');
        if (typeof operator === 'string') { try { operator = JSON.parse(operator); } catch (e) { operator = {}; } }
        operator.status = operator.status === 1 ? 0 : 1;
        tr.attr('data-operator', JSON.stringify(operator));
        tr.find('td').eq(5).html(operator.status === 1 ? '<span class="text--small badge font-weight-normal badge--success">Active</span>' : '<span class="text--small badge font-weight-normal badge--warning">Disabled</span>');
        var $actionsTd = tr.find('td').eq(6);
        $actionsTd.find('.toggleStatusBtn').remove();
        var toggleBtn = operator.status === 1 ? '<button type="button" class="icon-btn toggleStatusBtn btn--danger" title="Disable"><i class="la la-eye-slash"></i></button>' : '<button type="button" class="icon-btn toggleStatusBtn btn--success" title="Activate"><i class="la la-eye"></i></button>';
        $actionsTd.append(toggleBtn);
    });
    goToTab(1);
})(jQuery);
</script>
@endpush