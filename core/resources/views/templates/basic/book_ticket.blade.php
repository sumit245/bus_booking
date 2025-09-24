@extends($activeTemplate . $layout)

@section("content")
    <div class="row justify-content-between mx-2 p-2">
        {{-- Display active coupon banner --}}
        @if(isset($currentCoupon) && $currentCoupon->status && $currentCoupon->expiry_date && $currentCoupon->expiry_date->isFuture())
            <div class="coupon-display-banner">
                <p>🎉 **{{ $currentCoupon->coupon_name }}** Applied!
                @if($currentCoupon->discount_type == 'fixed')
                    Save {{ __($general->cur_sym) }}{{ showAmount($currentCoupon->coupon_value) }}
                @elseif($currentCoupon->discount_type == 'percentage')
                    Save {{ showAmount($currentCoupon->coupon_value) }}%
                @endif
                on your booking! Book before {{ showDateTime($currentCoupon->expiry_date, 'F j, Y') }} to avail this offer.
                </p>
            </div>
        @endif

        {{-- Left column to denote seat details and booking form --}}
        <div class="col-lg-4 col-md-4">
            <div class="seat-overview-wrapper">
                <form action="{{ route("block.seat") }}" method="POST" id="bookingForm" class="row gy-2">
                    @csrf
                    <div class="col-12">
                        <div class="form-group">
                            <i class="las la-calendar"></i>
                            <label for="date_of_journey"class="form-label">@lang("Journey Date")</label>
                            <input type="text" id="date_of_journey" class="form--control datpicker"
                                value="{{ Session::get("date_of_journey") ? Session::get("date_of_journey") : date("m/d/Y") }}"
                                name="date_of_journey" disabled>
                        </div>
                    </div>
                    <div class="col-12">
                        <i class="las la-location-arrow"></i>
                        <label for="origin-id" class="form-label">@lang("Pickup Point")</label>
                        <div class="form--group">
                            <input type="text" disabled id="origin-id" name="OriginId" class="form--control"
                                value="{{ $originCity->city_name }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <i class="las la-map-marker"></i>
                        <label for="destination-id" class="form-label">@lang("Dropping Point")</label>
                        <div class="form--group">
                            <input type="text" disabled id="destination-id" class="form--control" name="DestinationId"
                                value="{{ $destinationCity->city_name }}">
                        </div>
                    </div>
                    {{-- Hidden input for gender (will be set based on passenger title) --}}
                    <input type="hidden" name="gender" id="selected_gender" value="1">
                    <div class="col-12">
                        <div class="booked-seat-details d-none my-3">
                            <label>@lang("Selected Seats")</label>
                            <div class="list-group seat-details-animate">
                                <span
                                    class="list-group-item d-flex bg--base justify-content-between text-white">@lang("Seat Details")<span>@lang("Price")</span></span>
                                <div class="selected-seat-details"></div>
                                {{-- Subtotal removed as requested --}}
                                @if(isset($currentCoupon) && $currentCoupon->status && $currentCoupon->expiry_date && $currentCoupon->expiry_date->isFuture())
                                    <span class="list-group-item d-flex justify-content-between coupon-discount-display">
                                        <strong>@lang("Coupon Discount")</strong>
                                        <span id="totalCouponDiscountDisplay">0.00</span>
                                    </span>
                                @endif
                                <span class="list-group-item d-flex justify-content-between total-price-display">
                                    <strong>@lang("Total")</strong> {{-- Renamed from Grand Total --}}
                                    <span id="totalPriceDisplay">0.00</span>
                                </span>
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
                        <button type="submit" class="book-bus-btn">@lang("Continue")</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Right column with seat layout -->
        <div class="col-lg-7 col-md-7">
            <div class="seat-overview-wrapper">
                @include($activeTemplate . "partials.seatlayout", ["seatHtml" => $seatHtml])
                <div class="seat-for-reserved">
                    <div class="seat-condition available-seat">
                        <span class="seat"><span></span></span>
                        <p>@lang("Available Seats")</p>
                    </div>
                    <div class="seat-condition selected-by-you">
                        <span class="seat"><span></span></span>
                        <p>@lang("Selected by You")</p>
                    </div>
                    <div class="seat-condition selected-by-gents">
                        <div class="seat"><span></span></div>
                        <p>@lang("Booked by Gents")</p>
                    </div>
                    <div class="seat-condition selected-by-ladies">
                        <div class="seat"><span></span></div>
                        <p>@lang("Booked by Ladies")</p>
                    </div>
                    <div class="seat-condition selected-by-others">
                        <div class="seat"><span></span></div>
                        <p>@lang("Booked by Others")</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add this modal for boarding and dropping points -->
    <div class="modal fade" id="boardingPointsModal" tabindex="-1" role="dialog"
        aria-labelledby="boardingPointsModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang("Select Boarding & Dropping Points")</h5>
                    <button type="button" class="btn--close w-auto" data-bs-dismiss="modal"><i class="las la-times"></i></button>
                </div>
                <div class="modal-body">
                    <!-- Step indicator -->
                    <ul class="nav nav-tabs justify-content-center mb-4" id="bookingSteps" role="tablist"
                        style="justify-content: left!important;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="boarding-tab" data-bs-toggle="tab"
                                data-bs-target="#boarding-content" type="button" role="tab">
                                @lang("Boarding & Dropping")
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="passenger-tab" data-bs-toggle="tab" data-bs-target="#passenger-content"
                                type="button" role="tab">
                                @lang("Passenger Details")
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment-content"
                                type="button" role="tab">
                                @lang("Payment")
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <!-- Step 1: Boarding & Dropping Points -->
                        <div class="tab-pane fade show active" id="boarding-content" role="tabpanel">
                            <h6 class="mb-3 text-center">@lang("Please select boarding & dropping point")</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3">@lang("Boarding Points")</h6>
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
                                    <h6 class="mb-3">@lang("Dropping Points")</h6>
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
                                <button type="button" class="btn btn--success btn--sm" id="nextToPassengerBtn">
                                    @lang("Next")
                                </button>
                            </div>
                        </div>
                        <!-- Step 2: Passenger Details -->
                        <div class="tab-pane fade" id="passenger-content" role="tabpanel">
                            <h6 class="mb-3 text-center">@lang("Few details please")</h6>
                            <div class="passenger-details">
                                <h6 class="mb-3">@lang("Passenger Information")</h6>
                                <div class="row gy-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang("Title")<span class="text-danger">*</span></label>
                                            <select class="form--control" name="passenger_title" id="passenger_title">
                                                <option value="Mr" selected>@lang("Mr")</option>
                                                <option value="Ms">@lang("Ms")</option>
                                                <option value="Other">@lang("Other")</option>
                                            </select>
                                            <div class="invalid-feedback">This field is required!</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang("Age")<span class="text-danger">*</span></label>
                                            <input type="number" class="form--control" id="passenger_age" placeholder="@lang("Enter Age")"
                                                min="1" max="120" value="29">
                                            <div class="invalid-feedback">This field is required!</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang("First Name")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form--control" id="passenger_firstname"
                                                placeholder="@lang("Enter First Name")" value="{{ auth()->check() ? auth()->user()->firstname : "" }}">
                                            <div class="invalid-feedback">This field is required!</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang("Last Name")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form--control" id="passenger_lastname"
                                                placeholder="@lang("Enter Last Name")" value="{{ auth()->check() ? auth()->user()->lastname : "" }}">
                                            <div class="invalid-feedback">This field is required!</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang("Email")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" class="form--control" id="passenger_email"
                                                placeholder="@lang("Enter Email")" value="{{ auth()->check() ? auth()->user()->email : "" }}">
                                            <div class="invalid-feedback">This field is required!</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang("Phone Number")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="tel" class="form--control" id="passenger_phone" name="passenger_phone"
                                                    placeholder="@lang("Enter Phone Number")" value="">
                                                <button type="button" class="btn btn--base" id="sendOtpBtn">
                                                    @lang("Send OTP")
                                                </button>
                                            </div>
                                            <div class="invalid-feedback">This field is required!</div>
                                        </div>
                                    </div>
                                    <!-- Add OTP verification field (initially hidden) -->
                                    <div class="col-md-6 d-none" id="otpVerificationContainer">
                                        <div class="form-group">
                                            <label class="form-label">@lang("Enter OTP")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="text" class="form--control" id="otp_code" name="otp_code"
                                                    placeholder="@lang("Enter OTP sent to WhatsApp")" maxlength="6">
                                                <button type="button" class="btn btn--base" id="verifyOtpBtn">
                                                    @lang("Verify")
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
                                            <label class="form-label">@lang("Address")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <textarea class="form--control" id="passenger_address" placeholder="@lang("Enter Address")"></textarea>
                                            <div class="invalid-feedback">This field is required!</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" class="btn btn--danger btn--sm mx-2" id="backToBoardingBtn">
                                        @lang("Back")
                                    </button>
                                    <button type="submit" class="btn btn--success btn--sm mx-2" id="confirmPassengerBtn">
                                        @lang("Proceed to Pay")
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Step 3: Payment -->
                        <div class="tab-pane fade" id="payment-content" role="tabpanel">
                            <h6 class="mb-3 text-center">@lang("Pay to proceed")</h6>
                            <!-- Payment content will be handled by Razorpay -->
                            <div class="py-5 text-center">
                                <p>@lang("You will be redirected to the payment gateway.")</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- End of Booking Form modal --}}
