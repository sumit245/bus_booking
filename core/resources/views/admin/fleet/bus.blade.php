@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-md-12">
        <div class="card b-radius--10 ">
            <div class="card-body">
                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two" id="busTable">
                        <thead>
                            <tr>
                                <th>@lang('Bus Name')</th>
                                <th>@lang('Bus Number')</th>
                                <th>@lang('Route')</th>
                                <th>@lang('Capacity')</th>
                                <th>@lang('No of Deck')</th>
                                <th>@lang('Facilities')</th>
                                <th>@lang('AC')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody id="busTbody">
                            <tr>
                                <td>Volvo AC Sleeper</td>
                                <td>DL01AB1234</td>
                                <td>Delhi - Mumbai</td>
                                <td>40</td>
                                <td>2</td>
                                <td>WiFi, Water</td>
                                <td>AC</td>
                                <td><span class="badge badge--success">Active</span></td>
                                <td>
                                    <a href="{{ route('admin.fleet.edit') }}" class="icon-btn">
                                        <i class="la la-pen"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>Scania Non-AC</td>
                                <td>MH02XY5678</td>
                                <td>Mumbai - Pune</td>
                                <td>45</td>
                                <td>1</td>
                                <td>Charging, Recliner</td>
                                <td>Non AC</td>
                                <td><span class="badge badge--warning">Inactive</span></td>
                                <td>
                                    <a href="{{ route('admin.fleet.edit') }}" class="icon-btn">
                                        <i class="la la-pen"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer py-4"></div>
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
<a href="{{ route('admin.fleet.add') }}" class="btn btn-sm btn--primary box--shadow1 text--small">
    <i class="fa fa-fw fa-plus"></i>@lang('Add New Bus')
</a>
@endpush
