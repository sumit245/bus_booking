@extends($activeTemplate . $layout)

@section('content')
    <div class="row justify-content-between mx-2 p-2 seat-selection-container">
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

        <div class="col-lg-7 col-md-7 order-1 mx-1 order-lg-2" id="seatLayoutContainer">
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

        <div class="col-lg-4 col-md-4 order-2 order-lg-1" id="bookingFormContainer">
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
                                        <span class="fare-amount subtotal-display">₹0.00</span>
                                    </div>

                                    {{-- Platform Fee --}}
                                    <div class="fare-item platform-fee-display d-none">
                                        <span class="fare-label">
                                            @lang('Platform Fee')
                                            <span class="fee-percentage-label platform-fee-label"></span>
                                        </span>
                                        <span class="fare-amount platform-fee-amount">₹0.00</span>
                                    </div>

                                    {{-- Service Charge --}}
                                    <div class="fare-item service-charge-display d-none">
                                        <span class="fare-label">
                                            @lang('Service Charge')
                                            <span class="fee-percentage-label service-charge-label"></span>
                                        </span>
                                        <span class="fare-amount service-charge-amount">₹0.00</span>
                                    </div>

                                    {{-- GST --}}
                                    <div class="fare-item gst-display d-none">
                                        <span class="fare-label">
                                            @lang('GST')
                                            <span class="fee-percentage-label gst-label"></span>
                                        </span>
                                        <span class="fare-amount gst-amount">₹0.00</span>
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
                                            <span class="discount-label-text">@lang('Discount')</span>
                                            <button type="button" class="btn-link remove-coupon-btn"
                                                id="removeCouponBtn" title="@lang('Remove coupon')">
                                                <i class="las la-times"></i>
                                            </button>
                                        </span>
                                        <span class="fare-amount text-success total-coupon-discount-display">-₹0.00</span>
                                    </div>
                                </div>

                                {{-- Total --}}
                                <div class="total-section">
                                    <div class="total-item">
                                        <span class="total-label">@lang('Total Amount')</span>
                                        <span class="total-amount total-price-display">₹0.00</span>
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
    </div>

    {{-- Mobile booking summary bottom bar + sheet --}}
    <div class="booking-bottom-overlay d-lg-none" id="bookingBottomOverlay"></div>

    {{-- Mobile Bottom Sheet Container --}}
    <div class="booking-bottom-sheet d-lg-none" id="bookingBottomSheet">
        <div class="booked-seat-details-mobile">
            <div class="booking-summary-header">
                <h6 class="booking-summary-title">@lang('Booking Summary')</h6>
                <button type="button" class="close-bottom-sheet-btn" id="closeBottomSheet" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="booking-summary-card">
                {{-- Selected Seats --}}
                <div class="selected-seats-section">
                    <div class="selected-seat-details-mobile"></div>
                </div>

                {{-- Fare Breakdown --}}
                <div class="fare-breakdown">
                    {{-- Subtotal: Sum of (BaseFare + Markup) for all selected seats --}}
                    <div class="fare-item">
                        <span class="fare-label">@lang('Subtotal')</span>
                        <span class="fare-amount subtotal-display">₹0.00</span>
                    </div>

                    {{-- Platform Fee --}}
                    <div class="fare-item platform-fee-display d-none">
                        <span class="fare-label">
                            @lang('Platform Fee')
                            <span class="fee-percentage-label platform-fee-label"></span>
                        </span>
                        <span class="fare-amount platform-fee-amount">₹0.00</span>
                    </div>

                    {{-- Service Charge --}}
                    <div class="fare-item service-charge-display d-none">
                        <span class="fare-label">
                            @lang('Service Charge')
                            <span class="fee-percentage-label service-charge-label"></span>
                        </span>
                        <span class="fare-amount service-charge-amount">₹0.00</span>
                    </div>

                    {{-- GST --}}
                    <div class="fare-item gst-display d-none">
                        <span class="fare-label">
                            @lang('GST')
                            <span class="fee-percentage-label gst-label"></span>
                        </span>
                        <span class="fare-amount gst-amount">₹0.00</span>
                    </div>

                    {{-- Apply Coupon Code Section --}}
                    <div class="fare-item coupon-apply-section">
                        <div class="coupon-section-mobile">
                            <button type="button" class="btn-link apply-coupon-btn" id="applyCouponBtnMobile">
                                <i class="las la-tag"></i> @lang('Apply Coupon Code')
                            </button>
                            <div class="coupon-input-container d-none mt-2" id="couponInputContainerMobile">
                                <div class="input-group d-flex flex-nowrap">
                                    <input type="text" class="form--control" id="couponCodeInputMobile"
                                        placeholder="@lang('Enter coupon code')"
                                        style="height: 39px; border-top-right-radius: 0; border-bottom-right-radius: 0; flex: 1;">
                                    <button type="button" class="btn btn-primary" id="applyCouponCodeBtnMobile"
                                        style="width: auto; height: 39px; padding: 0 15px; border-top-left-radius: 0; border-bottom-left-radius: 0; white-space: nowrap; font-size: 13px;">
                                        @lang('Apply')
                                    </button>
                                </div>
                                <div class="coupon-error-message text-danger d-none mt-1" id="couponErrorMessageMobile">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Coupon Discount (shown only when coupon is applied) --}}
                    <div class="fare-item coupon-discount-display d-none">
                        <span class="fare-label text-success d-inline-flex align-items-center">
                            <span class="discount-label-text">@lang('Discount')</span>
                            <button type="button" class="btn-link remove-coupon-btn" id="removeCouponBtnMobile"
                                title="@lang('Remove coupon')">
                                <i class="las la-times"></i>
                            </button>
                        </span>
                        <span class="fare-amount text-success total-coupon-discount-display">-₹0.00</span>
                    </div>
                </div>

                {{-- Total --}}
                <div class="total-section">
                    <div class="total-item">
                        <span class="total-label">@lang('Total Amount')</span>
                        <span class="total-amount total-price-display">₹0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="booking-bottom-bar d-lg-none" id="bookingBottomBar">
        <div class="bottom-bar-info" id="toggleBottomSheetInfo">
            <span class="bottom-bar-seat-count" id="bottomBarSeatCount">@lang('No seat selected')</span>
            <span class="bottom-bar-total" id="bottomBarTotal">₹0.00</span>
            <i class="las la-chevron-up bottom-bar-chevron"></i>
        </div>
        <button type="button" class="btn btn-primary bottom-bar-button" id="continueToBookingMobileBtn">
            @lang('Continue to Booking')
        </button>
    </div>
@endsection

