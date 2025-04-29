  @extends($activeTemplate . $layout)
  @section("content")
    <div class="padding-top padding-bottom">

      <div class="row gx-sm-5 gy-4 gy-sm-5 justify-content-center px-5">
        {{-- Left column to denote seat details and booking form --}}
        <div class="col-lg-4 col-md-4 ml-5">
          <div class="seat-overview-wrapper">
            <form action="{{ route("block.seat") }}" method="POST" id="bookingForm" class="row gy-2">
              @csrf
              <input type="text" name="price" hidden>
              <div class="col-12">
                <div class="form-group">
                  <label for="date_of_journey"class="form-label">@lang("Journey Date")</label>
                  <input type="text" id="date_of_journey" class="form--control datpicker"
                    value="{{ Session::get("date_of_journey") ? Session::get("date_of_journey") : date("m/d/Y") }}"
                    name="date_of_journey" disabled>
                </div>
              </div>
              <div class="col-12">
                <label for="origin-id" class="form-label">@lang("Pickup Point")</label>
                <div class="form--group">
                  <i class="las la-location-arrow"></i>
                  <input type="text" disabled id="origin-id" name="OriginId" class="form--control"
                    value="{{ $originCity->city_name }}">
                </div>
              </div>
              <div class="col-12">
                <label for="destination-id" class="form-label">@lang("Dropping Point")</label>
                <div class="form--group">
                  <i class="las la-map-marker"></i>
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
              </div>
              <div class="col-12">
                <button type="submit" class="book-bus-btn">@lang("Continue")</button>
              </div>
            </form>
          </div>
        </div>
        <!-- Right column with seat layout -->
        <div class="col-lg-8 col-md-8 mr-5">
          <div class="seat-overview-wrapper">
            {{-- TODO: Add seat layout here --}}
            {!! (new \App\Http\Helpers\GenerateSeatLayout($seatLayout))->generateLayout() !!}
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
              <button type="button" class="btn--close w-auto" data-bs-dismiss="modal"><i
                  class="las la-times"></i></button>
            </div>
            <div class="modal-body">
              <!-- Step indicator -->
              <ul class="nav nav-tabs justify-content-center mb-4" id="bookingSteps" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="boarding-tab" data-bs-toggle="tab"
                    data-bs-target="#boarding-content" type="button" role="tab">
                    @lang("Please select boarding & dropping point")
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="passenger-tab" data-bs-toggle="tab" data-bs-target="#passenger-content"
                    type="button" role="tab">
                    @lang("Few details please")
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment-content"
                    type="button" role="tab">
                    @lang("Pay to proceed")
                  </button>
                </li>
              </ul>

              <div class="tab-content">
                <!-- Step 1: Boarding & Dropping Points -->
                <div class="tab-pane fade show active" id="boarding-content" role="tabpanel">
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
                  <div class="passenger-details">
                    <h6 class="mb-3">@lang("Passenger Information")</h6>

                    <div class="row gy-3">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label class="form-label">@lang("Title")<span class="text-danger">*</span></label>
                          <select class="form--control" id="passenger_title">
                            <option value="Mr" selected>@lang("Mr")</option>
                            <option value="Ms">@lang("Ms")</option>
                            <option value="Mrs">@lang("Mrs")</option>
                            <option value="Other">@lang("Other")</option>
                          </select>
                          <div class="invalid-feedback">This field is required!</div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group">
                          <label class="form-label">@lang("Age")<span class="text-danger">*</span></label>
                          <input type="number" class="form--control" id="passenger_age"
                            placeholder="@lang("Enter Age")" min="1" max="120" value="29">
                          <div class="invalid-feedback">This field is required!</div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group">
                          <label class="form-label">@lang("First Name")
                            <span class="text-danger">*</span>
                          </label>
                          <input type="text" class="form--control" id="passenger_firstname"
                            placeholder="@lang("Enter First Name")"
                            value="{{ auth()->check() ? auth()->user()->firstname : "" }}">
                          <div class="invalid-feedback">This field is required!</div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group">
                          <label class="form-label">@lang("Last Name")
                            <span class="text-danger">*</span>
                          </label>
                          <input type="text" class="form--control" id="passenger_lastname"
                            placeholder="@lang("Enter Last Name")"
                            value="{{ auth()->check() ? auth()->user()->lastname : "" }}">
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
                          <input type="tel" class="form--control" id="passenger_phone"
                            placeholder="@lang("Enter Phone Number")" value="">
                          <div class="invalid-feedback">This field is required!</div>
                        </div>
                      </div>

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

              </div>
            </div>
          </div>
        </div>
      </div>
      {{-- End of Booking Form modal --}}

    </div>
  @endsection

  @push("script")
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
      let selectedSeats = [];
      let selectedSeatsCount = 0;
      let totalPrice = 0;
      $('.seat-wrapper .seat').on('click', function() {
        let seatNumber = $(this).attr('data-seat');
        let seatPrice = $(this).attr('data-price');
        $(this).toggleClass('selected-by-you')

        if (!selectedSeats.includes(seatNumber)) {
          selectedSeats.push(seatNumber);
          totalPrice += parseFloat(seatPrice);
          // Add to display
          $('.selected-seat-details').append(
            `<span class="list-group-item d-flex justify-content-between">
                @lang("Seat") ${seatNumber} <span>${seatPrice}</span></span>`
          );

          // Update hidden fields with selected seats and total price
          $('input[name="seats"]').val(selectedSeats.join(','));
          $('input[name="price"]').val(totalPrice);
        } else {
          // Remove from display
          selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
          totalPrice -= parseFloat(seatPrice);
          $('.selected-seat-details span').each(function() {
            if ($(this).text().includes(seatNumber)) {
              $(this).remove();
            }
          });

          // Update hidden fields with selected seats and total price
          $('input[name="seats"]').val(selectedSeats.join(','));
          $('input[name="price"]').val(totalPrice);
        }
        console.log(selectedSeats.length, seatNumber, seatPrice, totalPrice);
        // Show/hide booked seat details
        if (selectedSeats.length > 0) {
          $('.booked-seat-details').removeClass('d-none').addClass('d-block');
        } else {
          $('.booked-seat-details').removeClass('d-block').addClass('d-none');
        }
      });

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
      });


      // Handle next button click to go to passenger details
      $('#nextToPassengerBtn').on('click', function() {
        $('#passenger-tab').tab('show');
      });

      // Handle passenger details form submission
      $('#confirmPassengerBtn').on('click', function() {
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
              // Redirect to Razorpay payment page with booking ID
              // Call Razorpay Payment Handler
              console.log(response.response?.Passenger)
              initiateRazorpayPayment(response.booking_id, response.response?.Passenger[0]?.SeatFare);
            } else {
              alert(response.message || "An error occurred. Please try again.");
            }
          },
          error: function(xhr) {
            console.log(xhr.responseJSON);
            alert(xhr.responseJSON?.message || "Failed to process booking. Please check your details.");
          }
        });
      })

      //   Handle confirm details button click to go to payment
      $('#confirmDetailsBtn').on('click', function() {

      });

      function initiateRazorpayPayment(bookingId, amount) {
        var options = {
          "key": "{{ env("RAZORPAY_KEY") }}", // Razorpay API Key
          "amount": amount * 100, // Convert to paise (₹1 = 100 paise)
          "currency": "INR",
          "name": "Ghumantoo",
          "description": "Seat Booking Payment",
          "image": "https://vindhyashrisolutions.com/assets/images/logoIcon/logo.png",
          "order_id": bookingId, // Unique booking ID
          "handler": function(response) {
            // Payment success callback
            processPaymentSuccess(response, bookingId);
          },
          "prefill": {
            "name": "{{ auth()->user()->name ?? "Guest" }}",
            "email": "{{ auth()->user()->email ?? "info@vindhyashrisolutions.com" }}",
            "contact": "{{ auth()->user()->phone ?? "" }}"
          },
          "theme": {
            "color": "#3399cc"
          }
        };

        // Open Razorpay Payment Modal
        var rzp = new Razorpay(options);
        rzp.open();
      }

      function processPaymentSuccess(response, bookingId) {
        $.ajax({
          url: "{{ route("book.ticket") }}",
          type: "POST",
          data: {
            _token: "{{ csrf_token() }}",
            payment_id: response.razorpay_payment_id,
            booking_id: bookingId
          },
          dataType: "json",
          success: function(res) {
            if (res.success) {
              alert("Payment successful! Booking confirmed.");
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
    </script>
  @endpush
