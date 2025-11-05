@extends('admin.layouts.app')

@section('panel')
    <div class="container-fluid px-0">
        <!-- Compact Search Header (Mobile-First Design) -->
        <div class="card mb-2">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 text-dark" style="font-size: 0.9rem; font-weight: 600;">
                            {{ $fromCityData->city_name }} → {{ $toCityData->city_name }}
                        </h6>
                        <small class="text-muted">
                            <i class="las la-calendar me-1"></i>
                            {{ \Carbon\Carbon::parse($dateOfJourney)->format('M j') }}
                            <i class="las la-users ms-2 me-1"></i>
                            {{ $passengers }}p
                        </small>
                    </div>
                    <div>
                        <a href="{{ route('admin.booking.search') }}" class="btn btn-outline-primary btn-sm">
                            <i class="las la-search"></i> @lang('New Search')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Single Line: Filter | Bus Count | Sort -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm" id="filterBtn" aria-label="Open filters"
                title="Filter buses by type, time, and price">
                <i class="las la-filter" aria-hidden="true"></i>
                <span class="d-none d-md-inline">@lang('Filter')</span>
                <span id="filterCount" class="badge badge-primary ml-1" style="display: none;"
                    aria-label="Active filters count">0</span>
            </button>

            @if (!empty($availableBuses))
                <div class="text-center">
                    <small class="text-muted">
                        <span id="busCount">{{ count($availableBuses ?? []) }}</span> @lang('buses found')
                    </small>
                </div>
            @endif

            <button type="button" class="btn btn-outline-secondary btn-sm" id="sortBtn" aria-label="Sort buses"
                title="Sort buses by departure time, price, or duration">
                <i class="las la-sort" aria-hidden="true"></i>
                <span class="d-none d-md-inline">@lang('Sort')</span>
            </button>
        </div>

        <!-- Bottom Sheet Filter Modal -->
        <div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-bottom" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterModalLabel">
                            <i class="las la-filter"></i>
                            @lang('Filters')
                            <span id="filterCount" class="badge badge-primary ml-2" style="display: none;">0</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="filterForm" method="GET" action="{{ route('admin.booking.results') }}">
                            <input type="hidden" name="OriginId" value="{{ request()->OriginId }}">
                            <input type="hidden" name="DestinationId" value="{{ request()->DestinationId }}">
                            <input type="hidden" name="DateOfJourney" value="{{ request()->DateOfJourney }}">
                            <input type="hidden" name="passengers" value="{{ $passengers }}">

                            <!-- Filter Chips (Horizontal) -->
                            <div class="row">
                                <!-- Bus Type Filter -->
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <label class="text-muted mb-2"><i class="las la-bus"></i> @lang('Bus Type')</label>
                                    <div class="filter-group">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input filter-checkbox"
                                                id="filterSeater" name="fleetType[]" value="Seater"
                                                {{ in_array('Seater', request()->fleetType ?? []) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="filterSeater">@lang('Seater')</label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input filter-checkbox"
                                                id="filterSleeper" name="fleetType[]" value="Sleeper"
                                                {{ in_array('Sleeper', request()->fleetType ?? []) ? 'checked' : '' }}>
                                            <label class="custom-control-label"
                                                for="filterSleeper">@lang('Sleeper')</label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input filter-checkbox"
                                                id="filterAC" name="fleetType[]" value="A/c"
                                                {{ in_array('A/c', request()->fleetType ?? []) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="filterAC">@lang('AC')</label>
                                        </div>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input filter-checkbox"
                                                id="filterNonAC" name="fleetType[]" value="Non-A/c"
                                                {{ in_array('Non-A/c', request()->fleetType ?? []) ? 'checked' : '' }}>
                                            <label class="custom-control-label"
                                                for="filterNonAC">@lang('Non-AC')</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Departure Time Filter -->
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <label class="text-muted mb-2"><i class="las la-clock"></i> @lang('Departure Time')</label>
                                    <div class="filter-group">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input filter-checkbox"
                                                id="filterMorning" name="departure_time[]" value="morning"
                                                {{ in_array('morning', request()->departure_time ?? []) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="filterMorning">
                                                <i class="las la-sun text-warning"></i> @lang('Morning')
                                                <small class="text-muted">(6:00 AM - 11:59 AM)</small>
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input filter-checkbox"
                                                id="filterAfternoon" name="departure_time[]" value="afternoon"
                                                {{ in_array('afternoon', request()->departure_time ?? []) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="filterAfternoon">
                                                <i class="las la-cloud-sun text-info"></i> @lang('Afternoon')
                                                <small class="text-muted">(12:00 PM - 5:59 PM)</small>
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input filter-checkbox"
                                                id="filterEvening" name="departure_time[]" value="evening"
                                                {{ in_array('evening', request()->departure_time ?? []) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="filterEvening">
                                                <i class="las la-cloud-moon text-primary"></i> @lang('Evening')
                                                <small class="text-muted">(6:00 PM - 11:59 PM)</small>
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input filter-checkbox"
                                                id="filterNight" name="departure_time[]" value="night"
                                                {{ in_array('night', request()->departure_time ?? []) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="filterNight">
                                                <i class="las la-moon text-dark"></i> @lang('Night')
                                                <small class="text-muted">(12:00 AM - 5:59 AM)</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price Range Filter (Dual-Thumb Slider) -->
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <label class="text-muted mb-2"><i class="las la-rupee-sign"></i>
                                        @lang('Price Range')</label>
                                    <div class="filter-group">
                                        @php
                                            $minPrice = request()->min_price ?? 0;
                                            $dynamicMaxPrice = 5000;
                                            if (
                                                isset($availableBuses) &&
                                                is_array($availableBuses) &&
                                                count($availableBuses) > 0
                                            ) {
                                                $prices = [];
                                                foreach ($availableBuses as $bus) {
                                                    if (isset($bus['BusPrice']['PublishedPrice'])) {
                                                        $prices[] = $bus['BusPrice']['PublishedPrice'];
                                                    }
                                                }
                                                if (!empty($prices)) {
                                                    $dynamicMaxPrice = max($prices);
                                                    $dynamicMaxPrice = ceil($dynamicMaxPrice / 100) * 100;
                                                }
                                            }
                                            $maxPrice = request()->max_price ?? $dynamicMaxPrice;
                                        @endphp
                                        <div class="dual-range-container">
                                            <div class="range-labels d-flex justify-content-between mb-2">
                                                <span class="small text-muted">₹<span
                                                        id="minPriceLabel">{{ $minPrice }}</span></span>
                                                <span class="small text-muted">₹<span
                                                        id="maxPriceLabel">{{ $maxPrice }}</span></span>
                                            </div>
                                            <div class="range-slider-container position-relative">
                                                <input type="range" class="custom-range range-slider"
                                                    id="minPriceRange" min="0" max="{{ $dynamicMaxPrice }}"
                                                    value="{{ $minPrice }}" step="100">
                                                <input type="range" class="custom-range range-slider"
                                                    id="maxPriceRange" min="0" max="{{ $dynamicMaxPrice }}"
                                                    value="{{ $maxPrice }}" step="100">
                                                <input type="hidden" name="min_price" id="minPriceInput"
                                                    value="{{ $minPrice }}">
                                                <input type="hidden" name="max_price" id="maxPriceInput"
                                                    value="{{ $maxPrice }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sort By -->
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <label class="text-muted mb-2"><i class="las la-sort"></i> @lang('Sort By')</label>
                                    <select class="form-control" id="sortBy" name="sortBy">
                                        <option value="">@lang('Best Match')</option>
                                        <option value="departure"
                                            {{ request()->sortBy == 'departure' ? 'selected' : '' }}>
                                            @lang('Departure: Earliest First')
                                        </option>
                                        <option value="price-low"
                                            {{ request()->sortBy == 'price-low' ? 'selected' : '' }}>
                                            @lang('Price: Low to High')
                                        </option>
                                        <option value="price-high"
                                            {{ request()->sortBy == 'price-high' ? 'selected' : '' }}>
                                            @lang('Price: High to Low')
                                        </option>
                                        <option value="duration" {{ request()->sortBy == 'duration' ? 'selected' : '' }}>
                                            @lang('Duration: Shortest First')
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Apply Button (Mobile) -->
                            <div class="d-sm-none">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="las la-check"></i>
                                    @lang('Apply Filters')
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('Close')</button>
                        <button type="button" id="resetFilters" class="btn btn-outline-secondary">
                            <i class="las la-redo"></i>
                            @lang('Reset')
                        </button>
                    </div>
                </div>
            </div>
        </div>


        @if (empty($availableBuses))
            <!-- No Results -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="las la-bus text-muted" style="font-size: 4rem;"></i>
                    <h4 class="text-muted mt-3">@lang('No buses found')</h4>
                    <p class="text-muted mb-4">
                        @lang('Sorry, no buses are available for the selected route and date.')
                        @lang('Try searching for a different date or route.')
                    </p>
                    <a href="{{ route('admin.booking.search') }}" class="btn btn-primary">
                        <i class="las la-search"></i>
                        @lang('Search Again')
                    </a>
                </div>
            </div>
        @else
            <!-- Available Buses -->
            <div class="row" id="busResults">
                @foreach ($availableBuses as $bus)
                    <div class="col-12 mb-3">
                        <div class="card bus-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <!-- Bus Icon & Travel Name in Single Line -->
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">

                                            <div class="bus-info">
                                                <h6 class="mb-0 text-dark">
                                                    {{ $bus['TravelName'] ?? ($bus['ServiceName'] ?? 'Bus Service') }}</h6>
                                                <small class="text-muted">{{ $bus['BusType'] ?? 'Bus' }} -
                                                    {{ $bus['ServiceName'] ?? 'Bus Operator' }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Departure & Duration -->
                                    <div class="col-md-3">
                                        <div class="schedule-info">
                                            @php
                                                $departure = \Carbon\Carbon::parse($bus['DepartureTime']);
                                                $arrival = isset($bus['ArrivalTime'])
                                                    ? \Carbon\Carbon::parse($bus['ArrivalTime'])
                                                    : $departure->copy()->addHours(4);
                                                $duration = $departure->diffInHours($arrival);
                                            @endphp
                                            <div class="d-flex justify-content-between align-items-center my-2">
                                                <strong class="text-dark">{{ $departure->format('h:i A') }}</strong>
                                                <span class="mx-2 text-muted">o—{{ $duration }}h—o</span>
                                                <strong class="text-dark">{{ $arrival->format('h:i A') }}</strong>
                                            </div>
                                            <small class="text-muted">{{ $bus['AvailableSeats'] ?? 'N/A' }} seats</small>
                                        </div>
                                    </div>

                                    <!-- Features -->
                                    <div class="col-md-3">
                                        <div class="bus-features">
                                            @if (isset($bus['LiveTrackingAvailable']) && $bus['LiveTrackingAvailable'])
                                                <span class="badge badge-light text-dark border">@lang('Live Tracking')</span>
                                            @endif
                                            @if (isset($bus['MTicketEnabled']) && $bus['MTicketEnabled'])
                                                <span class="badge badge-light text-dark border">@lang('M-Ticket')</span>
                                            @endif
                                            @if (isset($bus['PartialCancellationAllowed']) && $bus['PartialCancellationAllowed'])
                                                <span class="badge badge-light text-dark border">@lang('Partial Cancel')</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Price & Select Button -->
                            <div class="d-flex justify-content-between align-items-center p-2">
                                <div class="px-2">
                                    <small class="text-muted"
                                        style="font-size: 0.65rem; color: #6c757d;">@lang('starting from')</small>
                                    <h5 class="text-primary mb-1">
                                        ₹{{ number_format($bus['BusPrice']['PublishedPrice'] ?? ($bus['BusPrice']['BasePrice'] ?? 0), 0) }}
                                    </h5>
                                    <br>
                                </div>
                                <div>
                                    <button class="btn btn-primary btn-sm select-bus-btn"
                                        data-bus-id="{{ $bus['ResultIndex'] }}"
                                        data-bus-name="{{ $bus['TravelName'] ?? ($bus['ServiceName'] ?? 'Bus Service') }}"
                                        data-operator="{{ $bus['ServiceName'] ?? 'Bus Operator' }}"
                                        data-price="{{ $bus['BusPrice']['PublishedPrice'] ?? ($bus['BusPrice']['BasePrice'] ?? 0) }}">
                                        @lang('Select Bus')
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Controls -->
            @if (isset($pagination) && $pagination['total_results'] > $pagination['per_page'])
                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Bus results pagination">
                        <ul class="pagination">
                            @if ($pagination['current_page'] > 1)
                                <li class="page-item">
                                    <a class="page-link"
                                        href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}">
                                        <i class="las la-chevron-left"></i>
                                        @lang('Previous')
                                    </a>
                                </li>
                            @endif

                            <li class="page-item active">
                                <span class="page-link">
                                    @lang('Page') {{ $pagination['current_page'] }} @lang('of')
                                    {{ ceil($pagination['total_results'] / $pagination['per_page']) }}
                                </span>
                            </li>

                            @if ($pagination['has_more_pages'])
                                <li class="page-item">
                                    <a class="page-link"
                                        href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}">
                                        @lang('Next')
                                        <i class="las la-chevron-right"></i>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            @endif
        @endif
    </div>

    <!-- Bus Selection Modal -->
    <div class="modal fade" id="busSelectionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">@lang('Select Schedule')</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="modalContent">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        /* Bottom Sheet Modal Styling */
        .modal-dialog-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            margin: 0;
            max-width: 100%;
            transform: translateY(100%);
            transition: transform 0.3s ease-out;
        }

        .modal-dialog-bottom.show {
            transform: translateY(0);
        }

        .modal-dialog-bottom .modal-content {
            border-radius: 1rem 1rem 0 0;
            border: none;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-dialog-bottom .modal-header {
            border-bottom: 1px solid #e9ecef;
            border-radius: 1rem 1rem 0 0;
        }

        /* Compact header styling */
        .card-body.py-2 {
            padding: 0.5rem 1rem !important;
        }

        /* Filter button styling */
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Dual-Thumb Range Slider Styling */
        .dual-range-container {
            position: relative;
        }

        .range-slider-container {
            height: 20px;
        }

        .range-slider {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 20px;
            background: transparent;
            pointer-events: none;
        }

        .range-slider::-webkit-slider-thumb {
            pointer-events: all;
            position: relative;
            z-index: 1;
        }

        .range-slider::-moz-range-thumb {
            pointer-events: all;
            position: relative;
            z-index: 1;
        }

        #minPriceRange {
            z-index: 2;
        }

        #maxPriceRange {
            z-index: 1;
        }

        /* Monochromatic Design Enhancements */
        .bus-card {
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.2s ease;
        }

        .bus-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .badge-light {
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
        }

        .text-primary {
            color: #007bff !important;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        /* Mobile-first responsive adjustments */
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .card {
                margin-bottom: 0.5rem;
            }

            .bus-info h6 {
                font-size: 0.9rem;
            }

            .schedule-info {
                font-size: 0.85rem;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin search results page loaded');

            // Filter functionality
            const filterForm = document.getElementById('filterForm');
            const resetButton = document.getElementById('resetFilters');
            const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
            const sortBySelect = document.getElementById('sortBy');
            const minPriceRange = document.getElementById('minPriceRange');
            const maxPriceRange = document.getElementById('maxPriceRange');
            const minPriceLabel = document.getElementById('minPriceLabel');
            const maxPriceLabel = document.getElementById('maxPriceLabel');
            const minPriceInput = document.getElementById('minPriceInput');
            const maxPriceInput = document.getElementById('maxPriceInput');
            const filterBtn = document.getElementById('filterBtn');
            const sortBtn = document.getElementById('sortBtn');

            // Auto-submit on desktop, manual on mobile
            const isDesktop = window.innerWidth >= 576;

            // Filter button click handler
            if (filterBtn) {
                filterBtn.addEventListener('click', function() {
                    $('#filterModal').modal('show');
                });
            }

            // Sort button click handler
            if (sortBtn) {
                sortBtn.addEventListener('click', function() {
                    $('#filterModal').modal('show');
                    setTimeout(function() {
                        if (sortBySelect) sortBySelect.focus();
                    }, 500);
                });
            }

            // Update filter count badge
            function updateFilterCount() {
                const checkedFilters = document.querySelectorAll('.filter-checkbox:checked').length;
                const badge = document.getElementById('filterCount');
                if (checkedFilters > 0) {
                    badge.textContent = checkedFilters;
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            }

            // Dual-thumb price range sliders
            function updatePriceRange() {
                const minValue = parseInt(minPriceRange.value);
                const maxValue = parseInt(maxPriceRange.value);

                // Ensure min doesn't exceed max
                if (minValue >= maxValue) {
                    minPriceRange.value = maxValue - 100;
                    minPriceLabel.textContent = maxValue - 100;
                    minPriceInput.value = maxValue - 100;
                }

                // Ensure max doesn't go below min
                if (maxValue <= minValue) {
                    maxPriceRange.value = minValue + 100;
                    maxPriceLabel.textContent = minValue + 100;
                    maxPriceInput.value = minValue + 100;
                }

                minPriceLabel.textContent = minPriceRange.value;
                maxPriceLabel.textContent = maxPriceRange.value;
                minPriceInput.value = minPriceRange.value;
                maxPriceInput.value = maxPriceRange.value;

                if (isDesktop) {
                    filterForm.submit();
                }
            }

            if (minPriceRange) {
                minPriceRange.addEventListener('input', updatePriceRange);
            }

            if (maxPriceRange) {
                maxPriceRange.addEventListener('input', updatePriceRange);
            }

            // Filter checkboxes - auto submit on desktop
            filterCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    updateFilterCount();

                    if (isDesktop) {
                        filterForm.submit();
                    }
                });
            });

            // Sort dropdown - auto submit on desktop
            if (sortBySelect) {
                sortBySelect.addEventListener('change', function() {
                    if (isDesktop) {
                        filterForm.submit();
                    }
                });
            }

            // Reset filters
            if (resetButton) {
                resetButton.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Uncheck all checkboxes
                    filterCheckboxes.forEach(function(checkbox) {
                        checkbox.checked = false;
                    });

                    // Reset price range
                    if (minPriceRange && maxPriceRange) {
                        const maxValue = maxPriceRange.getAttribute('max');
                        minPriceRange.value = 0;
                        maxPriceRange.value = maxValue;
                        minPriceLabel.textContent = 0;
                        maxPriceLabel.textContent = maxValue;
                        minPriceInput.value = 0;
                        maxPriceInput.value = maxValue;
                    }

                    // Reset sort
                    if (sortBySelect) {
                        sortBySelect.value = '';
                    }

                    updateFilterCount();
                    filterForm.submit();
                });
            }

            // Initial filter count
            updateFilterCount();

            // Add click handlers to all select bus buttons
            document.querySelectorAll('.select-bus-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    const busId = this.getAttribute('data-bus-id');
                    const busName = this.getAttribute('data-bus-name');
                    const operator = this.getAttribute('data-operator');
                    const price = this.getAttribute('data-price');

                    // Generate slug from bus name
                    const slug = busName.toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');

                    // Redirect to admin seat selection page with proper slug
                    const url = `/bus_booking/admin/booking/seats/${busId}/${slug}`;
                    window.location.href = url;
                });
            });
        });
    </script>
@endpush
