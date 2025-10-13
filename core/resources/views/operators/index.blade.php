@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-0">{{ $pageTitle ?? 'Manage Operators' }}</h4>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('admin.fleet.operators.create') }}" class="btn btn--primary box--shadow1">
                <i class="fa fa-fw fa-plus"></i>@lang('Add New Operator')
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table--light style--two" id="operatorsTable">
                            <thead>
                                <tr>
                                    <th>@lang('Photo')</th>
                                    <th>@lang('Operator Name')</th>
                                    <th>@lang('Email')</th>
                                    <th>@lang('Mobile')</th>
                                    <th>@lang('Company Name')</th>
                                    <th>@lang('City')</th>
                                    <th>@lang('State')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Created')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($operators as $operator)
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="thumb">
                                                    <img src="{{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->photo, imagePath()['profile']['operator']['size']) }}"
                                                        alt="@lang('image')">
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="@lang('Operator Name')">
                                            <strong>{{ $operator->name }}</strong>
                                        </td>
                                        <td data-label="@lang('Email')">{{ $operator->email }}</td>
                                        <td data-label="@lang('Mobile')">{{ $operator->mobile }}</td>
                                        <td data-label="@lang('Company Name')">{{ $operator->company_name ?? 'N/A' }}</td>
                                        <td data-label="@lang('City')">{{ $operator->city ?? 'N/A' }}</td>
                                        <td data-label="@lang('State')">{{ $operator->state ?? 'N/A' }}</td>
                                        <td data-label="@lang('Status')">
                                            @if ($operator->all_details_completed)
                                                <span class="badge badge--success">@lang('Complete')</span>
                                            @elseif(
                                                $operator->basic_details_completed ||
                                                    $operator->company_details_completed ||
                                                    $operator->documents_completed ||
                                                    $operator->bank_details_completed)
                                                <span class="badge badge--warning">@lang('Incomplete')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Draft')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Created')">
                                            {{ showDateTime($operator->created_at, 'd M, Y') }}
                                        </td>
                                        <td data-label="@lang('Action')">
                                            <div class="button--group">
                                                <a href="{{ route('admin.fleet.operators.show', $operator->id) }}"
                                                    class="btn btn-sm btn--primary" title="@lang('View')">
                                                    <i class="la la-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.fleet.operators.edit', $operator->id) }}"
                                                    class="btn btn-sm btn--success" title="@lang('Edit')">
                                                    <i class="la la-pen"></i>
                                                </a>
                                                <form action="{{ route('admin.fleet.operators.destroy', $operator->id) }}"
                                                    method="POST" style="display: inline-block;"
                                                    onsubmit="return confirm('Are you sure you want to delete this operator?');">
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
                                            {{ __($emptyMessage ?? 'No operators found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($operators->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($operators) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <form action="{{ route('admin.fleet.operators.index') }}" method="GET"
        class="form-inline float-sm-right bg--white mb-2 ml-0 ml-xl-2 ml-lg-0">
        <div class="input-group has_append">
            <input type="text" name="search" class="form-control" placeholder="@lang('Search by name or email')"
                value="{{ request('search') }}">
            <div class="input-group-append">
                <button class="btn btn--primary" type="submit">
                    <i class="fa fa-search"></i>
                </button>
            </div>
        </div>
    </form>
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

        .thumb img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
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

            $('#operatorsTable').DataTable({
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
                    [1, 'asc']
                ],
                columnDefs: [{
                        targets: [0, 9], // Photo and Action columns
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: [7], // Status column
                        orderable: true,
                        searchable: false
                    }
                ],
                language: {
                    processing: "Loading...",
                    lengthMenu: "Show _MENU_ entries",
                    zeroRecords: "No operators found",
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
                initComplete: function() {
                    // Add custom filter for status
                    this.api().columns([7]).every(function() {
                        var column = this;
                        var select = $(
                                '<select class="form-control form-control-sm"><option value="">All Status</option><option value="1">Active</option><option value="0">Inactive</option></select>'
                            )
                            .appendTo($(column.header()).empty())
                            .on('change', function() {
                                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                column.search(val ? '^' + val + '$' : '', true, false)
                                    .draw();
                            });
                    });
                }
            });

            // Custom search functionality
            $('input[name="search"]').on('keyup', function() {
                var table = $('#operatorsTable').DataTable();
                table.search(this.value).draw();
            });

            // Delete confirmation
            $('.btn--danger').on('click', function(e) {
                if (!confirm(
                        'Are you sure you want to delete this operator? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });

            // Auto-refresh data every 30 seconds (optional)
            // setInterval(function() {
            //     $('#operatorsTable').DataTable().ajax.reload(null, false);
            // }, 30000);
        });
    </script>
@endpush
