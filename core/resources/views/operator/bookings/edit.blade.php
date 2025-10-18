@extends('operator.layouts.app')

@section('panel')
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-0">@lang('Edit Booking') - #{{ $booking->id }}</h4>
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
                    <form action="{{ route('operator.bookings.update', $booking) }}" method="POST" id="bookingForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Bus Information (Read-only) -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Bus')</label>
                                    <div class="form-control-plaintext">
                                        <strong>{{ $booking->operatorBus->travel_name }}</strong> -
                                        {{ $booking->operatorBus->bus_type }}
                                    </div>
                                </div>
                            </div>

                            <!-- Route Information (Read-only) -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Route')</label>
                                    <div class="form-control-plaintext">
                                        {{ $booking->operatorRoute->originCity->city_name }} →
                                        {{ $booking->operatorRoute->destinationCity->city_name }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Date Range Toggle -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Booking Type')</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="is_date_range" id="singleDate"
                                            value="0"
                                            {{ old('is_date_range', $booking->is_date_range ? '1' : '0') == '0' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="singleDate">
                                            @lang('Single Date')
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="is_date_range" id="dateRange"
                                            value="1"
                                            {{ old('is_date_range', $booking->is_date_range ? '1' : '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="dateRange">
                                            @lang('Date Range')
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Schedule Information (Read-only) -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Schedule')</label>
                                    <div class="form-control-plaintext">
                                        @if ($booking->busSchedule)
                                            {{ $booking->busSchedule->schedule_name ?: 'Schedule #' . $booking->busSchedule->id }}
                                        @else
                                            @lang('No specific schedule')
                                        @endif
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
                                        name="journey_date" id="journey_date"
                                        value="{{ old('journey_date', $booking->journey_date->format('Y-m-d')) }}"
                                        min="{{ date('Y-m-d') }}" required>
                                    @error('journey_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Date Range Fields -->
                            <div class="col-md-6" id="dateRangeFields" style="display: none;">
                                <div class="form-group">
                                    <label for="journey_date_end">@lang('End Date') <span
                                            class="text-danger">*</span></label>
                                    <input type="date"
                                        class="form-control @error('journey_date_end') is-invalid @enderror"
                                        name="journey_date_end" id="journey_date_end"
                                        value="{{ old('journey_date_end', $booking->journey_date_end ? $booking->journey_date_end->format('Y-m-d') : '') }}"
                                        min="{{ date('Y-m-d') }}">
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
                                    <input type="text" class="form-control @error('booking_reason') is-invalid @enderror"
                                        name="booking_reason" id="booking_reason"
                                        value="{{ old('booking_reason', $booking->booking_reason) }}"
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
                                        placeholder="@lang('Additional notes...')">{{ old('notes', $booking->notes) }}</textarea>
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
                                    <div id="seatSelectionArea">
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> @lang('Loading available seats...')
                                        </div>
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
                                        <i class="fa fa-fw fa-save"></i>@lang('Update Booking')
                                    </button>
                                    <a href="{{ route('operator.bookings.show', $booking) }}"
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
        $(document).ready(function() {
            // Handle date range toggle
            $('input[name="is_date_range"]').change(function() {
                if ($(this).val() == '1') {
                    $('#singleDateField').hide();
                    $('#dateRangeFields').show();
                    $('#journey_date_end').prop('required', true);
                } else {
                    $('#singleDateField').show();
                    $('#dateRangeFields').hide();
                    $('#journey_date_end').prop('required', false);
                }
            });

            // Load available seats when date changes
            $('#journey_date, #journey_date_end').change(function() {
                loadAvailableSeats();
            });

            function loadAvailableSeats() {
                const busId = {{ $booking->operator_bus_id }};
                const journeyDate = $('#journey_date').val();
                const journeyDateEnd = $('#journey_date_end').val();
                const isDateRange = $('input[name="is_date_range"]:checked').val() == '1';

                if (!journeyDate) {
                    $('#seatSelectionArea').html(
                        '<div class="alert alert-info"><i class="fa fa-info-circle"></i> @lang('Please select a date to view available seats.')</div>'
                        );
                    return;
                }

                $('#seatSelectionArea').html(
                    '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> @lang('Loading available seats...')</div>');

                $.get('{{ route('operator.bookings.get-available-seats') }}', {
                        bus_id: busId,
                        journey_date: journeyDate,
                        journey_date_end: journeyDateEnd,
                        is_date_range: isDateRange
                    })
                    .done(function(data) {
                        if (data.error) {
                            $('#seatSelectionArea').html(`<div class="alert alert-danger">${data.error}</div>`);
                            return;
                        }

                        let seatHtml = '<div class="seat-selection-container">';
                        seatHtml += '<div class="row">';

                        if (data.available_seats && data.available_seats.length > 0) {
                            $.each(data.available_seats, function(index, seat) {
                                const isSelected = {{ json_encode($booking->blocked_seats) }}.includes(
                                    seat);
                                seatHtml += `
                                <div class="col-md-2 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input seat-checkbox" type="checkbox" name="blocked_seats[]" value="${seat}" id="seat_${seat}" ${isSelected ? 'checked' : ''}>
                                        <label class="form-check-label seat-label" for="seat_${seat}">
                                            ${seat}
                                        </label>
                                    </div>
                                </div>
                            `;
                            });
                        } else {
                            seatHtml +=
                                '<div class="col-12"><div class="alert alert-warning">@lang('No seats available for the selected date(s).')</div></div>';
                        }

                        seatHtml += '</div>';
                        seatHtml += '<div class="mt-3">';
                        seatHtml +=
                            '<button type="button" class="btn btn-sm btn--info" onclick="selectAllSeats()">@lang('Select All')</button> ';
                        seatHtml +=
                            '<button type="button" class="btn btn-sm btn--secondary" onclick="clearAllSeats()">@lang('Clear All')</button>';
                        seatHtml += '</div>';
                        seatHtml += '</div>';

                        $('#seatSelectionArea').html(seatHtml);
                    })
                    .fail(function() {
                        $('#seatSelectionArea').html(
                        '<div class="alert alert-danger">@lang('Error loading available seats.')</div>');
                    });
            }

            // Initialize date range visibility
            if ($('input[name="is_date_range"]:checked').val() == '1') {
                $('#singleDateField').hide();
                $('#dateRangeFields').show();
                $('#journey_date_end').prop('required', true);
            }

            // Load seats on page load
            loadAvailableSeats();
        });

        function selectAllSeats() {
            $('.seat-checkbox').prop('checked', true);
        }

        function clearAllSeats() {
            $('.seat-checkbox').prop('checked', false);
        }
    </script>

    <style>
        .seat-selection-container {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background-color: #f9f9f9;
        }

        .seat-checkbox {
            margin-right: 5px;
        }

        .seat-label {
            font-weight: bold;
            cursor: pointer;
        }

        .seat-checkbox:checked+.seat-label {
            color: #007bff;
        }
    </style>
@endpush
