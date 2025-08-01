@extends($activeTemplate . $layout)

@php
    use Illuminate\Support\Facades\DB;
    $cities = DB::table("cities")->get();
    $originCity = DB::table("cities")->where("city_id", request()->OriginId)->first();
    $destinationCity = DB::table("cities")->where("city_id", request()->DestinationId)->first();
    $SearchTokenId = session()->get("search_token_id", null);
@endphp

@section("content")
    <div class="ticket-search-bar bg_img padding-top"
        style="background: url({{ getImage("assets/templates/basic/images/bg/inner.jpg") }}) left center;">
        <div class="container">
            <div class="bus-search-header">
                <form action="{{ route("search") }}" class="ticket-form ticket-form-two row g-3 justify-content-center">
                    <div class="col-md-12">
                        <div class="row align-items-center">
                            <!-- Origin Field -->
                            <div class="col-md-4 col-lg-3">
                                <div class="form--group">
                                    <i class="las la-location-arrow"></i>
                                    <input type="hidden" id="origin-id" name="OriginId" value="{{ request()->OriginId }}">
                                    <input type="text" id="origin" class="form--control" placeholder="@lang("From")"
                                        value="{{ $originCity->city_name ?? "" }}" autocomplete="off">
                                    <div id="autocomplete-list-origin" class="autocomplete-items"></div>
                                </div>
                            </div>
                            <!-- Swap Button -->
                            <div class="col-md-1 text-center">
                                <button type="button" id="swap-btn" class="swap-button" title="@lang('Swap locations')">
                                    <i class="las la-exchange-alt"></i>
                                </button>
                            </div>
                            <!-- Destination Field -->
                            <div class="col-md-4 col-lg-3">
                                <div class="form--group">
                                    <i class="las la-map-marker"></i>
                                    <input type="hidden" id="destination-id" name="DestinationId" value="{{ request()->DestinationId }}">
                                    <input type="text" id="destination" class="form--control" placeholder="@lang("To")"
                                        value="{{ $destinationCity->city_name ?? "" }}" autocomplete="off">
                                    <div id="autocomplete-list-destination" class="autocomplete-items"></div>
                                </div>
                            </div>
                            <!-- Date Field -->
                            <div class="col-md-4 col-lg-3">
                                <div class="form--group">
                                    <i class="las la-calendar-check"></i>
                                    <input type="text" name="DateOfJourney" class="form--control datpicker" placeholder="@lang("Date of Journey")"
                                        autocomplete="off" value="{{ request()->DateOfJourney }}">
                                </div>
                            </div>
                            <!-- Submit Button -->
                            <div class="col-md-6 col-lg-2">
                                <div class="form--group">
                                    <button type="submit">@lang("Modify")</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Ticket Section Starts Here -->
    <section class="ticket-section padding-bottom section-bg">
        <div class="container">
            <div class="row gy-5">
                <div class="col-lg-3">
                    <form action="{{ route("search") }}" id="filterFordsm">
                        @csrf
                        <input type="hidden" name="OriginId" value="{{ request()->OriginId }}" />
                        <input type="hidden" name="DestinationId" value="{{ request()->DestinationId }}" />
                        <input type="hidden" name="DateOfJourney" value="{{ request()->DateOfJourney }}" />
                        @include($activeTemplate . "partials.ticket-filter")
                    </form>
                </div>
                <div class="col-lg-9">
                    <div class="ticket-wrapper">
                        {{-- Display active coupon banner --}}
                        @if(isset($currentCoupon) && ($currentCoupon->flat_coupon_amount > 0 || $currentCoupon->percentage_coupon_amount > 0))
                            <div class="coupon-display-banner">
                                <p>🎉 **{{ $currentCoupon->coupon_name }}** Applied!
                                @if($currentCoupon->flat_coupon_amount > 0 && $currentCoupon->percentage_coupon_amount > 0)
                                    Save up to {{ __($general->cur_sym) }}{{ showAmount($currentCoupon->flat_coupon_amount) }} or {{ showAmount($currentCoupon->percentage_coupon_amount) }}% on your booking!
                                @elseif($currentCoupon->flat_coupon_amount > 0)
                                    Save {{ __($general->cur_sym) }}{{ showAmount($currentCoupon->flat_coupon_amount) }} on every booking!
                                @elseif($currentCoupon->percentage_coupon_amount > 0)
                                    Save {{ showAmount($currentCoupon->percentage_coupon_amount) }}% on every booking!
                                @endif
                                </p>
                            </div>
                        @endif

                        @forelse ($trips as $trip)
                            @php
                                $finalPrice = isset($trip["BusPrice"]["PublishedPrice"]) ? $trip["BusPrice"]["PublishedPrice"] : 0;
                                // PriceBeforeCoupon is set in BusService::applyCoupon
                                $priceBeforeCoupon = isset($trip["BusPrice"]["PriceBeforeCoupon"]) ? $trip["BusPrice"]["PriceBeforeCoupon"] : $finalPrice;

                                $couponThreshold = isset($currentCoupon) ? ($currentCoupon->coupon_threshold ?? 0) : 0;
                                $flatCouponAmount = isset($currentCoupon) ? ($currentCoupon->flat_coupon_amount ?? 0) : 0;
                                $percentageCouponAmount = isset($currentCoupon) ? ($currentCoupon->percentage_coupon_amount ?? 0) : 0;

                                $displaySavings = '';
                                $isDiscountApplied = ($priceBeforeCoupon > $finalPrice); // Check if any discount was actually applied

                                if ($isDiscountApplied) {
                                    // Determine which type of discount was applied based on the original price relative to the threshold
                                    if ($priceBeforeCoupon <= $couponThreshold) {
                                        // Flat coupon was applied
                                        $displaySavings = 'Save ' . __($general->cur_sym) . showAmount($flatCouponAmount);
                                    } else {
                                        // Percentage coupon was applied
                                        // Corrected: Display raw percentage value, trimmed for clean display
                                        $displaySavings = 'Save ' . rtrim(rtrim(sprintf('%.2f', $percentageCouponAmount), '0'), '.') . '%';
                                    }
                                }
                            @endphp
                            <div class="ticket-item"
                                data-departure="{{ isset($trip["DepartureTime"]) ? strtotime($trip["DepartureTime"]) : 0 }}"
                                data-price="{{ $finalPrice }}" {{-- Use finalPrice for sorting --}}
                                data-duration="{{ isset($trip["ArrivalTime"]) && isset($trip["DepartureTime"]) ? \Carbon\Carbon::parse($trip["ArrivalTime"])->diffInMinutes(\Carbon\Carbon::parse($trip["DepartureTime"])) : 0 }}">
                                <div class="ticket-grid">
                                    <div class="bus-details">
                                        <h5 class="bus-name">{{ __(isset($trip["TravelName"]) ? $trip["TravelName"] : "Unknown") }}</h5>
                                        <span class="bus-info">{{ __(isset($trip["BusType"]) ? $trip["BusType"] : "Standard") }}</span>
                                    </div>
                                    <div class="departure-details">
                                        <p class="time">
                                            {{ isset($trip["DepartureTime"]) ? \Carbon\Carbon::parse($trip["DepartureTime"])->format("h:i A") : "N/A" }}
                                        </p>
                                        <p class="place">
                                            {{ __(isset($trip["BoardingPointsDetails"][0]["CityPointLocation"]) ? $trip["BoardingPointsDetails"][0]["CityPointLocation"] : "Unknown") }}
                                        </p>
                                    </div>
                                    <div class="journey-time">
                                        <i class="las la-arrow-right"></i>
                                        @php
                                            if (isset($trip["DepartureTime"]) && isset($trip["ArrivalTime"])) {
                                                $departure = \Carbon\Carbon::parse($trip["DepartureTime"]);
                                                $arrival = \Carbon\Carbon::parse($trip["ArrivalTime"]);
                                                $diffInMinutes = $arrival->diffInMinutes($departure);
                                                $hours = floor($diffInMinutes / 60);
                                                $minutes = $diffInMinutes % 60;
                                                $duration = $hours . "h " . $minutes . "m";
                                            } else {
                                                $duration = "N/A";
                                            }
                                        @endphp
                                        <p>{{ $duration }}</p>
                                    </div>
                                    <div class="arrival-details">
                                        <p class="time">
                                            {{ isset($trip["ArrivalTime"]) ? \Carbon\Carbon::parse($trip["ArrivalTime"])->format("h:i A") : "N/A" }}
                                        </p>
                                        <p class="place">
                                            {{ __(isset($trip["DroppingPointsDetails"][0]["CityPointLocation"]) ? $trip["DroppingPointsDetails"][0]["CityPointLocation"] : "Unknown") }}
                                        </p>
                                    </div>
                                    <div class="seat-price-details">
                                        <p class="seats">{{ isset($trip["AvailableSeats"]) ? $trip["AvailableSeats"] : 0 }} Available Seats
                                        </p>
                                        <div class="price-container">
                                            @if($isDiscountApplied) {{-- Only show savings if a discount was actually applied --}}
                                                <p class="savings">{{ $displaySavings }}</p>
                                                <p class="original-price">{{ __($general->cur_sym) }}{{ showAmount($priceBeforeCoupon) }}</p>
                                            @endif
                                            <p class="current-price">{{ __($general->cur_sym) }}{{ showAmount($finalPrice) }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="select-seat-btn">
                                    <a class="btn btn--base"
                                        href="{{ route("ticket.seats", [isset($trip["ResultIndex"]) ? $trip["ResultIndex"] : 0, isset($trip["TravelName"]) ? slug($trip["TravelName"]) : "unknown"]) }}">@lang("Select Seat")</a>
                                </div>
                            </div>
                        @empty
                            <div class="ticket-item">
                                <h5>{{ __($emptyMessage) }}</h5>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push("script")
    <script>
        $(document).ready(function() {
            // Configure datepicker to disable past dates
            $('.datpicker').datepicker({
                minDate: new Date(),
                startDate: new Date(),
                maxDate: new Date(new Date().setDate(new Date().getDate() + 100)),
                autoclose: true,
                format: 'yyyy-mm-dd'
            });

            // Autocomplete functionality
            const cities = @json($cities); // Pass the cities array to JavaScript

            // Swap button functionality
            $('#swap-btn').on('click', function() {
                // Add animation class
                $(this).addClass('swap-animate');

                // Get current values
                const originValue = $('#origin').val();
                const originId = $('#origin-id').val();
                const destinationValue = $('#destination').val();
                const destinationId = $('#destination-id').val();

                // Swap the values with a slight delay for better UX
                setTimeout(() => {
                    $('#origin').val(destinationValue);
                    $('#origin-id').val(destinationId);
                    $('#destination').val(originValue);
                    $('#destination-id').val(originId);

                    // Remove animation class
                    $(this).removeClass('swap-animate');
                }, 150);

                // Clear any open autocomplete lists
                $('#autocomplete-list-origin').empty();
                $('#autocomplete-list-destination').empty();
            });

            function setupAutocomplete(inputId, listId, hiddenId) {
                $(`#${inputId}`).on('input', function() {
                    const input = $(this).val().toLowerCase();
                    $(`#${listId}`).empty(); // Clear previous suggestions
                    if (input.length === 0) return; // If input is empty, do nothing

                    // Filter cities based on input
                    const filteredCities = cities.filter(city => {
                        const cityName = city.city_name.toLowerCase();
                        return cityName.includes(input);
                    });

                    // Sort filtered cities to prioritize exact matches
                    filteredCities.sort((a, b) => {
                        const aName = a.city_name.toLowerCase();
                        const bName = b.city_name.toLowerCase();
                        return aName === input ? -1 : (bName === input ? 1 : 0);
                    });

                    // Create autocomplete suggestions
                    filteredCities.forEach(city => {
                        $(`#${listId}`).append(`
                            <div class="autocomplete-item" data-id="${city.city_id}">${city.city_name}</div>
                        `);
                    });
                });

                // Handle click on autocomplete item
                $(document).on('click', `#${listId} .autocomplete-item`, function() {
                    const cityId = $(this).data('id');
                    const cityName = $(this).text();
                    $(`#${inputId}`).val(cityName); // Set the input value
                    $(`#${hiddenId}`).val(cityId); // Set the hidden input value
                    $(`#${listId}`).empty(); // Clear suggestions
                });

                // Close the autocomplete list when clicking outside
                $(document).on('click', function(e) {
                    if (!$(e.target).closest(`#${inputId}`).length) {
                        $(`#${listId}`).empty();
                    }
                });
            }

            // Setup autocomplete for origin and destination
            setupAutocomplete('origin', 'autocomplete-list-origin', 'origin-id');
            setupAutocomplete('destination', 'autocomplete-list-destination', 'destination-id');

            // Handle filter changes for checkboxes
            $('.search').on('change', function() {
                $('#filterFordsm').submit();
            });

            // Handle price range filter changes
            if (typeof noUiSlider !== 'undefined') {
                const priceSlider = document.getElementById('price-slider');
                if (priceSlider && priceSlider.noUiSlider) {
                    priceSlider.noUiSlider.on('change', function() {
                        $('#filterFordsm').submit();
                    });
                }
            }

            // Handle sorting
            $('#sort-trips').on('change', function() {
                const sortBy = $(this).val();
                const $ticketItems = $('.ticket-item');
                $ticketItems.sort(function(a, b) {
                    switch (sortBy) {
                        case 'departure':
                            return $(a).data('departure') - $(b).data('departure');
                        case 'price-low':
                            return $(a).data('price') - $(b).data('price');
                        case 'price-high':
                            return $(b).data('price') - $(a).data('price');
                        case 'duration':
                            return $(a).data('duration') - $(b).data('duration');
                        default:
                            return $(a).data('departure') - $(b).data('departure');
                    }
                });
                $('.ticket-wrapper').append($ticketItems);
            });

            // Reset button functionality
            $('.reset-button').on('click', function(e) {
                e.preventDefault();
                // Reset all checkboxes
                $('input[type="checkbox"]').prop('checked', false);
                // Reset price slider if it exists
                if (typeof noUiSlider !== 'undefined') {
                    const priceSlider = document.getElementById('price-slider');
                    if (priceSlider && priceSlider.noUiSlider) {
                        priceSlider.noUiSlider.set([0, 5000]);
                    }
                }
                // Submit the form to apply the reset
                $('#filterFordsm').submit();
            });

            // Form validation for search form
            $('.ticket-form').on('submit', function(e) {
                const originId = $('#origin-id').val();
                const destinationId = $('#destination-id').val();
                const dateOfJourney = $('.datpicker').val();

                if (!originId || !destinationId || !dateOfJourney) {
                    e.preventDefault();
                    alert('Please select origin, destination, and journey date');
                    return false;
                }
                // Check if origin and destination are the same
                if (originId === destinationId) {
                    e.preventDefault();
                    alert('Origin and destination cannot be the same');
                    return false;
                }
                return true;
            });
        });
    </script>
@endpush

@push("style")
    <style>
        .ticket-item {
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            position: relative;
            padding-bottom: 70px;
        }
        .ticket-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1.5fr;
            gap: 50px;
            align-items: stretch;
        }
        .bus-details {
            padding-right: 10px;
        }
        .bus-name {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .bus-info {
            font-size: 13px;
            color: #666;
            display: block;
            margin-top: 5px;
        }
        .departure-details,
        .arrival-details {
            text-align: center;
        }
        .journey-time {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .journey-time i {
            margin-bottom: 5px;
            color: #e74c3c;
        }
        .time {
            font-weight: 600;
            font-size: 16px;
            margin: 0;
        }
        .place {
            font-size: 13px;
            color: #666;
            margin: 5px 0 0;
            max-width: 100%;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }
        .seat-price-details {
            text-align: right;
        }
        .seats {
            font-size: 14px;
            color: #666;
            margin: 0;
        }
        .price-container {
            margin-top: 5px;
        }
        .original-price {
            font-size: 14px !important;
            color: #999;
            margin: 0;
            text-decoration: line-through;
            font-weight: 400;
        }
        .current-price {
            font-size: 18px !important;
            font-weight: 700;
            color: #e74c3c;
            margin: 2px 0;
        }
        .savings {
            font-size: 12px !important;
            color: #27ae60;
            margin: 0;
            font-weight: 600;
            background-color: #e8f5e8;
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
        }
        .select-seat-btn {
            position: absolute;
            bottom: 20px;
            right: 20px;
        }
        .btn--base {
            padding: 8px 20px;
            border-radius: 5px;
            white-space: nowrap;
            color: white;
            border: none;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn--base:hover {
            background-color: #c0392b;
        }
        /* Loading indicator */
        .ticket-wrapper.loading {
            position: relative;
            min-height: 200px;
        }
        .ticket-wrapper.loading:after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #e74c3c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }
            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }
        /* Autocomplete styles */
        .autocomplete-items {
            overflow-y: auto;
            max-height: 200px;
            position: absolute;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            border-top: none;
            z-index: 99;
            top: 100%;
            left: 0;
            right: 0;
            background-color: #fff;
        }
        .autocomplete-item {
            padding: 10px;
            cursor: pointer;
            background-color: #fff;
        }
        .autocomplete-item:hover {
            background-color: #e9e9e9;
        }
        .swap-button {
            background-color: #e74c3c; /* Example color, adjust as needed */
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            padding: 0px; /* Important to override default button padding */
            color: white;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .swap-button:hover {
            transform: translateY(-2px);
        }
        .swap-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }
        .swap-button i {
            transition: transform 0.3s ease;
        }
        .swap-button:hover i {
            transform: rotate(180deg);
        }
        .swap-animate {
            animation: swapPulse 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .swap-button {
                width: 40px;
                height: 40px;
                font-size: 16px;
                margin: 10px 0;
            }
        }
        @media (max-width: 576px) {
            .ticket-form .row.align-items-center {
                flex-direction: column;
            }
            
            .swap-button {
                transform: rotate(90deg);
                margin: 15px 0;
            }
            
            .swap-button:hover {
                transform: rotate(90deg) translateY(-2px);
            }
        }
        .bus-details,
        .departure-details,
        .journey-time,
        .arrival-details,
        .seat-price-details {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
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

