@extends('agent.layouts.app')

@section('panel')
    <div class="container-fluid px-0">
        <!-- Booking Header -->
        <div class="card mb-2">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 text-dark" style="font-size: 0.9rem; font-weight: 600;">
                            {{-- <i class="las la-route text-primary"></i> --}}
                            {{ $originCity->city_name }} → {{ $destinationCity->city_name }}
                        </h6>
                        <small class="text-muted">
                            <i class="las la-calendar me-1"></i>
                            {{ \Carbon\Carbon::parse(session('date_of_journey'))->format('M j') }}
                        </small>
                    </div>
                    <div>
                        @if (auth('agent')->check())
                            <a href="{{ route('agent.search') }}" class="btn btn-sm btn-outline-primary">
                                <i class="las la-search"></i>
                                @lang('New Search')
                            </a>
                        @elseif(auth('admin')->check())
                            <a href="{{ route('admin.booking.search') }}" class="btn btn-sm btn-outline-primary">
                                <i class="las la-search"></i>
                                @lang('New Search')
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Layout: Boarding/Dropping Points First -->
        <div class="d-block d-lg-none">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="las la-map-marker"></i>
                        @lang('Boarding & Dropping Points')
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <label class="form-label">@lang('Boarding Point') *</label>
                            <select class="form-control" id="boarding_point_select_mobile" required>
                                <option value="">@lang('Select Boarding Point')</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">@lang('Dropping Point') *</label>
                            <select class="form-control" id="dropping_point_select_mobile" required>
                                <option value="">@lang('Select Dropping Point')</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-between">
            <!-- Left Column - Customer Details -->
            <div class="col-lg-4 col-md-4 order-2 order-lg-1">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="las la-user"></i>
                            @lang('Customer Details')
                        </h6>
                    </div>
                    <div class="card-body">
                        <form
                            action="{{ auth('agent')->check() ? route('agent.booking.block') : route('admin.booking.block') }}"
                            method="POST" id="agentBookingForm">
                            @csrf

                            <!-- Journey Details (Read-only) -->
                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Journey Date')</label>
                                <input type="text" class="form-control"
                                    value="{{ \Carbon\Carbon::parse(session('date_of_journey'))->format('M d, Y') }}"
                                    disabled />
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Route')</label>
                                <input type="text" class="form-control"
                                    value="{{ $originCity->city_name }} → {{ $destinationCity->city_name }}" disabled>
                            </div>

                            <!-- Agent/Admin Contact Info (Pre-filled) -->
                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Phone Number') *</label>
                                <div class="input-group">
                                    <span class="input-group-text">+91</span>
                                    <input type="tel" class="form-control" name="passenger_phone"
                                        value="@if (auth('agent')->check()) {{ auth('agent')->user()->phone ?? '' }}@elseif(auth('admin')->check()){{ '' }} @endif"
                                        required />
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Email') *</label>
                                <input type="email" class="form-control" name="passenger_email"
                                    value="{{ auth('agent')->check() ? auth('agent')->user()->email ?? '' : (auth('admin')->check() ? auth('admin')->user()->email ?? '' : '') }}"
                                    required />
                            </div>

                            <!-- Passenger Details (Dynamic based on selected seats) -->
                            <div id="passengerDetails"></div>

                            <!-- Commission Input -->
                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Commission Amount') (₹)</label>
                                <input type="number" class="form-control" id="commissionInput" min="0"
                                    step="0.01" value="0" />
                                <small class="text-muted">Add commission amount to total</small>
                            </div>

                            <!-- Booking Summary -->
                            <div class="booking-summary mb-3" id="bookingSummary" style="display: none;">
                                <h6>@lang('Booking Summary')</h6>
                                <div class="d-flex justify-content-between">
                                    <span>@lang('Base Fare'):</span>
                                    <span id="baseFare">₹0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>@lang('Commission'):</span>
                                    <span id="commissionDisplay">₹0.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between font-weight-bold">
                                    <span>@lang('Total'):</span>
                                    <span id="totalAmount">₹0.00</span>
                                </div>
                            </div>

                            <!-- Hidden Fields -->
                            <input type="hidden" name="boarding_point_index" id="boarding_point_index">
                            <input type="hidden" name="dropping_point_index" id="dropping_point_index">
                            <input type="hidden" name="seats" id="selected_seats">
                            <input type="hidden" name="price" id="total_price">
                            <input type="hidden" name="agent_id" value="{{ auth('agent')->id() }}">
                            <input type="hidden" name="booking_source" value="agent">

                            <!-- Desktop Button -->
                            <div class="d-none d-lg-block">
                                <button type="submit" class="btn btn-primary w-100" id="bookButton" disabled>
                                    <i class="las la-credit-card"></i>
                                    @lang('Continue to Payment')
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column - Seat Selection -->
            <div class="col-lg-7 col-md-7 order-1 order-lg-2">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="las la-bus"></i>
                            @lang('Select Seats')
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Seat Layout -->
                        <div class="bus">
                            @if ($seatHtml || ($parsedLayout && isset($parsedLayout['seat'])))
                                @include('templates.basic.partials.seatlayout', [
                                    'seatHtml' => $seatHtml,
                                    'parsedLayout' => $parsedLayout,
                                    'isOperatorBus' => $isOperatorBus,
                                ])
                            @else
                                <div class="alert alert-warning">
                                    <i class="las la-exclamation-triangle"></i>
                                    @lang('Seat layout is loading... Please wait or try refreshing the page.')
                                    <br>
                                    <small>Debug: seatHtml={{ $seatHtml ? 'Present' : 'Empty' }},
                                        parsedLayout={{ $parsedLayout ? 'Present' : 'Empty' }}</small>
                                </div>
                            @endif
                        </div>

                        <!-- Seat Legend -->
                        <div class="seat-legend mt-3">
                            <div class="d-flex flex-wrap gap-3">
                                <div class="d-flex align-items-center">
                                    <div class="seat-legend-item available"></div>
                                    <small>@lang('Available')</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="seat-legend-item selected"></div>
                                    <small>@lang('Selected')</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="seat-legend-item booked"></div>
                                    <small>@lang('Booked')</small>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Boarding & Dropping Points -->
                        <div class="d-none d-lg-block">
                            <div class="row mt-4 justify-content-between">
                                <div class="col-md-5">
                                    <div class="form-group mb-3">
                                        <label class="form-label" for="boarding_point_select">@lang('Boarding Point') *</label>
                                        <select class="form-control" id="boarding_point_select" required>
                                            <option value="">@lang('Select Boarding Point')</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="form-group mb-3">
                                        <label class="form-label" for="dropping_point_select">@lang('Dropping Point') *</label>
                                        <select class="form-control" id="dropping_point_select" required>
                                            <option value="">@lang('Select Dropping Point')</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Sticky Bottom Bar -->
        <div class="d-block d-lg-none">
            <div class="mobile-sticky-bar">
                <div class="container-fluid px-0">
                    <div class="d-flex"></div>
                    <button type="button" class="btn btn-outline-secondary w-48" id="resetButton">
                        <i class="las la-undo"></i>
                        @lang('Reset')
                    </button>
                    <button type="submit" form="agentBookingForm" class="btn btn-primary w-48 mx-1"
                        id="bookButtonMobile" disabled>
                        <i class="las la-credit-card"></i>
                        @lang('Proceed to Pay')
                    </button>
                </div>
            </div>
        </div>
    </div>

    </div>
