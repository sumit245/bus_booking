@extends('operator.layouts.app')

@section('panel')
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-0">@lang('Block Seats')</h4>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('operator.bookings.index') }}" class="btn btn--secondary box--shadow1">
                <i class="fa fa-fw fa-arrow-left"></i>@lang('Back to Bookings')
            </a>
        </div>
    </div>

    <!-- Debug Info -->
    <div class="alert alert-info">
        <strong>Debug:</strong> Buses: {{ $buses->count() }}, Routes: {{ $routes->count() }}
        @if ($buses->count() > 0)
            <br>First Bus: {{ $buses->first()->travel_name }}
        @endif
        @if ($routes->count() > 0)
            <br>First Route: {{ $routes->first()->originCity->city_name }} →
            {{ $routes->first()->destinationCity->city_name }}
        @endif
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <form action="{{ route('operator.bookings.store') }}" method="POST" id="bookingForm">
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
                                                {{ old('operator_bus_id', $selectedBus?->id) == $bus->id ? 'selected' : '' }}>
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
                            <!-- Schedule Selection (Optional) -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bus_schedule_id">@lang('Select Schedule') <small
                                            class="text-muted">(@lang('Optional'))</small></label>
                                    <select class="form-control @error('bus_schedule_id') is-invalid @enderror"
                                        name="bus_schedule_id" id="bus_schedule_id">
                                        <option value="">@lang('No specific schedule')</option>
                                    </select>
                                    @error('bus_schedule_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Date Range Toggle -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Booking Type')</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="is_date_range" id="singleDate"
                                            value="0" {{ old('is_date_range', '0') == '0' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="singleDate">
                                            @lang('Single Date')
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="is_date_range" id="dateRange"
                                            value="1" {{ old('is_date_range') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="dateRange">
                                            @lang('Date Range')
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Journey Date -->
                            <div class="col-md-6" id="singleDateField">
                                <div class="form-group">
                                    <label for="journey_date">@lang('Journey Date') <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('journey_date') is-invalid @enderror"
                                        name="journey_date" id="journey_date" value="{{ old('journey_date') }}"
                                        min="2025-10-17" required>
                                    @error('journey_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Date Range Fields (Hidden by default) -->
                            <div class="col-md-6" id="dateRangeFields" style="display: none;">
                                <div class="form-group">
                                    <label for="journey_date_start">@lang('Start Date') <span
                                            class="text-danger">*</span></label>
                                    <input type="date"
                                        class="form-control @error('journey_date_start') is-invalid @enderror"
                                        name="journey_date_start" id="journey_date_start"
                                        value="{{ old('journey_date_start') }}" min="2025-10-17">
                                    @error('journey_date_start')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row" id="endDateRow" style="display: none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="journey_date_end">@lang('End Date') <span
                                            class="text-danger">*</span></label>
                                    <input type="date"
                                        class="form-control @error('journey_date_end') is-invalid @enderror"
                                        name="journey_date_end" id="journey_date_end"
                                        value="{{ old('journey_date_end') }}" min="2025-10-17">
                                    @error('journey_date_end')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Booking Reason -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="booking_reason">@lang('Booking Reason')</label>
                                    <input type="text"
                                        class="form-control @error('booking_reason') is-invalid @enderror"
                                        name="booking_reason" id="booking_reason" value="{{ old('booking_reason') }}"
                                        placeholder="@lang('e.g., Maintenance, Private use, etc.')">
                                    @error('booking_reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notes">@lang('Notes')</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" id="notes" rows="3"
                                        placeholder="@lang('Additional notes...')">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Seat Selection -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('Select Seats to Block') <span class="text-danger">*</span></label>

                                    <!-- Seat Layout Display -->
                                    <div id="seatLayoutArea" class="mb-3" style="display: none;">
                                        <div class="alert alert-info">
                                            <strong>Seat Layout:</strong> <span class="text-success">Green =
                                                Available</span> |
                                            <span class="text-danger">Red = Blocked</span> |
                                            <span class="text-warning">Yellow = Selected</span>
                                        </div>
                                        <div id="seatLayoutContainer" class="seat-layout-container"></div>
                                    </div>

                                    <!-- Manual Seat Input -->
                                    <div class="seat-input-container">
                                        <input type="text"
                                            class="form-control @error('blocked_seats') is-invalid @enderror"
                                            name="blocked_seats_input" id="blocked_seats_input"
                                            placeholder="@lang('Enter seat numbers separated by commas (e.g., U1, U3, L5)')" value="{{ old('blocked_seats_input') }}">
                                        <small class="form-text text-muted">
                                            @lang('Enter seat numbers like U1, U3, L5, etc. Separate multiple seats with commas.')
                                        </small>
                                    </div>
                                    @error('blocked_seats')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn--primary box--shadow1">
                                        <i class="fa fa-fw fa-save"></i>@lang('Block Seats')
                                    </button>
                                    <a href="{{ route('operator.bookings.index') }}"
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
        // Show alert function
        function showAlert(message, type = 'info') {
            const alertClass = type === 'danger' ? 'alert-danger' :
                type === 'warning' ? 'alert-warning' :
                type === 'success' ? 'alert-success' : 'alert-info';

            const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>`;

            // Remove existing alerts
            $('.alert').remove();

            // Add new alert at the top of the form
            $('#bookingForm').prepend(alertHtml);

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }

        $(document).ready(function() {
            // Initialize date field requirements (single date mode by default)
            $('#journey_date').prop('required', true);
            $('#journey_date_start').prop('required', false);
            $('#journey_date_end').prop('required', false);

            // Check if date range is already selected and initialize accordingly
            if ($('input[name="is_date_range"]:checked').val() == '1') {
                $('input[name="is_date_range"]').trigger('change');
            }

            // Handle date range toggle
            $('input[name="is_date_range"]').change(function() {
                if ($(this).val() == '1') {
                    // Switch to date range mode
                    $('#singleDateField').hide();
                    $('#dateRangeFields').show();
                    $('#endDateRow').show();
                    $('#journey_date').prop('required', false); // Single date not required
                    $('#journey_date_start').prop('required', true); // Start date required
                    $('#journey_date_end').prop('required', true);

                    // Copy single date to start date if single date has value
                    if ($('#journey_date').val()) {
                        $('#journey_date_start').val($('#journey_date').val());
                    }
                } else {
                    // Switch to single date mode
                    $('#singleDateField').show();
                    $('#dateRangeFields').hide();
                    $('#endDateRow').hide();
                    $('#journey_date').prop('required', true); // Single date required
                    $('#journey_date_start').prop('required', false); // Start date not required
                    $('#journey_date_end').prop('required', false);

                    // Copy start date to single date if start date has value
                    if ($('#journey_date_start').val()) {
                        $('#journey_date').val($('#journey_date_start').val());
                    }
                }
            });

            // Load routes and schedules when bus is selected
            $('#operator_bus_id').change(function() {
                const busId = $(this).val();
                const routeSelect = $('#operator_route_id');
                const scheduleSelect = $('#bus_schedule_id');

                // Reset both route and schedule selection
                routeSelect.html('<option value="">@lang('Loading routes...')</option>');
                scheduleSelect.html('<option value="">@lang('No specific schedule')</option>');

                if (busId) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    // Load routes for this bus
                    console.log('Loading routes for bus:', busId);
                    $.get(`/bus_booking/operator/buses/${busId}/routes`, {})
                        .done(function(data) {
                            console.log('Routes loaded:', data);
                            routeSelect.html('<option value="">@lang('Choose Route')</option>');
                            if (data && data.length > 0) {
                                $.each(data, function(index, route) {
                                    routeSelect.append(
                                        `<option value="${route.id}">${route.origin_city} → ${route.destination_city}</option>`
                                    );
                                });
                            }
                        })
                        .fail(function(xhr, status, error) {
                            console.log('Route loading failed:', xhr.responseText, error);
                            routeSelect.html('<option value="">@lang('Choose Route')</option>');
                        });

                    // Load schedules for this bus
                    $.get(`/bus_booking/test-schedules`, {})
                        .done(function(data) {
                            console.log('Schedules loaded:', data);
                            scheduleSelect.html('<option value="">@lang('No specific schedule')</option>');
                            if (data && data.length > 0) {
                                $.each(data, function(index, schedule) {
                                    // Extract only time part from datetime string
                                    let departureTime;
                                    if (schedule.departure_time) {
                                        // If it contains 'T' (datetime format), extract time part
                                        if (schedule.departure_time.includes('T')) {
                                            const timePart = schedule.departure_time.split('T')[
                                                1];
                                            departureTime = timePart.substring(0,
                                            5); // Get HH:MM part
                                        } else if (schedule.departure_time.match(
                                                /^\d{2}:\d{2}$/)) {
                                            // If it's already in HH:MM format, use it directly
                                            departureTime = schedule.departure_time;
                                        } else {
                                            // Parse time string properly - just extract HH:MM
                                            const timeParts = schedule.departure_time.split(
                                            ':');
                                            const hours = timeParts[0];
                                            const minutes = timeParts[1];
                                            departureTime = hours + ':' + minutes;
                                        }
                                    } else {
                                        departureTime = '00:00';
                                    }

                                    scheduleSelect.append(
                                        `<option value="${schedule.id}">${schedule.schedule_name} - ${departureTime}</option>`
                                    );
                                });
                            }
                        })
                        .fail(function(xhr, status, error) {
                            console.log('Schedule loading failed:', error);
                            scheduleSelect.html('<option value="">@lang('No specific schedule')</option>');
                        });
                } else {
                    routeSelect.html('<option value="">@lang('Choose Route')</option>');
                }

                loadSeatLayout();
            });

            // Load schedules when route is changed
            $('#operator_route_id').change(function() {
                const busId = $('#operator_bus_id').val();
                const routeId = $(this).val();
                const scheduleSelect = $('#bus_schedule_id');

                if (busId && routeId) {
                    $.get(`/bus_booking/test-schedules`, {})
                        .done(function(data) {
                            console.log('Schedules reloaded for route:', data);
                            scheduleSelect.html('<option value="">@lang('No specific schedule')</option>');
                            if (data && data.length > 0) {
                                $.each(data, function(index, schedule) {
                                    // Extract only time part from datetime string
                                    let departureTime;
                                    if (schedule.departure_time) {
                                        // If it contains 'T' (datetime format), extract time part
                                        if (schedule.departure_time.includes('T')) {
                                            const timePart = schedule.departure_time.split('T')[
                                                1];
                                            departureTime = timePart.substring(0,
                                            5); // Get HH:MM part
                                        } else if (schedule.departure_time.match(
                                                /^\d{2}:\d{2}$/)) {
                                            // If it's already in HH:MM format, use it directly
                                            departureTime = schedule.departure_time;
                                        } else {
                                            // Parse time string properly - just extract HH:MM
                                            const timeParts = schedule.departure_time.split(
                                            ':');
                                            const hours = timeParts[0];
                                            const minutes = timeParts[1];
                                            departureTime = hours + ':' + minutes;
                                        }
                                    } else {
                                        departureTime = '00:00';
                                    }

                                    scheduleSelect.append(
                                        `<option value="${schedule.id}">${schedule.schedule_name} - ${departureTime}</option>`
                                    );
                                });
                            }
                        })
                        .fail(function(xhr, status, error) {
                            console.log('Schedule loading failed:', error);
                            scheduleSelect.html('<option value="">@lang('No specific schedule')</option>');
                        });
                }
            });

            // Load seat layout when date is selected
            $('#journey_date, #journey_date_start, #journey_date_end').change(function() {
                loadSeatLayout();
            });

            function loadSeatLayout() {
                const busId = $('#operator_bus_id').val();
                const isDateRange = $('input[name="is_date_range"]:checked').val() == '1';
                let journeyDate, journeyDateEnd;

                if (isDateRange) {
                    journeyDate = $('#journey_date_start').val();
                    journeyDateEnd = $('#journey_date_end').val();
                } else {
                    journeyDate = $('#journey_date').val();
                    journeyDateEnd = null;
                }

                console.log('Loading seat layout for bus:', busId, 'date:', journeyDate);

                if (!busId || !journeyDate) {
                    console.log('Missing bus or date, hiding seat layout');
                    $('#seatLayoutArea').hide();
                    return;
                }

                $('#seatLayoutArea').show();
                $('#seatLayoutContainer').html(
                    '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading seat layout...</div>'
                );

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.get('/bus_booking/test-seat-layout', {
                        bus_id: busId
                    })
                    .done(function(data) {
                        console.log('Seat layout loaded:', data);
                        if (data.error) {
                            console.log('Seat layout error:', data.error);
                            $('#seatLayoutContainer').html(
                                `<div class="alert alert-danger">${data.error}</div>`);
                            return;
                        }

                        // Display seat layout
                        console.log('Displaying seat layout HTML');
                        $('#seatLayoutContainer').html(data.seat_layout_html);

                        // Add click handlers for seat selection
                        $('.seat-layout-container .seat').click(function() {
                            if ($(this).hasClass('blocked')) {
                                return; // Don't allow selection of blocked seats
                            }

                            const seatId = $(this).attr('id');
                            if ($(this).hasClass('selected')) {
                                $(this).removeClass('selected');
                                removeSeatFromInput(seatId);
                            } else {
                                $(this).addClass('selected');
                                addSeatToInput(seatId);
                            }
                        });
                    })
                    .fail(function(xhr, status, error) {
                        console.log('Seat layout loading failed:', xhr.responseText, error);
                        $('#seatLayoutContainer').html(
                            '<div class="alert alert-danger">Error loading seat layout: ' + error + '</div>'
                        );
                    });
            }

            function addSeatToInput(seatId) {
                const currentInput = $('#blocked_seats_input').val();
                const seats = currentInput ? currentInput.split(',').map(s => s.trim()) : [];
                if (!seats.includes(seatId)) {
                    seats.push(seatId);
                    $('#blocked_seats_input').val(seats.join(', '));
                }
            }

            function removeSeatFromInput(seatId) {
                const currentInput = $('#blocked_seats_input').val();
                const seats = currentInput ? currentInput.split(',').map(s => s.trim()) : [];
                const filteredSeats = seats.filter(seat => seat !== seatId);
                $('#blocked_seats_input').val(filteredSeats.join(', '));
            }


            // Initialize date range visibility
            if ($('input[name="is_date_range"]:checked').val() == '1') {
                $('#singleDateField').hide();
                $('#dateRangeFields').show();
                $('#endDateRow').show();
                $('#journey_date_end').prop('required', true);
            }
        });

        // Process seat input before form submission
        $('#bookingForm').on('submit', function(e) {
            const seatInput = $('#blocked_seats_input').val().trim();
            if (!seatInput) {
                e.preventDefault();
                alert('Please enter seat numbers to block.');
                return false;
            }

            // Convert comma-separated seats to array
            const seats = seatInput.split(',').map(seat => seat.trim()).filter(seat => seat);

            // Add hidden inputs for each seat
            seats.forEach(function(seat) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'blocked_seats[]',
                    value: seat
                }).appendTo('#bookingForm');
            });
        });
    </script>

    <style>
        .seat-input-container {
            margin-bottom: 10px;
        }

        .seat-layout-container {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background-color: #f9f9f9;
            max-height: 500px;
            overflow-y: auto;
            position: relative;
        }

        .bus-layout {
            position: relative;
        }

        .deck {
            margin-bottom: 20px;
        }

        .deck h5 {
            margin-bottom: 10px;
            color: #333;
            font-weight: bold;
        }

        .seats-container {
            position: relative;
            min-height: 200px;
            border: 1px solid #ccc;
            background-color: #fff;
            padding: 10px;
        }

        .seat {
            position: absolute;
            cursor: pointer;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            border-radius: 3px;
            background-color: #28a745;
            color: white;
            transition: all 0.2s;
        }

        .seat:hover {
            background-color: #218838 !important;
            transform: scale(1.05);
        }

        .seat.blocked {
            background-color: #dc3545 !important;
            cursor: not-allowed;
        }

        .seat.selected {
            background-color: #ffc107 !important;
            color: #000 !important;
        }

        .seat.sleeper {
            background-color: #17a2b8;
        }

        .seat.seater {
            background-color: #28a745;
        }
    </style>
@endpush
