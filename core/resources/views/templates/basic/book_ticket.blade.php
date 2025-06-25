@extends($activeTemplate . $layout)
@section("content")
  <div class="row justify-content-between mx-2 p-2">
    {{-- Left column to denote seat details and booking form --}}
    <div class="col-lg-4 col-md-4">
      <div class="seat-overview-wrapper">
        <form action="{{ route("block.seat") }}" method="POST" id="bookingForm" class="row gy-2">
          @csrf
          <input type="text" name="price" hidden>
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
          {{-- Select Gender --}}
          <div class="col-12">
            <label class="form-label">@lang("Select Gender")</label>
            <div class="d-flex justify-content-between flex-wrap">
              <div class="form-group custom--radio">
                <input id="male" type="radio" name="gender" value="1">
                <label class="form-label" for="male">@lang("Male")</label>
              </div>
              <div class="form-group custom--radio">
                <input id="female" type="radio" name="gender" value="2">
                <label class="form-label" for="female">@lang("Female")</label>
              </div>
              <div class="form-group custom--radio">
                <input id="other" type="radio" name="gender" value="3">
                <label class="form-label" for="other">@lang("Other")</label>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="booked-seat-details d-none my-3">
              <label>@lang("Selected Seats")</label>
              <div class="list-group seat-details-animate">
                <span
                  class="list-group-item d-flex bg--base justify-content-between text-white">@lang("Seat Details")<span>@lang("Price")</span></span>
                <div class="selected-seat-details"></div>
              </div>
            </div>
            <input type="text" name="seats" hidden>
            <input type="text" name="price" hidden>
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
                      <select class="form--control" name="gender" id="passenger_title">
                        <option value="Mr" selected>@lang("Mr")</option>
                        <option value="Ms">@lang("Ms")</option>
                        <option value="Dr">@lang("Other")</option>
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
                  <div class="col-md-6" id="otpVerificationContainer" style="display: none;">
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
  use App\Models\MarkupTable;
  $markupData = \App\Models\MarkupTable::orderBy("id", "desc")->first();
  $flatMarkup = $markupData->flat_markup ?? 0;
  $percentageMarkup = $markupData->percentage_markup ?? 0;
  $threshold = $markupData->threshold ?? 0;
@endphp

