@extends('operator.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Add New Route')</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('operator.routes.store') }}" method="POST" id="routeForm">
                        @csrf

                        <!-- Basic Route Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Route Name') <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('route_name') is-invalid @enderror"
                                        name="route_name" value="{{ old('route_name') }}" required>
                                    @error('route_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Origin City') <span class="text-danger">*</span></label>
                                    <select class="form-control @error('origin_city_id') is-invalid @enderror"
                                        name="origin_city_id" id="origin_city_id" required>
                                        <option value="">@lang('Select Origin City')</option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city->city_id }}"
                                                {{ old('origin_city_id') == $city->city_id ? 'selected' : '' }}>
                                                {{ $city->city_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('origin_city_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Destination City') <span class="text-danger">*</span></label>
                                    <select class="form-control @error('destination_city_id') is-invalid @enderror"
                                        name="destination_city_id" id="destination_city_id" required>
                                        <option value="">@lang('Select Destination City')</option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city->city_id }}"
                                                {{ old('destination_city_id') == $city->city_id ? 'selected' : '' }}>
                                                {{ $city->city_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('destination_city_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Distance (km)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('distance') is-invalid @enderror" name="distance"
                                        value="{{ old('distance') }}" placeholder="e.g., 150.5">
                                    @error('distance')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Estimated Duration (hours)')</label>
                                    <input type="number" step="0.5" min="0.5" max="24"
                                        class="form-control @error('estimated_duration') is-invalid @enderror"
                                        name="estimated_duration" value="{{ old('estimated_duration') }}"
                                        placeholder="e.g., 3.5 (for 3 hours 30 minutes)">
                                    @error('estimated_duration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Enter duration in hours (e.g., 3.5 for 3 hours 30 minutes)')</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Base Fare (₹)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('base_fare') is-invalid @enderror" name="base_fare"
                                        value="{{ old('base_fare') }}" placeholder="e.g., 500.00">
                                    @error('base_fare')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('Description')</label>
                                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                                        placeholder="@lang('Route description (optional)')">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Boarding Points -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="mb-3">@lang('Boarding Points') <span class="text-danger">*</span></h5>
                                <div id="boardingPointsContainer">
                                    <div class="boarding-point-item border p-3 mb-3">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('Point Name') <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control"
                                                        name="boarding_points[0][point_name]" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('Location') <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control"
                                                        name="boarding_points[0][point_location]" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('Time') <span class="text-danger">*</span></label>
                                                    <input type="time" class="form-control"
                                                        name="boarding_points[0][point_time]" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('Contact Number')</label>
                                                    <input type="text" class="form-control"
                                                        name="boarding_points[0][contact_number]">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Address') <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" name="boarding_points[0][point_address]" rows="2" required></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Landmark')</label>
                                                    <input type="text" class="form-control"
                                                        name="boarding_points[0][point_landmark]">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn--info btn-sm" onclick="addBoardingPoint()">
                                    <i class="la la-plus"></i> @lang('Add Boarding Point')
                                </button>
                            </div>
                        </div>

                        <!-- Dropping Points -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="mb-3">@lang('Dropping Points') <span class="text-danger">*</span></h5>
                                <div id="droppingPointsContainer">
                                    <div class="dropping-point-item border p-3 mb-3">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('Point Name') <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control"
                                                        name="dropping_points[0][point_name]" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('Location') <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control"
                                                        name="dropping_points[0][point_location]" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('Time') <span class="text-danger">*</span></label>
                                                    <input type="time" class="form-control"
                                                        name="dropping_points[0][point_time]" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('Contact Number')</label>
                                                    <input type="text" class="form-control"
                                                        name="dropping_points[0][contact_number]">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Address')</label>
                                                    <textarea class="form-control" name="dropping_points[0][point_address]" rows="2"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Landmark')</label>
                                                    <input type="text" class="form-control"
                                                        name="dropping_points[0][point_landmark]">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn--info btn-sm" onclick="addDroppingPoint()">
                                    <i class="la la-plus"></i> @lang('Add Dropping Point')
                                </button>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn--primary">@lang('Create Route')</button>
                            <a href="{{ route('operator.routes.index') }}"
                                class="btn btn--secondary">@lang('Cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        let boardingPointCount = 1;
        let droppingPointCount = 1;

        function addBoardingPoint() {
            const container = document.getElementById('boardingPointsContainer');
            const newPoint = document.createElement('div');
            newPoint.className = 'boarding-point-item border p-3 mb-3';
            newPoint.innerHTML = `
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('Point Name') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="boarding_points[${boardingPointCount}][point_name]" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('Location') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="boarding_points[${boardingPointCount}][point_location]" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('Time') <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="boarding_points[${boardingPointCount}][point_time]" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('Contact Number')</label>
                            <input type="text" class="form-control" name="boarding_points[${boardingPointCount}][contact_number]">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('Address') <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="boarding_points[${boardingPointCount}][point_address]" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('Landmark')</label>
                            <input type="text" class="form-control" name="boarding_points[${boardingPointCount}][point_landmark]">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <button type="button" class="btn btn--danger btn-sm" onclick="removeBoardingPoint(this)">
                            <i class="la la-trash"></i> @lang('Remove')
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newPoint);
            boardingPointCount++;
        }

        function addDroppingPoint() {
            const container = document.getElementById('droppingPointsContainer');
            const newPoint = document.createElement('div');
            newPoint.className = 'dropping-point-item border p-3 mb-3';
            newPoint.innerHTML = `
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('Point Name') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="dropping_points[${droppingPointCount}][point_name]" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('Location') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="dropping_points[${droppingPointCount}][point_location]" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('Time') <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="dropping_points[${droppingPointCount}][point_time]" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('Contact Number')</label>
                            <input type="text" class="form-control" name="dropping_points[${droppingPointCount}][contact_number]">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('Address')</label>
                            <textarea class="form-control" name="dropping_points[${droppingPointCount}][point_address]" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('Landmark')</label>
                            <input type="text" class="form-control" name="dropping_points[${droppingPointCount}][point_landmark]">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <button type="button" class="btn btn--danger btn-sm" onclick="removeDroppingPoint(this)">
                            <i class="la la-trash"></i> @lang('Remove')
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newPoint);
            droppingPointCount++;
        }

        function removeBoardingPoint(button) {
            const container = document.getElementById('boardingPointsContainer');
            if (container.children.length > 1) {
                button.closest('.boarding-point-item').remove();
            } else {
                alert('@lang('At least one boarding point is required')');
            }
        }

        function removeDroppingPoint(button) {
            const container = document.getElementById('droppingPointsContainer');
            if (container.children.length > 1) {
                button.closest('.dropping-point-item').remove();
            } else {
                alert('@lang('At least one dropping point is required')');
            }
        }

        // Validate origin and destination are different
        document.getElementById('routeForm').addEventListener('submit', function(e) {
            const origin = document.getElementById('origin_city_id').value;
            const destination = document.getElementById('destination_city_id').value;

            if (origin === destination) {
                e.preventDefault();
                alert('@lang('Origin and destination cities must be different')');
                return false;
            }
        });
    </script>
@endpush

@push('breadcrumb-plugins')
    <a href="{{ route('operator.routes.index') }}" class="btn btn-sm btn--primary box--shadow1 text--small">
        <i class="las la-angle-double-left"></i>@lang('Go Back')
    </a>
@endpush
