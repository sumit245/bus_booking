@extends('operator.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Schedule Details')</h4>
                    <div class="card-tools">
                        <a href="{{ route('operator.schedules.edit', $schedule) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> @lang('Edit')
                        </a>
                        <a href="{{ route('operator.schedules.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> @lang('Back')
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Schedule Information -->
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">@lang('Schedule Information')</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>@lang('Schedule Name'):</strong></td>
                                    <td>{{ $schedule->schedule_name ?: 'Schedule #' . $schedule->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Departure Time'):</strong></td>
                                    <td><span class="badge badge-info">{{ $schedule->formatted_departure_time }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Arrival Time'):</strong></td>
                                    <td><span class="badge badge-success">{{ $schedule->formatted_arrival_time }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Duration'):</strong></td>
                                    <td>{{ $schedule->duration }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Days of Operation'):</strong></td>
                                    <td>{{ $schedule->days_of_operation_text }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Status'):</strong></td>
                                    <td>
                                        @if ($schedule->is_active)
                                            <span class="badge badge-success">@lang('Active')</span>
                                        @else
                                            <span class="badge badge-danger">@lang('Inactive')</span>
                                        @endif
                                        <span class="badge badge-secondary">{{ ucfirst($schedule->status) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Sort Order'):</strong></td>
                                    <td>{{ $schedule->sort_order }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Date Range -->
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">@lang('Date Range')</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>@lang('Start Date'):</strong></td>
                                    <td>{{ $schedule->start_date ? $schedule->start_date->format('M d, Y') : 'Immediate' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('End Date'):</strong></td>
                                    <td>{{ $schedule->end_date ? $schedule->end_date->format('M d, Y') : 'Indefinite' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Created'):</strong></td>
                                    <td>{{ $schedule->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Last Updated'):</strong></td>
                                    <td>{{ $schedule->updated_at->format('M d, Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Bus Information -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">@lang('Bus Information')</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>@lang('Travel Name'):</strong></td>
                                    <td>{{ $schedule->operatorBus->travel_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Bus Type'):</strong></td>
                                    <td>{{ $schedule->operatorBus->bus_type }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Total Seats'):</strong></td>
                                    <td>{{ $schedule->operatorBus->total_seats }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Bus Status'):</strong></td>
                                    <td>
                                        @if ($schedule->operatorBus->is_active)
                                            <span class="badge badge-success">@lang('Active')</span>
                                        @else
                                            <span class="badge badge-danger">@lang('Inactive')</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Route Information -->
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">@lang('Route Information')</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>@lang('Route'):</strong></td>
                                    <td>{{ $schedule->operatorRoute->originCity->city_name }} →
                                        {{ $schedule->operatorRoute->destinationCity->city_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Distance'):</strong></td>
                                    <td>{{ $schedule->operatorRoute->distance ? $schedule->operatorRoute->distance . ' km' : 'Not specified' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Estimated Duration'):</strong></td>
                                    <td>{{ $schedule->operatorRoute->estimated_duration ? $schedule->operatorRoute->estimated_duration . ' hours' : 'Not specified' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Route Status'):</strong></td>
                                    <td>
                                        @if ($schedule->operatorRoute->is_active)
                                            <span class="badge badge-success">@lang('Active')</span>
                                        @else
                                            <span class="badge badge-danger">@lang('Inactive')</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if ($schedule->notes)
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h5 class="text-primary mb-3">@lang('Notes')</h5>
                                <div class="alert alert-info">
                                    {{ $schedule->notes }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                <a href="{{ route('operator.schedules.edit', $schedule) }}" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> @lang('Edit Schedule')
                                </a>
                                <button type="button" class="btn btn-{{ $schedule->is_active ? 'secondary' : 'success' }}"
                                    onclick="toggleStatus({{ $schedule->id }})">
                                    <i class="fas fa-{{ $schedule->is_active ? 'pause' : 'play' }}"></i>
                                    @lang($schedule->is_active ? 'Deactivate' : 'Activate')
                                </button>
                                <button type="button" class="btn btn-danger"
                                    onclick="deleteSchedule({{ $schedule->id }})">
                                    <i class="fas fa-trash"></i> @lang('Delete')
                                </button>
                            </div>
                        </div>
                    </div>
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