<div class="booking-flyout" id="bookingFlyout">
    <div class="flyout-overlay" id="flyoutOverlay"></div>
    <div class="flyout-content">
        <div class="flyout-header">
            <h5 class="flyout-title">@lang('Complete Your Booking')</h5>
            <button type="button" class="flyout-close" id="closeFlyout">
                <i class="las la-times"></i>
            </button>
        </div>
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
                <div class="tab-pane fade show active" id="boarding-content" role="tabpanel">
                    {{-- <div class="step-title">@lang('Select Boarding & Dropping Points')</div> --}}
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3 d-flex justify-content-between align-items-center">
                                <span>@lang('Boarding Points')</span>
                                <i class="las la-chevron-down boarding-points-toggle"
                                    style="display: none; cursor: pointer; font-size: 18px; color: #D63942;"></i>
                            </h6>
                            <div class="boarding-points-container">
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
                <div class="tab-pane fade" id="passenger-content" role="tabpanel">
                    <div class="passenger-details">
                        {{-- <h6 class="mb-3">@lang('Passenger Information')</h6> --}}
                        <div class="row gy-3">
                            <div class="col-md-6 col-sm-6 col-6">
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
                            <div class="col-md-6 col-sm-6 col-6">
                                <div class="form-group">
                                    <label class="form-label">@lang('Age')<span
                                            class="text-danger">*</span></label>
                                    <input type="number" class="form--control" id="passenger_age"
                                        placeholder="@lang('Enter Age')" min="1" max="120"
                                        value="29">
                                    <div class="invalid-feedback">This field is required!</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-6 col-6">
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
                            <div class="col-md-6 col-sm-6 col-6">
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
                            <div class="col-12">
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

                            <div class="col-md-12 col-sm-12 col-12">
                                <div class="form-group">
                                    <label class="form-label">@lang('Phone Number')
                                        <span class="text-danger">*</span>
                                    </label>

                                    <div class="input-group d-flex flex-nowrap">
                                        <span
                                            style="height: 39px; background: #eee; border: 2px solid #e9ecef; border-right: 0; border-top-left-radius: 5px; border-bottom-left-radius: 5px; display: flex; align-items: center; padding: 0 10px; color: #555; font-weight: 500;">
                                            +91
                                        </span>

                                        <input type="tel" class="form--control" id="passenger_phone"
                                            name="passenger_phone" placeholder="@lang('Enter mobile number')" maxlength="10"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                            value="{{ auth()->check() && auth()->user()->mobile ? str_replace('91', '', auth()->user()->mobile) : '' }}"
                                            style="height: 39px; border-radius: 0; flex: 1; width: 1%;">

                                        @if (!auth()->check())
                                            <button type="button" class="btn btn-primary otp-btn" id="sendOtpBtn"
                                                style="width: auto; height: 39px !important; padding: 0 15px; border-top-left-radius: 0; border-bottom-left-radius: 0; white-space: nowrap; font-size: 13px;">
                                                @lang('Send OTP')
                                            </button>
                                        @endif
                                    </div>

                                    <div class="invalid-feedback">This field is required!</div>
                                </div>
                            </div>

                            <div class="col-md-12 col-sm-12 col-12 d-none" id="otpVerificationContainer">
                                <div class="form-group">
                                    <label class="form-label">@lang('Enter OTP')
                                        <span class="text-danger">*</span>
                                    </label>

                                    <div class="input-group d-flex flex-nowrap">
                                        <input type="tel" class="form--control" id="otp_code" name="otp_code"
                                            placeholder="@lang('Enter 6-digit OTP')" maxlength="6"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                            style="height: 39px; border-top-right-radius: 0; border-bottom-right-radius: 0; flex: 1; width: 1%;">

                                        <button type="button" class="btn btn-primary" id="verifyOtpBtn"
                                            style="width: auto; height: 39px; padding: 0 15px; border-top-left-radius: 0; border-bottom-left-radius: 0; white-space: nowrap; font-size: 13px;">
                                            @lang('Verify OTP')
                                        </button>
                                    </div>

                                    <div class="invalid-feedback">Invalid OTP!</div>

                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <small class="text-muted">@lang('OTP sent to your WhatsApp')</small>
                                        <small>
                                            <span id="otpCountdown" class="text-muted fw-bold"></span>

                                            <a href="javascript:void(0)" id="resendOtpLink"
                                                class="text-primary d-none"
                                                style="text-decoration: none; font-weight: 600;">
                                                @lang('Resend OTP')
                                            </a>
                                        </small>
                                    </div>
                                </div>
                            </div>

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
                <div class="tab-pane fade" id="payment-content" role="tabpanel">
                    <div class="payment-invoice">
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

                        <div class="invoice-section fare-section">
                            <h6 class="section-title">@lang('Fare Details')</h6>
                            <div id="invoice-fare-details"></div>
                        </div>
                    </div>
                </div>
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

