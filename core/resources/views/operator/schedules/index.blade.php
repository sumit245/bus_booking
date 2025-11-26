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
                                                    onclick="toggleStatus({{ $schedule->id }})" title="@lang('Toggle Status')">
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

        function toggleStatus(scheduleId) {
            if (confirm('@lang('Are you sure you want to toggle the status of this schedule?')')) {
                fetch(`/operator/schedules/${scheduleId}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the schedule status.');
                    });
            }
        }

        function deleteSchedule(scheduleId) {
            document.getElementById('deleteForm').action = `/operator/schedules/${scheduleId}`;
            $('#deleteModal').modal('show');
        }
    </script>
@endpush