@endsection

@push('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Check if selectedSeats is already declared
        if (typeof selectedSeats === 'undefined') {
            var selectedSeats = [];
        }
        var baseFare = 0;
        var commissionAmount = 0;

        document.addEventListener('DOMContentLoaded', function() {
            // Load boarding/dropping points
            loadBoardingPoints();

            // Handle commission input change
            document.getElementById('commissionInput').addEventListener('input', updateBookingSummary);

            // Handle reset button
            document.getElementById('resetButton').addEventListener('click', function() {
                resetForm();
            });

            // Handle form submission
            document.getElementById('agentBookingForm').addEventListener('submit', function(e) {
                e.preventDefault();

                if (selectedSeats.length === 0) {
                    alert('@lang('Please select at least one seat')');
                    return;
                }

                // Validate passenger details
                if (!validatePassengerDetails()) {
                    return;
                }

                // Set hidden field values - handle both desktop and mobile
                const boardingSelect = document.getElementById('boarding_point_select');
                const droppingSelect = document.getElementById('dropping_point_select');
                const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
                const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

                if (boardingSelect && droppingSelect) {
                    document.getElementById('boarding_point_index').value = boardingSelect.value;
                    document.getElementById('dropping_point_index').value = droppingSelect.value;
                } else if (boardingSelectMobile && droppingSelectMobile) {
                    document.getElementById('boarding_point_index').value = boardingSelectMobile.value;
                    document.getElementById('dropping_point_index').value = droppingSelectMobile.value;
                }
                document.getElementById('selected_seats').value = selectedSeats.join(',');
                document.getElementById('total_price').value = baseFare + commissionAmount;

                // Submit form
                this.submit();
            });
        });

        // Override the AddRemoveSeat function for agent booking
        window.AddRemoveSeat = function(element, seatId, price) {
            console.log('AddRemoveSeat called:', {
                element,
                seatId,
                price
            });

            const seatNumber = seatId;
            const seatPrice = parseFloat(price);

            element.classList.toggle('selected');
            const alreadySelected = selectedSeats.includes(seatNumber);

            if (!alreadySelected) {
                selectedSeats.push(seatNumber);
                baseFare += seatPrice;
                console.log('Seat added:', seatNumber, 'Total seats:', selectedSeats.length);
            } else {
                selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
                baseFare -= seatPrice;
                console.log('Seat removed:', seatNumber, 'Total seats:', selectedSeats.length);
            }

            updatePassengerDetails();
            updateBookingSummary();
            updateBookButton();
        }

        function updatePassengerDetails() {
            console.log('updatePassengerDetails called with seats:', selectedSeats);
            const container = document.getElementById('passengerDetails');
            container.innerHTML = '';

            if (selectedSeats.length === 0) {
                container.innerHTML =
                    '<div class="text-muted text-center py-3"><i class="las la-info-circle"></i> Select seats to add passenger details</div>';
                return;
            }

            selectedSeats.forEach((seatId, index) => {
                const passengerDiv = document.createElement('div');
                passengerDiv.className = 'passenger-card mb-2';

                // Get language strings
                const fullNamePlaceholder = '{{ __('Full Name') }}';
                const agePlaceholder = '{{ __('Age') }}';
                const genderText = '{{ __('Gender') }}';
                const maleText = '{{ __('Male') }}';
                const femaleText = '{{ __('Female') }}';
                const otherText = '{{ __('Other') }}';

                passengerDiv.innerHTML = `
                                    <div class="passenger-header">
                                        <span class="passenger-number">P${index + 1}</span>
                                        <span class="seat-badge">Seat ${seatId}</span>
                                    </div>
                                    <div class="passenger-fields">
                                        <div class="field-group">
                                            <input type="text" class="form-control" name="passenger_names[]" required 
                                                placeholder="${fullNamePlaceholder}" data-seat="${seatId}">
                                        </div>
                                        <div class="field-group">
                                            <input type="number" class="form-control" name="passenger_ages[]" required 
                                                min="1" max="120" placeholder="${agePlaceholder}" data-seat="${seatId}">
                                        </div>
                                        <div class="field-group">
                                            <select class="form-control" name="passenger_genders[]" required data-seat="${seatId}">
                                                <option value="">${genderText}</option>
                                                <option value="1">${maleText}</option>
                                                <option value="2">${femaleText}</option>
                                                <option value="3">${otherText}</option>
                                            </select>
                                        </div>
                                    </div>
                                `;
                container.appendChild(passengerDiv);
            });
        }

        function updateBookingSummary() {
            commissionAmount = parseFloat(document.getElementById('commissionInput').value) || 0;
            const total = baseFare + commissionAmount;

            document.getElementById('baseFare').textContent = '₹' + baseFare.toFixed(2);
            document.getElementById('commissionDisplay').textContent = '₹' + commissionAmount.toFixed(2);
            document.getElementById('totalAmount').textContent = '₹' + total.toFixed(2);

            if (selectedSeats.length > 0) {
                document.getElementById('bookingSummary').style.display = 'block';
            } else {
                document.getElementById('bookingSummary').style.display = 'none';
            }
        }

        function updateBookButton() {
            const bookButton = document.getElementById('bookButton');
            const bookButtonMobile = document.getElementById('bookButtonMobile');
            const hasSeats = selectedSeats.length > 0;

            // Check desktop boarding points
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');

            // Check mobile boarding points
            const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
            const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

            let hasBoardingPoint = false;
            let hasDroppingPoint = false;

            if (boardingSelect && droppingSelect) {
                hasBoardingPoint = boardingSelect.value;
                hasDroppingPoint = droppingSelect.value;
            } else if (boardingSelectMobile && droppingSelectMobile) {
                hasBoardingPoint = boardingSelectMobile.value;
                hasDroppingPoint = droppingSelectMobile.value;
            }

            const isFormValid = hasSeats && hasBoardingPoint && hasDroppingPoint;

            if (bookButton) bookButton.disabled = !isFormValid;
            if (bookButtonMobile) bookButtonMobile.disabled = !isFormValid;
        }

        function loadBoardingPoints() {
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');
            const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
            const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

            // Show loading state
            if (boardingSelect) boardingSelect.innerHTML = '<option value="">@lang('Loading...')</option>';
            if (droppingSelect) droppingSelect.innerHTML = '<option value="">@lang('Loading...')</option>';
            if (boardingSelectMobile) boardingSelectMobile.innerHTML = '<option value="">@lang('Loading...')</option>';
            if (droppingSelectMobile) droppingSelectMobile.innerHTML = '<option value="">@lang('Loading...')</option>';

            // Load boarding points from API
            fetch('{{ route('agent.booking.boarding-points') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateBoardingPoints(data.data.BoardingPointsDetails || []);
                        populateDroppingPoints(data.data.DroppingPointsDetails || []);
                    } else {
                        console.error('Error loading boarding points:', data.message);
                        showError('Failed to load boarding points');
                    }
                })
                .catch(error => {
                    console.error('Error loading boarding points:', error);
                    showError('Failed to load boarding points');
                });

            // Add event listeners for validation
            if (boardingSelect) boardingSelect.addEventListener('change', updateBookButton);
            if (droppingSelect) droppingSelect.addEventListener('change', updateBookButton);
            if (boardingSelectMobile) boardingSelectMobile.addEventListener('change', updateBookButton);
            if (droppingSelectMobile) droppingSelectMobile.addEventListener('change', updateBookButton);
        }

        function populateBoardingPoints(points) {
            const select = document.getElementById('boarding_point_select');
            const selectMobile = document.getElementById('boarding_point_select_mobile');

            if (select) {
                select.innerHTML = '<option value="">@lang('Select Boarding Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;

                    // Format time for display
                    let timeStr = '';
                    if (point.CityPointTime) {
                        const time = new Date(point.CityPointTime);
                        timeStr = time.toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }

                    // Build display text with name, time, and contact
                    let displayText = point.CityPointName || '';
                    if (timeStr) {
                        displayText += ` - ${timeStr}`;
                    }
                    if (point.CityPointContactNumber) {
                        displayText += ` (${point.CityPointContactNumber})`;
                    }

                    option.textContent = displayText;
                    // Store additional data in data attributes for tooltip/display
                    option.setAttribute('data-time', timeStr);
                    option.setAttribute('data-contact', point.CityPointContactNumber || '');
                    option.setAttribute('data-location', point.CityPointLocation || point.CityPointName || '');
                    select.appendChild(option);
                });
            }

            if (selectMobile) {
                selectMobile.innerHTML = '<option value="">@lang('Select Boarding Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;

                    // Format time for display
                    let timeStr = '';
                    if (point.CityPointTime) {
                        const time = new Date(point.CityPointTime);
                        timeStr = time.toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }

                    // Build display text with name, time, and contact
                    let displayText = point.CityPointName || '';
                    if (timeStr) {
                        displayText += ` - ${timeStr}`;
                    }
                    if (point.CityPointContactNumber) {
                        displayText += ` (${point.CityPointContactNumber})`;
                    }

                    option.textContent = displayText;
                    // Store additional data in data attributes
                    option.setAttribute('data-time', timeStr);
                    option.setAttribute('data-contact', point.CityPointContactNumber || '');
                    option.setAttribute('data-location', point.CityPointLocation || point.CityPointName || '');
                    selectMobile.appendChild(option);
                });
            }
        }

        function populateDroppingPoints(points) {
            const select = document.getElementById('dropping_point_select');
            const selectMobile = document.getElementById('dropping_point_select_mobile');

            if (select) {
                select.innerHTML = '<option value="">@lang('Select Dropping Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;

                    // Format time for display
                    let timeStr = '';
                    if (point.CityPointTime) {
                        const time = new Date(point.CityPointTime);
                        timeStr = time.toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }

                    // Build display text with name, time, and contact
                    let displayText = point.CityPointName || '';
                    if (timeStr) {
                        displayText += ` - ${timeStr}`;
                    }
                    if (point.CityPointContactNumber) {
                        displayText += ` (${point.CityPointContactNumber})`;
                    }

                    option.textContent = displayText;
                    // Store additional data in data attributes for tooltip/display
                    option.setAttribute('data-time', timeStr);
                    option.setAttribute('data-contact', point.CityPointContactNumber || '');
                    option.setAttribute('data-location', point.CityPointLocation || point.CityPointName || '');
                    select.appendChild(option);
                });
            }

            if (selectMobile) {
                selectMobile.innerHTML = '<option value="">@lang('Select Dropping Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;

                    // Format time for display
                    let timeStr = '';
                    if (point.CityPointTime) {
                        const time = new Date(point.CityPointTime);
                        timeStr = time.toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }

                    // Build display text with name, time, and contact
                    let displayText = point.CityPointName || '';
                    if (timeStr) {
                        displayText += ` - ${timeStr}`;
                    }
                    if (point.CityPointContactNumber) {
                        displayText += ` (${point.CityPointContactNumber})`;
                    }

                    option.textContent = displayText;
                    // Store additional data in data attributes
                    option.setAttribute('data-time', timeStr);
                    option.setAttribute('data-contact', point.CityPointContactNumber || '');
                    option.setAttribute('data-location', point.CityPointLocation || point.CityPointName || '');
                    selectMobile.appendChild(option);
                });
            }
        }

        function showError(message) {
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');

            boardingSelect.innerHTML = `<option value="">${message}</option>`;
            droppingSelect.innerHTML = `<option value="">${message}</option>`;
        }

        function validatePassengerDetails() {
            const passengerNames = document.querySelectorAll('.passenger-name');
            const passengerAges = document.querySelectorAll('.passenger-age');
            const passengerGenders = document.querySelectorAll('.passenger-gender');

            let isValid = true;

            // Validate names
            passengerNames.forEach((input, index) => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            // Validate ages
            passengerAges.forEach((input, index) => {
                if (!input.value || input.value < 1 || input.value > 120) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            // Validate genders
            passengerGenders.forEach((input, index) => {
                if (!input.value) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                alert('@lang('Please fill in all passenger details correctly')');
            }

            return isValid;
        }

        function resetForm() {
            // Clear selected seats
            selectedSeats = [];
            baseFare = 0;
            commissionAmount = 0;

            // Remove selected class from all seats
            document.querySelectorAll('.seat.selected').forEach(seat => {
                seat.classList.remove('selected');
            });

            // Clear passenger details
            document.getElementById('passengerDetails').innerHTML =
                '<div class="text-muted text-center py-3"><i class="las la-info-circle"></i> Select seats to add passenger details</div>';

            // Reset commission input
            document.getElementById('commissionInput').value = 0;

            // Reset boarding/dropping points
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');
            const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
            const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

            if (boardingSelect) boardingSelect.value = '';
            if (droppingSelect) droppingSelect.value = '';
            if (boardingSelectMobile) boardingSelectMobile.value = '';
            if (droppingSelectMobile) droppingSelectMobile.value = '';

            // Update UI
            updateBookingSummary();
            updateBookButton();

            // Scroll to top
            window.scrollTo(0, 0);
        }
    </script>
@endpush

@push('style')
    <style>
        /* Seat Legend Styles */
        .seat-legend-item {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 8px;
        }

        .seat-legend-item.available {
            background-color: #fff;
            border: 1px solid #ccc;
        }

        .seat-legend-item.selected {
            background-color: #c8e6c9;
            border: 1px solid #81c784;
        }

        .seat-legend-item.booked {
            background-color: #e0e0e0;
            border: 1px solid #bdbdbd;
        }

        /* Passenger Detail Styles */
        .passenger-detail {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6 !important;
        }

        .passenger-detail h6 {
            color: #495057;
            font-weight: 600;
        }

        /* Booking Summary Styles */
        .booking-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .booking-summary h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 10px;
        }

        /* Form Styles */
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-primary:disabled {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        /* Input Group Styles */
        .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #495057;
        }

        /* Compact Passenger Cards */
        .passenger-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }

        .passenger-card:hover {
            border-color: #007bff;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.1);
        }

        .passenger-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e9ecef;
        }

        .passenger-number {
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .seat-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .passenger-fields {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 8px;
            align-items: end;
        }

        .field-group {
            display: flex;
            flex-direction: column;
        }

        .field-group .form-control {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 0.875rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .field-group .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .field-group .form-control::placeholder {
            color: #6c757d;
            font-size: 0.8rem;
        }

        /* Mobile Sticky Bar */
        .mobile-sticky-bar {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 15px;
            z-index: 1000;
        }

        /* Add bottom padding to body on mobile to prevent content from being hidden behind sticky bar */
        @media (max-width: 991px) {
            body {
                padding-bottom: 80px;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .passenger-fields {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .passenger-card {
                padding: 10px;
            }

            /* Ensure proper spacing for mobile layout */
            .order-1 {
                order: 1;
            }

            .order-2 {
                order: 2;
            }
        }

        /* Desktop Layout */
        @media (min-width: 992px) {
            .order-lg-1 {
                order: 1;
            }

            .order-lg-2 {
                order: 2;
            }
        }
    </style>
@endpush
