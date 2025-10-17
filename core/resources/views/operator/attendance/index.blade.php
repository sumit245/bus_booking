@extends('operator.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="card-title mb-0">@lang('Attendance Calendar')</h4>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="btn-group" role="group">
                                <a href="{{ route('operator.attendance.index', ['month' => \Carbon\Carbon::parse($currentMonth)->subMonth()->format('Y-m')]) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="las la-chevron-left"></i> Previous
                                </a>
                                <span class="btn btn-sm btn-primary">
                                    {{ \Carbon\Carbon::parse($currentMonth)->format('F Y') }}
                                </span>
                                <a href="{{ route('operator.attendance.index', ['month' => \Carbon\Carbon::parse($currentMonth)->addMonth()->format('Y-m')]) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    Next <i class="las la-chevron-right"></i>
                                </a>
                            </div>
                            <button type="button" class="btn btn-sm btn-success ml-2" data-toggle="modal"
                                data-target="#exportModal">
                                <i class="las la-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if ($staff->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered attendance-calendar">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="min-width: 120px;">Employee ID</th>
                                        <th style="min-width: 150px;">Name</th>
                                        <th style="min-width: 100px;">Role</th>
                                        @foreach ($calendarDays as $day)
                                            <th class="text-center {{ $day['isWeekend'] ? 'weekend' : '' }}"
                                                style="min-width: 40px;">
                                                {{ $day['day'] }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($staff as $member)
                                        <tr>
                                            <td><strong>{{ $member->employee_id }}</strong></td>
                                            <td>{{ $member->first_name }} {{ $member->last_name }}</td>
                                            <td><span class="badge badge-info">{{ ucfirst($member->role) }}</span></td>
                                            @foreach ($calendarDays as $day)
                                                @php
                                                    $key = $member->id . '_' . $day['date'];
                                                    $attendance = $attendanceData[$key] ?? null;
                                                    $status = $attendance ? $attendance->status : null;
                                                @endphp
                                                <td class="text-center attendance-cell {{ $day['isWeekend'] ? 'weekend' : '' }}"
                                                    data-staff-id="{{ $member->id }}" data-date="{{ $day['date'] }}"
                                                    data-status="{{ $status }}">
                                                    @if ($status === 'absent')
                                                        <span class="badge badge-danger">A</span>
                                                    @elseif($status === 'present')
                                                        <span class="badge badge-success">P</span>
                                                    @elseif($status === 'half_day')
                                                        <span class="badge badge-warning">H</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-3">
                            {{ $staff->appends(['month' => $currentMonth])->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="las la-users la-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No staff members found</h5>
                            <p class="text-muted">Add staff members to start tracking attendance.</p>
                            <a href="{{ route('operator.staff.create') }}" class="btn btn-primary">
                                <i class="las la-plus"></i> Add Staff
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Attendance</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form action="{{ route('operator.attendance.export') }}" method="GET" id="exportForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="exportMonth">Select Month</label>
                            <input type="month" class="form-control" id="exportMonth" name="month"
                                value="{{ $currentMonth }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="las la-download"></i> Export to Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .attendance-calendar {
            font-size: 12px;
        }

        .attendance-calendar th {
            background-color: #f8f9fa;
            font-weight: 600;
            padding: 8px 4px;
        }

        .attendance-calendar td {
            padding: 8px 4px;
            vertical-align: middle;
        }

        .attendance-cell {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .attendance-cell:hover {
            background-color: #f8f9fa;
        }

        .weekend {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .badge {
            font-size: 10px;
            padding: 4px 6px;
        }

        .attendance-cell .badge {
            cursor: pointer;
        }

        /* Status colors */
        .badge-danger {
            background-color: #dc3545;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
    </style>
@endpush

@push('script')
    <script>
        $(document).ready(function() {
            // Handle export form submission
            $('#exportForm').on('submit', function(e) {
                var month = $('#exportMonth').val();
                console.log('Export form submitted with month:', month);
                console.log('Form action:', $(this).attr('action'));
            });

            // Handle attendance cell clicks
            $('.attendance-cell').click(function() {
                var $cell = $(this);
                var staffId = $cell.data('staff-id');
                var date = $cell.data('date');
                var currentStatus = $cell.data('status');

                // Cycle through statuses: null -> P -> A -> H -> null
                var nextStatus;
                if (!currentStatus) {
                    nextStatus = 'P';
                } else if (currentStatus === 'present') {
                    nextStatus = 'A';
                } else if (currentStatus === 'absent') {
                    nextStatus = 'H';
                } else if (currentStatus === 'half_day') {
                    nextStatus = null;
                }

                // Debug logging
                console.log('Updating attendance:', {
                    staffId: staffId,
                    date: date,
                    currentStatus: currentStatus,
                    nextStatus: nextStatus
                });

                // Update via AJAX
                $.ajax({
                    url: '{{ route('operator.attendance.update-status') }}',
                    method: 'POST',
                    data: {
                        staff_id: staffId,
                        date: date,
                        status: nextStatus,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update the cell display
                            var $badge = $cell.find('.badge');
                            if (nextStatus === 'P') {
                                $cell.html('<span class="badge badge-success">P</span>');
                                $cell.data('status', 'present');
                            } else if (nextStatus === 'A') {
                                $cell.html('<span class="badge badge-danger">A</span>');
                                $cell.data('status', 'absent');
                            } else if (nextStatus === 'H') {
                                $cell.html('<span class="badge badge-warning">H</span>');
                                $cell.data('status', 'half_day');
                            } else {
                                $cell.html('<span class="text-muted">-</span>');
                                $cell.data('status', null);
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error('Error updating attendance:', xhr.responseText);
                        var errorMessage = 'Error updating attendance. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert(errorMessage);
                    }
                });
            });
        });
    </script>
@endpush
