@extends($activeTemplate . $layout)

@section('content')
    <div class="row justify-content-between mx-2 p-2">
        {{-- Display active coupon banner --}}
        @if (isset($currentCoupon) &&
                $currentCoupon->status &&
                $currentCoupon->expiry_date &&
                $currentCoupon->expiry_date->isFuture())
            <div class="coupon-display-banner">
                <p>🎉 **{{ $currentCoupon->coupon_name }}** Applied!
                    @if ($currentCoupon->discount_type == 'fixed')
                        Save {{ __($general->cur_sym) }}{{ showAmount($currentCoupon->coupon_value) }}
                    @elseif($currentCoupon->discount_type == 'percentage')
                        Save {{ showAmount($currentCoupon->coupon_value) }}%
                    @endif
                    on your booking! Book before {{ showDateTime($currentCoupon->expiry_date, 'F j, Y') }} to avail this
                    offer.
                </p>
            </div>
        @endif

        {{-- Left column to denote seat details and booking form --}}
        <div class="col-lg-4 col-md-4">
            <div class="seat-overview-wrapper">
                <form action="{{ route('block.seat') }}" method="POST" id="bookingForm" class="row gy-2">
                    @csrf
                    <div class="col-12">
                        <div class="form-group">
                            <i class="las la-calendar"></i>
                            <label for="date_of_journey"class="form-label">@lang('Journey Date')</label>
                            <input type="text" id="date_of_journey" class="form--control datpicker"
                                value="{{ Session::get('date_of_journey') ? Session::get('date_of_journey') : date('m/d/Y') }}"
                                name="date_of_journey" disabled>
                        </div>
                    </div>
                    <div class="col-12">
                        <i class="las la-location-arrow"></i>
                        <label for="origin-id" class="form-label">@lang('Pickup Point')</label>
                        <div class="form--group">
                            <input type="text" disabled id="origin-id" name="OriginId" class="form--control"
                                value="{{ $originCity->city_name }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <i class="las la-map-marker"></i>
                        <label for="destination-id" class="form-label">@lang('Dropping Point')</label>
                        <div class="form--group">
                            <input type="text" disabled id="destination-id" class="form--control" name="DestinationId"
                                value="{{ $destinationCity->city_name }}">
                        </div>
                    </div>
                    {{-- Hidden input for gender (will be set based on passenger title) --}}
                    <input type="hidden" name="gender" id="selected_gender" value="1">
                    <div class="col-12">
                        <div class="booked-seat-details d-none my-3" id="billing-details">
                            <h6 class="booking-summary-title">@lang('Booking Summary')</h6>
                            <div class="booking-summary-card">
                                {{-- Selected Seats --}}
                                <div class="selected-seats-section">
                                    <div class="selected-seat-details"></div>
                                </div>

                                {{-- Fare Breakdown --}}
                                <div class="fare-breakdown">
                                    {{-- Subtotal --}}
                                    <div class="fare-item">
                                        <span class="fare-label">@lang('Base Fare')</span>
                                        <span class="fare-amount" id="subtotalDisplay">₹0.00</span>
                                    </div>

                                    {{-- Service Charge --}}
                                    <div class="fare-item service-charge-display d-none">
                                        <span class="fare-label">@lang('Service Charge') (<span
                                                id="serviceChargePercentage">0</span>%)</span>
                                        <span class="fare-amount" id="serviceChargeAmount">₹0.00</span>
                                    </div>

                                    {{-- Platform Fee --}}
                                    <div class="fare-item platform-fee-display d-none">
                                        <span class="fare-label">@lang('Platform Fee') (<span
                                                id="platformFeePercentage">0</span>% + ₹<span
                                                id="platformFeeFixed">0</span>)</span>
                                        <span class="fare-amount" id="platformFeeAmount">₹0.00</span>
                                    </div>

                                    {{-- GST --}}
                                    <div class="fare-item gst-display d-none">
                                        <span class="fare-label">@lang('GST') (<span
                                                id="gstPercentage">0</span>%)</span>
                                        <span class="fare-amount" id="gstAmount">₹0.00</span>
                                    </div>

                                    {{-- Coupon Discount --}}
                                    @if (isset($currentCoupon) &&
                                            $currentCoupon->status &&
                                            $currentCoupon->expiry_date &&
                                            $currentCoupon->expiry_date->isFuture())
                                        <div class="fare-item coupon-discount-display">
                                            <span class="fare-label text-success">@lang('Coupon Discount')</span>
                                            <span class="fare-amount text-success"
                                                id="totalCouponDiscountDisplay">-₹0.00</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Total --}}
                                <div class="total-section">
                                    <div class="total-item">
                                        <span class="total-label">@lang('Total Amount')</span>
                                        <span class="total-amount" id="totalPriceDisplay">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="text" name="seats" hidden>
                        <input type="text" name="price" hidden>

                        {{-- Hidden fields for booking data --}}
                        <input type="hidden" name="boarding_point_index" id="form_boarding_point_index">
                        <input type="hidden" name="dropping_point_index" id="form_dropping_point_index">
                        <input type="hidden" name="passenger_title" id="form_passenger_title">
                        <input type="hidden" name="passenger_firstname" id="form_passenger_firstname">
                        <input type="hidden" name="passenger_lastname" id="form_passenger_lastname">
                        <input type="hidden" name="passenger_email" id="form_passenger_email">
                        <input type="hidden" name="passenger_phone" id="form_passenger_phone">
                        <input type="hidden" name="passenger_age" id="form_passenger_age">
                        <input type="hidden" name="passenger_address" id="form_passenger_address">
                        <input type="hidden" name="boarding_point_name" id="form_boarding_point_name">
                        <input type="hidden" name="boarding_point_location" id="form_boarding_point_location">
                        <input type="hidden" name="boarding_point_time" id="form_boarding_point_time">
                        <input type="hidden" name="dropping_point_name" id="form_dropping_point_name">
                        <input type="hidden" name="dropping_point_location" id="form_dropping_point_location">
                        <input type="hidden" name="dropping_point_time" id="form_dropping_point_time">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="book-bus-btn btn-primary">@lang('Continue to Booking')</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Right column with seat layout -->
        <div class="col-lg-7 col-md-7">
            <div class="seat-overview-wrapper">
                @include($activeTemplate . 'partials.seatlayout', ['seatHtml' => $seatHtml])
                <div class="seat-for-reserved">
                    <div class="seat-condition available-seat">
                        <span class="seat"><span></span></span>
                        <p>@lang('Available Seats')</p>
                    </div>
                    <div class="seat-condition selected-by-you">
                        <span class="seat"><span></span></span>
                        <p>@lang('Selected by You')</p>
                    </div>
                    <div class="seat-condition selected-by-gents">
                        <div class="seat"><span></span></div>
                        <p>@lang('Booked by Gents')</p>
                    </div>
                    <div class="seat-condition selected-by-ladies">
                        <div class="seat"><span></span></div>
                        <p>@lang('Booked by Ladies')</p>
                    </div>
                    <div class="seat-condition selected-by-others">
                        <div class="seat"><span></span></div>
                        <p>@lang('Booked by Others')</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add this flyout for booking process -->
    <div class="booking-flyout" id="bookingFlyout">
        <div class="flyout-overlay" id="flyoutOverlay"></div>
        <div class="flyout-content">
            <div class="flyout-header">
                <h5 class="flyout-title">@lang('Complete Your Booking')</h5>
                <button type="button" class="flyout-close" id="closeFlyout">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="flyout-body">
                <!-- Step indicator -->
                <ul class="nav nav-tabs justify-content-center mb-4" id="bookingSteps" role="tablist"
                    style="justify-content: left!important;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="boarding-tab" data-bs-toggle="tab"
                            data-bs-target="#boarding-content" type="button" role="tab">
                            @lang('Boarding & Dropping')
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="passenger-tab" data-bs-toggle="tab"
                            data-bs-target="#passenger-content" type="button" role="tab">
                            @lang('Passenger Details')
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment-content"
                            type="button" role="tab">
                            @lang('Payment')
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <!-- Step 1: Boarding & Dropping Points -->
                    <div class="tab-pane fade show active" id="boarding-content" role="tabpanel">
                        <div class="step-title">@lang('Select Boarding & Dropping Points')</div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3">@lang('Boarding Points')</h6>
                                <div class="boarding-points-container">
                                    <!-- Boarding points will be loaded here -->
                                    <div class="py-5 text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">@lang('Dropping Points')</h6>
                                <div class="dropping-points-container">
                                    <!-- Dropping points will be loaded here -->
                                    <div class="py-5 text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="selected_boarding_point" id="selected_boarding_point">
                        <input type="hidden" name="selected_dropping_point" id="selected_dropping_point">
                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-primary btn-sm next-btn" id="nextToPassengerBtn">
                                @lang('Continue')
                            </button>
                        </div>
                    </div>
                    <!-- Step 2: Passenger Details -->
                    <div class="tab-pane fade" id="passenger-content" role="tabpanel">
                        <div class="step-title">@lang('Passenger Details')</div>
                        <div class="passenger-details">
                            <h6 class="mb-3">@lang('Passenger Information')</h6>
                            <div class="row gy-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Title')<span
                                                class="text-danger">*</span></label>
                                        <select class="form--control" name="passenger_title" id="passenger_title">
                                            <option value="Mr" selected>@lang('Mr')</option>
                                            <option value="Ms">@lang('Ms')</option>
                                            <option value="Other">@lang('Other')</option>
                                        </select>
                                        <div class="invalid-feedback">This field is required!</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Age')<span
                                                class="text-danger">*</span></label>
                                        <input type="number" class="form--control" id="passenger_age"
                                            placeholder="@lang('Enter Age')" min="1" max="120"
                                            value="29">
                                        <div class="invalid-feedback">This field is required!</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('First Name')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form--control" id="passenger_firstname"
                                            placeholder="@lang('Enter First Name')"
                                            value="{{ auth()->check() ? auth()->user()->firstname : '' }}">
                                        <div class="invalid-feedback">This field is required!</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Last Name')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form--control" id="passenger_lastname"
                                            placeholder="@lang('Enter Last Name')"
                                            value="{{ auth()->check() ? auth()->user()->lastname : '' }}">
                                        <div class="invalid-feedback">This field is required!</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Email')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form--control" id="passenger_email"
                                            placeholder="@lang('Enter Email')"
                                            value="{{ auth()->check() ? auth()->user()->email : '' }}">
                                        <div class="invalid-feedback">This field is required!</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Phone Number')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="tel" class="form--control my-2" id="passenger_phone"
                                                name="passenger_phone" placeholder="@lang('Enter your WhatsApp mobile number')" value="">
                                            <button type="button" class="btn btn-primary btn-sm otp-btn"
                                                id="sendOtpBtn">
                                                @lang('Send OTP to WhatsApp')
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">This field is required!</div>
                                    </div>
                                </div>
                                <!-- Add OTP verification field (initially hidden) -->
                                <div class="col-md-6 d-none" id="otpVerificationContainer">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Enter OTP')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form--control my-2" id="otp_code"
                                                name="otp_code" placeholder="@lang('Enter 6-digit OTP received on WhatsApp')" maxlength="6">
                                            <button type="button" class="btn btn-primary btn-sm otp-btn"
                                                id="verifyOtpBtn">
                                                @lang('Verify OTP')
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">Invalid OTP!</div>
                                        <small class="text-muted">OTP sent to your WhatsApp number</small>
                                    </div>
                                </div>
                                <!-- Add hidden field to track OTP verification status -->
                                <input type="hidden" name="is_otp_verified" id="is_otp_verified" value="0">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Address')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form--control" id="passenger_address" placeholder="@lang('Enter Address')"></textarea>
                                        <div class="invalid-feedback">This field is required!</div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-3">
                                <button type="button" class="btn btn--danger btn--sm mx-2" id="backToBoardingBtn">
                                    @lang('Back')
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm mx-2" id="confirmPassengerBtn">
                                    @lang('Proceed to Payment')
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Step 3: Payment -->
                    <div class="tab-pane fade" id="payment-content" role="tabpanel">
                        <div class="step-title">@lang('Payment & Confirmation')</div>
                        <!-- Payment content will be handled by Razorpay -->
                        <div class="py-5 text-center">
                            <p>@lang('You will be redirected to the payment gateway.')</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- End of Booking Form flyout --}}
