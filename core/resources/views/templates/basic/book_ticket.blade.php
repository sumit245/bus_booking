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
                                    {{-- Subtotal: Sum of (BaseFare + Markup) for all selected seats --}}
                                    <div class="fare-item">
                                        <span class="fare-label">@lang('Subtotal')</span>
                                        <span class="fare-amount" id="subtotalDisplay">₹0.00</span>
                                    </div>

                                    {{-- Platform Fee --}}
                                    <div class="fare-item platform-fee-display d-none">
                                        <span class="fare-label">
                                            @lang('Platform Fee')
                                            <span id="platformFeeLabel" class="fee-percentage-label"></span>
                                        </span>
                                        <span class="fare-amount" id="platformFeeAmount">₹0.00</span>
                                    </div>

                                    {{-- Service Charge --}}
                                    <div class="fare-item service-charge-display d-none">
                                        <span class="fare-label">
                                            @lang('Service Charge')
                                            <span id="serviceChargeLabel" class="fee-percentage-label"></span>
                                        </span>
                                        <span class="fare-amount" id="serviceChargeAmount">₹0.00</span>
                                    </div>

                                    {{-- GST --}}
                                    <div class="fare-item gst-display d-none">
                                        <span class="fare-label">
                                            @lang('GST')
                                            <span id="gstLabel" class="fee-percentage-label"></span>
                                        </span>
                                        <span class="fare-amount" id="gstAmount">₹0.00</span>
                                    </div>

                                    {{-- Apply Coupon Code Section --}}
                                    <div class="fare-item coupon-apply-section">
                                        <span class="fare-label">
                                            <button type="button" class="btn-link apply-coupon-btn" id="applyCouponBtn">
                                                <i class="las la-tag"></i> @lang('Apply Coupon Code')
                                            </button>
                                        </span>
                                        <span class="fare-amount coupon-input-wrapper">
                                            <div class="coupon-input-container d-none" id="couponInputContainer">
                                                <div class="input-group">
                                                    <input type="text" class="form--control" id="couponCodeInput"
                                                        placeholder="@lang('Enter coupon code')">
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                        id="applyCouponCodeBtn">
                                                        @lang('Apply')
                                                    </button>
                                                </div>
                                                <div class="coupon-error-message text-danger d-none"
                                                    id="couponErrorMessage">
                                                </div>
                                            </div>
                                        </span>
                                    </div>

                                    {{-- Coupon Discount (shown only when coupon is applied) --}}
                                    <div class="fare-item coupon-discount-display d-none">
                                        <span class="fare-label text-success">
                                            <span id="discountLabelText">@lang('Discount')</span>
                                            <button type="button" class="btn-link remove-coupon-btn" id="removeCouponBtn"
                                                title="@lang('Remove coupon')">
                                                <i class="las la-times"></i>
                                            </button>
                                        </span>
                                        <span class="fare-amount text-success" id="totalCouponDiscountDisplay">-₹0.00</span>
                                    </div>
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
                        <input type="text" name="coupon_code" id="form_coupon_code" hidden>

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
                        <button type="submit" class="book-bus-btn btn-primary" id="continueToBookingBtn"
                            disabled>@lang('Continue to Booking')</button>
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
@endsection

