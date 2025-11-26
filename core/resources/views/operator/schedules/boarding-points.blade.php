@extends('operator.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Manage Boarding Points') -
                        {{ $schedule->schedule_name ?: 'Schedule #' . $schedule->id }}</h4>
                    <div class="card-tools">
                        <button type="button" class="btn btn--primary btn-sm" data-toggle="modal" data-target="#addPointModal">
                            <i class="las la-plus"></i> @lang('Add Boarding Point')
                        </button>
                        <a href="{{ route('operator.schedules.show', $schedule) }}" class="btn btn--secondary btn-sm">
                            <i class="las la-arrow-left"></i> @lang('Back to Schedule')
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Schedule Info -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Route')</label>
                                <p><strong>{{ $schedule->operatorRoute->originCity->city_name }} →
                                        {{ $schedule->operatorRoute->destinationCity->city_name }}</strong></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Departure Time')</label>
                                <p><span class="badge badge--info">{{ $schedule->formatted_departure_time }}</span></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Bus')</label>
                                <p>{{ $schedule->operatorBus->travel_name }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Status')</label>
                                <p>
                                    @if ($schedule->is_active)
                                        <span class="badge badge--success">@lang('Active')</span>
                                    @else
                                        <span class="badge badge--danger">@lang('Inactive')</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule-Specific Boarding Points -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">@lang('Schedule-Specific Boarding Points') ({{ $schedule->boardingPoints->count() }})</h5>
                            <div class="table-responsive">
                                <table class="table table--light style--two">
                                    <thead>
                                        <tr>
                                            <th>@lang('Index')</th>
                                            <th>@lang('Point Name')</th>
                                            <th>@lang('Location')</th>
                                            <th>@lang('Address')</th>
                                            <th>@lang('Landmark')</th>
                                            <th>@lang('Contact')</th>
                                            <th>@lang('Time')</th>
                                            <th>@lang('Status')</th>
                                            <th>@lang('Action')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($schedule->boardingPoints as $point)
                                            <tr>
                                                <td><span class="badge badge--info">{{ $point->point_index }}</span></td>
                                                <td><strong>{{ $point->point_name }}</strong></td>
                                                <td>{{ $point->point_location }}</td>
                                                <td>{{ $point->point_address }}</td>
                                                <td>{{ $point->point_landmark ?? 'N/A' }}</td>
                                                <td>{{ $point->contact_number ?? 'N/A' }}</td>
                                                <td><span
                                                        class="badge badge--primary">{{ \Carbon\Carbon::parse($point->point_time)->format('h:i A') }}</span>
                                                </td>
                                                <td>
                                                    @if ($point->status)
                                                        <span class="badge badge--success">@lang('Active')</span>
                                                    @else
                                                        <span class="badge badge--danger">@lang('Inactive')</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn--warning btn-sm"
                                                        onclick="editPoint({{ $point->id }}, '{{ $point->point_name }}', '{{ $point->point_address }}', '{{ $point->point_location }}', '{{ $point->point_landmark }}', '{{ $point->contact_number }}', {{ $point->point_index }}, '{{ $point->point_time }}', {{ $point->status }})">
                                                        <i class="las la-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn--danger btn-sm"
                                                        onclick="deletePoint({{ $point->id }})">
                                                        <i class="las la-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted">@lang('No schedule-specific boarding points found. Add points or route-level points will be used as fallback.')</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Route-Level Boarding Points (Fallback) -->
                    @if ($schedule->operatorRoute->boardingPoints->count() > 0)
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert--info">
                                    <i class="las la-info-circle"></i>
                                    @lang('Route-level boarding points below will be used as fallback if no schedule-specific points are defined.')
                                </div>
                                <h5 class="mb-3">@lang('Route-Level Boarding Points (Fallback)')
                                    ({{ $schedule->operatorRoute->boardingPoints->count() }})</h5>
                                <div class="table-responsive">
                                    <table class="table table--light style--two">
                                        <thead>
                                            <tr>
                                                <th>@lang('Index')</th>
                                                <th>@lang('Point Name')</th>
                                                <th>@lang('Location')</th>
                                                <th>@lang('Address')</th>
                                                <th>@lang('Landmark')</th>
                                                <th>@lang('Contact')</th>
                                                <th>@lang('Time')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($schedule->operatorRoute->boardingPoints as $point)
                                                <tr class="table-secondary">
                                                    <td><span
                                                            class="badge badge--secondary">{{ $point->point_index }}</span>
                                                    </td>
                                                    <td>{{ $point->point_name }}</td>
                                                    <td>{{ $point->point_location }}</td>
                                                    <td>{{ $point->point_address }}</td>
                                                    <td>{{ $point->point_landmark ?? 'N/A' }}</td>
                                                    <td>{{ $point->contact_number ?? 'N/A' }}</td>
                                                    <td><span
                                                            class="badge badge--secondary">{{ \Carbon\Carbon::parse($point->point_time)->format('h:i A') }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Add Point Modal -->
    <div class="modal fade" id="addPointModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('operator.schedules.boarding-points.store', $schedule) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Add Boarding Point')</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Point Name') <span class="text-danger">*</span></label>
                                    <input type="text" name="point_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Point Location') <span class="text-danger">*</span></label>
                                    <input type="text" name="point_location" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('Point Address') <span class="text-danger">*</span></label>
                                    <textarea name="point_address" class="form-control" rows="2" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Landmark')</label>
                                    <input type="text" name="point_landmark" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Contact Number')</label>
                                    <input type="text" name="contact_number" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Point Index') <span class="text-danger">*</span></label>
                                    <input type="number" name="point_index" class="form-control" min="1"
                                        required>
                                    <small class="text-muted">@lang('Order of the point in the route')</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Point Time') <span class="text-danger">*</span></label>
                                    <input type="time" name="point_time" class="form-control" required>
                                    <small class="text-muted">@lang('Pickup time at this point')</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary"
                            data-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn--primary">@lang('Add Point')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Point Modal -->
    <div class="modal fade" id="editPointModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST" id="editForm">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Edit Boarding Point')</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Point Name') <span class="text-danger">*</span></label>
                                    <input type="text" name="point_name" id="edit_point_name" class="form-control"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Point Location') <span class="text-danger">*</span></label>
                                    <input type="text" name="point_location" id="edit_point_location"
                                        class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('Point Address') <span class="text-danger">*</span></label>
                                    <textarea name="point_address" id="edit_point_address" class="form-control" rows="2" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Landmark')</label>
                                    <input type="text" name="point_landmark" id="edit_point_landmark"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Contact Number')</label>
                                    <input type="text" name="contact_number" id="edit_contact_number"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Point Index') <span class="text-danger">*</span></label>
                                    <input type="number" name="point_index" id="edit_point_index" class="form-control"
                                        min="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Point Time') <span class="text-danger">*</span></label>
                                    <input type="time" name="point_time" id="edit_point_time" class="form-control"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Status') <span class="text-danger">*</span></label>
                                    <select name="status" id="edit_status" class="form-control" required>
                                        <option value="1">@lang('Active')</option>
                                        <option value="0">@lang('Inactive')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary"
                            data-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn--primary">@lang('Update Point')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Delete Boarding Point')</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>@lang('Are you sure you want to delete this boarding point? This action cannot be undone.')</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" data-dismiss="modal">@lang('Cancel')</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn--danger">@lang('Delete')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .info-item {
            margin-bottom: 15px;
        }

        .info-item label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            display: block;
            font-size: 0.875rem;
        }

        .info-item p {
            margin: 0;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .card-tools {
            display: flex;
            gap: 10px;
        }

        .table-secondary {
            opacity: 0.7;
        }
    </style>
@endpush

@push('breadcrumb-plugins')
    <a href="{{ route('operator.schedules.show', $schedule) }}" class="btn btn-sm btn--primary box--shadow1 text--small">
        <i class="las la-angle-double-left"></i>@lang('Back to Schedule')
    </a>
@endpush

@push('script')
    <script>
        function editPoint(id, name, address, location, landmark, contact, index, time, status) {
            document.getElementById('edit_point_name').value = name;
            document.getElementById('edit_point_address').value = address;
            document.getElementById('edit_point_location').value = location;
            document.getElementById('edit_point_landmark').value = landmark || '';
            document.getElementById('edit_contact_number').value = contact || '';
            document.getElementById('edit_point_index').value = index;
            document.getElementById('edit_point_time').value = time;
            document.getElementById('edit_status').value = status;

            document.getElementById('editForm').action = `/operator/schedules/{{ $schedule->id }}/boarding-points/${id}`;
            $('#editPointModal').modal('show');
        }

        function deletePoint(pointId) {
            document.getElementById('deleteForm').action =
                `/operator/schedules/{{ $schedule->id }}/boarding-points/${pointId}`;
            $('#deleteModal').modal('show');
        }
    </script>
@endpush