@endsection

@php
    use App\Models\MarkupTable;
    use App\Models\CouponTable;
    use Carbon\Carbon;

    $markupData = \App\Models\MarkupTable::orderBy('id', 'desc')->first();
    $flatMarkup = isset($markupData->flat_markup) ? (float) $markupData->flat_markup : 0;
    $percentageMarkup = isset($markupData->percentage_markup) ? (float) $markupData->percentage_markup : 0;
    $threshold = isset($markupData->threshold) ? (float) $markupData->threshold : 0;

    // Fetch fee settings from general settings
    $generalSettings = \App\Models\GeneralSetting::first();
    $gstPercentage = $generalSettings->gst_percentage ?? 0;
    $serviceChargePercentage = $generalSettings->service_charge_percentage ?? 0;
    $platformFeePercentage = $generalSettings->platform_fee_percentage ?? 0;
    $platformFeeFixed = $generalSettings->platform_fee_fixed ?? 0;

    // Fetch the current active and unexpired coupon directly in the blade file using fully qualified class names
    $currentCoupon = \App\Models\CouponTable::where('status', 1)
        ->where('expiry_date', '>=', \Carbon\Carbon::today())
        ->first();

    // Ensure coupon values are numeric before JSON encoding for JavaScript
    if ($currentCoupon) {
        $currentCoupon->coupon_threshold = (float) $currentCoupon->coupon_threshold;
        $currentCoupon->coupon_value = (float) $currentCoupon->coupon_value;
        // Ensure status is explicitly boolean for JSON encoding
        $currentCoupon->status = (bool) $currentCoupon->status;
    }

    // Pass the current coupon object to JavaScript
    $currentCouponJson = json_encode($currentCoupon ?? null);
