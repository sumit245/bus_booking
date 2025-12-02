@extends($activeTemplate . $layout)

@php
    use Illuminate\Support\Facades\DB;
    $cities = DB::table('cities')->get();
    $originCity = DB::table('cities')->where('city_id', request()->OriginId)->first();
    $destinationCity = DB::table('cities')->where('city_id', request()->DestinationId)->first();
    $SearchTokenId = session()->get('search_token_id', null);
@endphp

@section('content')
    <div class="ticket-search-bar bg_img padding-top"
        style="background: url({{ getImage('assets/templates/basic/images/bg/inner.jpg') }}) left center;">
        <div class="container">
            <div class="bus-search-header">
                <form action="{{ route('search') }}" class="ticket-form ticket-form-two row g-3 justify-content-center">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-12 position-relative swap-button-container">
                                <!-- Origin Field -->
                                <div class="col-12 my-2">
                                    <div class="form--group">
                                        <i class="las la-location-arrow"></i>
                                        <input type="hidden" id="origin-id" name="OriginId"
                                            value="{{ request()->OriginId }}">
                                        <input type="text" id="origin" class="form--control"
                                            placeholder="@lang('From')" value="{{ $originCity->city_name ?? '' }}"
                                            autocomplete="off">
                                        <div id="autocomplete-list-origin" class="autocomplete-items"></div>
                                    </div>
                                </div>

                                <!-- Swap Button -->
                                <button type="button" id="swap-btn" class="swap-button" title="@lang('Swap locations')">
                                    <i class="las la-exchange-alt"></i>
                                </button>

                                <!-- Destination Field -->
                                <div class="col-12 my-2">
                                    <div class="form--group">
                                        <i class="las la-map-marker"></i>
                                        <input type="hidden" id="destination-id" name="DestinationId"
                                            value="{{ request()->DestinationId }}">
                                        <input type="text" id="destination" class="form--control"
                                            placeholder="@lang('To')" value="{{ $destinationCity->city_name ?? '' }}"
                                            autocomplete="off">
                                        <div id="autocomplete-list-destination" class="autocomplete-items"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Date Field -->
                            <div class="col-md-12 my-2">
                                <div class="form--group">
                                    <i class="las la-calendar-check"></i>
                                    <input type="text" name="DateOfJourney" class="form--control datpicker"
                                        placeholder="@lang('Date of Journey')" autocomplete="off"
                                        value="{{ request()->DateOfJourney }}">
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-md-12 my-2">
                                <div class="form--group">
                                    <button type="submit" class="form--control">@lang('Modify')</button>
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
            <!-- Mobile Filter and Sort Buttons -->
            <div class="mobile-filter-sort d-lg-none mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-filter btn-outline" id="mobileFilterBtn">
                        <i class="las la-filter"></i> @lang('Filter')
                    </button>
                    <button type="button" class="btn btn-sort btn-outline" id="mobileSortBtn">
                        <i class="las la-sort"></i> @lang('Sort')
                    </button>
                </div>
            </div>

            <div class="row gy-5">
                <!-- Desktop Filter - Hidden on mobile -->
                <div class="col-lg-3 d-none d-lg-block">
                    <form action="{{ route('search') }}" id="filterFordsm">
                        @csrf
                        <input type="hidden" name="OriginId" value="{{ request()->OriginId }}" />
                        <input type="hidden" name="DestinationId" value="{{ request()->DestinationId }}" />
                        <input type="hidden" name="DateOfJourney" value="{{ request()->DateOfJourney }}" />
                        @include($activeTemplate . 'partials.ticket-filter')
                    </form>
                </div>

                <!-- Mobile Filter Sidebar (Off-canvas) -->
                <div class="mobile-filter-sidebar d-lg-none">
                    <div class="mobile-filter-overlay" id="mobileFilterOverlay"></div>
                    <div class="mobile-filter-content" id="mobileFilterContent">
                        <div class="mobile-filter-header">
                            <h4>@lang('Filter')</h4>
                            <button type="button" class="mobile-filter-close" id="mobileFilterClose">
                                <i class="las la-times"></i>
                            </button>
                        </div>
                        <form action="{{ route('search') }}" id="mobileFilterForm">
                            @csrf
                            <input type="hidden" name="OriginId" value="{{ request()->OriginId }}" />
                            <input type="hidden" name="DestinationId" value="{{ request()->DestinationId }}" />
                            <input type="hidden" name="DateOfJourney" value="{{ request()->DateOfJourney }}" />
                            @include($activeTemplate . 'partials.ticket-filter')
                        </form>
                    </div>
                </div>

                <!-- Mobile Sort Dropdown -->
                <div class="mobile-sort-dropdown d-lg-none" id="mobileSortDropdown">
                    <button class="sort-option" data-sort="price-asc">
                        <i class="las la-sort-amount-up"></i> @lang('Price: Low to High')
                    </button>
                    <button class="sort-option" data-sort="price-desc">
                        <i class="las la-sort-amount-down"></i> @lang('Price: High to Low')
                    </button>
                    <button class="sort-option" data-sort="departure-asc">
                        <i class="las la-clock"></i> @lang('Departure: Early to Late')
                    </button>
                    <button class="sort-option" data-sort="departure-desc">
                        <i class="las la-clock"></i> @lang('Departure: Late to Early')
                    </button>
                    <button class="sort-option" data-sort="duration-asc">
                        <i class="las la-hourglass-half"></i> @lang('Duration: Short to Long')
                    </button>
                </div>

                <div class="col-lg-9 col-12">
                    <div class="ticket-wrapper">
                        {{-- Display active coupon banner - Hidden on mobile --}}
                        @if (isset($currentCoupon) &&
                                $currentCoupon->status &&
                                $currentCoupon->expiry_date &&
                                $currentCoupon->expiry_date->isFuture())
                            <div class="coupon-display-banner d-none d-lg-block">
                                <p>🎉{{ $currentCoupon->coupon_name }} Applied!
                                    @if ($currentCoupon->discount_type == 'fixed')
                                        Save {{ __($general->cur_sym) }}{{ showAmount($currentCoupon->coupon_value) }}
                                    @elseif($currentCoupon->discount_type == 'percentage')
                                        Save {{ showAmount($currentCoupon->coupon_value) }}%
                                    @endif
                                    on your booking! Book before {{ showDateTime($currentCoupon->expiry_date, 'F j, Y') }}
                                    to avail this offer.
                                </p>
                            </div>
                        @endif

                        @forelse ($trips as $trip)
                            @php
                                $finalPrice = isset($trip['BusPrice']['PublishedPrice'])
                                    ? $trip['BusPrice']['PublishedPrice']
                                    : 0;
                                // PriceBeforeCoupon is set in BusService::applyCoupon
                                $priceBeforeCoupon = isset($trip['BusPrice']['PriceBeforeCoupon'])
                                    ? $trip['BusPrice']['PriceBeforeCoupon']
                                    : $finalPrice;

                                $couponThreshold = isset($currentCoupon) ? $currentCoupon->coupon_threshold ?? 0 : 0;
                                $discountType = isset($currentCoupon)
                                    ? $currentCoupon->discount_type ?? 'fixed'
                                    : 'fixed';
                                $couponValue = isset($currentCoupon) ? $currentCoupon->coupon_value ?? 0 : 0;

                                $displaySavings = '';
                                $isDiscountApplied = $priceBeforeCoupon > $finalPrice; // Check if any discount was actually applied

                                if ($isDiscountApplied) {
                                    if ($discountType == 'fixed') {
                                        $displaySavings = 'Save ' . __($general->cur_sym) . showAmount($couponValue);
                                    } elseif ($discountType == 'percentage') {
                                        $displaySavings =
                                            'Save ' . rtrim(rtrim(sprintf('%.2f', $couponValue), '0'), '.') . '%';
                                    }
                                }
                            @endphp
                            <div class="ticket-item"
                                data-departure="{{ isset($trip['DepartureTime']) ? strtotime($trip['DepartureTime']) : 0 }}"
                                data-price="{{ $finalPrice }}" {{-- Use finalPrice for sorting --}}
                                data-duration="{{ isset($trip['ArrivalTime']) && isset($trip['DepartureTime']) ? \Carbon\Carbon::parse($trip['ArrivalTime'])->diffInMinutes(\Carbon\Carbon::parse($trip['DepartureTime'])) : 0 }}">
                                {{-- Desktop Layout --}}
                                <div class="ticket-grid d-none d-lg-grid">
                                    <div class="bus-details">
                                        <h5 class="bus-name">
                                            {{ __(isset($trip['TravelName']) ? $trip['TravelName'] : 'Unknown') }}</h5>
                                        <span
                                            class="bus-info">{{ __(isset($trip['BusType']) ? $trip['BusType'] : 'Standard') }}</span>
                                    </div>
                                    <div class="departure-details">
                                        <p class="time">
                                            {{ isset($trip['DepartureTime']) ? \Carbon\Carbon::parse($trip['DepartureTime'])->format('h:i A') : 'N/A' }}
                                        </p>
                                        <p class="place">
                                            {{ __(isset($trip['BoardingPointsDetails'][0]['CityPointLocation']) ? $trip['BoardingPointsDetails'][0]['CityPointLocation'] : 'Unknown') }}
                                        </p>
                                    </div>
                                    <div class="journey-time">
                                        <i class="las la-arrow-right"></i>
                                        @php
                                            if (isset($trip['DepartureTime']) && isset($trip['ArrivalTime'])) {
                                                $departure = \Carbon\Carbon::parse($trip['DepartureTime']);
                                                $arrival = \Carbon\Carbon::parse($trip['ArrivalTime']);
                                                $diffInMinutes = $arrival->diffInMinutes($departure);
                                                $hours = floor($diffInMinutes / 60);
                                                $minutes = $diffInMinutes % 60;
                                                $duration = $hours . 'h ' . $minutes . 'm';
                                            } else {
                                                $duration = 'N/A';
                                            }
                                        @endphp
                                        <p>{{ $duration }}</p>
                                    </div>
                                    <div class="arrival-details">
                                        <p class="time">
                                            {{ isset($trip['ArrivalTime']) ? \Carbon\Carbon::parse($trip['ArrivalTime'])->format('h:i A') : 'N/A' }}
                                        </p>
                                        <p class="place">
                                            {{ __(isset($trip['DroppingPointsDetails'][0]['CityPointLocation']) ? $trip['DroppingPointsDetails'][0]['CityPointLocation'] : 'Unknown') }}
                                        </p>
                                    </div>
                                    <div class="seat-price-details">
                                        <p class="seats">
                                            {{ isset($trip['AvailableSeats']) ? $trip['AvailableSeats'] : 0 }} Available
                                            Seats
                                        </p>
                                        <div class="price-container">
                                            @if ($isDiscountApplied)
                                                {{-- Only show savings if a discount was actually applied --}}
                                                <p class="savings">{{ $displaySavings }}</p>
                                                <p class="original-price">
                                                    {{ __($general->cur_sym) }}{{ showAmount($priceBeforeCoupon) }}</p>
                                            @endif
                                            <p class="current-price">
                                                {{ __($general->cur_sym) }}{{ showAmount($finalPrice) }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Mobile Layout (App Style) --}}
                                <div class="ticket-item-mobile d-lg-none">
                                    <div class="ticket-header-mobile">
                                        <div class="bus-name-mobile">
                                            <h5 class="bus-name-title">
                                                {{ __(isset($trip['TravelName']) ? $trip['TravelName'] : 'Unknown') }}
                                            </h5>
                                            <span
                                                class="bus-type-mobile">{{ __(isset($trip['BusType']) ? $trip['BusType'] : 'Standard') }}</span>
                                        </div>
                                    </div>

                                    <div class="ticket-time-mobile">
                                        <div class="departure-time-mobile">
                                            <p class="time-mobile">
                                                {{ isset($trip['DepartureTime']) ? \Carbon\Carbon::parse($trip['DepartureTime'])->format('h:iA') : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="duration-badge-mobile">
                                            @php
                                                if (isset($trip['DepartureTime']) && isset($trip['ArrivalTime'])) {
                                                    $departure = \Carbon\Carbon::parse($trip['DepartureTime']);
                                                    $arrival = \Carbon\Carbon::parse($trip['ArrivalTime']);
                                                    $diffInMinutes = $arrival->diffInMinutes($departure);
                                                    $hours = floor($diffInMinutes / 60);
                                                    $minutes = $diffInMinutes % 60;
                                                    $duration = $hours . 'h:' . $minutes . 'm';
                                                } else {
                                                    $duration = 'N/A';
                                                }
                                            @endphp
                                            <span>{{ $duration }}</span>
                                        </div>
                                        <div class="arrival-time-mobile">
                                            <p class="time-mobile">
                                                {{ isset($trip['ArrivalTime']) ? \Carbon\Carbon::parse($trip['ArrivalTime'])->format('h:iA') : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="ticket-footer-mobile">
                                        <div class="ticket-footer-left">
                                            <div class="seats-mobile">
                                                <i class="las la-chair"></i>
                                                <span>{{ isset($trip['AvailableSeats']) ? $trip['AvailableSeats'] : 0 }}
                                                    Seats</span>
                                            </div>
                                            <div class="price-mobile">
                                                @if ($isDiscountApplied)
                                                    <p class="price-original-mobile">
                                                        {{ __($general->cur_sym) }}{{ showAmount($priceBeforeCoupon) }}
                                                    </p>
                                                @endif
                                                <p class="price-current-mobile">From
                                                    {{ __($general->cur_sym) }}{{ showAmount($finalPrice) }}</p>
                                            </div>
                                        </div>
                                        <div class="ticket-footer-right">
                                            <div class="select-seat-btn-mobile">
                                                <a class="btn btn--base"
                                                    href="{{ route('ticket.seats', [isset($trip['ResultIndex']) ? $trip['ResultIndex'] : 0, isset($trip['TravelName']) ? slug($trip['TravelName']) : 'unknown']) }}">@lang('Select Seat')</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="select-seat-btn d-none d-lg-block">
                                    <a class="btn btn--base"
                                        href="{{ route('ticket.seats', [isset($trip['ResultIndex']) ? $trip['ResultIndex'] : 0, isset($trip['TravelName']) ? slug($trip['TravelName']) : 'unknown']) }}">@lang('Select Seat')</a>
                                </div>
                            </div>
                        @empty
                            <div class="ticket-item">
                                <h5>{{ __($emptyMessage) }}</h5>
                            </div>
                        @endforelse

                        {{-- Pagination Controls --}}
                        @if (isset($pagination) && $pagination['total_results'] > $pagination['per_page'])
                            <div class="pagination-wrapper mt-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="pagination-info">
                                        <p class="mb-0 text-muted">
                                            Showing {{ ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 }}
                                            to
                                            {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total_results']) }}
                                            of {{ $pagination['total_results'] }} buses
                                        </p>
                                    </div>
                                    <nav aria-label="Bus results pagination">
                                        <ul class="pagination mb-0">
                                            @if ($pagination['current_page'] > 1)
                                                <li class="page-item">
                                                    <a class="page-link" aria-label="Previous"
                                                        href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}">
                                                        <i class="las la-angle-left"></i>
                                                    </a>
                                                </li>
                                            @else
                                                <li class="page-item disabled">
                                                    <span class="page-link" aria-label="Previous">
                                                        <i class="las la-angle-left"></i>
                                                    </span>
                                                </li>
                                            @endif

                                            @php
                                                $currentPage = $pagination['current_page'];
                                                $totalPages = ceil(
                                                    $pagination['total_results'] / $pagination['per_page'],
                                                );
                                                $startPage = max(1, $currentPage - 2);
                                                $endPage = min($totalPages, $currentPage + 2);

                                                if ($startPage > 1) {
                                                    $showFirst = true;
                                                    $showFirstDots = $startPage > 2;
                                                } else {
                                                    $showFirst = false;
                                                    $showFirstDots = false;
                                                }

                                                if ($endPage < $totalPages) {
                                                    $showLast = true;
                                                    $showLastDots = $endPage < $totalPages - 1;
                                                } else {
                                                    $showLast = false;
                                                    $showLastDots = false;
                                                }
                                            @endphp

                                            @if ($showFirst)
                                                <li class="page-item">
                                                    <a class="page-link"
                                                        href="{{ request()->fullUrlWithQuery(['page' => 1]) }}">1</a>
                                                </li>
                                                @if ($showFirstDots)
                                                    <li class="page-item disabled">
                                                        <span class="page-link"><i class="las la-ellipsis-h"></i></span>
                                                    </li>
                                                @endif
                                            @endif

                                            @for ($page = $startPage; $page <= $endPage; $page++)
                                                @if ($page == $currentPage)
                                                    <li class="page-item active">
                                                        <span class="page-link">{{ $page }}</span>
                                                    </li>
                                                @else
                                                    <li class="page-item">
                                                        <a class="page-link"
                                                            href="{{ request()->fullUrlWithQuery(['page' => $page]) }}">{{ $page }}</a>
                                                    </li>
                                                @endif
                                            @endfor

                                            @if ($showLast)
                                                @if ($showLastDots)
                                                    <li class="page-item disabled">
                                                        <span class="page-link"><i class="las la-ellipsis-h"></i></span>
                                                    </li>
                                                @endif
                                                <li class="page-item">
                                                    <a class="page-link"
                                                        href="{{ request()->fullUrlWithQuery(['page' => $totalPages]) }}">{{ $totalPages }}</a>
                                                </li>
                                            @endif

                                            @if ($pagination['has_more_pages'])
                                                <li class="page-item">
                                                    <a class="page-link" aria-label="Next"
                                                        href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}">
                                                        <i class="las la-angle-right"></i>
                                                    </a>
                                                </li>
                                            @else
                                                <li class="page-item disabled">
                                                    <span class="page-link" aria-label="Next">
                                                        <i class="las la-angle-right"></i>
                                                    </span>
                                                </li>
                                            @endif
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            // Configure datepicker to disable past dates
            $('.datpicker').datepicker({
                minDate: 0, // 0 = today (jQuery UI datepicker)
                maxDate: '+100d', // +100 days from today
                dateFormat: 'yy-mm-dd' // jQuery UI uses dateFormat not format
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
            $('#sort-by').on('change', function() {
                $('#filterFordsm').submit();
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

            // Mobile Filter Sidebar Functionality
            const mobileFilterBtn = $('#mobileFilterBtn');
            const mobileFilterSidebar = $('.mobile-filter-sidebar');
            const mobileFilterOverlay = $('#mobileFilterOverlay');
            const mobileFilterClose = $('#mobileFilterClose');
            const mobileFilterContent = $('.mobile-filter-content');

            mobileFilterBtn.on('click', function() {
                mobileFilterSidebar.addClass('active');
                $('body').css('overflow', 'hidden');
            });

            mobileFilterClose.on('click', function() {
                closeMobileFilter();
            });

            mobileFilterOverlay.on('click', function() {
                closeMobileFilter();
            });

            function closeMobileFilter() {
                mobileFilterSidebar.removeClass('active');
                $('body').css('overflow', '');
            }

            // Close filter on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && mobileFilterSidebar.hasClass('active')) {
                    closeMobileFilter();
                }
            });

            // Mobile Sort Dropdown Functionality
            const mobileSortBtn = $('#mobileSortBtn');
            const mobileSortDropdown = $('#mobileSortDropdown');
            let currentSort = null;

            mobileSortBtn.on('click', function(e) {
                e.stopPropagation();
                mobileSortDropdown.toggleClass('active');
            });

            // Close sort dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#mobileSortBtn, #mobileSortDropdown').length) {
                    mobileSortDropdown.removeClass('active');
                }
            });

            // Sort functionality
            $('.sort-option').on('click', function() {
                const sortType = $(this).data('sort');
                $('.sort-option').removeClass('active');
                $(this).addClass('active');
                mobileSortDropdown.removeClass('active');
                currentSort = sortType;
                sortTickets(sortType);
            });

            function sortTickets(sortType) {
                const ticketItems = $('.ticket-item').toArray();

                ticketItems.sort(function(a, b) {
                    let aValue, bValue;

                    switch (sortType) {
                        case 'price-asc':
                            aValue = parseFloat($(a).data('price')) || 0;
                            bValue = parseFloat($(b).data('price')) || 0;
                            return aValue - bValue;

                        case 'price-desc':
                            aValue = parseFloat($(a).data('price')) || 0;
                            bValue = parseFloat($(b).data('price')) || 0;
                            return bValue - aValue;

                        case 'departure-asc':
                            aValue = parseInt($(a).data('departure')) || 0;
                            bValue = parseInt($(b).data('departure')) || 0;
                            return aValue - bValue;

                        case 'departure-desc':
                            aValue = parseInt($(a).data('departure')) || 0;
                            bValue = parseInt($(b).data('departure')) || 0;
                            return bValue - aValue;

                        case 'duration-asc':
                            aValue = parseInt($(a).data('duration')) || 0;
                            bValue = parseInt($(b).data('duration')) || 0;
                            return aValue - bValue;

                        default:
                            return 0;
                    }
                });

                const ticketWrapper = $('.ticket-wrapper');
                ticketItems.forEach(function(item) {
                    ticketWrapper.append(item);
                });
            }

            // Auto-submit mobile filter form when changes are made
            $('#mobileFilterForm .search').on('change', function() {
                $('#mobileFilterForm').submit();
            });

            // Handle mobile filter form reset
            $('#mobileFilterForm .reset-button').on('click', function(e) {
                e.preventDefault();
                $('#mobileFilterForm')[0].reset();
                // Reset price slider if it exists
                if (typeof priceSlider !== 'undefined' && priceSlider.noUiSlider) {
                    const dynamicMaxPrice = {{ $dynamicMaxPrice ?? 5000 }};
                    priceSlider.noUiSlider.set([0, dynamicMaxPrice]);
                }
                setTimeout(function() {
                    $('#mobileFilterForm').submit();
                }, 300);
            });
        });
    </script>
