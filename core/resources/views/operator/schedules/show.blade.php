@extends('operator.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">@lang('Schedule Details')</h4>
                        <a href="{{ route('operator.schedules.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> @lang('Back')
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('operator.schedules.edit', $schedule) }}" class="btn btn-warning btn-sm mr-2">
                            <i class="fas fa-edit"></i> @lang('Edit')
                        </a>
                        <button type="button"
                            class="btn btn-{{ $schedule->is_active ? 'secondary' : 'success' }} btn-sm mr-2"
                            onclick="toggleStatus({{ $schedule->id }})">
                            <i class="fas fa-{{ $schedule->is_active ? 'pause' : 'play' }}"></i>
                            @lang($schedule->is_active ? 'Deactivate' : 'Activate')
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteSchedule({{ $schedule->id }})">
                            <i class="fas fa-trash"></i> @lang('Delete')
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Schedule Information -->
                        <div class="col-md-12">
                            <h5 class="text-primary mb-3">@lang('Schedule Information')</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Schedule Name'):</strong><br>{{ $schedule->schedule_name ?: 'Schedule #' . $schedule->id }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Departure Time'):</strong><br><span
                                            class="badge badge-info">{{ $schedule->formatted_departure_time }}</span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Arrival Time'):</strong><br><span
                                            class="badge badge-success">{{ $schedule->formatted_arrival_time }}</span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Duration'):</strong><br>{{ $schedule->duration }}</p>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Days of Operation'):</strong><br>{{ $schedule->days_of_operation_text }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Status'):</strong><br>
                                        @if ($schedule->is_active)
                                            <span class="badge badge-success">@lang('Active')</span>
                                        @else
                                            <span class="badge badge-danger">@lang('Inactive')</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Sort Order'):</strong><br>{{ $schedule->sort_order }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <!-- Empty for now -->
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Start Date'):</strong><br>{{ $schedule->start_date ? $schedule->start_date->format('M d, Y') : 'Immediate' }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('End Date'):</strong><br>{{ $schedule->end_date ? $schedule->end_date->format('M d, Y') : 'Indefinite' }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Created'):</strong><br>{{ $schedule->created_at->format('M d, Y H:i') }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Last Updated'):</strong><br>{{ $schedule->updated_at->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bus & Route Information -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="text-primary mb-3">@lang('Bus Information')</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Travel Name'):</strong><br>{{ $schedule->operatorBus->travel_name }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Bus Type'):</strong><br>{{ $schedule->operatorBus->bus_type }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Total Seats'):</strong><br>{{ $schedule->operatorBus->total_seats }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Bus Status'):</strong><br>
                                        @if ($schedule->operatorBus->is_active)
                                            <span class="badge badge-success">@lang('Active')</span>
                                        @else
                                            <span class="badge badge-danger">@lang('Inactive')</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5 class="text-primary mb-3">@lang('Route Information')</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-2">
                                        <strong>@lang('Route'):</strong><br>{{ $schedule->operatorRoute->originCity->city_name }}
                                        → {{ $schedule->operatorRoute->destinationCity->city_name }}
                                    </p>
                                </div>
                                <div class="col-md-2">
                                    <p class="mb-2">
                                        <strong>@lang('Distance'):</strong><br>{{ $schedule->operatorRoute->distance ? $schedule->operatorRoute->distance . ' km' : 'Not specified' }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Estimated Duration'):</strong><br>{{ $schedule->operatorRoute->estimated_duration ? $schedule->operatorRoute->estimated_duration . ' hours' : 'Not specified' }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Route Status'):</strong><br>
                                        @if ($schedule->operatorRoute->is_active)
                                            <span class="badge badge-success">@lang('Active')</span>
                                        @else
                                            <span class="badge badge-danger">@lang('Inactive')</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
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

                    <!-- Boarding/Dropping Points Management -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="text-primary mb-3">@lang('Boarding & Dropping Points Management')</h5>
                            <div class="mb-3">
                                <a href="{{ route('operator.schedules.boarding-points', $schedule) }}"
                                    class="btn btn-primary mr-3">
                                    <i class="fas fa-map-marker-alt"></i> @lang('Manage Boarding Points')
                                    <span class="badge badge-light ml-2">{{ $schedule->boardingPoints->count() }}</span>
                                </a>
                                <a href="{{ route('operator.schedules.dropping-points', $schedule) }}"
                                    class="btn btn-success">
                                    <i class="fas fa-map-marker"></i> @lang('Manage Dropping Points')
                                    <span class="badge badge-light ml-2">{{ $schedule->droppingPoints->count() }}</span>
                                </a>
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