@push("script")
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <script>
    let selectedSeats = [];
    let totalPrice = 0;

    function AddRemoveSeat(el, seatId, price) {
      const seatNumber = seatId;
      const seatPrice = parseFloat(price);

      const flatMarkup = parseFloat("{{ $flatMarkup }}");
      const percentageMarkup = parseFloat("{{ $percentageMarkup }}");
      const threshold = parseFloat("{{ $threshold }}");

      const markupAmount = seatPrice < threshold ?
        flatMarkup :
        (seatPrice * percentageMarkup / 100);

      const priceWithMarkup = seatPrice + markupAmount;

      el.classList.toggle('selected');

      const alreadySelected = selectedSeats.includes(seatNumber);

      if (!alreadySelected) {
        selectedSeats.push(seatNumber);
        totalPrice += priceWithMarkup;

        $('.selected-seat-details').append(
          `<span class="list-group-item d-flex justify-content-between">
        @lang("Seat") ${seatNumber} <span>${priceWithMarkup.toFixed(2)}</span></span>`
        );
      } else {
        selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
        totalPrice -= priceWithMarkup;

        $('.selected-seat-details span').each(function() {
          if ($(this).text().includes(seatNumber)) {
            $(this).remove();
          }
        });
      }

      // Update hidden inputs
      $('input[name="seats"]').val(selectedSeats.join(','));
      $('input[name="price"]').val(totalPrice.toFixed(2));

      if (selectedSeats.length > 0) {
        $('.booked-seat-details').removeClass('d-none').addClass('d-block');
      } else {
        $('.booked-seat-details').removeClass('d-block').addClass('d-none');
      }
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

      $('input[name="gender"]').on('change', function() {
        let selectedGender = $(this).val();
        let titleField = $('#passenger_title');

        if (selectedGender === "1") {
          titleField.val("Mr"); // Male -> Mr
        } else if (selectedGender === "2") {
          titleField.val("Ms"); // Female -> Ms
        } else if (selectedGender === "3") {
          titleField.val("Other"); // Other -> Other
        }
      });

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
    $('#confirmPassengerBtn').on('click', function() {
      $('#payment-tab').tab('show');

      $('#bookingForm').append(
        `<input type="hidden" name="boarding_point_index" value="${$('#selected_boarding_point').val()}">`);
      $('#bookingForm').append(
        `<input type="hidden" name="dropping_point_index" value="${$('#selected_dropping_point').val()}">`);
      $('#bookingForm').append(
        `<input type="hidden" name="passenger_title" value="${$('#passenger_title').val()}">`);
      $('#bookingForm').append(
        `<input type="hidden" name="passenger_firstname" value="${$('#passenger_firstname').val()}">`);
      $('#bookingForm').append(
        `<input type="hidden" name="passenger_lastname" value="${$('#passenger_lastname').val()}">`);
      $('#bookingForm').append(
        `<input type="hidden" name="passenger_email" value="${$('#passenger_email').val()}">`);
      $('#bookingForm').append(
        `<input type="hidden" name="passenger_phone" value="${$('#passenger_phone').val()}">`);
      $('#bookingForm').append(
        `<input type="hidden" name="passenger_age" value="${$('#passenger_age').val()}">`);
      $('#bookingForm').append(
        `<input type="hidden" name="passenger_address" value="${$('#passenger_address').val()}">`);

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
            console.log(response.response?.Passenger);
            // Get the booking ID from the response or use the server generated one
            const bookingId = response.booking_id || serverGeneratedTrx;
            const amount = response.response?.Passenger[0]?.Seat?.SeatFare || totalPrice;

            // First create a Razorpay order
            createRazorpayOrder(bookingId, amount);
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
    // Step 3: Process payment success
    // Step 3: Process payment success
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
            phone: phoneNumber,
            name: $('#passenger_firstname').val() + ' ' + $('#passenger_lastname').val()
          },
          success: function(response) {
            if (response.success) {
              // Show OTP verification field
              $('#otpVerificationContainer').show();
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

      // Modify the confirm passenger button to check OTP verification
      $('#confirmPassengerBtn').on('click', function(e) {
        if ($('#is_otp_verified').val() !== '1') {
          e.preventDefault();
          e.stopPropagation();
          alert('Please verify your phone number with OTP before proceeding');
          return false;
        }

        // Continue with the existing functionality
        $('#payment-tab').tab('show');

        // Rest of your existing code...
      });
    });








    // When a boarding point is selected, store its details
    $('.boarding-point-card').on('click', function() {
      // Get the boarding point details
      const pointName = $(this).find('.card-title').text();
      const pointLocation = $(this).find('.card-text:first').text();
      const pointTime = $(this).find('.card-text:contains("clock")').text();

      // Store in hidden fields for later use
      $('#bookingForm').append(`<input type="hidden" name="boarding_point_name" value="${pointName}">`);
      $('#bookingForm').append(`<input type="hidden" name="boarding_point_location" value="${pointLocation}">`);
      $('#bookingForm').append(`<input type="hidden" name="boarding_point_time" value="${pointTime}">`);
    });

    // When a dropping point is selected, store its details
    $('.dropping-point-card').on('click', function() {
      // Get the dropping point details
      const pointName = $(this).find('.card-title').text();
      const pointLocation = $(this).find('.card-text:first').text();
      const pointTime = $(this).find('.card-text:contains("clock")').text();

      // Store in hidden fields for later use
      $('#bookingForm').append(`<input type="hidden" name="dropping_point_name" value="${pointName}">`);
      $('#bookingForm').append(`<input type="hidden" name="dropping_point_location" value="${pointLocation}">`);
      $('#bookingForm').append(`<input type="hidden" name="dropping_point_time" value="${pointTime}">`);
    });
  </script>
  <style>
    .row {
      gap: 0px;
    }
  </style>
@endpush