<!-- Flyout Modal (outside content section to avoid footer inclusion) -->
<div class="booking-flyout" id="bookingFlyout">
    <div class="flyout-overlay" id="flyoutOverlay"></div>
    <div class="flyout-content">
        <div class="flyout-header">
            <h5 class="flyout-title">@lang('Complete Your Booking')</h5>
            <button type="button" class="flyout-close" id="closeFlyout">
                <i class="las la-times"></i>
            </button>
        </div>
        <!-- Step indicator tabs at top -->
        <ul class="nav nav-tabs justify-content-center mb-3" id="bookingSteps" role="tablist"
            style="justify-content: left!important;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="boarding-tab" data-bs-toggle="tab"
                    data-bs-target="#boarding-content" type="button" role="tab">
                    @lang('Boarding & Dropping')
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="passenger-tab" data-bs-toggle="tab" data-bs-target="#passenger-content"
                    type="button" role="tab">
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
        <div class="flyout-body">
            <div class="tab-content">
                <!-- Step 1: Boarding & Dropping Points -->
                <div class="tab-pane fade show active" id="boarding-content" role="tabpanel">
                    {{-- <div class="step-title">@lang('Select Boarding & Dropping Points')</div> --}}
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
                </div>
                <!-- Step 2: Passenger Details -->
                <div class="tab-pane fade" id="passenger-content" role="tabpanel">
                    <div class="passenger-details">
                        {{-- <h6 class="mb-3">@lang('Passenger Information')</h6> --}}
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
                                        {{-- <span class="text-danger">*</span> --}}
                                    </label>
                                    <input type="email" class="form--control" id="passenger_email"
                                        placeholder="@lang('Enter Email')"
                                        value="{{ auth()->check() ? auth()->user()->email : '' }}">
                                    {{-- <div class="invalid-feedback">This field is required!</div> --}}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">@lang('Phone Number')
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="tel" class="form--control my-2" id="passenger_phone"
                                            name="passenger_phone" placeholder="@lang('Enter your WhatsApp mobile number')"
                                            value="{{ auth()->check() && auth()->user()->mobile ? str_replace('91', '', auth()->user()->mobile) : '' }}">
                                        @if (!auth()->check())
                                            <button type="button" class="btn btn-primary btn-sm otp-btn"
                                                id="sendOtpBtn">
                                                @lang('Send OTP to WhatsApp')
                                            </button>
                                        @endif
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
                            <input type="hidden" name="is_otp_verified" id="is_otp_verified"
                                value="{{ auth()->check() ? '1' : '0' }}">
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
                    </div>
                </div>
                <!-- Step 2: Bottom action buttons (fixed at bottom) -->
                <div class="passenger-bottom-nav">
                    <div class="d-flex justify-content-between w-100">
                        <button type="button" class="btn btn-outline-secondary btn-sm me-2" id="backToBoardingBtn">
                            <i class="las la-arrow-left"></i> @lang('Back')
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg ml-2" id="confirmPassengerBtn">
                            @lang('Proceed to Pay') <i class="las la-arrow-right"></i>
                        </button>
                    </div>
                </div>
                <!-- Step 3: Payment -->
                <div class="tab-pane fade" id="payment-content" role="tabpanel">
                    <!-- Payment Invoice -->
                    <div class="payment-invoice">
                        <!-- Journey Details -->
                        <div class="invoice-section">
                            <h6 class="section-title">@lang('Journey Details')</h6>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div>
                                        <span class="detail-label">@lang('Route'):</span>
                                        <span class="detail-value" id="invoice-route"></span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div>
                                        <span class="detail-label">@lang('Date'):</span>
                                        <span class="detail-value" id="invoice-date"></span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <span class="detail-label">@lang('Boarding'):</span>
                                    <span class="detail-value" id="invoice-boarding"></span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="detail-label">@lang('Dropping'):</span>
                                    <span class="detail-value" id="invoice-dropping"></span>
                                </div>
                            </div>
                        </div>


                        <!-- Passenger Details -->
                        <div class="invoice-section">
                            <h6 class="section-title">@lang('Passenger Details')</h6>
                            <div class="row">
                                <div class="col-sm-6">
                                    <span class="detail-label">@lang('Name'):</span>
                                    <span class="detail-value" id="invoice-passenger-name"></span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="detail-label">@lang('Phone'):</span>
                                    <span class="detail-value" id="invoice-passenger-phone"></span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="detail-label">@lang('Age'):</span>
                                    <span class="detail-value" id="invoice-passenger-age"></span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="detail-label">@lang('Seat(s)'):</span>
                                    <span class="detail-value" id="invoice-seats"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Fare Details -->
                        <div class="invoice-section fare-section">
                            <h6 class="section-title">@lang('Fare Details')</h6>
                            <div id="invoice-fare-details"></div>
                        </div>
                    </div>
                </div>
                <!-- Step 3: Bottom action buttons (fixed at bottom) -->
                <div class="payment-bottom-nav">
                    <div class="d-flex justify-content-between w-100">
                        <button type="button" class="btn btn-outline-secondary btn-sm me-2" id="backToPassengerBtn">
                            <i class="las la-arrow-left"></i> @lang('Back')
                        </button>
                        <button type="button" class="btn btn-primary btn-lg ms-2" id="payNowBtn">
                            <i class="las la-credit-card"></i> @lang('Pay Now') <span
                                id="payment-total-display"></span>
                        </button>
                    </div>
                </div>
                <!-- Continue button at bottom (shown on boarding/dropping tab only) -->
                <div class="flyout-bottom-nav" id="flyoutBottomNav">
                    <button type="button" class="btn btn-primary w-100 continue-btn" id="nextToPassengerBtn"
                        disabled>
                        @lang('Continue')
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- End of Booking Flyout -->

