@extends('operator.layouts.app')

@section('panel')
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-0">@lang('Create New Schedule')</h4>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('operator.schedules.index') }}" class="btn btn--secondary box--shadow1">
                <i class="fa fa-fw fa-arrow-left"></i>@lang('Back to Schedules')
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <form action="{{ route('operator.schedules.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- Bus Selection -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="operator_bus_id">@lang('Select Bus') <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control @error('operator_bus_id') is-invalid @enderror"
                                        name="operator_bus_id" id="operator_bus_id" required>
                                        <option value="">@lang('Choose Bus')</option>
                                        @foreach ($buses as $bus)
                                            <option value="{{ $bus->id }}"
                                                {{ old('operator_bus_id') == $bus->id ? 'selected' : '' }}>
                                                {{ $bus->travel_name }} - {{ $bus->bus_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('operator_bus_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Route Selection -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="operator_route_id">@lang('Select Route') <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control @error('operator_route_id') is-invalid @enderror"
                                        name="operator_route_id" id="operator_route_id" required>
                                        <option value="">@lang('Choose Route')</option>
                                        @foreach ($routes as $route)
                                            <option value="{{ $route->id }}"
                                                {{ old('operator_route_id') == $route->id ? 'selected' : '' }}>
                                                {{ $route->originCity->city_name }} →
                                                {{ $route->destinationCity->city_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('operator_route_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Schedule Name -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="schedule_name">@lang('Schedule Name')</label>
                                    <input type="text" class="form-control @error('schedule_name') is-invalid @enderror"
                                        name="schedule_name" id="schedule_name" value="{{ old('schedule_name') }}"
                                        placeholder="@lang('e.g., Morning Service, Evening Express')">
                                    @error('schedule_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Sort Order -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sort_order">@lang('Sort Order')</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                        name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}"
                                        min="0">
                                    <small class="form-text text-muted">@lang('Lower numbers appear first')</small>
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Departure Time -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="departure_time">@lang('Departure Time') <span
                                            class="text-danger">*</span></label>
                                    <input type="time" class="form-control @error('departure_time') is-invalid @enderror"
                                        name="departure_time" id="departure_time" value="{{ old('departure_time') }}"
                                        required>
                                    @error('departure_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Arrival Time -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="arrival_time">@lang('Arrival Time') <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control @error('arrival_time') is-invalid @enderror"
                                        name="arrival_time" id="arrival_time" value="{{ old('arrival_time') }}" required>
                                    @error('arrival_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Days of Operation -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('Days of Operation') <span class="text-danger">*</span></label>

                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" id="is_daily" name="is_daily"
                                            value="1" {{ old('is_daily') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_daily">
                                            <strong>@lang('Daily (All Days)')</strong>
                                        </label>
                                    </div>

                                    <div id="days_selection" style="{{ old('is_daily') ? 'display: none;' : '' }}">
                                        <div class="row">
                                            @php
                                                $days = [
                                                    'monday',
                                                    'tuesday',
                                                    'wednesday',
                                                    'thursday',
                                                    'friday',
                                                    'saturday',
                                                    'sunday',
                                                ];
                                                $dayLabels = [
                                                    'monday' => 'Monday',
                                                    'tuesday' => 'Tuesday',
                                                    'wednesday' => 'Wednesday',
                                                    'thursday' => 'Thursday',
                                                    'friday' => 'Friday',
                                                    'saturday' => 'Saturday',
                                                    'sunday' => 'Sunday',
                                                ];
                                            @endphp
                                            @foreach ($days as $day)
                                                <div class="col-md-3 col-sm-6">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input day-checkbox"
                                                            name="days_of_operation[]" value="{{ $day }}"
                                                            id="day_{{ $day }}"
                                                            {{ in_array($day, old('days_of_operation', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="day_{{ $day }}">
                                                            {{ $dayLabels[$day] }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @error('days_of_operation')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Start Date -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">@lang('Start Date')</label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                        name="start_date" id="start_date" value="{{ old('start_date') }}">
                                    <small class="form-text text-muted">@lang('Leave empty for immediate start')</small>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- End Date -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">@lang('End Date')</label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                        name="end_date" id="end_date" value="{{ old('end_date') }}">
                                    <small class="form-text text-muted">@lang('Leave empty for indefinite operation')</small>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="notes">@lang('Notes')</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" id="notes" rows="3"
                                        placeholder="@lang('Additional notes about this schedule...')">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn--primary box--shadow1">
                                        <i class="fa fa-fw fa-save"></i>@lang('Create Schedule')
                                    </button>
                                    <a href="{{ route('operator.schedules.index') }}"
                                        class="btn btn--secondary box--shadow1">
                                        <i class="fa fa-fw fa-times"></i>@lang('Cancel')
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        // Handle daily checkbox
        document.getElementById('is_daily').addEventListener('change', function() {
            const daysSelection = document.getElementById('days_selection');
            const dayCheckboxes = document.querySelectorAll('.day-checkbox');

            if (this.checked) {
                daysSelection.style.display = 'none';
                dayCheckboxes.forEach(checkbox => checkbox.checked = false);
            } else {
                daysSelection.style.display = 'block';
            }
        });

        // Handle day checkboxes
        document.querySelectorAll('.day-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const dailyCheckbox = document.getElementById('is_daily');
                if (this.checked) {
                    dailyCheckbox.checked = false;
                    document.getElementById('days_selection').style.display = 'block';
                }
            });
        });

        // Auto-calculate duration
        document.getElementById('departure_time').addEventListener('change', calculateDuration);
        document.getElementById('arrival_time').addEventListener('change', calculateDuration);

        function calculateDuration() {
            const departure = document.getElementById('departure_time').value;
            const arrival = document.getElementById('arrival_time').value;

            if (departure && arrival) {
                const depTime = new Date('2000-01-01 ' + departure);
                let arrTime = new Date('2000-01-01 ' + arrival);

                // Handle next day arrival
                if (arrTime <= depTime) {
                    arrTime.setDate(arrTime.getDate() + 1);
                }

                const diffMs = arrTime - depTime;
                const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

                // Show duration info (optional)
                console.log(`Duration: ${diffHours}h ${diffMinutes}m`);
            }
        }
    </script>
@endpush
