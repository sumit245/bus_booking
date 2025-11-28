@php
    use Illuminate\Support\Facades\DB;
    $contents = getContent('banner.content', true);
    $counters = App\Models\Counter::get();
    $cities = DB::table('cities')->get();
@endphp

<!-- Banner Section Starts Here -->
<section class="banner-section"
    style="background: url({{ getImage('assets/images/frontend/banner/' . $contents->data_values->background_image, '1500x88') }}) repeat-x bottom;">
    <div class="container">
        <div class="banner-wrapper">
            <div class="banner-content">
                <h1 class="title">{{ __($contents->data_values->heading) }}</h1>
                <a href="{{ __(@$contents->data_values->link) }}"
                    class="cmn--btn">{{ __(@$contents->data_values->link_title) }}</a>
            </div>
            <div class="ticket-form-wrapper mt-4">
                <form action="{{ route('search') }}" class="ticket-form row g-3 justify-content-center mt-4 pt-4">
                    <h4 class="title my-4">@lang('Choose Your Ticket')</h4>

                    <div class="col-md-12 d-flex justify-content-between">
                        <div class="row align-items-center">
                            <!-- Origin Field -->
                            <div class="col-md-5 my-2">
                                <div class="form--group">
                                    <i class="las la-location-arrow"></i>
                                    <input type="hidden" id="origin-id" name="OriginId"
                                        value="{{ request()->OriginId }}">
                                    <input type="text" id="origin" class="form--control"
                                        placeholder="@lang('From')" autocomplete="off">
                                    <div id="autocomplete-list-origin" class="autocomplete-items"></div>
                                </div>
                            </div>

                            <!-- Swap Button -->
                            <div class="col-md-2 text-center my-2">
                                <button type="button" id="swap-btn" class="swap-button" title="@lang('Swap locations')">
                                    <i class="las la-exchange-alt"></i>
                                </button>
                            </div>

                            <!-- Destination Field -->
                            <div class="col-md-5 my-2">
                                <div class="form--group">
                                    <i class="las la-map-marker"></i>
                                    <input type="hidden" id="destination-id" name="DestinationId"
                                        value="{{ request()->DestinationId }}">
                                    <input type="text" id="destination" class="form--control"
                                        placeholder="@lang('To')" autocomplete="off">
                                    <div id="autocomplete-list-destination" class="autocomplete-items"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 my-2">
                        <div class="form--group">
                            <i class="las la-calendar-check"></i>
                            <input type="text" name="DateOfJourney" class="form--control datpicker"
                                placeholder="@lang('Departure Date')" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form--group">
                            <button>@lang('Find Tickets')</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="shape">
        <img src="{{ getImage('assets/images/frontend/banner/' . $contents->data_values->animation_image, '200x69') }}"
            alt="bg">
    </div>
</section>
<!-- Banner Section Ends Here -->

@push('script')
    <script>
        $(document).ready(function() {
            $('.datpicker').datepicker({
                minDate: 0, // 0 = today (jQuery UI datepicker)
                maxDate: '+100d', // +100 days from today
                dateFormat: 'yy-mm-dd' // jQuery UI uses dateFormat not format
            });

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

                    // Filter cities based on input - match initial 3 characters only
                    const filteredCities = cities.filter(city => {
                        const cityName = city.city_name.toLowerCase();
                        // Only show cities that start with the input (minimum 3 characters)
                        if (input.length >= 3) {
                            return cityName.startsWith(input);
                        }
                        // For less than 3 characters, show all cities (user is still typing)
                        return true;
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

            // Form validation for search form
            $('.ticket-form').on('submit', function(e) {
                const originId = $('#origin-id').val();
                const destinationId = $('#destination-id').val();
                const dateOfJourney = $('.datpicker').val();

                if (!originId || !destinationId || !dateOfJourney) {
                    e.preventDefault();
                    alert('Please select origin, destination, and journey date.');
                    return false;
                }
                // Check if origin and destination are the same
                if (originId === destinationId) {
                    e.preventDefault();
                    alert('Origin and destination cannot be the same.');
                    return false;
                }
                return true;
            });

        });
    </script>
@endpush

@push('style')
    <style>
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

        /* Swap Button Styles */
        .swap-button {

            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            padding: 0px;
            !important color: white;
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
    </style>
@endpush
