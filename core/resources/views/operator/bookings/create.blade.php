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
                                            @if ($route->originCity && $route->destinationCity)
                                                <option value="{{ $route->id }}"
                                                    {{ old('operator_route_id') == $route->id ? 'selected' : '' }}>
                                                    {{ $route->originCity->city_name }} →
                                                    {{ $route->destinationCity->city_name }}
                                                </option>
                                            @endif
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
                                    <div class="d-flex justify-content-around">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_date_range"
                                                id="singleDate" value="0"
                                                {{ old('is_date_range', '0') == '0' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="singleDate">
                                                @lang('Single Date')
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_date_range"
                                                id="dateRange" value="1"
                                                {{ old('is_date_range') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="dateRange">
                                                @lang('Date Range')
                                            </label>
                                        </div>
                                    </div>
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

                        <div class="row">
                            <!-- Journey Date -->
                            <div class="col-md-6" id="singleDateField">
                                <div class="form-group">
                                    <label for="journey_date">@lang('Journey Date') <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('journey_date') is-invalid @enderror"
                                        name="journey_date" id="journey_date" value="{{ old('journey_date') }}"
                                        min="{{ date('Y-m-d') }}" required>
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
                                        value="{{ old('journey_date_start') }}" min="{{ date('Y-m-d') }}">
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
                                        value="{{ old('journey_date_end') }}" min="{{ date('Y-m-d') }}">
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
                // DON'T clear routes - they're already loaded in the HTML
                // routeSelect.html('<option value="">@lang('Loading routes...')</option>');
                scheduleSelect.html('<option value="">@lang('No specific schedule')</option>');

                if (busId) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    // Routes are already loaded in HTML, no need to reload via AJAX
                    // Just load schedules
                    console.log('Loading schedules for bus:', busId);
                    $.get("{{ route('operator.schedules.get-for-date') }}", {
                            bus_id: busId,
                            route_id: $('#operator_route_id').val()
                        })
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
                    $.get("{{ route('operator.schedules.get-for-date') }}", {
                            bus_id: busId,
                            route_id: routeId
                        })
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
                            // Reload seat layout after schedules are loaded
                            loadSeatLayout();
                        })
                        .fail(function(xhr, status, error) {
                            console.log('Schedule loading failed:', error);
                            scheduleSelect.html('<option value="">@lang('No specific schedule')</option>');
                        });
                }
            });

            // Reload seat layout when schedule changes
            $('#bus_schedule_id').change(function() {
                loadSeatLayout();
            });

            // Load seat layout when date is selected
            $('#journey_date, #journey_date_start, #journey_date_end').change(function() {
                loadSeatLayout();
            });

            function loadSeatLayout() {
                const busId = $('#operator_bus_id').val();
                const scheduleId = $('#bus_schedule_id').val();
                const isDateRange = $('input[name="is_date_range"]:checked').val() == '1';
                let journeyDate, journeyDateEnd;

                if (isDateRange) {
                    journeyDate = $('#journey_date_start').val();
                    journeyDateEnd = $('#journey_date_end').val();
                } else {
                    journeyDate = $('#journey_date').val();
                    journeyDateEnd = null;
                }

                console.log('Loading seat layout for bus:', busId, 'schedule:', scheduleId, 'date:', journeyDate);

                if (!busId || !scheduleId || !journeyDate) {
                    console.log('Missing bus, schedule or date, hiding seat layout');
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

                $.get("{{ route('operator.bookings.get-seat-layout') }}", {
                        bus_id: busId,
                        schedule_id: scheduleId,
                        journey_date: journeyDate,
                        journey_date_end: journeyDateEnd,
                        is_date_range: isDateRange ? 1 : 0
                    })
                    .done(function(data) {
                        console.log('Seat layout loaded:', data);
                        if (data.error) {
                            console.log('Seat layout error:', data.error);
                            $('#seatLayoutContainer').html(
                                `<div class="alert alert-danger">${data.error}</div>`);
                            return;
                        }

                        // Log seat availability breakdown
                        console.log('Seat availability breakdown:', {
                            customer_booked: data.customer_booked_seats || [],
                            operator_blocked: data.operator_blocked_seats || [],
                            all_blocked: data.blocked_seats || [],
                            customer_count: (data.customer_booked_seats || []).length,
                            operator_count: (data.operator_blocked_seats || []).length,
                            total_blocked: (data.blocked_seats || []).length
                        });

                        // Render seat layout using parsed data (same format as SiteController and ApiTicketController)
                        const seatHtml = renderSeatLayout(data.html, data.blocked_seats || []);
                        $('#seatLayoutContainer').html(seatHtml);

                        // Add click handlers for seat selection - use event delegation for dynamically created elements
                        $('#seatLayoutContainer').off('click').on('click', '.nseat, .hseat, .vseat',
                    function() {
                            // Only available seats (nseat, hseat, vseat) are clickable
                            // Blocked seats (bseat, bhseat, bvseat) won't trigger this
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

            /**
             * Render seat layout from parsed JSON structure
             * Matches the rendering used in book_ticket.blade.php
             */
            function renderSeatLayout(parsedLayout, blockedSeats) {
                if (!parsedLayout || !parsedLayout.seat) {
                    return '<div class="alert alert-warning">No seat layout available</div>';
                }

                let html = '<div class="bus-seat-layout">';

                // Render upper deck if exists
                if (parsedLayout.seat.upper_deck && parsedLayout.seat.upper_deck.rows && Object.keys(parsedLayout
                        .seat.upper_deck.rows).length > 0) {
                    html += '<div class="deck-container upper-deck">';
                    html += '<h6 class="deck-title">Upper Deck</h6>';
                    html += '<div class="seat-grid" style="position: relative; min-height: 300px;">';
                    html += renderDeckSeats(parsedLayout.seat.upper_deck.rows);
                    html += '</div></div>';
                }

                // Render lower deck if exists
                if (parsedLayout.seat.lower_deck && parsedLayout.seat.lower_deck.rows && Object.keys(parsedLayout
                        .seat.lower_deck.rows).length > 0) {
                    html += '<div class="deck-container lower-deck">';
                    html += '<h6 class="deck-title">Lower Deck</h6>';
                    html += '<div class="seat-grid" style="position: relative; min-height: 300px;">';
                    html += renderDeckSeats(parsedLayout.seat.lower_deck.rows);
                    html += '</div></div>';
                }

                html += '</div>';
                return html;
            }

            /**
             * Render seats for a deck from rows array
             */
            function renderDeckSeats(rows) {
                let html = '';
                let seatTypeCounts = {};

                Object.values(rows).forEach(function(row) {
                    if (!Array.isArray(row)) return;

                    row.forEach(function(seat) {
                        if (!seat || !seat.seat_id) return;

                        const seatId = seat.seat_id;
                        const seatType = seat.type || 'nseat';
                        const position = seat.position || 0;
                        const left = seat.left || 0;
                        const price = seat.price || 0;

                        // Count seat types for debugging
                        seatTypeCounts[seatType] = (seatTypeCounts[seatType] || 0) + 1;

                        // Determine seat class based on type
                        let seatClass = seatType;

                        // Available seats: nseat, hseat, vseat (green/blue)
                        // Booked seats: bseat, bhseat, bvseat (red/unavailable)

                        html += `<div class="${seatClass}" id="${seatId}" `;
                        html += `style="position: absolute; top: ${position}px; left: ${left}px;" `;
                        html += `data-price="${price}" data-seat-id="${seatId}">`;
                        html += seatId;
                        html += '</div>';
                    });
                });

                console.log('Seat type counts:', seatTypeCounts);
                return html;
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

        .bus-seat-layout {
            position: relative;
            width: 100%;
        }

        .deck-container {
            margin-bottom: 30px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .deck-title {
            margin-bottom: 15px;
            color: #333;
            font-weight: 600;
            font-size: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }

        .seat-grid {
            position: relative;
            min-height: 300px;
            background-color: #fafafa;
            border: 1px solid #ddd;
            padding: 10px;
        }

        /* Seat styling - matches the standard seat layout across the application */
        .nseat,
        .hseat,
        .vseat,
        .bseat,
        .bhseat,
        .bvseat {
            position: absolute;
            cursor: pointer;
            border: 2px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            border-radius: 4px;
            transition: all 0.2s;
            width: 35px;
            height: 35px;
            color: white;
        }

        /* Available seats - Green */
        .nseat {
            background-color: #28a745;
            border-color: #1e7e34;
        }

        .nseat:hover {
            background-color: #218838 !important;
            transform: scale(1.1);
        }

        /* Available sleeper seats - Blue */
        .hseat,
        .vseat {
            background-color: #17a2b8;
            border-color: #117a8b;
            width: 35px;
            height: 70px;
        }

        .hseat:hover,
        .vseat:hover {
            background-color: #138496 !important;
            transform: scale(1.05);
        }

        /* Booked/Blocked seats - Red (not clickable) */
        .bseat,
        .bhseat,
        .bvseat {
            background-color: #dc3545 !important;
            border-color: #bd2130 !important;
            cursor: not-allowed !important;
        }

        .bhseat,
        .bvseat {
            width: 35px;
            height: 70px;
        }

        /* Selected seats - Yellow */
        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #ffc107 !important;
            border-color: #d39e00 !important;
            color: #000 !important;
        }

        /* Prevent interaction with booked seats */
        .bseat:hover,
        .bhseat:hover,
        .bvseat:hover {
            transform: none !important;
            cursor: not-allowed !important;
        }
    </style>
@endpush
