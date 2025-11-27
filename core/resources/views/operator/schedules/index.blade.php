@extends('operator.layouts.app')

@section('panel')
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-0">@lang('Bus Schedules')</h4>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('operator.schedules.create') }}" class="btn btn--primary box--shadow1">
                <i class="fa fa-fw fa-plus"></i>@lang('Add New Schedule')
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <select class="form-control" id="busFilter">
                                <option value="">@lang('All Buses')</option>
                                @foreach ($buses as $bus)
                                    <option value="{{ $bus->id }}"
                                        {{ request('bus_id') == $bus->id ? 'selected' : '' }}>
                                        {{ $bus->travel_name }} ({{ $bus->bus_type }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="routeFilter">
                                <option value="">@lang('All Routes')</option>
                                @foreach ($routes as $route)
                                    <option value="{{ $route->id }}"
                                        {{ request('route_id') == $route->id ? 'selected' : '' }}>
                                        {{ $route->originCity->city_name }} → {{ $route->destinationCity->city_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="statusFilter">
                                <option value="">@lang('All Status')</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                                    @lang('Active')</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                                    @lang('Inactive')</option>
                                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>
                                    @lang('Suspended')</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-check-label">
                                <input type="checkbox" class="form-check-input" id="activeOnly"
                                    {{ request('active_only') ? 'checked' : '' }}>
                                @lang('Active Only')
                            </label>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn--primary" onclick="applyFilters()">@lang('Filter')</button>
                        </div>
                    </div>

                    <!-- Schedules Table -->
                    <div class="table-responsive">
                        <table class="table table--light style--two" id="schedulesTable">
                            <thead>
                                <tr>
                                    <th>@lang('Schedule')</th>
                                    <th>@lang('Bus')</th>
                                    <th>@lang('Route')</th>
                                    <th>@lang('Departure')</th>
                                    <th>@lang('Arrival')</th>
                                    <th>@lang('Duration')</th>
                                    <th>@lang('Days')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedules as $schedule)
                                    <tr>
                                        <td data-label="@lang('Schedule')">
                                            <strong>{{ $schedule->schedule_name ?: 'Schedule #' . $schedule->id }}</strong>
                                            @if ($schedule->notes)
                                                <br><small
                                                    class="text-muted">{{ Str::limit($schedule->notes, 50) }}</small>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Bus')">
                                            {{ $schedule->operatorBus->travel_name }}<br>
                                            <small class="text-muted">{{ $schedule->operatorBus->bus_type }}</small>
                                        </td>
                                        <td data-label="@lang('Route')">
                                            {{ $schedule->operatorRoute->originCity->city_name }} →
                                            {{ $schedule->operatorRoute->destinationCity->city_name }}
                                        </td>
                                        <td data-label="@lang('Departure')">
                                            <strong>{{ $schedule->formatted_departure_time }}</strong>
                                        </td>
                                        <td data-label="@lang('Arrival')">
                                            <strong>{{ $schedule->formatted_arrival_time }}</strong>
                                        </td>
                                        <td data-label="@lang('Duration')">
                                            {{ $schedule->duration }}
                                        </td>
                                        <td data-label="@lang('Days')">
                                            {{ $schedule->days_of_operation_text }}
                                        </td>
                                        <td data-label="@lang('Status')">
                                            @if ($schedule->is_active)
                                                <span class="badge badge--success">@lang('Active')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Inactive')</span>
                                            @endif
                                            <br>
                                            <small class="text-muted">{{ ucfirst($schedule->status) }}</small>
                                        </td>
                                        <td data-label="@lang('Action')">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('operator.schedules.show', $schedule) }}"
                                                    class="btn btn--info btn-sm" title="@lang('View')">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('operator.schedules.edit', $schedule) }}"
                                                    class="btn btn--warning btn-sm" title="@lang('Edit')">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <button type="button"
                                                    class="btn btn-{{ $schedule->is_active ? 'btn--secondary' : 'btn--success' }} btn-sm"
                                                    onclick="toggleStatus({{ $schedule->id }}, {{ $schedule->is_active ? 'true' : 'false' }})"
                                                    title="@lang('Toggle Status')">
                                                    <i class="fa fa-{{ $schedule->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                                <button type="button" class="btn btn--danger btn-sm"
                                                    onclick="deleteSchedule({{ $schedule->id }})"
                                                    title="@lang('Delete')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                            <div class="btn-group mt-1" role="group">
                                                <a href="{{ route('operator.schedules.boarding-points', $schedule) }}"
                                                    class="btn btn--primary btn-sm" title="@lang('Boarding Points')">
                                                    <i class="las la-map-marker-alt"></i> <span
                                                        class="badge badge-light">{{ $schedule->boardingPoints->count() }}</span>
                                                </a>
                                                <a href="{{ route('operator.schedules.dropping-points', $schedule) }}"
                                                    class="btn btn--success btn-sm" title="@lang('Dropping Points')">
                                                    <i class="las la-map-marker"></i> <span
                                                        class="badge badge-light">{{ $schedule->droppingPoints->count() }}</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">@lang('No schedules found.')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($schedules->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $schedules->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Delete Schedule')</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>@lang('Are you sure you want to delete this schedule? This action cannot be undone.')</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('Cancel')</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">@lang('Delete')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-lib')
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@push('script')
    <script>
        // Filter functionality
        document.getElementById('busFilter').addEventListener('change', function() {
            applyFilters();
        });

        document.getElementById('routeFilter').addEventListener('change', function() {
            applyFilters();
        });

        document.getElementById('statusFilter').addEventListener('change', function() {
            applyFilters();
        });

        document.getElementById('activeOnly').addEventListener('change', function() {
            applyFilters();
        });

        function applyFilters() {
            const busId = document.getElementById('busFilter').value;
            const routeId = document.getElementById('routeFilter').value;
            const status = document.getElementById('statusFilter').value;
            const activeOnly = document.getElementById('activeOnly').checked;

            const params = new URLSearchParams();
            if (busId) params.append('bus_id', busId);
            if (routeId) params.append('route_id', routeId);
            if (status) params.append('status', status);
            if (activeOnly) params.append('active_only', '1');

            window.location.href = '{{ route('operator.schedules.index') }}?' + params.toString();
        }

        function toggleStatus(scheduleId, isCurrentlyActive) {
            if (isCurrentlyActive) {
                // Fetch revenue impact data
                fetch(`{{ url('operator/schedules') }}/${scheduleId}/revenue-impact`, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                console.error('Server response:', text);
                                throw new Error(`HTTP error! status: ${response.status}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Revenue data:', data);
                        if (data.success) {
                            const revenuePerTrip = data.total_revenue_per_trip;
                            const averagePrice = data.average_price_per_seat;
                            const totalSeats = data.total_seats;

                            // Show revenue warning with SweetAlert2
                            Swal.fire({
                                title: 'Deactivate Schedule?',
                                html: `<div class="text-left">
                                        <p><strong>Potential Revenue Loss Per Trip:</strong></p>
                                        <h4 class="text-danger">₹${revenuePerTrip.toLocaleString('en-IN')}</h4>
                                        <hr>
                                        <p><small>Average Price: ₹${averagePrice} × ${totalSeats} seats</small></p>
                                        <p class="mb-3"><small>Route: ${data.route}</small></p>
                                        <p class="text-warning"><i class="fa fa-exclamation-triangle"></i> This schedule will no longer be available for booking.</p>
                                    </div>`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Continue',
                                cancelButtonText: 'Cancel'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Show date range input
                                    showDeactivationDateInput(scheduleId);
                                }
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Could not fetch revenue data', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'An error occurred while fetching revenue data: ' + error.message, 'error');
                    });
            } else {
                // Activating - simple confirmation
                Swal.fire({
                    title: 'Activate Schedule?',
                    text: 'This schedule will become available for booking.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Activate',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitToggleStatus(scheduleId, null, null);
                    }
                });
            }
        }

        function showDeactivationDateInput(scheduleId) {
            // Get tomorrow's date as default
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().split('T')[0];

            Swal.fire({
                title: 'Deactivation Period',
                html: `
                    <div class="form-group text-left">
                        <label for="deactivation_start" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="deactivation_start" value="${tomorrowStr}" min="${tomorrowStr}" required>
                        <small class="text-muted">Schedule will be deactivated from this date</small>
                    </div>
                    <div class="form-group text-left mt-3">
                        <label for="deactivation_end" class="form-label">End Date (Optional)</label>
                        <input type="date" class="form-control" id="deactivation_end" min="${tomorrowStr}">
                        <small class="text-muted">Leave empty for lifetime deactivation</small>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Deactivate',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const startDate = document.getElementById('deactivation_start').value;
                    const endDate = document.getElementById('deactivation_end').value;

                    if (!startDate) {
                        Swal.showValidationMessage('Start date is required');
                        return false;
                    }

                    if (endDate && endDate <= startDate) {
                        Swal.showValidationMessage('End date must be after start date');
                        return false;
                    }

                    return {
                        start: startDate,
                        end: endDate
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitToggleStatus(scheduleId, result.value.start, result.value.end);
                }
            });
        }

        function submitToggleStatus(scheduleId, startDate, endDate) {
            const formData = new FormData();
            formData.append('_method', 'PATCH');
            if (startDate) {
                formData.append('deactivation_start', startDate);
            }
            if (endDate) {
                formData.append('deactivation_end', endDate);
            }

            fetch(`{{ url('operator/schedules') }}/${scheduleId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success!', data.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'An error occurred', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    let errorMessage = 'An error occurred while updating the schedule status.';
                    if (error.errors) {
                        errorMessage = Object.values(error.errors).flat().join('<br>');
                    } else if (error.message) {
                        errorMessage = error.message;
                    }
                    Swal.fire('Error', errorMessage, 'error');
                });
        }

        function deleteSchedule(scheduleId) {
            document.getElementById('deleteForm').action = `/operator/schedules/${scheduleId}`;
            $('#deleteModal').modal('show');
        }
    </script>
@endpush
