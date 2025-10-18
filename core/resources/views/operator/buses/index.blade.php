@extends('operator.layouts.app')
@section('panel')
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-0">{{ $pageTitle ?? 'Manage Buses' }}</h4>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('operator.buses.create') }}" class="btn btn--primary box--shadow1">
                <i class="fa fa-fw fa-plus"></i>@lang('Add New Bus')
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table--light style--two" id="busesTable">
                            <thead>
                                <tr>
                                    <th>@lang('Bus Number')</th>
                                    <th>@lang('Travel Name')</th>
                                    <th>@lang('Bus Type')</th>
                                    <th>@lang('Current Route')</th>
                                    <th>@lang('Seats')</th>
                                    <th>@lang('Base Price')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Created')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($buses as $bus)
                                    <tr>
                                        <td data-label="@lang('Bus Number')">
                                            <strong>{{ $bus->bus_number }}</strong>
                                            @if ($bus->has_expiring_documents)
                                                <br><small class="text-warning"><i class="la la-exclamation-triangle"></i>
                                                    Documents expiring soon</small>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Travel Name')">
                                            <span class="badge badge--primary">{{ $bus->travel_name }}</span>
                                        </td>
                                        <td data-label="@lang('Bus Type')">
                                            <span class="badge badge--info">{{ $bus->bus_type }}</span>
                                        </td>
                                        <td data-label="@lang('Current Route')">
                                            @if ($bus->currentRoute)
                                                <span class="badge badge--success">
                                                    {{ $bus->currentRoute->originCity->city_name ?? 'N/A' }} →
                                                    {{ $bus->currentRoute->destinationCity->city_name ?? 'N/A' }}
                                                </span>
                                            @else
                                                <span class="badge badge--secondary">@lang('Not Assigned')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Seats')">
                                            <div class="d-flex flex-column">
                                                <span
                                                    class="badge badge--primary">{{ $bus->available_seats }}/{{ $bus->total_seats }}</span>
                                                <small class="text-muted">{{ $bus->occupancy_percentage }}% occupied</small>
                                                @php
                                                    $activeLayout = $bus->activeSeatLayout;
                                                @endphp
                                                @if ($activeLayout)
                                                    <small class="text-success">
                                                        <i class="las la-chair"></i> Layout:
                                                        {{ $activeLayout->layout_name }}
                                                    </small>
                                                @else
                                                    <small class="text-warning">
                                                        <i class="las la-exclamation-triangle"></i> No active layout
                                                    </small>
                                                @endif
                                            </div>
                                        </td>
                                        <td data-label="@lang('Base Price')">
                                            {{ $bus->formatted_base_price }}
                                        </td>
                                        <td data-label="@lang('Status')">
                                            @if ($bus->status)
                                                <span class="badge badge--success">@lang('Active')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Inactive')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Created')">
                                            {{ showDateTime($bus->created_at, 'd M, Y') }}
                                        </td>
                                        <td data-label="@lang('Action')">
                                            <div class="button--group">
                                                <a href="{{ route('operator.buses.show', $bus->id) }}"
                                                    class="btn btn-sm btn--primary" title="@lang('View')">
                                                    <i class="la la-eye"></i>
                                                </a>
                                                <a href="{{ route('operator.buses.edit', $bus->id) }}"
                                                    class="btn btn-sm btn--success" title="@lang('Edit')">
                                                    <i class="la la-pen"></i>
                                                </a>
                                                <a href="{{ route('operator.buses.seat-layouts.index', $bus->id) }}"
                                                    class="btn btn-sm btn--info" title="@lang('Manage Seat Layouts')">
                                                    <i class="las la-chair"></i>
                                                </a>
                                                <form action="{{ route('operator.buses.toggle-status', $bus->id) }}"
                                                    method="POST" style="display: inline-block;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="btn btn-sm {{ $bus->status ? 'btn--warning' : 'btn--success' }}"
                                                        title="{{ $bus->status ? __('Deactivate') : __('Activate') }}"
                                                        onclick="return confirm('Are you sure you want to {{ $bus->status ? 'deactivate' : 'activate' }} this bus?');">
                                                        <i class="la la-{{ $bus->status ? 'ban' : 'check' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('operator.buses.destroy', $bus->id) }}"
                                                    method="POST" style="display: inline-block;"
                                                    onsubmit="return confirm('Are you sure you want to delete this bus?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn--danger"
                                                        title="@lang('Delete')">
                                                        <i class="la la-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">
                                            {{ __('No buses found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($buses->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($buses) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <form action="{{ route('operator.buses.index') }}" method="GET"
        class="form-inline float-sm-right bg--white mb-2 ml-0 ml-xl-2 ml-lg-0">
        <div class="input-group has_append">
            <input type="text" name="search" class="form-control" placeholder="@lang('Search by bus number, travel name')"
                value="{{ request('search') }}">
            <div class="input-group-append">
                <button class="btn btn--primary" type="submit">
                    <i class="fa fa-search"></i>
                </button>
            </div>
        </div>
    </form>

    <!-- Filter by Route -->
    <div class="form-inline float-sm-right bg--white mb-2 ml-0 ml-xl-2 ml-lg-0">
        <form action="{{ route('operator.buses.index') }}" method="GET">
            <div class="input-group has_append">
                <select name="route_id" class="form-control" onchange="this.form.submit()">
                    <option value="">@lang('All Routes')</option>
                    @foreach ($routes as $route)
                        <option value="{{ $route->id }}" {{ request('route_id') == $route->id ? 'selected' : '' }}>
                            {{ $route->originCity->city_name ?? 'N/A' }} →
                            {{ $route->destinationCity->city_name ?? 'N/A' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <!-- Filter by Status -->
    <div class="form-inline float-sm-right bg--white mb-2 ml-0 ml-xl-2 ml-lg-0">
        <form action="{{ route('operator.buses.index') }}" method="GET">
            <div class="input-group has_append">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">@lang('All Status')</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>@lang('Active')</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>@lang('Inactive')</option>
                </select>
            </div>
        </form>
    </div>
@endpush

@push('style')
    <style>
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #333;
            margin-bottom: 10px;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 12px;
            margin-left: 10px;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px 8px;
            margin: 0 5px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5rem 0.75rem;
            margin-left: 2px;
            border: 1px solid #dee2e6;
            color: #007bff;
            background: #fff;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .button--group {
            display: flex;
            gap: 5px;
        }

        .button--group .btn {
            padding: 5px 8px;
            font-size: 12px;
        }

        @media (max-width: 768px) {

            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_length {
                text-align: left;
                margin-bottom: 10px;
            }

            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
                margin-left: 0;
                margin-top: 5px;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        $(document).ready(function() {
            "use strict";

            $('#busesTable').DataTable({
                responsive: true,
                processing: true,
                serverSide: false,
                searching: true,
                ordering: true,
                info: true,
                paging: true,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                pageLength: 25,
                order: [
                    [0, 'asc']
                ],
                columnDefs: [{
                    targets: [8], // Action column
                    orderable: false,
                    searchable: false
                }],
                language: {
                    processing: "Loading...",
                    lengthMenu: "Show _MENU_ entries",
                    zeroRecords: "No buses found",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            });

            // Custom search functionality
            $('input[name="search"]').on('keyup', function() {
                var table = $('#busesTable').DataTable();
                table.search(this.value).draw();
            });

            // Delete confirmation
            $('.btn--danger').on('click', function(e) {
                if (!confirm('Are you sure you want to delete this bus? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endpush