@php
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
        let subtotalBeforeCoupon = 0; // Sum of (BaseFare + Markup) for all selected seats (BEFORE coupon)
        let finalTotalPrice = 0; // Final total with all fees
        let totalCouponDiscountApplied = 0; // Track total discount applied on subtotal
        let appliedCouponCode = null; // Track applied coupon code
        let appliedCouponData = null; // Store coupon data for validation
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

        // Calculate coupon discount on subtotal (not per seat)
        function calculateCouponDiscount(subtotal) {
            if (!appliedCouponCode || !appliedCouponData) {
                return 0;
            }

            const couponThreshold = parseFloat(appliedCouponData.coupon_threshold) || 0;
            const discountType = appliedCouponData.discount_type;
            // API returns 'discount_value', not 'coupon_value'
            const couponValue = parseFloat(appliedCouponData.discount_value || appliedCouponData.coupon_value) || 0;

            // Check if subtotal meets threshold
            if (isNaN(subtotal) || subtotal <= 0 || isNaN(couponThreshold) || subtotal <= couponThreshold) {
                return 0;
            }

            if (!discountType || isNaN(couponValue) || couponValue <= 0) {
                return 0;
            }

            let discountAmount = 0;
            if (discountType === 'fixed') {
                discountAmount = couponValue;
            } else if (discountType === 'percentage') {
                discountAmount = (subtotal * couponValue / 100);
            }

            // Ensure discount doesn't exceed subtotal and is a valid number
            discountAmount = Math.min(discountAmount, subtotal);
            return isNaN(discountAmount) ? 0 : Math.max(0, discountAmount);
        }

        function updatePriceDisplays() {
            // Calculate coupon discount on subtotal (not per seat)
            totalCouponDiscountApplied = calculateCouponDiscount(subtotalBeforeCoupon);

            // Platform Fee (percentage + fixed) - calculated on subtotal
            platformFeeAmount = (subtotalBeforeCoupon * platformFeePercentage / 100) + platformFeeFixed;

            // Service Charge - calculated on subtotal
            serviceChargeAmount = (subtotalBeforeCoupon * serviceChargePercentage / 100);

            // GST (on subtotal + service charge + platform fee)
            const amountBeforeGST = subtotalBeforeCoupon + serviceChargeAmount + platformFeeAmount;
            gstAmount = (amountBeforeGST * gstPercentage / 100);

            // Final total: subtotal + fees - discount
            finalTotalPrice = subtotalBeforeCoupon + serviceChargeAmount + platformFeeAmount + gstAmount -
                totalCouponDiscountApplied;

            // Update displays with currency symbol
            $('#subtotalDisplay').text('₹' + subtotalBeforeCoupon.toFixed(2));

            // Platform Fee
            if (platformFeePercentage > 0 || platformFeeFixed > 0) {
                let platformFeeLabel = '';
                if (platformFeePercentage > 0 && platformFeeFixed > 0) {
                    platformFeeLabel = ` (${platformFeePercentage}% + ₹${platformFeeFixed.toFixed(2)})`;
                } else if (platformFeePercentage > 0) {
                    platformFeeLabel = ` (${platformFeePercentage}%)`;
                } else {
                    platformFeeLabel = '';
                }
                $('#platformFeeLabel').text(platformFeeLabel);
                $('#platformFeeAmount').text('₹' + platformFeeAmount.toFixed(2));
                $('.platform-fee-display').removeClass('d-none').addClass('d-flex');
            } else {
                $('.platform-fee-display').removeClass('d-flex').addClass('d-none');
            }

            // Service Charge
            if (serviceChargePercentage > 0) {
                $('#serviceChargeLabel').text(` (${serviceChargePercentage}%)`);
                $('#serviceChargeAmount').text('₹' + serviceChargeAmount.toFixed(2));
                $('.service-charge-display').removeClass('d-none').addClass('d-flex');
            } else {
                $('.service-charge-display').removeClass('d-flex').addClass('d-none');
            }

            // GST
            if (gstPercentage > 0) {
                $('#gstLabel').text(` (${gstPercentage}%)`);
                $('#gstAmount').text('₹' + gstAmount.toFixed(2));
                $('.gst-display').removeClass('d-none').addClass('d-flex');
            } else {
                $('.gst-display').removeClass('d-flex').addClass('d-none');
            }

            // Coupon Discount - Show/hide mutually exclusive with Apply Coupon Code
            if (totalCouponDiscountApplied > 0 && appliedCouponCode && !isNaN(totalCouponDiscountApplied) &&
                appliedCouponData) {
                // Build discount label with percentage if applicable
                let discountLabel = 'Discount';
                if (appliedCouponData.discount_type === 'percentage') {
                    const discountPercent = parseFloat(appliedCouponData.discount_value || appliedCouponData
                        .coupon_value) || 0;
                    if (!isNaN(discountPercent) && discountPercent > 0) {
                        discountLabel = `Discount (${discountPercent}%)`;
                    }
                }

                // Update discount label text
                $('#discountLabelText').text(discountLabel);

                $('#totalCouponDiscountDisplay').text('-₹' + totalCouponDiscountApplied.toFixed(2));
                $('.coupon-discount-display').removeClass('d-none').addClass('d-flex');

                // Hide Apply Coupon Code section when discount is shown
                $('.coupon-apply-section').addClass('d-none');
            } else {
                $('.coupon-discount-display').removeClass('d-flex').addClass('d-none');

                // Show Apply Coupon Code section when no discount (and hide input if it was shown)
                $('.coupon-apply-section').removeClass('d-none');
                $('#couponInputContainer').addClass('d-none').removeClass('d-flex');
            }

            // Total Amount
            $('#totalPriceDisplay').text('₹' + finalTotalPrice.toFixed(2));

            // Update the hidden input for the final price to be sent to the backend
            $('input[name="price"]').val(finalTotalPrice.toFixed(2));
        }

        function AddRemoveSeat(el, seatId, price) {
            const seatNumber = seatId;
            const seatOriginalPrice = parseFloat(price);

            // Calculate markup
            const markupAmount = seatOriginalPrice < threshold ?
                flatMarkup :
                (seatOriginalPrice * percentageMarkup / 100);

            // Price with markup (this is what goes into subtotal)
            const priceWithMarkup = seatOriginalPrice + markupAmount;

            el.classList.toggle('selected');
            const alreadySelected = selectedSeats.includes(seatNumber);

            if (!alreadySelected) {
                selectedSeats.push(seatNumber);
                subtotalBeforeCoupon += priceWithMarkup; // Add to subtotal (BEFORE coupon)
                $('.selected-seat-details').append(
                    `<span class="list-group-item d-flex justify-content-between" data-seat-id="${seatNumber}" data-price="${priceWithMarkup.toFixed(2)}">
                        @lang('Seat') ${seatNumber} <span>{{ __($general->cur_sym) }}${priceWithMarkup.toFixed(2)}</span>
                    </span>`
                );
            } else {
                selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
                // Get the stored price from the data attribute
                const storedPrice = parseFloat($(`.selected-seat-details span[data-seat-id="${seatNumber}"]`).data(
                    'price'));
                subtotalBeforeCoupon -= storedPrice; // Subtract from subtotal
                $(`.selected-seat-details span[data-seat-id="${seatNumber}"]`).remove();
            }

            // Update hidden input for selected seats
            $('input[name="seats"]').val(selectedSeats.join(','));

            if (selectedSeats.length > 0) {
                $('.booked-seat-details').removeClass('d-none').addClass('d-block');
            } else {
                $('.booked-seat-details').removeClass('d-block').addClass('d-none');
                // Clear coupon when no seats selected
                if (appliedCouponCode) {
                    appliedCouponCode = null;
                    appliedCouponData = null;
                    $('#form_coupon_code').val('');
                }
            }
            // Toggle booking button enabled state and gray out based on seat selection
            const bookBtn = document.querySelector('.book-bus-btn');
            if (bookBtn) {
                if (selectedSeats.length === 0) {
                    bookBtn.disabled = true;
                    bookBtn.classList.add('disabled');
                } else {
                    bookBtn.disabled = false;
                    bookBtn.classList.remove('disabled');
                }
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
                <div class="point-card boarding-point-card" data-index="${point.CityPointIndex}">
                    <div class="point-info-row">
                        <div class="point-time">${time}</div>
                        <div class="point-name">${point.CityPointName}</div>
                    </div>
                    <div class="point-details-row">
                        <i class="las la-map-marker-alt"></i>
                        <span class="point-location">${point.CityPointLocation || point.CityPointName}</span>
                        ${point.CityPointContactNumber ? `<span class="point-contact">- ${point.CityPointContactNumber}</span>` : ''}
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
                <div class="point-card dropping-point-card" data-index="${point.CityPointIndex}">
                    <div class="point-info-row">
                        <div class="point-time">${time}</div>
                        <div class="point-name">${point.CityPointName}</div>
                    </div>
                    <div class="point-details-row">
                        <i class="las la-map-marker-alt"></i>
                        <span class="point-location">${point.CityPointLocation || point.CityPointName}</span>
                        ${point.CityPointContactNumber ? `<span class="point-contact">- ${point.CityPointContactNumber}</span>` : ''}
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
                // $('#passenger_address').val(selectedLocation);
                $('#selected_dropping_point').val($(this).data('index'));
            });
        }

        // Coupon validation and application
        $('#applyCouponBtn').on('click', function() {
            const $container = $('#couponInputContainer');
            if ($container.hasClass('d-none')) {
                $container.removeClass('d-none').addClass('d-flex');
                $('#couponCodeInput').focus();
            }
        });

        $('#applyCouponCodeBtn').on('click', function() {
            const couponCode = $('#couponCodeInput').val().trim();
            if (!couponCode) {
                $('#couponErrorMessage').text('Please enter a coupon code').removeClass('d-none');
                return;
            }

            if (subtotalBeforeCoupon <= 0) {
                $('#couponErrorMessage').text('Please select seats first').removeClass('d-none');
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Validating...');
            $('#couponErrorMessage').addClass('d-none');

            $.ajax({
                url: "{{ url('/api/coupons/validate') }}",
                type: "POST",
                data: {
                    coupon_code: couponCode,
                    total_amount: subtotalBeforeCoupon
                },
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success && response.valid && response.data) {
                        // Coupon is valid, apply it
                        appliedCouponCode = couponCode;
                        appliedCouponData = response.data;

                        // Debug: Log coupon data to console
                        console.log('Coupon applied:', appliedCouponData);

                        $('#form_coupon_code').val(couponCode);

                        // Hide coupon input, show discount row
                        $('#couponInputContainer').addClass('d-none').removeClass('d-flex');
                        $('#couponCodeInput').val('');
                        // Show the link button again if needed (will be hidden when discount is shown)
                        updatePriceDisplays();
                    } else {
                        $('#couponErrorMessage').text(response.message || 'Invalid coupon code')
                            .removeClass('d-none');
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message ||
                        'Failed to validate coupon. Please try again.';
                    $('#couponErrorMessage').text(errorMsg).removeClass('d-none');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('@lang('Apply')');
                }
            });
        });

        // Remove coupon
        $('#removeCouponBtn').on('click', function() {
            appliedCouponCode = null;
            appliedCouponData = null;
            $('#form_coupon_code').val('');
            // Reset discount label
            $('#discountLabelText').text('Discount');
            updatePriceDisplays();
        });

        // Allow Enter key to apply coupon
        $('#couponCodeInput').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#applyCouponCodeBtn').click();
            }
        });

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
                        color: #D63942;
                        font-weight: bold;
                        border-bottom: 2px solid #D63942;
                    }
                `)
                .appendTo('head');
        });

        // Handle next button click to go to passenger details
        $('#nextToPassengerBtn').on('click', function() {
            // Validate that both boarding and dropping points are selected
            const boardingSelected = $('#selected_boarding_point').val();
            const droppingSelected = $('#selected_dropping_point').val();

            if (!boardingSelected || !droppingSelected) {
                alert('@lang('Please select both boarding and dropping points before continuing.')');
                return;
            }

            $('#passenger-tab').tab('show');
        });

        // Enable/disable continue button based on point selection
        function updateContinueButtonState() {
            const boardingSelected = $('#selected_boarding_point').val();
            const droppingSelected = $('#selected_dropping_point').val();
            const continueBtn = $('#nextToPassengerBtn');

            if (boardingSelected && droppingSelected) {
                continueBtn.prop('disabled', false).removeClass('disabled');
            } else {
                continueBtn.prop('disabled', true).addClass('disabled');
            }
        }

        // Call this function whenever a point is selected
        $(document).on('click', '.boarding-point-card, .dropping-point-card', function() {
            setTimeout(updateContinueButtonState, 100);
        });

        // Handle back button clicks
        $('#backToBoardingBtn').on('click', function() {
            $('#boarding-tab').tab('show');
        });

        $('#backToPassengerBtn').on('click', function() {
            $('#passenger-tab').tab('show');
        });

        // Handle passenger details form submission
        $('#confirmPassengerBtn').on('click', function(e) {
            // Skip OTP verification if user is already logged in
            @if (!auth()->check())
                if ($('#is_otp_verified').val() !== '1') {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Please verify your phone number with OTP before proceeding');
                    return false;
                }
            @endif

            // Update passenger data in hidden form fields
            $('#form_passenger_title').val($('#passenger_title').val());

            // Also update the main gender input for the form
            let selectedTitle = $('#passenger_title').val();
            let genderValue = selectedTitle === "Mr" ? "1" : (selectedTitle === "Ms" ? "2" : "3");
            $('#selected_gender').val(genderValue);

            $('#form_passenger_firstname').val($('#passenger_firstname').val());
            $('#form_passenger_lastname').val($('#passenger_lastname').val());
            $('#form_passenger_email').val($('#passenger_email').val());
            $('#form_passenger_phone').val($('#passenger_phone').val());
            $('#form_passenger_age').val($('#passenger_age').val());
            $('#form_passenger_address').val($('#passenger_address').val());

            // Update the payment invoice with all details
            updatePaymentInvoice();

            // Switch to the payment tab
            $('#payment-tab').tab('show');
        });

        // Function to update payment invoice
        function updatePaymentInvoice() {
            // Journey details
            $('#invoice-route').text($('#origin-id').val() + ' → ' + $('#destination-id').val());
            $('#invoice-date').text($('#date_of_journey').val());
            $('#invoice-seats').text(selectedSeats.join(', '));

            // Boarding & Dropping points
            const boardingPoint = $('.boarding-point-card.selected .point-name').text() || 'Not selected';
            const boardingTime = $('.boarding-point-card.selected .point-time').text() || '';
            $('#invoice-boarding').text(boardingPoint + (boardingTime ? ' - ' + boardingTime : ''));

            const droppingPoint = $('.dropping-point-card.selected .point-name').text() || 'Not selected';
            const droppingTime = $('.dropping-point-card.selected .point-time').text() || '';
            $('#invoice-dropping').text(droppingPoint + (droppingTime ? ' - ' + droppingTime : ''));

            // Passenger details
            const passengerName = $('#passenger_title').val() + '. ' + $('#passenger_firstname').val() + ' ' + $(
                '#passenger_lastname').val();
            $('#invoice-passenger-name').text(passengerName);
            $('#invoice-passenger-phone').text($('#passenger_phone').val());
            $('#invoice-passenger-age').text($('#passenger_age').val() + ' years');

            // Fare details - clone from booking summary
            const fareHTML = $('.fare-breakdown').html() + '<div class="total-section mt-3">' + $('.total-section').html() +
                '</div>';
            $('#invoice-fare-details').html(fareHTML);

            // Update total display
            $('#payment-total-display').text('(' + $('#totalPriceDisplay').text() + ')');
        }

        // Show/hide bottom nav buttons based on active tab
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const targetTab = $(e.target).attr('data-bs-target');

            // Hide all bottom navs first
            $('#flyoutBottomNav, .passenger-bottom-nav, .payment-bottom-nav').hide();

            // Show appropriate bottom nav
            if (targetTab === '#boarding-content') {
                $('#flyoutBottomNav').show();
            } else if (targetTab === '#passenger-content') {
                $('.passenger-bottom-nav').show();
            } else if (targetTab === '#payment-content') {
                $('.payment-bottom-nav').show();
            }
        });

        // Handle the final payment submission
        $('#payNowBtn').on('click', function() {
            let formData = $('#bookingForm').serialize();
            const serverGeneratedTrx = "{{ getTrx(10) }}";
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Processing...');

            $.ajax({
                url: "{{ route('block.seat') }}",
                type: "POST",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Call Payment Handler with total_amount from server (includes all fees)
                        const amount = parseFloat(response.amount || finalTotalPrice);
                        createPaymentOrder(response.order_id, response.ticket_id, amount);
                    } else {
                        $btn.prop('disabled', false).html(
                            `@lang('Pay Now') <span id="payment-total-display">${$('#totalPriceDisplay').text()}</span>`
                        );
                        alert(response.message || "An error occurred. Please try again.");
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON);
                    alert(xhr.responseJSON?.message ||
                        "Failed to process booking. Please check your details.");
                    $btn.prop('disabled', false).html(
                        `@lang('Pay Now') <span id="payment-total-display">${$('#totalPriceDisplay').text()}</span>`
                    );
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

        // Coupon validation and application
        $(document).on('click', '#applyCouponBtn', function() {
            const $container = $('#couponInputContainer');
            if ($container.hasClass('d-none')) {
                $container.removeClass('d-none').addClass('d-flex');
                $('#couponCodeInput').focus();
            }
        });

        $(document).on('click', '#applyCouponCodeBtn', function() {
            const couponCode = $('#couponCodeInput').val().trim();
            if (!couponCode) {
                $('#couponErrorMessage').text('Please enter a coupon code').removeClass('d-none');
                return;
            }

            if (subtotalBeforeCoupon <= 0) {
                $('#couponErrorMessage').text('Please select seats first').removeClass('d-none');
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Validating...');
            $('#couponErrorMessage').addClass('d-none');

            $.ajax({
                url: "{{ url('/api/coupons/validate') }}",
                type: "POST",
                data: {
                    coupon_code: couponCode,
                    total_amount: subtotalBeforeCoupon
                },
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success && response.valid && response.data) {
                        // Coupon is valid, apply it
                        appliedCouponCode = couponCode;
                        appliedCouponData = response.data;

                        // Debug: Log coupon data to console
                        console.log('Coupon applied:', appliedCouponData);

                        $('#form_coupon_code').val(couponCode);

                        // Hide coupon input, show discount row
                        $('#couponInputContainer').addClass('d-none').removeClass('d-flex');
                        $('#couponCodeInput').val('');
                        // Show the link button again if needed (will be hidden when discount is shown)
                        updatePriceDisplays();
                    } else {
                        $('#couponErrorMessage').text(response.message || 'Invalid coupon code')
                            .removeClass('d-none');
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message ||
                        'Failed to validate coupon. Please try again.';
                    $('#couponErrorMessage').text(errorMsg).removeClass('d-none');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('@lang('Apply')');
                }
            });
        });

        // Remove coupon
        $(document).on('click', '#removeCouponBtn', function() {
            appliedCouponCode = null;
            appliedCouponData = null;
            $('#form_coupon_code').val('');
            updatePriceDisplays();
        });

        // Allow Enter key to apply coupon
        $(document).on('keypress', '#couponCodeInput', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#applyCouponCodeBtn').click();
            }
        });

        $(document).ready(function() {
            // If user is logged in, mark OTP as verified and hide OTP section
            @if (auth()->check())
                $('#is_otp_verified').val('1');
                $('#otpVerificationContainer').addClass('d-none');
            @endif

            // Show "Send OTP" button if user changes phone number (and they're not logged in, or changed to different number)
            @if (auth()->check())
                let originalPhone = $('#passenger_phone').val();
                $('#passenger_phone').on('input change', function() {
                    const currentPhone = $(this).val().trim();
                    // If logged in but phone changed, show OTP button again
                    if (currentPhone !== originalPhone && currentPhone.length >= 10) {
                        // Create and show OTP button if it doesn't exist
                        if ($('#sendOtpBtn').length === 0) {
                            $('#passenger_phone').parent().append(
                                '<button type="button" class="btn btn-primary btn-sm otp-btn" id="sendOtpBtn">@lang('Send OTP to WhatsApp')</button>'
                            );
                        }
                        $('#sendOtpBtn').show();
                        $('#is_otp_verified').val('0');
                    } else if (currentPhone === originalPhone) {
                        // Phone back to original, hide OTP button
                        $('#sendOtpBtn').hide();
                        $('#is_otp_verified').val('1');
                    }
                });
            @endif

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
                            // Show OTP verification field only if user is not logged in
                            @if (!auth()->check())
                                $('#otpVerificationContainer').removeClass('d-none').addClass(
                                    'd-block');
                            @endif
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

            // Get the boarding point details from the card
            const pointName = $(this).find('.point-name').text().trim();
            const pointLocation = $(this).find('.point-location').text().trim();
            const pointTime = $(this).find('.point-time').text().trim();
            const pointIndex = $(this).data('index');

            // Store in hidden fields for later use
            $('#form_boarding_point_index').val(pointIndex);
            $('#form_boarding_point_name').val(pointName);
            $('#form_boarding_point_location').val(pointLocation);
            $('#form_boarding_point_time').val(pointTime);

            console.log('Boarding point selected:', {
                index: pointIndex,
                name: pointName,
                location: pointLocation,
                time: pointTime
            });
        });

        // When a dropping point is selected, store its details
        $(document).on('click', '.dropping-point-card', function() {
            // Get the dropping point details from the card
            const pointName = $(this).find('.point-name').text().trim();
            const pointLocation = $(this).find('.point-location').text().trim();
            const pointTime = $(this).find('.point-time').text().trim();
            const pointIndex = $(this).data('index');

            // Store in hidden fields for later use
            $('#form_dropping_point_index').val(pointIndex);
            $('#form_dropping_point_name').val(pointName);
            $('#form_dropping_point_location').val(pointLocation);
            $('#form_dropping_point_time').val(pointTime);

            console.log('Dropping point selected:', {
                index: pointIndex,
                name: pointName,
                location: pointLocation,
                time: pointTime
            });
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
            color: white;
        }

        .flyout-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            padding: 0;
            border-radius: 50%;
            transition: background-color 0.2s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
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


        .boarding-point-card,
        .dropping-point-card,
        .point-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .boarding-point-card:hover,
        .dropping-point-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.1);
        }

        .point-card.selected {
            border-color: #667eea !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .boarding-point-card.border-primary,
        .dropping-point-card.border-primary {
            /* This class seems unused now, but keeping for compatibility */
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
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .book-bus-btn:hover:not(:disabled):not(.disabled) {
            background: #c32d36;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(214, 57, 66, 0.3);
        }

        .book-bus-btn:disabled,
        .book-bus-btn.disabled {
            background: #cccccc;
            color: #666666;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Professional Boarding/Dropping Point Cards */
        .point-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-bottom: 12px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            max-width: 100%;
        }

        .point-info-row {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 4px;
            flex-wrap: nowrap;
        }

        .point-details-row {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            line-height: 1.3;
            flex-wrap: wrap;
        }

        .boarding-point-card:hover,
        .dropping-point-card:hover,
        .point-card:hover {
            border-color: #D63942;
            box-shadow: 0 4px 12px rgba(214, 57, 66, 0.15);
            transform: translateY(-1px);
        }

        .boarding-point-card.selected,
        .dropping-point-card.selected,
        .point-card.selected {
            border-color: #D63942;
            background: #D63942;
            color: white;
            box-shadow: 0 4px 12px rgba(214, 57, 66, 0.2);
        }


        .point-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #333;
            white-space: normal;
            word-wrap: break-word;
        }

        .point-time {
            font-size: 1.1rem;
            color: #333;
            font-weight: 700;
            min-width: 65px;
            flex-shrink: 0;
        }

        .boarding-point-card.selected .point-time,
        .dropping-point-card.selected .point-time,
        .point-card.selected .point-time {
            color: white;
        }



        .point-location {
            font-size: 0.8rem;
            color: #666;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            line-height: 1.4;
            flex: 1;
        }

        .point-location:last-child,
        .point-contact:last-child {
            margin-bottom: 0;
        }

        .boarding-point-card.selected .point-location,
        .dropping-point-card.selected .point-location,
        .point-card.selected .point-location {
            color: rgba(255, 255, 255, 0.85);
        }

        .point-contact {
            font-size: 0.75rem;
            color: #999;
            margin-left: 4px;
            flex-shrink: 0;
        }

        .point-card.selected .point-contact {
            color: rgba(255, 255, 255, 0.75);
        }

        /* Flyout bottom navigation (tabs + continue button) */
        .flyout-bottom-nav {
            position: fixed;
            bottom: 0;
            left: auto;
            right: 0;
            width: 500px;
            background: white;
            padding: 16px 20px;
            border-top: 2px solid #e9ecef;
            z-index: 100;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 768px) {
            .flyout-bottom-nav {
                width: 100%;
            }
        }

        .flyout-bottom-nav .nav-tabs {
            border-bottom: none;
            margin-bottom: 12px;
        }

        .flyout-bottom-nav .continue-btn {
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: #D63942;
            border: none;
            color: white;
            width: 100%;
        }

        .flyout-bottom-nav .continue-btn:hover:not(:disabled):not(.disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(214, 57, 66, 0.3);
            background: #c32d36;
        }

        .flyout-bottom-nav .continue-btn:disabled,
        .flyout-bottom-nav .continue-btn.disabled {
            background: #cccccc;
            color: #666666;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Passenger bottom nav - same styling as flyout bottom nav */
        .passenger-bottom-nav,
        .payment-bottom-nav {
            position: fixed;
            bottom: 0;
            left: auto;
            right: 0;
            width: 500px;
            background: white;
            padding: 12px 16px;
            border-top: 2px solid #e9ecef;
            z-index: 100;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
            display: none;
        }

        @media (max-width: 768px) {

            .passenger-bottom-nav,
            .payment-bottom-nav {
                width: 100%;
            }
        }

        /* Ensure all flyout buttons have consistent font size */
        .passenger-bottom-nav button,
        .payment-bottom-nav button,
        .flyout-bottom-nav button {
            font-size: 14px;
        }

        /* Payment Invoice Styles */
        .payment-invoice {
            padding: 0;
        }

        .invoice-title {
            color: #333;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #D63942;
        }

        .invoice-section {
            background: #fff;
            padding: 12px;
            padding-top: 0px;
            margin-bottom: 12px;
        }

        .invoice-section.fare-section {
            background: #f8f9fa;
        }

        .section-title {
            color: #D63942;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            line-height: 1.3;
        }

        .detail-label {
            color: #666;
            font-size: 12px;
            font-weight: 500;
        }

        .detail-value {
            color: #333;
            font-size: 12px;
            font-weight: 600;
            text-align: right;
        }

        /* Adjust flyout body padding to account for fixed bottom nav */
        .flyout-body {
            padding: 24px 24px 100px 24px;
            overflow-y: auto;
            max-height: calc(100vh - 120px);
        }

        /* Ensure tab content has enough bottom padding */
        #passenger-content {
            padding-bottom: 80px;
        }

        /* Better section spacing */
        .col-md-6 h6 {
            color: #333;
            font-weight: 600;
            margin-bottom: 16px;
            font-size: 1rem;
        }

        /* Coupon Apply Button */
        .apply-coupon-btn {
            background: none;
            border: none;
            color: #D63942;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s ease;
        }

        .apply-coupon-btn:hover {
            color: #c32d36;
            text-decoration: underline;
        }

        .apply-coupon-btn i {
            font-size: 1rem;
        }

        /* Coupon Input Wrapper */
        .coupon-input-wrapper {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
        }

        /* Coupon Input Container */
        .coupon-input-container {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }

        .coupon-input-container .input-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .coupon-input-container .form--control {
            width: 150px;
            padding: 6px 10px;
            font-size: 0.85rem;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }

        .coupon-input-container .btn {
            padding: 6px 12px;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .coupon-error-message {
            font-size: 0.75rem;
            max-width: 200px;
            text-align: right;
        }

        /* Remove Coupon Button */
        .remove-coupon-btn {
            background: none;
            border: none;
            color: #dc3545;
            padding: 0;
            margin-left: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            transition: color 0.2s ease;
        }

        .remove-coupon-btn:hover {
            color: #c82333;
        }

        .remove-coupon-btn i {
            font-size: 0.85rem;
        }

        /* Fee Percentage Label */
        .fee-percentage-label {
            color: #666;
            font-size: 0.85rem;
            font-weight: normal;
        }

        /* Coupon Apply Section */
        .coupon-apply-section {
            border-top: 1px solid #e9ecef;
            padding-top: 10px;
            margin-top: 5px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .coupon-apply-section .fare-label {
            flex: 1;
        }

        .coupon-apply-section .fare-amount {
            flex: 1;
            display: flex;
            justify-content: flex-end;
        }
    </style>
@endpush