@endphp

@push('script')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        let selectedSeats = [];
        let finalTotalPrice = 0;
        let totalCouponDiscountApplied = 0; // Track total discount applied across all seats
        let subtotalAmount = 0; // Track subtotal before fees
        let serviceChargeAmount = 0;
        let platformFeeAmount = 0;
        let gstAmount = 0;

        // These variables are now populated from the @php block
        const flatMarkup = parseFloat("{{ $flatMarkup }}");
        const percentageMarkup = parseFloat("{{ $percentageMarkup }}");
        const threshold = parseFloat("{{ $threshold }}");
        const gstPercentage = parseFloat("{{ $gstPercentage }}");
        const serviceChargePercentage = parseFloat("{{ $serviceChargePercentage }}");
        const platformFeePercentage = parseFloat("{{ $platformFeePercentage }}");
        const platformFeeFixed = parseFloat("{{ $platformFeeFixed }}");
        const currentCoupon = {!! $currentCouponJson !!}; // Coupon object from PHP, will be null if no active coupon
        console.log(currentCoupon)

        function calculatePerSeatDiscount(seatPriceWithMarkup) {
            // Check if coupon exists, is active, and not expired
            // Use loose equality for status to handle potential type differences (e.g., 1 vs true)
            const isCouponValid = currentCoupon &&
                currentCoupon.status == 1 &&
                (currentCoupon.expiry_date && new Date(currentCoupon.expiry_date) >= new Date());

            if (!isCouponValid) {
                return 0; // No active or valid coupon
            }

            const couponThreshold = parseFloat(currentCoupon.coupon_threshold);
            const discountType = currentCoupon.discount_type;
            const couponValue = parseFloat(currentCoupon.coupon_value);

            let discountAmount = 0;

            // Apply discount ONLY if price is ABOVE the threshold
            if (seatPriceWithMarkup > couponThreshold) {
                if (discountType === 'fixed') {
                    discountAmount = couponValue;
                } else if (discountType === 'percentage') {
                    discountAmount = (seatPriceWithMarkup * couponValue / 100);
                }
            }

            // Ensure discount amount does not exceed the price after markup
            const finalDiscount = Math.min(discountAmount, seatPriceWithMarkup);
            return finalDiscount;
        }

        function updatePriceDisplays() {
            // Calculate fees
            subtotalAmount = finalTotalPrice;

            // Service Charge
            serviceChargeAmount = (subtotalAmount * serviceChargePercentage / 100);

            // Platform Fee (percentage + fixed)
            platformFeeAmount = (subtotalAmount * platformFeePercentage / 100) + platformFeeFixed;

            // GST (on subtotal + service charge + platform fee)
            const amountBeforeGST = subtotalAmount + serviceChargeAmount + platformFeeAmount;
            gstAmount = (amountBeforeGST * gstPercentage / 100);

            // Final total
            finalTotalPrice = amountBeforeGST + gstAmount;

            // Update displays with currency symbol
            $('#subtotalDisplay').text('₹' + subtotalAmount.toFixed(2));
            $('#totalCouponDiscountDisplay').text('-₹' + totalCouponDiscountApplied.toFixed(2));
            $('#totalPriceDisplay').text('₹' + finalTotalPrice.toFixed(2));

            // Show/hide fee rows based on values
            if (serviceChargePercentage > 0) {
                $('#serviceChargePercentage').text(serviceChargePercentage);
                $('#serviceChargeAmount').text('₹' + serviceChargeAmount.toFixed(2));
                $('.service-charge-display').removeClass('d-none').addClass('d-flex');
            } else {
                $('.service-charge-display').removeClass('d-flex').addClass('d-none');
            }

            if (platformFeePercentage > 0 || platformFeeFixed > 0) {
                $('#platformFeePercentage').text(platformFeePercentage);
                $('#platformFeeFixed').text(platformFeeFixed.toFixed(2));
                $('#platformFeeAmount').text('₹' + platformFeeAmount.toFixed(2));
                $('.platform-fee-display').removeClass('d-none').addClass('d-flex');
            } else {
                $('.platform-fee-display').removeClass('d-flex').addClass('d-none');
            }

            if (gstPercentage > 0) {
                $('#gstPercentage').text(gstPercentage);
                $('#gstAmount').text('₹' + gstAmount.toFixed(2));
                $('.gst-display').removeClass('d-none').addClass('d-flex');
            } else {
                $('.gst-display').removeClass('d-flex').addClass('d-none');
            }

            // Update the hidden input for the final price to be sent to the backend
            $('input[name="price"]').val(finalTotalPrice.toFixed(2));
        }

        function AddRemoveSeat(el, seatId, price) {
            const seatNumber = seatId;
            const seatOriginalPrice = parseFloat(price);

            const markupAmount = seatOriginalPrice < threshold ?
                flatMarkup :
                (seatOriginalPrice * percentageMarkup / 100);

            const priceWithMarkup = seatOriginalPrice + markupAmount;

            const discountAmountPerSeat = calculatePerSeatDiscount(priceWithMarkup);
            const priceAfterCouponPerSeat = Math.max(0, priceWithMarkup - discountAmountPerSeat);

            el.classList.toggle('selected');
            const alreadySelected = selectedSeats.includes(seatNumber);

            if (!alreadySelected) {
                selectedSeats.push(seatNumber);
                finalTotalPrice += priceAfterCouponPerSeat;
                totalCouponDiscountApplied += discountAmountPerSeat; // Add to total discount
                $('.selected-seat-details').append(
                    `<span class="list-group-item d-flex justify-content-between" data-seat-id="${seatNumber}" data-discount-applied="${discountAmountPerSeat.toFixed(2)}">
                        @lang('Seat') ${seatNumber} <span>{{ __($general->cur_sym) }}${priceAfterCouponPerSeat.toFixed(2)}</span>
                    </span>`
                );
            } else {
                selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
                finalTotalPrice -= priceAfterCouponPerSeat;
                totalCouponDiscountApplied -= discountAmountPerSeat; // Subtract from total discount
                $(`.selected-seat-details span[data-seat-id="${seatNumber}"]`).remove(); // Remove specific seat display
            }

            // Update hidden input for selected seats
            $('input[name="seats"]').val(selectedSeats.join(','));

            if (selectedSeats.length > 0) {
                $('.booked-seat-details').removeClass('d-none').addClass('d-block');
            } else {
                $('.booked-seat-details').removeClass('d-block').addClass('d-none');
            }
            updatePriceDisplays(); // Update all displayed prices
        }

        // Handle form submission
        $('#bookingForm').on('submit', function(e) {
            e.preventDefault();
            fetchBoardingPoints();
        });

        function fetchBoardingPoints() {
            $.ajax({
                url: "{{ route('get.boarding.points') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function() {
                    // Show flyout
                    $('#bookingFlyout').addClass('active');
                },
                success: function(response) {
                    renderBoardingPoints(response.data.BoardingPointsDetails || []);
                    renderDroppingPoints(response.data.DroppingPointsDetails || []);
                },
                error: function(xhr) {
                    console.log("Error: " + (xhr.responseJSON?.message || "Failed to fetch boarding points"));
                    $('#bookingFlyout').removeClass('active');
                }
            });
        }

        function renderBoardingPoints(points) {
            if (points.length === 0) {
                $('.boarding-points-container').html('<div class="alert alert-info">No boarding points available</div>');
                return;
            }
            let html = '';
            points.forEach(point => {
                let time = new Date(point.CityPointTime).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                html += `
                <div class="boarding-point-card" data-index="${point.CityPointIndex}">
                    <div class="card-header">
                        <div class="point-name">${point.CityPointName}</div>
                        <div class="point-time">
                            <i class="las la-clock"></i>
                            <span>${time}</span>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="point-location">
                            <i class="las la-map-marker-alt"></i>
                            <span>${point.CityPointLocation || point.CityPointName}</span>
                        </div>
                        ${point.CityPointContactNumber ? `
                            <div class="point-contact">
                                <i class="las la-phone"></i>
                                <span>${point.CityPointContactNumber}</span>
                            </div>
                            ` : ''}
                    </div>
                </div>
                `;
            });
            $('.boarding-points-container').html(html);
            // Add click event to boarding point cards
            $('.boarding-point-card').on('click', function() {
                $('.boarding-point-card').removeClass('selected');
                $(this).addClass('selected');
                $('#selected_boarding_point').val($(this).data('index'));
            });
        }

        function renderDroppingPoints(points) {
            if (points.length === 0) {
                $('.dropping-points-container').html('<div class="alert alert-info">No dropping points available</div>');
                return;
            }
            let html = '';
            points.forEach(point => {
                let time = new Date(point.CityPointTime).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                html += `
                <div class="dropping-point-card" data-index="${point.CityPointIndex}">
                    <div class="card-header">
                        <div class="point-name">${point.CityPointName}</div>
                        <div class="point-time">
                            <i class="las la-clock"></i>
                            <span>${time}</span>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="point-location">
                            <i class="las la-map-marker-alt"></i>
                            <span>${point.CityPointLocation || point.CityPointName}</span>
                        </div>
                        ${point.CityPointContactNumber ? `
                            <div class="point-contact">
                                <i class="las la-phone"></i>
                                <span>${point.CityPointContactNumber}</span>
                            </div>
                            ` : ''}
                    </div>
                </div>
                `;
            });
            $('.dropping-points-container').html(html);
            // Add click event to dropping point cards
            $('.dropping-point-card').on('click', function() {
                $('.dropping-point-card').removeClass('selected');
                $(this).addClass('selected');
                let selectedLocation = $(this).find('.point-location span').text().trim();
                $('#passenger_address').val(selectedLocation);
                $('#selected_dropping_point').val($(this).data('index'));
            });
        }

        $(document).ready(function() {
            // Disable booked seats
            $('.seat-wrapper .seat.booked').attr('disabled', true);

            // Handle flyout close
            $('#closeFlyout, #flyoutOverlay').on('click', function() {
                $('#bookingFlyout').removeClass('active');
            });

            // Handle passenger title change to automatically set gender
            $('#passenger_title').on('change', function() {
                let selectedTitle = $(this).val();
                let genderValue;
                if (selectedTitle === "Mr") {
                    genderValue = "1"; // Male
                } else if (selectedTitle === "Ms") {
                    genderValue = "2"; // Female
                } else {
                    genderValue = "3"; // Other
                }
                // Update the hidden gender field
                $('#selected_gender').val(genderValue);
            });

            // Set initial gender value based on default title selection
            $('#passenger_title').trigger('change');

            // Add CSS for tab styling
            $('<style>')
                .prop('type', 'text/css')
                .html(`
                    #bookingSteps .nav-link {
                        color: #6c757d;
                        font-weight: normal;
                    }
                    #bookingSteps .nav-link.active {
                        color: #000;
                        font-weight: bold;
                        border-bottom: 2px solid #007bff;
                    }
                `)
                .appendTo('head');
        });

        // Handle next button click to go to passenger details
        $('#nextToPassengerBtn').on('click', function() {
            $('#passenger-tab').tab('show');
        });

        // Handle back button click
        $('#backToBoardingBtn').on('click', function() {
            $('#boarding-tab').tab('show');
        });

        // Handle passenger details form submission
        $('#confirmPassengerBtn').on('click', function(e) {
            if ($('#is_otp_verified').val() !== '1') {
                e.preventDefault();
                e.stopPropagation();
                alert('Please verify your phone number with OTP before proceeding');
                return false;
            }

            $('#payment-tab').tab('show');

            // Update hidden form fields with passenger and point details
            $('#form_boarding_point_index').val($('#selected_boarding_point').val());
            $('#form_dropping_point_index').val($('#selected_dropping_point').val());
            $('#form_passenger_title').val($('#passenger_title').val());
            $('#form_passenger_firstname').val($('#passenger_firstname').val());
            $('#form_passenger_lastname').val($('#passenger_lastname').val());
            $('#form_passenger_email').val($('#passenger_email').val());
            $('#form_passenger_phone').val($('#passenger_phone').val());
            $('#form_passenger_age').val($('#passenger_age').val());
            $('#form_passenger_address').val($('#passenger_address').val());

            // Submit the booking form before opening the payment tab
            let formData = $('#bookingForm').serialize();
            const serverGeneratedTrx = "{{ getTrx(10) }}";

            $.ajax({
                url: "{{ route('block.seat') }}",
                type: "POST",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Call Payment Handler
                        const amount = parseFloat($('input[name="price"]').val());
                        createPaymentOrder(response.order_id, response.ticket_id, amount);
                    } else {
                        alert(response.message || "An error occurred. Please try again.");
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON);
                    alert(xhr.responseJSON?.message ||
                        "Failed to process booking. Please check your details.");
                }
            });
        });

        // Direct booking function
        function createPaymentOrder(orderId, ticketId, amount) {
            var options = {
                "key": "{{ env('RAZORPAY_KEY') }}",
                "amount": amount * 100, // Convert to paise
                "currency": "INR",
                "name": "Ghumantoo",
                "description": "Seat Booking Payment",
                "order_id": orderId,
                "image": "https://vindhyashrisolutions.com/assets/images/logoIcon/logo.png",
                "prefill": {
                    "name": $('#passenger_firstname').val() + ' ' + $('#passenger_lastname').val(),
                    "email": $('#passenger_email').val(),
                    "contact": $('#passenger_phone').val()
                },
                "handler": function(response) {
                    // Process payment success
                    processPaymentSuccess(response, ticketId);
                },
                "theme": {
                    "color": "#3399cc"
                }
            };
            var rzp = new Razorpay(options);
            rzp.open();
        }

        // Process payment success
        function processPaymentSuccess(response, ticketId) {
            $.ajax({
                url: "{{ route('book.ticket') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature,
                    ticket_id: ticketId
                },
                dataType: "json",
                success: function(res) {
                    if (res.success) {
                        alert("Payment successful! Ticket booked successfully.");
                        window.location.href = res.redirect;
                    } else {
                        alert(res.message || "Payment verification failed. Please contact support.");
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON);
                    alert(xhr.responseJSON?.message || "Failed to verify payment. Please contact support.");
                }
            });
        }

        // Old Razorpay functions removed - now using direct booking

        $(document).ready(function() {
            // Send OTP button click handler
            $('#sendOtpBtn').on('click', function() {
                const phoneNumber = $('#passenger_phone').val().trim();
                if (!phoneNumber) {
                    alert('Please enter a valid phone number');
                    return;
                }
                // Disable button and show loading state
                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Sending...');
                // Send AJAX request to send OTP
                $.ajax({
                    url: "{{ route('send.otp') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        mobile_number: phoneNumber,
                        user_name: $('#passenger_firstname').val() + ' ' + $('#passenger_lastname')
                            .val()
                    },
                    success: function(response) {
                        console.log(response);
                        if (response.status === 200) {
                            // Show OTP verification field
                            $('#otpVerificationContainer').removeClass('d-none').addClass(
                                'd-block');
                            alert('OTP sent to your WhatsApp number');
                        } else {
                            alert(response.message || 'Failed to send OTP. Please try again.');
                        }
                    },
                    error: function(xhr) {
                        alert('Error: ' + (xhr.responseJSON?.message || 'Failed to send OTP'));
                    },
                    complete: function() {
                        // Reset button state
                        $btn.prop('disabled', false).html('@lang('Send OTP')');
                    }
                });
            });

            // Verify OTP button click handler
            $('#verifyOtpBtn').on('click', function() {
                const otp = $('#otp_code').val().trim();
                const phone = $('#passenger_phone').val().trim();
                if (!otp) {
                    alert('Please enter the OTP');
                    return;
                }
                // Disable button and show loading state
                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Verifying...');
                // Send AJAX request to verify OTP
                $.ajax({
                    url: "{{ route('verify.otp') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        mobile_number: phone,
                        otp: otp
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            // Mark OTP as verified
                            $('#is_otp_verified').val('1');
                            $('#otpVerificationContainer').removeClass('has-error').addClass(
                                'has-success');
                            $('#otp_code').prop('disabled', true);
                            $btn.html('<i class="las la-check"></i> Verified').addClass(
                                'btn--success');
                            // If user is logged in through OTP
                            if (response.user_logged_in) {
                                alert('You have been logged in successfully!');
                            }
                        } else {
                            $('#otpVerificationContainer').addClass('has-error');
                            alert(response.message || 'Invalid OTP. Please try again.');
                            $btn.prop('disabled', false).html(
                                '@lang('Verify')');
                        }
                    },
                    error: function(xhr) {
                        alert('Error: ' + (xhr.responseJSON?.message ||
                            'Failed to verify OTP'));
                        $btn.prop('disabled', false).html('@lang('Verify')');
                    }
                });
            });
        });

        // When a boarding point is selected, store its details
        $(document).on('click', '.boarding-point-card', function() {
            // Get the boarding point details
            const pointName = $(this).find('.card-title').text();
            const pointLocation = $(this).find('.card-text:first').text();
            const pointTime = $(this).find('.card-text:contains("clock")').text();
            // Store in hidden fields for later use
            $('#form_boarding_point_name').val(pointName);
            $('#form_boarding_point_location').val(pointLocation);
            $('#form_boarding_point_time').val(pointTime);
        });

        // When a dropping point is selected, store its details
        $(document).on('click', '.dropping-point-card', function() {
            // Get the dropping point details
            const pointName = $(this).find('.card-title').text();
            const pointLocation = $(this).find('.card-text:first').text();
            const pointTime = $(this).find('.card-text:contains("clock")').text();
            // Store in hidden fields for later use
            $('#form_dropping_point_name').val(pointName);
            $('#form_dropping_point_location').val(pointLocation);
            $('#form_dropping_point_time').val(pointTime);
        });
    </script>

    @endpush

    @push('style')
    <style>
        .row {
            gap: 0px;
        }

        /* Simpler styles for price displays */
        .coupon-discount-display,
        .total-price-display {
            font-size: 1.1em;
            border-top: 1px solid #eee;
            padding-top: 10px;
            margin-top: 10px;
            color: #000;
            /* Ensure black text */
            font-weight: normal;
            /* Remove bold */
        }

        .coupon-discount-display span,
        .total-price-display span {
            font-weight: normal;
            /* Ensure numbers are also not bold */
            color: #000;
            /* Ensure numbers are also black */
        }

        .coupon-discount-display strong,
        .total-price-display strong {
            font-weight: normal;
            /* Ensure labels are not bold */
        }

        /* Keep the red color for the discount amount itself */
        .coupon-discount-display span {
            color: #e74c3c;
        }

        /* New style for coupon banner */
        .coupon-display-banner {
            background-color: #d4edda;
            /* Light green background */
            color: #155724;
            /* Dark green text */
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 1.1em;
            font-weight: 600;
            text-align: center;
            border: 1px solid #c3e6cb;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .coupon-display-banner p {
            margin: 0;
        }

        /* Flyout Styles */
        .booking-flyout {
            position: fixed;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: none;
            transition: all 0.3s ease;
        }

        .booking-flyout.active {
            display: flex;
        }

        .flyout-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
        }

        .flyout-content {
            position: absolute;
            top: 0;
            right: 0;
            width: 500px;
            height: 100%;
            background: white;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .booking-flyout.active .flyout-content {
            transform: translateX(0);
        }

        .flyout-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .flyout-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .flyout-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background-color 0.2s ease;
        }

        .flyout-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .flyout-body {
            padding: 20px;
        }

        /* Responsive flyout */
        @media (max-width: 768px) {
            .flyout-content {
                width: 100%;
            }
        }

        /* Enhanced step styling */
        #bookingSteps .nav-link {
            color: #6c757d;
            font-weight: normal;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        #bookingSteps .nav-link.active {
            color: #667eea;
            font-weight: bold;
            border-bottom-color: #667eea;
            background: none;
        }

        #bookingSteps .nav-link:hover {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        /* Enhanced card styling */
        .boarding-point-card,
        .dropping-point-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .boarding-point-card:hover,
        .dropping-point-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.1);
        }

        .boarding-point-card.border-primary,
        .dropping-point-card.border-primary {
            border-color: #667eea !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* Enhanced form styling */
        .form--control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form--control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        /* Enhanced button styling */
        .btn--success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn--success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .btn--danger {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn--danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* Professional Booking Summary Styles */
        .booking-summary-title {
            color: #333;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .booking-summary-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .selected-seats-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f3f4;
        }

        .fare-breakdown {
            margin-bottom: 20px;
        }

        .fare-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .fare-item:last-child {
            border-bottom: none;
        }

        .fare-label {
            color: #666;
            font-size: 0.9rem;
        }

        .fare-amount {
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .total-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .total-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-label {
            color: #333;
            font-weight: 600;
            font-size: 1rem;
        }

        .total-amount {
            color: #D63942;
            font-weight: 700;
            font-size: 1.2rem;
        }

        /* Professional Step Titles */
        .step-title {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px 0;
        }

        /* Update Flyout Header Color */
        .flyout-header {
            background: #D63942 !important;
        }

        /* Update Step Colors */
        #bookingSteps .nav-link.active {
            color: #D63942 !important;
            border-bottom-color: #D63942 !important;
        }

        #bookingSteps .nav-link:hover {
            color: #D63942 !important;
            border-bottom-color: #D63942 !important;
        }

        /* Update Card Colors */
        .boarding-point-card:hover,
        .dropping-point-card:hover {
            border-color: #D63942 !important;
            box-shadow: 0 4px 8px rgba(214, 57, 66, 0.1) !important;
        }

        .boarding-point-card.border-primary,
        .dropping-point-card.border-primary {
            border-color: #D63942 !important;
            background: #D63942 !important;
            color: white !important;
        }

        /* Update Form Colors */
        .form--control:focus {
            border-color: #D63942 !important;
            box-shadow: 0 0 0 0.2rem rgba(214, 57, 66, 0.25) !important;
        }

        .form--control::placeholder {
            color: #999;
            font-size: 0.85rem;
        }

        /* Professional Button Styling */
        .btn-primary {
            background: #D63942;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #c32d36;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(214, 57, 66, 0.3);
        }

        .otp-btn {
            font-size: 0.85rem;
            padding: 8px 12px;
        }

        .book-bus-btn {
            background: #D63942;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .book-bus-btn:hover {
            background: #c32d36;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(214, 57, 66, 0.3);
        }

        /* Professional Boarding/Dropping Point Cards */
        .boarding-point-card,
        .dropping-point-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 12px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .boarding-point-card:hover,
        .dropping-point-card:hover {
            border-color: #D63942;
            box-shadow: 0 4px 12px rgba(214, 57, 66, 0.15);
            transform: translateY(-1px);
        }

        .boarding-point-card.selected,
        .dropping-point-card.selected {
            border-color: #D63942;
            background: #D63942;
            color: white;
            box-shadow: 0 4px 12px rgba(214, 57, 66, 0.2);
        }

        .card-header {
            padding: 16px 20px 12px;
            border-bottom: 1px solid #f1f3f4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .boarding-point-card.selected .card-header,
        .dropping-point-card.selected .card-header {
            border-bottom-color: rgba(255, 255, 255, 0.2);
        }

        .point-name {
            font-weight: 600;
            font-size: 1rem;
            color: #333;
        }

        .boarding-point-card.selected .point-name,
        .dropping-point-card.selected .point-name {
            color: white;
        }

        .point-time {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .boarding-point-card.selected .point-time,
        .dropping-point-card.selected .point-time {
            color: rgba(255, 255, 255, 0.9);
        }

        .point-time i {
            font-size: 0.85rem;
        }

        .card-content {
            padding: 12px 20px 16px;
        }

        .point-location,
        .point-contact {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #666;
        }

        .point-location:last-child,
        .point-contact:last-child {
            margin-bottom: 0;
        }

        .boarding-point-card.selected .point-location,
        .boarding-point-card.selected .point-contact,
        .dropping-point-card.selected .point-location,
        .dropping-point-card.selected .point-contact {
            color: rgba(255, 255, 255, 0.9);
        }

        .point-location i,
        .point-contact i {
            font-size: 0.9rem;
            width: 16px;
            text-align: center;
        }

        /* Improve flyout overall spacing */
        .flyout-body {
            padding: 24px;
        }

        /* Better section spacing */
        .col-md-6 h6 {
            color: #333;
            font-weight: 600;
            margin-bottom: 16px;
            font-size: 1rem;
        }

        /* Professional Next/Continue buttons */
        .next-btn {
            padding: 10px 24px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .next-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(214, 57, 66, 0.3);
        }
    </style>
@endpush