@push('script')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        (function($) {
            'use strict';

            // Helper function for toast notifications (fallback if notify() not available)
            function showToast(type, message) {
                if (typeof notify !== 'undefined') {
                    notify(type, message);
                } else if (typeof iziToast !== 'undefined') {
                    iziToast[type]({
                        message: message,
                        position: "topRight"
                    });
                } else {
                    // Fallback to alert if toast library not loaded
                    alert(message);
                }
            }

            let selectedSeats = [];
            // All prices stored as integers in paisa (multiply by 100, divide by 100 only for display)
            let subtotalBeforeCoupon = 0; // Sum of (BaseFare + Markup) for all selected seats in paisa
            let finalTotalPrice = 0; // Final total with all fees in paisa
            let totalCouponDiscountApplied = 0; // Track total discount applied on subtotal in paisa
            let appliedCouponCode = null; // Track applied coupon code
            let appliedCouponData = null; // Store coupon data for validation
            let serviceChargeAmount = 0; // in paisa
            let platformFeeAmount = 0; // in paisa
            let gstAmount = 0; // in paisa

            // Helper function to format paisa as rupees for display
            function formatPrice(paisa) {
                return '₹' + (paisa / 100).toFixed(2);
            }

            // Helper function to convert rupees to paisa
            function rupeesToPaisa(rupees) {
                return Math.round(rupees * 100);
            }

            // These variables are populated from the Controller
            const flatMarkup = {{ $flatMarkup }};
            const percentageMarkup = {{ $percentageMarkup }};
            const threshold = {{ $threshold }};
            const gstPercentage = {{ $gstPercentage }};
            const serviceChargePercentage = {{ $serviceChargePercentage }};
            const platformFeePercentage = {{ $platformFeePercentage }};
            const platformFeeFixed = {{ $platformFeeFixed }};
            const currentCoupon =
                {!! $currentCouponJson !!}; // Coupon object from Controller, will be null if no active coupon

            // Variable to hold the timer interval
            let otpTimerInterval;

            function startResendTimer() {
                const duration = 180; // 3 minutes in seconds
                let timer = duration,
                    minutes, seconds;

                const display = $('#otpCountdown');
                const resendLink = $('#resendOtpLink');

                // Reset UI: Show timer, hide resend link
                display.removeClass('d-none');
                resendLink.addClass('d-none');

                // Clear any existing timer to prevent overlaps
                clearInterval(otpTimerInterval);

                // Define update function to run immediately AND in interval
                function updateDisplay() {
                    minutes = parseInt(timer / 60, 10);
                    seconds = parseInt(timer % 60, 10);

                    minutes = minutes < 10 ? "0" + minutes : minutes;
                    seconds = seconds < 10 ? "0" + seconds : seconds;

                    display.text("Resend in " + minutes + ":" + seconds);

                    if (--timer < 0) {
                        clearInterval(otpTimerInterval);
                        display.addClass('d-none');
                        resendLink.removeClass('d-none');
                    }
                }

                // Run once immediately so text appears instantly
                updateDisplay();

                // Start interval
                otpTimerInterval = setInterval(updateDisplay, 1000);
            }

            // --- INTEGRATION INSTRUCTIONS ---

            // 1. Trigger the timer when you successfully send the OTP
            // Find your existing $('#sendOtpBtn').on('click'...) success callback and add:
            // startResendTimer();

            // 2. Handle Resend Click
            $(document).on('click', '#resendOtpLink', function() {
                // Trigger the original Send OTP button click to resend
                $('#sendOtpBtn').click();

                // Restart the timer immediately (or wait for success in the click handler)
                startResendTimer();
            });

            // Calculate coupon discount on subtotal (not per seat)
            // All amounts in paisa (integers)
            function calculateCouponDiscount(subtotalPaisa) {
                if (!appliedCouponCode || !appliedCouponData) {
                    return 0;
                }

                const couponThresholdPaisa = rupeesToPaisa(parseFloat(appliedCouponData.coupon_threshold) || 0);
                const discountType = appliedCouponData.discount_type;
                // API returns 'discount_value', not 'coupon_value'
                const couponValue = parseFloat(appliedCouponData.discount_value || appliedCouponData.coupon_value) || 0;

                // Check if subtotal meets threshold (both in paisa)
                if (isNaN(subtotalPaisa) || subtotalPaisa <= 0 || isNaN(couponThresholdPaisa) || subtotalPaisa <=
                    couponThresholdPaisa) {
                    return 0;
                }

                if (!discountType || isNaN(couponValue) || couponValue <= 0) {
                    return 0;
                }

                let discountAmountPaisa = 0;
                if (discountType === 'fixed') {
                    discountAmountPaisa = rupeesToPaisa(couponValue);
                } else if (discountType === 'percentage') {
                    // Calculate percentage on paisa, result in paisa
                    discountAmountPaisa = Math.round(subtotalPaisa * couponValue / 100);
                }

                // Ensure discount doesn't exceed subtotal and is a valid number
                discountAmountPaisa = Math.min(discountAmountPaisa, subtotalPaisa);
                return isNaN(discountAmountPaisa) ? 0 : Math.max(0, discountAmountPaisa);
            }

            function updatePriceDisplays() {
                // All calculations in paisa (integers) to avoid floating-point errors

                // Calculate coupon discount on subtotal (not per seat) - returns paisa
                totalCouponDiscountApplied = calculateCouponDiscount(subtotalBeforeCoupon);

                // Convert percentages and fixed amounts to paisa for calculations
                const platformFeeFixedPaisa = rupeesToPaisa(platformFeeFixed);

                // Platform Fee (percentage + fixed) - calculated on subtotal in paisa
                const platformFeePercentagePaisa = Math.round(subtotalBeforeCoupon * platformFeePercentage / 100);
                platformFeeAmount = platformFeePercentagePaisa + platformFeeFixedPaisa;

                // Service Charge - calculated on subtotal in paisa
                serviceChargeAmount = Math.round(subtotalBeforeCoupon * serviceChargePercentage / 100);

                // GST (on subtotal + service charge + platform fee) - all in paisa
                const amountBeforeGSTPaisa = subtotalBeforeCoupon + serviceChargeAmount + platformFeeAmount;
                gstAmount = Math.round(amountBeforeGSTPaisa * gstPercentage / 100);

                // Final total: subtotal + fees - discount (all in paisa)
                finalTotalPrice = subtotalBeforeCoupon + serviceChargeAmount + platformFeeAmount + gstAmount -
                    totalCouponDiscountApplied;

                // Update displays using class selectors (works for both desktop and mobile)
                $('.subtotal-display').text(formatPrice(subtotalBeforeCoupon));

                // Platform Fee
                if (platformFeePercentage > 0 || platformFeeFixed > 0) {
                    let platformFeeLabel = '';
                    if (platformFeePercentage > 0 && platformFeeFixed > 0) {
                        platformFeeLabel = ` (${platformFeePercentage}% + ${formatPrice(platformFeeFixedPaisa)})`;
                    } else if (platformFeePercentage > 0) {
                        platformFeeLabel = ` (${platformFeePercentage}%)`;
                    } else {
                        platformFeeLabel = '';
                    }
                    $('.platform-fee-label').text(platformFeeLabel);
                    $('.platform-fee-amount').text(formatPrice(platformFeeAmount));
                    $('.platform-fee-display').removeClass('d-none').addClass('d-flex');
                } else {
                    $('.platform-fee-display').removeClass('d-flex').addClass('d-none');
                }

                // Service Charge
                if (serviceChargePercentage > 0) {
                    $('.service-charge-label').text(` (${serviceChargePercentage}%)`);
                    $('.service-charge-amount').text(formatPrice(serviceChargeAmount));
                    $('.service-charge-display').removeClass('d-none').addClass('d-flex');
                } else {
                    $('.service-charge-display').removeClass('d-flex').addClass('d-none');
                }

                // GST
                if (gstPercentage > 0) {
                    $('.gst-label').text(` (${gstPercentage}%)`);
                    $('.gst-amount').text(formatPrice(gstAmount));
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

                    // Update discount label text using class selector
                    $('.discount-label-text').text(discountLabel);

                    $('.total-coupon-discount-display').text('-' + formatPrice(totalCouponDiscountApplied));
                    $('.coupon-discount-display').removeClass('d-none').addClass('d-flex');

                    // Hide Apply Coupon Code section when discount is shown
                    $('.coupon-apply-section').addClass('d-none');
                } else {
                    $('.coupon-discount-display').removeClass('d-flex').addClass('d-none');

                    // Show Apply Coupon Code section when no discount (and hide input if it was shown)
                    $('.coupon-apply-section').removeClass('d-none');
                    $('#couponInputContainer, #couponInputContainerMobile').addClass('d-none').removeClass('d-flex');
                }

                // Total Amount using class selector (works for both desktop and mobile)
                $('.total-price-display').text(formatPrice(finalTotalPrice));

                // Update the hidden input for the final price to be sent to the backend (convert paisa to rupees)
                $('input[name="price"]').val((finalTotalPrice / 100).toFixed(2));

                // Update mobile bottom bar total (only relevant on small screens but harmless elsewhere)
                $('#bottomBarTotal').text(formatPrice(finalTotalPrice));
            }

            function AddRemoveSeat(el, seatId, price) {
                const seatNumber = seatId;
                const seatOriginalPriceRupees = parseFloat(price);
                const seatOriginalPricePaisa = rupeesToPaisa(seatOriginalPriceRupees);

                // Calculate markup in rupees, then convert to paisa
                let markupAmountRupees = 0;
                if (seatOriginalPriceRupees < threshold) {
                    markupAmountRupees = flatMarkup;
                } else {
                    markupAmountRupees = seatOriginalPriceRupees * percentageMarkup / 100;
                }
                const markupAmountPaisa = rupeesToPaisa(markupAmountRupees);

                // Price with markup in paisa (this is what goes into subtotal)
                const priceWithMarkupPaisa = seatOriginalPricePaisa + markupAmountPaisa;
                const priceWithMarkupRupees = priceWithMarkupPaisa / 100;

                el.classList.toggle('selected');
                const alreadySelected = selectedSeats.includes(seatNumber);

                if (!alreadySelected) {
                    selectedSeats.push(seatNumber);
                    subtotalBeforeCoupon += priceWithMarkupPaisa; // Add to subtotal in paisa (BEFORE coupon)
                    const seatHTML = `<span class="list-group-item d-flex justify-content-between" data-seat-id="${seatNumber}" data-price="${priceWithMarkupRupees.toFixed(2)}">
                        @lang('Seat') ${seatNumber} <span>{{ __($general->cur_sym) }}${priceWithMarkupRupees.toFixed(2)}</span>
                    </span>`;
                    $('.selected-seat-details').append(seatHTML);
                    $('.selected-seat-details-mobile').append(seatHTML);
                } else {
                    selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
                    // Get the stored price from the data attribute (in rupees), convert to paisa
                    const storedPriceRupees = parseFloat($(`.selected-seat-details span[data-seat-id="${seatNumber}"]`)
                        .data(
                            'price'));
                    const storedPricePaisa = rupeesToPaisa(storedPriceRupees);
                    subtotalBeforeCoupon -= storedPricePaisa; // Subtract from subtotal in paisa
                    $(`.selected-seat-details span[data-seat-id="${seatNumber}"]`).remove();
                    $(`.selected-seat-details-mobile span[data-seat-id="${seatNumber}"]`).remove();
                }

                // Update hidden input for selected seats
                $('input[name="seats"]').val(selectedSeats.join(','));

                // Update mobile bottom bar seat count + visibility
                const seatCount = selectedSeats.length;
                let seatLabel = '@lang('No seat selected')';
                if (seatCount === 1) {
                    seatLabel = '1 @lang('seat')';
                } else if (seatCount > 1) {
                    seatLabel = seatCount + ' @lang('seats')';
                }
                $('#bottomBarSeatCount').text(seatLabel);

                if (seatCount > 0) {
                    $('#bookingBottomBar').addClass('active');
                } else {
                    $('#bookingBottomBar').removeClass('active');
                    $('#bookingBottomOverlay').removeClass('active');
                    $('body').removeClass('booking-sheet-open');
                }

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
                        // Reset boarding points toggle state when loading new data
                        $('.boarding-points-toggle').hide();
                        $('.boarding-points-container').show();
                        renderBoardingPoints(response.data.BoardingPointsDetails || []);
                        renderDroppingPoints(response.data.DroppingPointsDetails || []);
                    },
                    error: function(xhr) {
                        console.log("Error: " + (xhr.responseJSON?.message ||
                            "Failed to fetch boarding points"));
                        $('#bookingFlyout').removeClass('active');
                    }
                });
            }


            function renderBoardingPoints(points) {
                if (points.length === 0) {
                    $('.boarding-points-container').html(
                        '<div class="alert alert-info">No boarding points available</div>');
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
                    </div>
                    ${point.CityPointContactNumber ? `<div class="point-contact-row"><span class="point-contact">${point.CityPointContactNumber}</span></div>` : ''}
                </div>
                `;
                });
                $('.boarding-points-container').html(html);
                // Add click event to boarding point cards
                $('.boarding-point-card').on('click', function() {
                    $('.boarding-point-card').removeClass('selected');
                    $(this).addClass('selected');
                    $('#selected_boarding_point').val($(this).data('index'));

                    // Slide up boarding points container and show chevron toggle
                    $('.boarding-points-container').slideUp('slow', function() {
                        $('.boarding-points-toggle').removeClass('la-chevron-up').addClass(
                            'la-chevron-down').show();
                    });
                });
            }

            function renderDroppingPoints(points) {
                if (points.length === 0) {
                    $('.dropping-points-container').html(
                        '<div class="alert alert-info">No dropping points available</div>');
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


            // Handle form submission
            $('#bookingForm').on('submit', function(e) {
                e.preventDefault();
                fetchBoardingPoints();
            });

            // Coupon validation and application
            $('#applyCouponBtn, #applyCouponBtnMobile').on('click', function() {
                const isMobile = $(this).attr('id') === 'applyCouponBtnMobile';
                const $container = isMobile ? $('#couponInputContainerMobile') : $('#couponInputContainer');
                const $input = isMobile ? $('#couponCodeInputMobile') : $('#couponCodeInput');
                if ($container.hasClass('d-none')) {
                    $container.removeClass('d-none').addClass('d-flex');
                    $input.focus();
                }
            });

            $('#applyCouponCodeBtn, #applyCouponCodeBtnMobile').on('click', function() {
                const isMobile = $(this).attr('id') === 'applyCouponCodeBtnMobile';
                const $input = isMobile ? $('#couponCodeInputMobile') : $('#couponCodeInput');
                const $errorMsg = isMobile ? $('#couponErrorMessageMobile') : $('#couponErrorMessage');
                const $container = isMobile ? $('#couponInputContainerMobile') : $('#couponInputContainer');

                const couponCode = $input.val().trim();
                if (!couponCode) {
                    $errorMsg.text('Please enter a coupon code').removeClass('d-none');
                    return;
                }

                // Convert paisa to rupees for API call
                if (subtotalBeforeCoupon <= 0) {
                    $errorMsg.text('Please select seats first').removeClass('d-none');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Validating...');
                $errorMsg.addClass('d-none');

                $.ajax({
                    url: "{{ url('/api/coupons/validate') }}",
                    type: "POST",
                    data: {
                        coupon_code: couponCode,
                        total_amount: subtotalBeforeCoupon / 100 // Convert paisa to rupees for API
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

                            // Hide coupon input, show discount row (both desktop and mobile)
                            $container.addClass('d-none').removeClass('d-flex');
                            $input.val('');
                            // Show the link button again if needed (will be hidden when discount is shown)
                            updatePriceDisplays();
                        } else {
                            $errorMsg.text(response.message || 'Invalid coupon code').removeClass(
                                'd-none');
                        }
                    },
                    error: function(xhr) {
                        const errorMsgText = xhr.responseJSON?.message ||
                            'Failed to validate coupon. Please try again.';
                        $errorMsg.text(errorMsgText).removeClass('d-none');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('@lang('Apply')');
                    }
                });
            });

            // Remove coupon (works for both desktop and mobile)
            $('#removeCouponBtn, #removeCouponBtnMobile').on('click', function() {
                appliedCouponCode = null;
                appliedCouponData = null;
                $('#form_coupon_code').val('');
                // Reset discount label using class selector
                $('.discount-label-text').text('Discount');
                updatePriceDisplays();
            });

            // Allow Enter key to apply coupon (both desktop and mobile)
            $('#couponCodeInput, #couponCodeInputMobile').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    const isMobile = $(this).attr('id') === 'couponCodeInputMobile';
                    const $btn = isMobile ? $('#applyCouponCodeBtnMobile') : $('#applyCouponCodeBtn');
                    $btn.click();
                }
            });

            $(document).ready(function() {
                // Disable booked seats
                $('.seat-wrapper .seat.booked').attr('disabled', true);

                // Mobile bottom sheet toggle - clicking info section opens bottom sheet
                $('#toggleBottomSheetInfo').on('click', function() {
                    // Only respond if bar is active (at least one seat selected)
                    if (!$('#bookingBottomBar').hasClass('active')) {
                        return;
                    }

                    $('body').toggleClass('booking-sheet-open');
                    $('#bookingBottomOverlay').toggleClass('active');

                    // Force update prices when opening to ensure content is populated
                    if ($('body').hasClass('booking-sheet-open') && typeof updatePriceDisplays ===
                        'function') {
                        updatePriceDisplays();
                    }
                });

                // Mobile continue to booking button - opens flyout modal
                $('#continueToBookingMobileBtn').on('click', function() {
                    // Only respond if bar is active (at least one seat selected)
                    if (!$('#bookingBottomBar').hasClass('active')) {
                        return;
                    }
                    // Close bottom sheet if open
                    $('body').removeClass('booking-sheet-open');
                    $('#bookingBottomOverlay').removeClass('active');
                    // Open flyout modal
                    fetchBoardingPoints();
                });

                $('#bookingBottomOverlay').on('click', function() {
                    $('body').removeClass('booking-sheet-open');
                    $('#bookingBottomOverlay').removeClass('active');
                });

                // Close bottom sheet button
                $('#closeBottomSheet').on('click', function() {
                    $('body').removeClass('booking-sheet-open');
                    $('#bookingBottomOverlay').removeClass('active');
                });

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
                    showToast('error', '@lang('Please select both boarding and dropping points before continuing.')');
                    return;
                }

                $('#passenger-tab').tab('show');
            });

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

            // Toggle boarding points container with chevron
            $(document).on('click', '.boarding-points-toggle', function(e) {
                e.stopPropagation(); // Prevent event bubbling
                const $container = $('.boarding-points-container');
                const $toggle = $(this);

                if ($container.is(':visible')) {
                    // Container is visible, hide it
                    $container.slideUp('slow', function() {
                        $toggle.removeClass('la-chevron-up').addClass('la-chevron-down');
                    });
                } else {
                    // Container is hidden, show it
                    $container.slideDown('slow', function() {
                        $toggle.removeClass('la-chevron-down').addClass('la-chevron-up');
                    });
                }
            });

            // Reset boarding points container when tab is shown (only if no selection exists)
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('data-bs-target') === '#boarding-content') {
                    // If boarding point was selected, keep it collapsed; otherwise show it
                    const boardingSelected = $('#selected_boarding_point').val();
                    if (boardingSelected && $('.boarding-points-container').is(':visible')) {
                        // Boarding point was selected but container is still visible (first time)
                        // This will be handled by the click handler
                    } else if (!boardingSelected) {
                        // No selection, ensure container is visible
                        $('.boarding-points-container').show();
                        $('.boarding-points-toggle').hide();
                    }
                }
            });

            // Function to update payment invoice with all booking details
            function updatePaymentInvoice() {
                // Journey Details
                const originCity = $('#origin-id').val() || '';
                const destinationCity = $('#destination-id').val() || '';
                const route = originCity && destinationCity ? `${originCity} to ${destinationCity}` : '';

                // Get journey date from form
                const journeyDateInput = $('input[name="date_of_journey"]');
                let journeyDate = '';
                if (journeyDateInput.length) {
                    const dateValue = journeyDateInput.val();
                    if (dateValue) {
                        // Convert date format if needed (m/d/Y to readable format)
                        try {
                            const dateParts = dateValue.split('/');
                            if (dateParts.length === 3) {
                                const month = dateParts[0];
                                const day = dateParts[1];
                                const year = dateParts[2];
                                journeyDate = `${day}/${month}/${year}`;
                            } else {
                                journeyDate = dateValue;
                            }
                        } catch (e) {
                            journeyDate = dateValue;
                        }
                    }
                }

                // Get boarding point details
                let boardingPoint = '';
                const boardingPointName = $('#form_boarding_point_name').val();
                const boardingPointLocation = $('#form_boarding_point_location').val();
                if (boardingPointName) {
                    boardingPoint = boardingPointLocation || boardingPointName;
                } else {
                    // Fallback: get from selected card
                    const selectedBoardingCard = $('.boarding-point-card.selected');
                    if (selectedBoardingCard.length) {
                        boardingPoint = selectedBoardingCard.find('.point-name').text().trim() ||
                            selectedBoardingCard.find('.point-location').text().trim();
                    }
                }

                // Get dropping point details
                let droppingPoint = '';
                const droppingPointName = $('#form_dropping_point_name').val();
                const droppingPointLocation = $('#form_dropping_point_location').val();
                if (droppingPointName) {
                    droppingPoint = droppingPointLocation || droppingPointName;
                } else {
                    // Fallback: get from selected card
                    const selectedDroppingCard = $('.dropping-point-card.selected');
                    if (selectedDroppingCard.length) {
                        droppingPoint = selectedDroppingCard.find('.point-name').text().trim() ||
                            selectedDroppingCard.find('.point-location').text().trim();
                    }
                }

                // Passenger Details
                const firstName = $('#passenger_firstname').val() || '';
                const lastName = $('#passenger_lastname').val() || '';
                const passengerName = `${firstName} ${lastName}`.trim() || 'N/A';
                const passengerPhone = $('#passenger_phone').val() || 'N/A';
                const passengerAge = $('#passenger_age').val() || 'N/A';

                // Selected Seats
                const seats = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'N/A';

                // Update Journey Details
                $('#invoice-route').text(route || 'N/A');
                $('#invoice-date').text(journeyDate || 'N/A');
                $('#invoice-boarding').text(boardingPoint || 'N/A');
                $('#invoice-dropping').text(droppingPoint || 'N/A');

                // Update Passenger Details
                $('#invoice-passenger-name').text(passengerName);
                $('#invoice-passenger-phone').text(passengerPhone);
                $('#invoice-passenger-age').text(passengerAge);
                $('#invoice-seats').text(seats);

                // Update Fare Details - build HTML from current fare breakdown
                let fareHTML = '';

                // Subtotal
                fareHTML += `
                    <div class="detail-row">
                        <span class="detail-label">Sub Total</span>
                        <span class="detail-value">${formatPrice(subtotalBeforeCoupon)}</span>
                    </div>
                `;

                // Platform Fee
                if (platformFeeAmount > 0) {
                    let platformFeeLabel = 'Platform Fee';
                    if (platformFeePercentage > 0 && platformFeeFixed > 0) {
                        platformFeeLabel =
                            `Platform Fee (${platformFeePercentage}% + ${formatPrice(rupeesToPaisa(platformFeeFixed))})`;
                    } else if (platformFeePercentage > 0) {
                        platformFeeLabel = `Platform Fee (${platformFeePercentage}%)`;
                    }
                    fareHTML += `
                        <div class="detail-row">
                            <span class="detail-label">${platformFeeLabel}</span>
                            <span class="detail-value">${formatPrice(platformFeeAmount)}</span>
                        </div>
                    `;
                }

                // Service Charge
                if (serviceChargeAmount > 0) {
                    fareHTML += `
                        <div class="detail-row">
                            <span class="detail-label">Service Charge (${serviceChargePercentage}%)</span>
                            <span class="detail-value">${formatPrice(serviceChargeAmount)}</span>
                        </div>
                    `;
                }

                // GST
                if (gstAmount > 0) {
                    fareHTML += `
                        <div class="detail-row">
                            <span class="detail-label">GST (${gstPercentage}%)</span>
                            <span class="detail-value">${formatPrice(gstAmount)}</span>
                        </div>
                    `;
                }

                // Coupon Discount
                if (totalCouponDiscountApplied > 0 && appliedCouponCode) {
                    let discountLabel = 'Discount';
                    if (appliedCouponData && appliedCouponData.discount_type === 'percentage') {
                        const discountPercent = parseFloat(appliedCouponData.discount_value || appliedCouponData
                            .coupon_value) || 0;
                        if (!isNaN(discountPercent) && discountPercent > 0) {
                            discountLabel = `Discount (${discountPercent}%)`;
                        }
                    }
                    fareHTML += `
                        <div class="detail-row">
                            <span class="detail-label">${discountLabel}</span>
                            <span class="detail-value" style="color: #e74c3c;">-${formatPrice(totalCouponDiscountApplied)}</span>
                        </div>
                    `;
                }

                // Total
                fareHTML += `
                    <div class="detail-row" style="border-top: 2px solid #D63942; padding-top: 8px; margin-top: 8px;">
                        <span class="detail-label" style="font-weight: 700; font-size: 14px;">Total Amount</span>
                        <span class="detail-value" style="font-weight: 700; font-size: 14px; color: #D63942;">${formatPrice(finalTotalPrice)}</span>
                    </div>
                `;

                $('#invoice-fare-details').html(fareHTML);
            }

            // Handle passenger details form submission
            $('#confirmPassengerBtn').on('click', function(e) {
                // Skip OTP verification if user is already logged in
                @if (!auth()->check())
                    if ($('#is_otp_verified').val() !== '1') {
                        e.preventDefault();
                        e.stopPropagation();
                        showToast('error', 'Please verify your phone number with OTP before proceeding');
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

            // Function to create and open Razorpay payment window
            function createPaymentOrder(orderId, ticketId, amount) {
                // Check if Razorpay is loaded
                if (typeof Razorpay === 'undefined') {
                    showToast('error', 'Payment gateway is not loaded. Please refresh the page and try again.');
                    return;
                }

                // Get Razorpay key from environment or use a default
                const razorpayKey = '{{ env('RAZORPAY_KEY', '') }}';

                if (!razorpayKey) {
                    showToast('error', 'Payment gateway configuration error. Please contact support.');
                    return;
                }

                // Get passenger details for prefill
                const passengerName = ($('#passenger_firstname').val() || '') + ' ' + ($('#passenger_lastname').val() ||
                    '');
                const passengerEmail = $('#passenger_email').val() || '';
                const passengerPhone = $('#passenger_phone').val() || '';

                // Convert amount to paisa (Razorpay expects amount in smallest currency unit)
                const amountInPaisa = Math.round(amount * 100);

                // Get origin and destination for description
                const originCity = $('#origin-id').val() || '';
                const destinationCity = $('#destination-id').val() || '';
                const description = originCity && destinationCity ?
                    `Bus Ticket Booking - ${originCity} to ${destinationCity}` :
                    'Bus Ticket Booking';

                // Razorpay options
                const options = {
                    "key": razorpayKey,
                    "amount": amountInPaisa, // Amount in paisa
                    "currency": "INR",
                    "name": "{{ config('app.name') }}",
                    "description": description,
                    "order_id": orderId,
                    "handler": function(response) {
                        // Payment successful - verify payment and complete booking
                        $.ajax({
                            url: "{{ route('book.ticket') }}",
                            type: "POST",
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            data: JSON.stringify({
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_signature: response.razorpay_signature,
                                ticket_id: ticketId
                            }),
                            contentType: 'application/json',
                            dataType: 'json',
                            success: function(data) {
                                if (data.success) {
                                    // Close the booking flyout
                                    $('#bookingFlyout').removeClass('active');

                                    // Show success message
                                    showToast('success', data.message ||
                                        'Payment successful! Ticket booked successfully.');

                                    // Redirect to success page or ticket
                                    setTimeout(function() {
                                        if (data.redirect) {
                                            window.location.href = data.redirect;
                                        } else if (data.ticket_id) {
                                            window.location.href =
                                                "{{ url('/users/print-ticket') }}/" + data
                                                .ticket_id;
                                        } else {
                                            window.location.href =
                                                "{{ route('user.dashboard') }}";
                                        }
                                    }, 1500);
                                } else {
                                    showToast('error', data.message ||
                                        'Payment verification failed. Please contact support.');
                                    // Re-enable the button
                                    $('#payNowBtn').prop('disabled', false).html(
                                        `@lang('Pay Now') <span id="payment-total-display">${$('.total-price-display').first().text()}</span>`
                                    );
                                }
                            },
                            error: function(xhr) {
                                console.error('Payment verification error:', xhr);
                                showToast('error', xhr.responseJSON?.message ||
                                    'Payment verification failed. Please contact support.');
                                // Re-enable the button
                                $('#payNowBtn').prop('disabled', false).html(
                                    `@lang('Pay Now') <span id="payment-total-display">${$('.total-price-display').first().text()}</span>`
                                );
                            }
                        });
                    },
                    "prefill": {
                        "name": passengerName.trim() || 'Customer',
                        "email": passengerEmail || '',
                        "contact": passengerPhone || ''
                    },
                    "theme": {
                        "color": "#D63942"
                    },
                    "modal": {
                        "ondismiss": function() {
                            // User closed the payment window without completing payment
                            console.log('Payment cancelled by user');
                            // Re-enable the button
                            $('#payNowBtn').prop('disabled', false).html(
                                `@lang('Pay Now') <span id="payment-total-display">${$('.total-price-display').first().text()}</span>`
                            );
                        }
                    }
                };

                try {
                    const rzp = new Razorpay(options);
                    rzp.open();
                } catch (error) {
                    console.error('Error opening Razorpay:', error);
                    showToast('error', 'Error opening payment window. Please try again.');
                    // Re-enable the button
                    $('#payNowBtn').prop('disabled', false).html(
                        `@lang('Pay Now') <span id="payment-total-display">${$('.total-price-display').first().text()}</span>`
                    );
                }
            }

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
                            // finalTotalPrice is in paisa, convert to rupees if server amount not provided
                            const amount = parseFloat(response.amount || (finalTotalPrice / 100));
                            createPaymentOrder(response.order_id, response.ticket_id, amount);
                        } else {
                            $btn.prop('disabled', false).html(
                                `@lang('Pay Now') <span id="payment-total-display">${$('.total-price-display').first().text()}</span>`
                            );
                            showToast('error', response.message ||
                                "An error occurred. Please try again.");
                        }
                    },
                    error: function(xhr) {
                        (xhr.responseJSON);
                        showToast('error', xhr.responseJSON?.message ||
                            "Failed to process booking. Please check your details.");
                        $btn.prop('disabled', false).html(
                            `@lang('Pay Now') <span id="payment-total-display">${$('#totalPriceDisplay').text()}</span>`
                        );
                    }
                });
            });


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

                // Convert paisa to rupees for API call
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
                        total_amount: subtotalBeforeCoupon / 100 // Convert paisa to rupees for API
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
                                    '<button type="button" class="btn btn-primary" id="sendOtpBtn" style="width: auto; height: 39px !important; padding: 0 15px; border-top-left-radius: 0; border-bottom-left-radius: 0; white-space: nowrap; font-size: 13px;">@lang('Send OTP')</button>'
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
                        showToast('error', 'Please enter a valid phone number');
                        return;
                    }
                    // Disable button and show loading state
                    const $btn = $(this);
                    $btn.prop('disabled', true).html(
                        '<i class="las la-spinner la-spin"></i> Sending...');
                    // Send AJAX request to send OTP
                    $.ajax({
                        url: "{{ route('send.otp') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            mobile_number: phoneNumber,
                            user_name: $('#passenger_firstname').val() + ' ' + $(
                                    '#passenger_lastname')
                                .val()
                        },
                        success: function(response) {
                            (response);
                            if (response.status === 200) {
                                // Show OTP verification field only if user is not logged in
                                @if (!auth()->check())
                                    $('#otpVerificationContainer').removeClass('d-none')
                                        .addClass(
                                            'd-block');
                                @endif
                                showToast('success', 'OTP sent to your WhatsApp number');

                                // Start timer on success
                                startResendTimer();

                            } else {
                                showToast('error', response.message ||
                                    'Failed to send OTP. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            showToast('error', 'Error: ' + (xhr.responseJSON?.message ||
                                'Failed to send OTP'));
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
                        showToast('error', 'Please enter the OTP');
                        return;
                    }
                    // Disable button and show loading state
                    const $btn = $(this);
                    $btn.prop('disabled', true).html(
                        '<i class="las la-spinner la-spin"></i> Verifying...');
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
                                $('#otpVerificationContainer').removeClass('has-error')
                                    .addClass(
                                        'has-success');
                                $('#otp_code').prop('disabled', true);
                                $btn.html('<i class="las la-check"></i> Verified').addClass(
                                    'btn--success');
                                // If user is logged in through OTP
                                if (response.user_logged_in) {
                                    showToast('success',
                                        'You have been logged in successfully!');
                                }
                            } else {
                                $('#otpVerificationContainer').addClass('has-error');
                                showToast('error', response.message ||
                                    'Invalid OTP. Please try again.');
                                $btn.prop('disabled', false).html(
                                    '@lang('Verify')');
                            }
                        },
                        error: function(xhr) {
                            showToast('error', 'Error: ' + (xhr.responseJSON?.message ||
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

                // Slide up boarding points container and show chevron toggle
                $('.boarding-points-container').slideUp('slow', function() {
                    $('.boarding-points-toggle').removeClass('la-chevron-up').addClass(
                        'la-chevron-down').show();
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
            });

            // Expose AddRemoveSeat to global scope for inline onclick handlers in seat layout HTML
            window.AddRemoveSeat = AddRemoveSeat;

        })(jQuery);
    </script>
    @endpush

    @push('style')
    <style>
        /* ===== Book Ticket Styles ===== */

        .seat-selection-container {
            margin-top: 30px;
        }

        @media (max-width: 991px) {
            .seat-selection-container {
                margin-top: 20px;
            }
        }

        @media (max-width: 575px) {
            .seat-selection-container {
                margin-top: 15px;
            }
        }

        .row {
            gap: 0px;
        }

        /* ===== Mobile Booking Bottom Bar & Sheet ===== */

        @media (max-width: 991px) {

            /* Hide booking form container completely on mobile - show bottom bar instead */
            /* Keep container accessible for bottom sheet booking summary */
            #bookingFormContainer {
                /* Make column take no space in layout */
                flex: 0 0 0 !important;
                max-width: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                /* Hide visually but keep in DOM for bottom sheet access */
                position: absolute;
                width: 0;
                height: 0;
                overflow: visible;
                opacity: 0;
                pointer-events: none;
                z-index: -1;
            }

            /* Mobile Bottom Sheet Container */
            #bookingBottomSheet {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 1050;
                max-height: 70vh;
                overflow-y: auto;
                transform: translateY(100%);
                transition: transform 0.25s ease-out;
                background: #fff;
                border-radius: 16px 16px 0 0;
                box-shadow: 0 -4px 18px rgba(0, 0, 0, 0.18);
                padding: 20px;
            }

            body.booking-sheet-open #bookingBottomSheet {
                transform: translateY(0);
            }

            /* Bottom Sheet Header with Close Button */
            .booking-summary-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }

            .close-bottom-sheet-btn {
                background: transparent;
                border: none;
                font-size: 24px;
                color: #666;
                cursor: pointer;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all 0.2s ease;
            }

            .close-bottom-sheet-btn:hover {
                background: #f5f5f5;
                color: #333;
            }

            .close-bottom-sheet-btn:active {
                background: #e9ecef;
                transform: scale(0.95);
            }

            #bookingBottomSheet .booked-seat-details-mobile {
                width: 100%;
            }

            /* Compact bottom bar */
            .booking-bottom-bar {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                padding: 10px 14px;
                background: #ffffff;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.08);
                display: none;
                align-items: center;
                justify-content: space-between;
                z-index: 1040;
            }

            .booking-bottom-bar.active {
                display: flex;
            }

            .booking-bottom-bar .bottom-bar-info {
                display: flex;
                flex-direction: column;
                gap: 2px;
                width: 50%;
                margin-right: 8px;
                cursor: pointer;
                position: relative;
                padding-right: 24px;
            }

            .booking-bottom-bar .bottom-bar-info .bottom-bar-chevron {
                position: absolute;
                right: 0;
                top: 50%;
                transform: translateY(-50%);
                font-size: 14px;
                color: #666;
                transition: transform 0.3s ease;
            }

            body.booking-sheet-open .booking-bottom-bar .bottom-bar-info .bottom-bar-chevron {
                transform: translateY(-50%) rotate(180deg);
            }

            .booking-bottom-seat-count,
            .bottom-bar-seat-count {
                font-size: 13px;
                color: #555;
            }

            .bottom-bar-total {
                font-weight: 700;
                font-size: 15px;
                color: #D63942;
            }

            .bottom-bar-button {
                padding: 8px 14px;
                font-size: 13px;
                font-weight: 600;
                border-radius: 6px;
                white-space: nowrap;
            }

            .booking-bottom-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.45);
                z-index: 1035;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.2s ease;
            }

            .booking-bottom-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            /* Adjust bottom bar when sheet is open */
            body.booking-sheet-open .booking-bottom-bar {
                transform: translateY(0);
            }

            /* Prevent bar from overlapping the fixed flyout bottom nav */
            .booking-flyout.active+.booking-bottom-bar {
                bottom: 70px;
            }

            .total-price-display {
                border-top: none !important;
                padding-top: 0px !important;
                margin-top: 0px !important;
            }
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
            padding-bottom: 100px;
            /* Space for fixed bottom nav buttons */
        }


        /* Responsive nav-tabs */
        @media (max-width: 768px) {
            .flyout-bottom-nav {
                width: 100%;
                left: 0;
                right: 0;
            }

            .flyout-content {
                width: 100%;
            }

            #bookingSteps.nav-tabs {
                flex-wrap: wrap;
                gap: 4px;
            }

            #bookingSteps .nav-item {
                flex: 1 1 auto;
                min-width: 0;
            }

            #bookingSteps .nav-link {
                font-size: 12px;
                padding: 8px 6px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .flyout-header {
                padding: 12px 16px;
            }

            .flyout-body {
                padding: 15px;
                padding-bottom: 90px;
                /* Space for fixed bottom nav on mobile */
            }

            .passenger-bottom-nav,
            .payment-bottom-nav {
                width: 100%;
                left: 0;
                right: 0;
            }
        }

        @media (max-width: 480px) {
            #bookingSteps .nav-link {
                font-size: 11px;
                padding: 6px 4px;
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
            width: 100%;
            box-sizing: border-box;
        }

        .point-info-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 8px;
            width: 100%;
        }

        .point-details-row {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            line-height: 1.4;
            width: 100%;
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
            overflow-wrap: break-word;
            flex: 1;
            min-width: 0;
            line-height: 1.4;
            word-break: break-word;
        }

        .point-time {
            font-size: 1.1rem;
            color: #333;
            font-weight: 700;
            min-width: 50px;
            width: 50px;
            flex-shrink: 0;
            line-height: 1.4;
        }

        .boarding-point-card.selected .point-time,
        .dropping-point-card.selected .point-time,
        .point-card.selected .point-time {
            color: white;
        }



        .point-location {
            font-size: 0.8rem;
            color: #666;
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: break-word;
            line-height: 1.4;
            flex: 1;
            min-width: 0;
            white-space: normal;
            max-width: 100%;
            width: 100%;
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

        .point-contact-row {
            margin-top: 4px;
            width: 100%;
        }

        .point-contact {
            font-size: 0.75rem;
            color: #999;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-all;
            display: block;
            width: 100%;
            max-width: 100%;
            line-height: 1.4;
        }

        .point-details-row i {
            flex-shrink: 0;
            margin-top: 2px;
            align-self: flex-start;
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
            /* width: 500px; */
            background: white;
            padding: 16px 20px;
            border-top: 2px solid #e9ecef;
            z-index: 100;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
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
            /* right: 0; */
            /* width: 500px; */
            background: white;
            padding: 12px 16px;
            border-top: 2px solid #e9ecef;
            z-index: 100;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
            display: none;
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

        /* Boarding Points Toggle Chevron */
        .boarding-points-toggle {
            transition: transform 0.3s ease;
            font-size: 18px !important;
            color: #D63942 !important;
            cursor: pointer;
        }

        .boarding-points-toggle:hover {
            color: #c32d36 !important;
            transform: scale(1.1);
        }

        /* Coupon Apply Button */
        .apply-coupon-btn {
            background: none;
            border: none;
            color: #D63942;
            text-decoration: none !important;
            font-size: 0.9rem;
            padding: 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s ease;
            white-space: nowrap;
        }

        .apply-coupon-btn:hover {
            color: #c32d36;
            text-decoration: none !important;
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
            /* gap: 8px; */
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
            text-decoration: none;
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

        /* Mobile Bottom Sheet - Coupon Section */
        @media (max-width: 991px) {
            #bookingBottomSheet .coupon-apply-section {
                display: block;
            }

            #bookingBottomSheet .coupon-section-mobile {
                display: flex;
                flex-direction: column;
                width: 100%;
            }

            #bookingBottomSheet .coupon-input-container {
                width: 100%;
            }

            #bookingBottomSheet .coupon-input-container .input-group {
                width: 100%;
            }

            #bookingBottomSheet .coupon-error-message {
                max-width: 100%;
                text-align: left;
            }
        }
    </style>
@endpush