@endpush

@push('style')
    <style>
        .ticket-item {
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            position: relative;
            padding-bottom: 70px;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }

        .ticket-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1.5fr;
            gap: 50px;
            align-items: stretch;
        }

        @media (max-width: 1199px) {
            .ticket-grid {
                gap: 30px;
            }
        }

        .bus-details {
            padding-right: 10px;
            min-width: 0;
            overflow: hidden;
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
            min-width: 0;
            overflow: hidden;
        }

        .journey-time {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #666;
            font-size: 14px;
            min-width: 0;
            overflow: hidden;
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
            min-width: 0;
            overflow: hidden;
        }

        .seats {
            font-size: 14px;
            color: #666;
            margin: 0;
        }

        .price-container {
            margin-top: 5px;
            min-width: 0;
            overflow: hidden;
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

        /* Swap Button Container */
        .swap-button-container {
            padding-bottom: 10px;
        }

        .swap-button {
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            padding: 0;
            background: var(--main-color);
            color: white;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 15;
            overflow: hidden;
        }

        .swap-button:hover {
            transform: translateY(-50%) scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .swap-button:active {
            transform: translateY(-50%) scale(0.95);
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
                width: 35px;
                height: 35px;
                font-size: 16px;
                right: 12px;
            }

            .swap-button i {
                transform: rotate(90deg);
            }

            .swap-button:hover i {
                transform: rotate(270deg);
            }
        }

        @media (max-width: 576px) {
            .swap-button {
                width: 32px;
                height: 32px;
                font-size: 14px;
                right: 10px;
            }

            .swap-button i {
                transform: rotate(90deg);
            }

            .swap-button:hover i {
                transform: rotate(270deg);
            }
        }

        /* Ensure consistent input and button styling */
        .bus-search-header .ticket-form .form--control {
            width: 100%;
            border-radius: 5px !important;
        }

        .bus-search-header .ticket-form .form--group button.form--control {
            width: 100%;
            border-radius: 5px !important;
        }

        /* Add padding to destination input to avoid overlap with swap button */
        .bus-search-header .swap-button-container #destination {
            padding-right: 50px;
        }

        @media (max-width: 991px) {
            .bus-search-header {
                padding: 25px 15px;
                margin-bottom: 20px !important;
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

        /* Mobile Filter and Sort Buttons */
        .mobile-filter-sort {
            padding: 0 15px;
        }

        .mobile-filter-sort>div {
            gap: 12px;
        }

        .btn-filter,
        .btn-sort {
            background: transparent;
            color: var(--main-color);
            border: 2px solid var(--main-color);
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s ease;
            white-space: nowrap;
            min-width: auto;
            width: auto;
        }

        .btn-filter:hover,
        .btn-sort:hover {
            background: var(--main-color);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-filter i,
        .btn-sort i {
            font-size: 16px;
        }

        .btn-outline {
            background: transparent;
        }

        /* Mobile Filter Sidebar */
        .mobile-filter-sidebar {
            display: none;
        }

        .mobile-filter-sidebar.active {
            display: block;
        }

        .mobile-filter-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mobile-filter-sidebar.active .mobile-filter-overlay {
            opacity: 1;
        }

        .mobile-filter-content {
            position: fixed;
            top: 0;
            right: -100%;
            width: 85%;
            max-width: 400px;
            height: 100%;
            background: #fff;
            z-index: 9999;
            overflow-y: auto;
            transition: right 0.3s ease;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .mobile-filter-sidebar.active .mobile-filter-content {
            right: 0;
        }

        .mobile-filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: var(--main-color);
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .mobile-filter-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .mobile-filter-close {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .mobile-filter-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .mobile-filter-content #mobileFilterForm {
            padding: 20px;
        }

        /* Mobile Sort Dropdown */
        .mobile-filter-sort {
            position: relative;
        }

        .mobile-sort-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            left: auto;
            width: calc(50% - 4px);
            min-width: 200px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            padding: 10px 0;
            max-height: 300px;
            overflow-y: auto;
        }

        .mobile-sort-dropdown.active {
            display: block;
        }

        .sort-option {
            width: 100%;
            background: transparent;
            border: none;
            padding: 12px 20px;
            text-align: left;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #333;
            transition: background 0.2s ease;
        }

        .sort-option:hover {
            background: #f5f5f5;
        }

        .sort-option.active {
            background: var(--main-color);
            color: #fff;
        }

        .sort-option i {
            font-size: 16px;
        }

        /* Hide ticket filter on mobile */
        @media (max-width: 991px) {
            .ticket-wrapper {
                padding: 0;
            }

            .ticket-item {
                margin-bottom: 15px;
                padding-bottom: 20px;
            }
        }

        /* Mobile Ticket Item Layout (App Style) */
        .ticket-item-mobile {
            width: 100%;
        }

        .ticket-header-mobile {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .bus-name-mobile {
            flex: 1;
        }

        .bus-name-title {
            font-size: 16px;
            font-weight: 700;
            color: #1f1f1f;
            margin: 0 0 4px 0;
            line-height: 1.2;
        }

        .bus-type-mobile {
            font-size: 12px;
            color: #666;
            display: block;
            margin-top: 2px;
            line-height: 1.2;
        }

        .ticket-time-mobile {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 4px 0;
        }

        .departure-time-mobile,
        .arrival-time-mobile {
            flex: 0 0 auto;
        }

        .time-mobile {
            font-size: 14px;
            font-weight: 600;
            color: #1f1f1f;
            margin: 0;
        }

        .duration-badge-mobile {
            flex: 1;
            text-align: center;
            padding: 0 8px;
        }

        .duration-badge-mobile span {
            display: inline-block;
            background: #f0f0f0;
            color: #666;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .ticket-footer-mobile {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            padding-top: 8px;
            border-top: 1px solid #f0f0f0;
            gap: 12px;
        }

        .ticket-footer-left {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: 3px;
            flex: 1;
            min-width: 0;
        }

        .ticket-footer-right {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .seats-mobile {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #28a745;
            font-size: 13px;
            font-weight: 500;
        }

        .seats-mobile i {
            font-size: 16px;
        }

        .price-mobile {
            text-align: left;
            display: flex;
            align-items: baseline;
            gap: 6px;
            flex-wrap: wrap;
        }

        .select-seat-btn-mobile .btn {
            padding: 10px 20px;
            border-radius: 5px;
            white-space: nowrap;
            color: white;
            border: none;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            background: var(--main-color);
            font-weight: 600;
        }

        .select-seat-btn-mobile .btn:hover {
            background: #0e9e4d;
        }

        .price-original-mobile {
            font-size: 12px;
            color: #999;
            text-decoration: line-through;
            margin: 0;
            display: inline-block;
            line-height: 1;
        }

        .price-current-mobile {
            font-size: 15px;
            font-weight: 700;
            color: #1f1f1f;
            margin: 0;
            display: inline-block;
            line-height: 1;
        }

        /* Mobile specific adjustments */
        @media (max-width: 991px) {
            .ticket-item {
                padding: 15px;
                padding-bottom: 15px;
                position: relative;
                overflow: visible;
                margin-bottom: 15px;
            }

            .ticket-item-mobile {
                padding: 0;
                width: 100%;
                box-sizing: border-box;
            }

            .ticket-header-mobile {
                margin-bottom: 8px;
            }

            .ticket-time-mobile {
                margin-bottom: 8px;
                padding: 2px 0;
            }

            .bus-name-title {
                font-size: 15px;
                word-wrap: break-word;
                overflow-wrap: break-word;
                line-height: 1.2;
                margin-bottom: 3px;
            }

            .bus-type-mobile {
                font-size: 11px;
                word-wrap: break-word;
                overflow-wrap: break-word;
                line-height: 1.2;
                margin-top: 0;
            }

            .time-mobile {
                font-size: 13px;
                white-space: nowrap;
            }

            .duration-badge-mobile span {
                font-size: 11px;
                padding: 3px 8px;
                white-space: nowrap;
            }

            .price-mobile {
                text-align: left;
                gap: 5px;
            }

            .price-current-mobile {
                font-size: 14px;
                white-space: nowrap;
            }

            .price-original-mobile {
                font-size: 11px;
                white-space: nowrap;
            }

            .seats-mobile {
                font-size: 12px;
                white-space: nowrap;
            }

            .ticket-footer-mobile {
                padding-top: 6px;
                gap: 10px;
            }

            .ticket-footer-left {
                gap: 2px;
            }

            .select-seat-btn-mobile .btn {
                padding: 8px 16px;
                font-size: 13px;
            }
        }

        @media (max-width: 575px) {
            .ticket-item {
                padding: 12px;
                padding-bottom: 12px;
            }

            .ticket-header-mobile {
                margin-bottom: 6px;
            }

            .ticket-time-mobile {
                margin-bottom: 6px;
                padding: 2px 0;
            }

            .bus-name-title {
                font-size: 14px;
                line-height: 1.15;
                margin-bottom: 2px;
            }

            .bus-type-mobile {
                font-size: 10px;
                line-height: 1.15;
            }

            .time-mobile {
                font-size: 12px;
            }

            .price-current-mobile {
                font-size: 13px;
            }

            .seats-mobile {
                font-size: 11px;
            }

            .duration-badge-mobile {
                padding: 0 4px;
            }

            .duration-badge-mobile span {
                font-size: 10px;
                padding: 2px 6px;
            }

            .ticket-footer-mobile {
                padding-top: 5px;
                gap: 8px;
            }

            .select-seat-btn-mobile .btn {
                padding: 7px 14px;
                font-size: 12px;
            }
        }

        /* Pagination styles - Modern Round Design */
        .pagination-wrapper {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .pagination {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            list-style: none;
            padding: 0;
        }

        .pagination .page-item {
            margin: 0;
        }

        .pagination .page-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #e0e0e0;
            background-color: #fff;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 14px;
            line-height: 1;
        }

        .pagination .page-link i {
            font-size: 16px;
        }

        .pagination .page-link:hover:not(.disabled) {
            background-color: var(--main-color);
            border-color: var(--main-color);
            color: #fff;
            transform: scale(1.1);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--main-color);
            border-color: var(--main-color);
            color: #fff;
        }

        .pagination .page-item.disabled .page-link {
            color: #bbb;
            background-color: #f5f5f5;
            border-color: #e0e0e0;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .pagination .page-item.disabled .page-link:hover {
            transform: none;
        }

        .pagination-info {
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .pagination-wrapper {
                padding: 15px;
            }

            .pagination-wrapper .d-flex {
                flex-direction: column;
                gap: 15px;
            }

            .pagination-info {
                text-align: center;
                font-size: 13px;
            }

            .pagination {
                flex-wrap: wrap;
                gap: 6px;
            }

            .pagination .page-link {
                width: 36px;
                height: 36px;
                font-size: 13px;
            }

            .pagination .page-link i {
                font-size: 14px;
            }
        }

        @media (max-width: 575px) {
            .pagination .page-link {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }

            .pagination .page-link i {
                font-size: 13px;
            }

            .pagination {
                gap: 4px;
            }
        }
    </style>
@endpush