@endsection

@php
    // Explicitly import classes within the @php block
    use App\Models\MarkupTable;
    use App\Models\CouponTable;
    use Carbon\Carbon;

    $markupData = MarkupTable::orderBy("id", "desc")->first();
    $flatMarkup = isset($markupData->flat_markup) ? (float) $markupData->flat_markup : 0;
    $percentageMarkup = isset($markupData->percentage_markup) ? (float) $markupData->percentage_markup : 0;
    $threshold = isset($markupData->threshold) ? (float) $markupData->threshold : 0;

    // Fetch the current active and unexpired coupon directly in the blade file
    $currentCoupon = CouponTable::where('status', 1)
                                ->where('expiry_date', '>=', Carbon::today())
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

@push("script")
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        let selectedSeats = [];
        let finalTotalPrice = 0;
        let totalCouponDiscountApplied = 0; // Track total discount applied across all seats

        // These variables are now populated from the @php block
        const flatMarkup = parseFloat("{{ $flatMarkup }}");
        const percentageMarkup = parseFloat("{{ $percentageMarkup }}");
        const threshold = parseFloat("{{ $threshold }}");
        const currentCoupon = {!! $currentCouponJson !!}; // Coupon object from PHP, will be null if no active coupon

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
            $('#totalCouponDiscountDisplay').text('-' + totalCouponDiscountApplied.toFixed(2));
            $('#totalPriceDisplay').text(finalTotalPrice.toFixed(2));

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
                        @lang("Seat") ${seatNumber} <span>{{ __($general->cur_sym) }}${priceAfterCouponPerSeat.toFixed(2)}</span>
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
                url: "{{ route("get.boarding.points") }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function() {
                    // Show modal
                    $('#boardingPointsModal').modal('show');
                },
                success: function(response) {
                    renderBoardingPoints(response.data.BoardingPointsDetails || []);
                    renderDroppingPoints(response.data.DroppingPointsDetails || []);
                },
                error: function(xhr) {
                    console.log("Error: " + (xhr.responseJSON?.message || "Failed to fetch boarding points"));
                    $('#boardingPointsModal').modal('hide');
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
                <div class="card mb-1 boarding-point-card" data-index="${point.CityPointIndex}">
                    <div class="card-body">
                        <h6 class="card-title">${point.CityPointName}</h6>
                        <p class="card-text mb-1"><i class="las la-map-marker"></i> ${point.CityPointLocation}</p>
                        <p class="card-text mb-1"><i class="las la-clock"></i> ${time}</p>
                        ${point.CityPointContactNumber ? `<p class="card-text mb-1"><i class="las la-phone"></i> ${point.CityPointContactNumber}</p>` : ''}
                        ${point.CityPointLandmark ? `<p class="card-text mb-0"><i class="las la-landmark"></i> ${point.CityPointLandmark}</p>` : ''}
                    </div>
                </div>
                `;
            });
            $('.boarding-points-container').html(html);
            // Add click event to boarding point cards
            $('.boarding-point-card').on('click', function() {
                $('.boarding-point-card').removeClass('border-primary bg-light');
                $(this).addClass('border-primary bg-light');
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
                <div class="card my-3 dropping-point-card" data-index="${point.CityPointIndex}">
                    <div class="card-body">
                        <h6 class="card-title">${point.CityPointName}</h6>
                        <p class="card-text mb-1"><i class="las la-map-marker"></i> ${point.CityPointLocation}</p>
                        <p class="card-text mb-0"><i class="las la-clock"></i> ${time}</p>
                    </div>
                </div>
                `;
            });
            $('.dropping-points-container').html(html);
            // Add click event to dropping point cards
            $('.dropping-point-card').on('click', function() {
                $('.dropping-point-card').removeClass('border-primary bg-light');
                $(this).addClass('border-primary bg-light');
                let selectedLocation = $(this).find('.card-text:first').text().trim(); // Extracts the dropping point location
                $('#passenger_address').val(selectedLocation); // Sets address field
                $('#selected_dropping_point').val($(this).data('index'));
            });
        }

        $(document).ready(function() {
            // Disable booked seats
            $('.seat-wrapper .seat.booked').attr('disabled', true);

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
                url: "{{ route("block.seat") }}",
                type: "POST",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Call Razorpay Payment Handler
                        const amount = parseFloat($('input[name="price"]').val());
                        createRazorpayOrder(response.booking_id || serverGeneratedTrx, amount); // Pass bookingId
                    } else {
                        alert(response.message || "An error occurred. Please try again.");
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON);
                    alert(xhr.responseJSON?.message || "Failed to process booking. Please check your details.");
                }
            });
        });

        // Step 1: Create a Razorpay order
        function createRazorpayOrder(bookingId, amount) {
            $.ajax({
                url: "{{ route("razorpay.create-order") }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    amount: amount,
                    booking_id: bookingId
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Step 2: Open Razorpay payment modal with the order ID
                        openRazorpayModal(response.order_id, bookingId, amount);
                    } else {
                        alert(response.message || "Failed to create payment order");
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON);
                    alert(xhr.responseJSON?.message || "Failed to create payment order. Please try again.");
                }
            });
        }

        // Step 2: Open Razorpay payment modal
        function openRazorpayModal(orderId, bookingId, amount) {
            var options = {
                "key": "{{ env("RAZORPAY_KEY") }}",
                "amount": amount * 100, // Convert to paise
                "currency": "INR",
                "name": "Ghumantoo",
                "description": "Seat Booking Payment",
                "order_id": orderId, // This is important!
                "image": "https://vindhyashrisolutions.com/assets/images/logoIcon/logo.png",
                "prefill": {
                    "name": $('#passenger_firstname').val() + ' ' + $('#passenger_lastname').val(),
                    "email": $('#passenger_email').val(),
                    "contact": $('#passenger_phone').val()
                },
                "handler": function(response) {
                    // Step 3: Process payment success with all required parameters
                    processPaymentSuccess(response, bookingId);
                },
                "theme": {
                    "color": "#3399cc"
                }
            };
            var rzp = new Razorpay(options);
            rzp.open();
        }

        // Step 3: Process payment success
        function processPaymentSuccess(response, bookingId) {
            $.ajax({
                url: "{{ route("razorpay.verify-payment") }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature,
                    booking_id: bookingId
                },
                dataType: "json",
                success: function(res) {
                    if (res.success) {
                        // Show success message
                        alert("Payment successful! Redirecting to ticket page...");
                        // Redirect to the print ticket page
                        window.location.href = res.redirect;
                    } else {
                        alert("Payment verification failed. Please contact support.");
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON);
                    alert(xhr.responseJSON?.message || "Failed to verify payment.");
                }
            });
        }

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
                    url: "{{ route("send.otp") }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        mobile_number: phoneNumber,
                        user_name: $('#passenger_firstname').val() + ' ' + $('#passenger_lastname').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show OTP verification field
                            $('#otpVerificationContainer').removeClass('d-none');
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
                        $btn.prop('disabled', false).html('@lang("Send OTP")');
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
                    url: "{{ route("verify.otp") }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        phone: phone,
                        otp: otp
                    },
                    success: function(response) {
                        if (response.success) {
                            // Mark OTP as verified
                            $('#is_otp_verified').val('1');
                            $('#otpVerificationContainer').removeClass('has-error').addClass('has-success');
                            $('#otp_code').prop('disabled', true);
                            $btn.html('<i class="las la-check"></i> Verified').addClass('btn--success');
                            // If user is logged in through OTP
                            if (response.user_logged_in) {
                                alert('You have been logged in successfully!');
                            }
                        } else {
                            $('#otpVerificationContainer').addClass('has-error');
                            alert(response.message || 'Invalid OTP. Please try again.');
                            $btn.prop('disabled', false).html(
                                '@lang("Verify")');
                        }
                    },
                    error: function(xhr) {
                        alert('Error: ' + (xhr.responseJSON?.message || 'Failed to verify OTP'));
                        $btn.prop('disabled', false).html('@lang("Verify")');
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

@push("style")
<style>
    .row {
        gap: 0px;
    }
    /* Simpler styles for price displays */
    .coupon-discount-display, .total-price-display {
        font-size: 1.1em;
        border-top: 1px solid #eee;
        padding-top: 10px;
        margin-top: 10px;
        color: #000; /* Ensure black text */
        font-weight: normal; /* Remove bold */
    }
    .coupon-discount-display span, .total-price-display span {
        font-weight: normal; /* Ensure numbers are also not bold */
        color: #000; /* Ensure numbers are also black */
    }
    .coupon-discount-display strong, .total-price-display strong {
        font-weight: normal; /* Ensure labels are not bold */
    }
    /* Keep the red color for the discount amount itself */
    .coupon-discount-display span {
        color: #e74c3c;
    }
    /* New style for coupon banner */
    .coupon-display-banner {
        background-color: #d4edda; /* Light green background */
        color: #155724; /* Dark green text */
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-size: 1.1em;
        font-weight: 600;
        text-align: center;
        border: 1px solid #c3e6cb;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .coupon-display-banner p {
        margin: 0;
    }
</style>
@endpush
