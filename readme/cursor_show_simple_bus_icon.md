# Show simple bus icon
_Exported on 07/11/2025 at 13:05:05 GMT+5:30 from Cursor (2.0.60)_

---

**User**

just show bus icon no need of complex logic

---

**Cursor**

I'll help you simplify the bus icon display. Let me check what's currently in the file around your cursor position.

I can see the complex logic for displaying different icons based on bus type (sleeper, AC, seater). Let me simplify it to just show a bus icon:

```blade
@extends('agent.layouts.app')

@section('panel')
    <div class="container-fluid">
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
                        <a href="{{ route('agent.search') }}" class="btn btn-outline-primary btn-sm">
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
                        <form id="filterForm" method="GET" action="{{ route('agent.search.results') }}">
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
                    <a href="{{ route('agent.search') }}" class="btn btn-primary">
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
                                            <div class="bus-icon me-3">
                                                <i class="las la-bus text-primary" style="font-size: 1.2rem;" title="Bus"></i>
                                            </div>
                                            <div class="bus-info">
                                                <h6 class="mb-1 text-dark">
                                                    {{ $bus['TravelName'] ?? ($bus['ServiceName'] ?? 'Bus Service') }}</h6>
                                                <small
                                                    class="text-muted">{{ $bus['ServiceName'] ?? 'Bus Operator' }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Departure & Duration -->
                                    <div class="col-md-3">
                                        <div class="schedule-info">
                                            <div class="departure-time mb-1">
                                                <strong
                                                    class="text-dark">{{ \Carbon\Carbon::parse($bus['DepartureTime'])->format('h:i A') }}</strong>
                                            </div>
                                            @php
                                                $departure = \Carbon\Carbon::parse($bus['DepartureTime']);
                                                $arrival = isset($bus['ArrivalTime'])
                                                    ? \Carbon\Carbon::parse($bus['ArrivalTime'])
                                                    : $departure->copy()->addHours(4);
                                                $duration = $departure->diffInHours($arrival);
                                            @endphp
                                            <small class="text-muted">Duration: {{ $duration }}h</small>
                                            <br>
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

                                    <!-- Price & Select Button -->
                                    <div class="col-md-2 text-right">
                                        <div class="price-section">
                                            <h5 class="text-primary mb-1">
                                                ₹{{ number_format($bus['BusPrice']['PublishedPrice'] ?? ($bus['BusPrice']['BasePrice'] ?? 0), 0) }}
                                            </h5>
                                            <small class="text-muted"
                                                style="font-size: 0.75rem; color: #6c757d;">@lang('starting from')</small>
                                            <br>
                                            <button class="btn btn-primary btn-sm mt-2 select-bus-btn"
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
            console.log('Agent search results page loaded');

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

                    // Redirect to agent seat selection page with proper slug
                    const url = `/bus_booking/agent/booking/seats/${busId}/${slug}`;
                    window.location.href = url;
                });
            });
        });
    </script>
@endpush

```

Done! I've simplified the bus icon display. Instead of the complex logic that checked for sleeper, AC, or seater bus types and showed different icons, it now simply shows a bus icon (`las la-bus`) for all buses. Much cleaner and simpler! 🚌

---

**User**

Redesign to adjust single Line like:
4:13AM      o-1h-o        5:13AM

---

**Cursor**

I'll redesign the time display to show it in a single line with the format you specified. Let me read the current code first to understand the structure better.

Now I'll redesign it to show the times in a single line with the visual format you requested:

```blade
@extends('agent.layouts.app')

@section('panel')
    <div class="container-fluid">
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
                        <a href="{{ route('agent.search') }}" class="btn btn-outline-primary btn-sm">
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
                        <form id="filterForm" method="GET" action="{{ route('agent.search.results') }}">
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
                    <a href="{{ route('agent.search') }}" class="btn btn-primary">
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
                                            <div class="d-flex align-items-center mb-2">
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

                                    <!-- Price & Select Button -->
                                    <div class="col-md-2 text-right">
                                        <div class="price-section">
                                            <h5 class="text-primary mb-1">
                                                ₹{{ number_format($bus['BusPrice']['PublishedPrice'] ?? ($bus['BusPrice']['BasePrice'] ?? 0), 0) }}
                                            </h5>
                                            <small class="text-muted"
                                                style="font-size: 0.75rem; color: #6c757d;">@lang('starting from')</small>
                                            <br>
                                            <button class="btn btn-primary btn-sm mt-2 select-bus-btn"
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
            console.log('Agent search results page loaded');

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

                    // Redirect to agent seat selection page with proper slug
                    const url = `/bus_booking/agent/booking/seats/${busId}/${slug}`;
                    window.location.href = url;
                });
            });
        });
    </script>
@endpush

```

Perfect! I've redesigned the time display to show it in a single line format as you requested:

**4:13 AM  o—1h—o  5:13 AM**

The new layout:
- Shows departure time, duration with visual connectors (o—1h—o), and arrival time all in one line
- Uses flexbox to keep everything aligned
- Available seats are displayed below on a separate line
- Clean and compact design that's easy to read at a glance

---

**User**

Show Both buttons in single line each half of width and some horizontal margin padings

---

**Cursor**

I'll help you adjust the buttons to be in a single line with each taking half the width and add some horizontal padding. Let me read the current structure first.

Now I'll redesign the buttons to be in a single line with proper spacing:

```blade
@extends('agent.layouts.app')

@section('panel')
    <div class="container-fluid px-0">
        <!-- Booking Header -->
        <div class="card mb-2">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 text-dark" style="font-size: 0.9rem; font-weight: 600;">
                            {{-- <i class="las la-route text-primary"></i> --}}
                            {{ $originCity->city_name }} → {{ $destinationCity->city_name }}
                        </h6>
                        <small class="text-muted">
                            <i class="las la-calendar me-1"></i>
                            {{ \Carbon\Carbon::parse(session('date_of_journey'))->format('M j') }}
                        </small>
                    </div>
                    <div>
                        <a href="{{ route('agent.search') }}" class="btn btn-sm btn-outline-primary">
                            <i class="las la-search"></i>
                            @lang('New Search')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Layout: Boarding/Dropping Points First -->
        <div class="d-block d-lg-none">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="las la-map-marker"></i>
                        @lang('Boarding & Dropping Points')
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <label class="form-label">@lang('Boarding Point') *</label>
                            <select class="form-control" id="boarding_point_select_mobile" required>
                                <option value="">@lang('Select Boarding Point')</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">@lang('Dropping Point') *</label>
                            <select class="form-control" id="dropping_point_select_mobile" required>
                                <option value="">@lang('Select Dropping Point')</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Customer Details -->
            <div class="col-lg-4 col-md-4 order-2 order-lg-1">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="las la-user"></i>
                            @lang('Customer Details')
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('agent.booking.block') }}" method="POST" id="agentBookingForm">
                            @csrf

                            <!-- Journey Details (Read-only) -->
                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Journey Date')</label>
                                <input type="text" class="form-control"
                                    value="{{ \Carbon\Carbon::parse(session('date_of_journey'))->format('M d, Y') }}"
                                    disabled>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Route')</label>
                                <input type="text" class="form-control"
                                    value="{{ $originCity->city_name }} → {{ $destinationCity->city_name }}" disabled>
                            </div>

                            <!-- Agent Contact Info (Pre-filled) -->
                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Phone Number') *</label>
                                <div class="input-group">
                                    <span class="input-group-text">+91</span>
                                    <input type="tel" class="form-control" name="passenger_phone"
                                        value="{{ auth('agent')->user()->mobile }}" required>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Email') *</label>
                                <input type="email" class="form-control" name="passenger_email"
                                    value="{{ auth('agent')->user()->email }}" required>
                            </div>

                            <!-- Passenger Details (Dynamic based on selected seats) -->
                            <div id="passengerDetails">
                                <!-- Passenger details will be dynamically added here -->
                            </div>

                            <!-- Commission Input -->
                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Commission Amount') (₹)</label>
                                <input type="number" class="form-control" id="commissionInput" min="0"
                                    step="0.01" value="0">
                                <small class="text-muted">Add commission amount to total</small>
                            </div>

                            <!-- Booking Summary -->
                            <div class="booking-summary mb-3" id="bookingSummary" style="display: none;">
                                <h6>@lang('Booking Summary')</h6>
                                <div class="d-flex justify-content-between">
                                    <span>@lang('Base Fare'):</span>
                                    <span id="baseFare">₹0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>@lang('Commission'):</span>
                                    <span id="commissionDisplay">₹0.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between font-weight-bold">
                                    <span>@lang('Total'):</span>
                                    <span id="totalAmount">₹0.00</span>
                                </div>
                            </div>

                            <!-- Hidden Fields -->
                            <input type="hidden" name="boarding_point_index" id="boarding_point_index">
                            <input type="hidden" name="dropping_point_index" id="dropping_point_index">
                            <input type="hidden" name="seats" id="selected_seats">
                            <input type="hidden" name="price" id="total_price">
                            <input type="hidden" name="agent_id" value="{{ auth('agent')->id() }}">
                            <input type="hidden" name="booking_source" value="agent">

                            <!-- Desktop Button -->
                            <div class="d-none d-lg-block">
                                <button type="submit" class="btn btn-primary w-100" id="bookButton" disabled>
                                    <i class="las la-credit-card"></i>
                                    @lang('Continue to Payment')
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column - Seat Selection -->
            <div class="col-lg-8 col-md-8 order-1 order-lg-2">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="las la-chair"></i>
                            @lang('Select Seats')
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Seat Layout -->
                        <div class="bus">
                            @if ($seatHtml || ($parsedLayout && isset($parsedLayout['seat'])))
                                @include('templates.basic.partials.seatlayout', [
                                    'seatHtml' => $seatHtml,
                                    'parsedLayout' => $parsedLayout,
                                    'isOperatorBus' => $isOperatorBus,
                                ])
                            @else
                                <div class="alert alert-warning">
                                    <i class="las la-exclamation-triangle"></i>
                                    @lang('Seat layout is loading... Please wait or try refreshing the page.')
                                    <br>
                                    <small>Debug: seatHtml={{ $seatHtml ? 'Present' : 'Empty' }},
                                        parsedLayout={{ $parsedLayout ? 'Present' : 'Empty' }}</small>
                                </div>
                            @endif
                        </div>

                        <!-- Seat Legend -->
                        <div class="seat-legend mt-3">
                            <div class="d-flex flex-wrap gap-3">
                                <div class="d-flex align-items-center">
                                    <div class="seat-legend-item available"></div>
                                    <small>@lang('Available')</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="seat-legend-item selected"></div>
                                    <small>@lang('Selected')</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="seat-legend-item booked"></div>
                                    <small>@lang('Booked')</small>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Boarding & Dropping Points -->
                        <div class="d-none d-lg-block">
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <label class="form-label">@lang('Boarding Point') *</label>
                                    <select class="form-control" id="boarding_point_select" required>
                                        <option value="">@lang('Select Boarding Point')</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">@lang('Dropping Point') *</label>
                                    <select class="form-control" id="dropping_point_select" required>
                                        <option value="">@lang('Select Dropping Point')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Sticky Bottom Bar -->
        <div class="d-block d-lg-none">
            <div class="mobile-sticky-bar">
                <div class="container-fluid px-3">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary flex-fill" id="resetButton">
                            <i class="las la-undo"></i>
                            @lang('Reset')
                        </button>
                        <button type="submit" form="agentBookingForm" class="btn btn-primary flex-fill"
                            id="bookButtonMobile" disabled>
                            <i class="las la-credit-card"></i>
                            @lang('Proceed to Pay')
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Check if selectedSeats is already declared
        if (typeof selectedSeats === 'undefined') {
            var selectedSeats = [];
        }
        var baseFare = 0;
        var commissionAmount = 0;

        document.addEventListener('DOMContentLoaded', function() {
            // Load boarding/dropping points
            loadBoardingPoints();

            // Handle commission input change
            document.getElementById('commissionInput').addEventListener('input', updateBookingSummary);

            // Handle reset button
            document.getElementById('resetButton').addEventListener('click', function() {
                resetForm();
            });

            // Handle form submission
            document.getElementById('agentBookingForm').addEventListener('submit', function(e) {
                e.preventDefault();

                if (selectedSeats.length === 0) {
                    alert('@lang('Please select at least one seat')');
                    return;
                }

                // Validate passenger details
                if (!validatePassengerDetails()) {
                    return;
                }

                // Set hidden field values - handle both desktop and mobile
                const boardingSelect = document.getElementById('boarding_point_select');
                const droppingSelect = document.getElementById('dropping_point_select');
                const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
                const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

                if (boardingSelect && droppingSelect) {
                    document.getElementById('boarding_point_index').value = boardingSelect.value;
                    document.getElementById('dropping_point_index').value = droppingSelect.value;
                } else if (boardingSelectMobile && droppingSelectMobile) {
                    document.getElementById('boarding_point_index').value = boardingSelectMobile.value;
                    document.getElementById('dropping_point_index').value = droppingSelectMobile.value;
                }
                document.getElementById('selected_seats').value = selectedSeats.join(',');
                document.getElementById('total_price').value = baseFare + commissionAmount;

                // Submit form
                this.submit();
            });
        });

        // Override the AddRemoveSeat function for agent booking
        window.AddRemoveSeat = function(element, seatId, price) {
            console.log('AddRemoveSeat called:', {
                element,
                seatId,
                price
            });

            const seatNumber = seatId;
            const seatPrice = parseFloat(price);

            element.classList.toggle('selected');
            const alreadySelected = selectedSeats.includes(seatNumber);

            if (!alreadySelected) {
                selectedSeats.push(seatNumber);
                baseFare += seatPrice;
                console.log('Seat added:', seatNumber, 'Total seats:', selectedSeats.length);
            } else {
                selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
                baseFare -= seatPrice;
                console.log('Seat removed:', seatNumber, 'Total seats:', selectedSeats.length);
            }

            updatePassengerDetails();
            updateBookingSummary();
            updateBookButton();
        }

        function updatePassengerDetails() {
            console.log('updatePassengerDetails called with seats:', selectedSeats);
            const container = document.getElementById('passengerDetails');
            container.innerHTML = '';

            if (selectedSeats.length === 0) {
                container.innerHTML =
                    '<div class="text-muted text-center py-3"><i class="las la-info-circle"></i> Select seats to add passenger details</div>';
                return;
            }

            selectedSeats.forEach((seatId, index) => {
                const passengerDiv = document.createElement('div');
                passengerDiv.className = 'passenger-card mb-2';

                // Get language strings
                const fullNamePlaceholder = '{{ __('Full Name') }}';
                const agePlaceholder = '{{ __('Age') }}';
                const genderText = '{{ __('Gender') }}';
                const maleText = '{{ __('Male') }}';
                const femaleText = '{{ __('Female') }}';
                const otherText = '{{ __('Other') }}';

                passengerDiv.innerHTML = `
                    <div class="passenger-header">
                        <span class="passenger-number">P${index + 1}</span>
                        <span class="seat-badge">Seat ${seatId}</span>
                    </div>
                    <div class="passenger-fields">
                        <div class="field-group">
                            <input type="text" class="form-control passenger-name" name="passenger_names[]" required 
                                placeholder="${fullNamePlaceholder}" data-seat="${seatId}">
                        </div>
                        <div class="field-group">
                            <input type="number" class="form-control passenger-age" name="passenger_ages[]" required 
                                min="1" max="120" placeholder="${agePlaceholder}" data-seat="${seatId}">
                        </div>
                        <div class="field-group">
                            <select class="form-control passenger-gender" name="passenger_genders[]" required data-seat="${seatId}">
                                <option value="">${genderText}</option>
                                <option value="1">${maleText}</option>
                                <option value="2">${femaleText}</option>
                                <option value="3">${otherText}</option>
                            </select>
                        </div>
                    </div>
                `;
                container.appendChild(passengerDiv);
            });
        }

        function updateBookingSummary() {
            commissionAmount = parseFloat(document.getElementById('commissionInput').value) || 0;
            const total = baseFare + commissionAmount;

            document.getElementById('baseFare').textContent = '₹' + baseFare.toFixed(2);
            document.getElementById('commissionDisplay').textContent = '₹' + commissionAmount.toFixed(2);
            document.getElementById('totalAmount').textContent = '₹' + total.toFixed(2);

            if (selectedSeats.length > 0) {
                document.getElementById('bookingSummary').style.display = 'block';
            } else {
                document.getElementById('bookingSummary').style.display = 'none';
            }
        }

        function updateBookButton() {
            const bookButton = document.getElementById('bookButton');
            const bookButtonMobile = document.getElementById('bookButtonMobile');
            const hasSeats = selectedSeats.length > 0;

            // Check desktop boarding points
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');

            // Check mobile boarding points
            const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
            const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

            let hasBoardingPoint = false;
            let hasDroppingPoint = false;

            if (boardingSelect && droppingSelect) {
                hasBoardingPoint = boardingSelect.value;
                hasDroppingPoint = droppingSelect.value;
            } else if (boardingSelectMobile && droppingSelectMobile) {
                hasBoardingPoint = boardingSelectMobile.value;
                hasDroppingPoint = droppingSelectMobile.value;
            }

            const isFormValid = hasSeats && hasBoardingPoint && hasDroppingPoint;

            if (bookButton) bookButton.disabled = !isFormValid;
            if (bookButtonMobile) bookButtonMobile.disabled = !isFormValid;
        }

        function loadBoardingPoints() {
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');
            const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
            const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

            // Show loading state
            if (boardingSelect) boardingSelect.innerHTML = '<option value="">@lang('Loading...')</option>';
            if (droppingSelect) droppingSelect.innerHTML = '<option value="">@lang('Loading...')</option>';
            if (boardingSelectMobile) boardingSelectMobile.innerHTML = '<option value="">@lang('Loading...')</option>';
            if (droppingSelectMobile) droppingSelectMobile.innerHTML = '<option value="">@lang('Loading...')</option>';

            // Load boarding points from API
            fetch('{{ route('agent.booking.boarding-points') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateBoardingPoints(data.data.BoardingPointsDetails || []);
                        populateDroppingPoints(data.data.DroppingPointsDetails || []);
                    } else {
                        console.error('Error loading boarding points:', data.message);
                        showError('Failed to load boarding points');
                    }
                })
                .catch(error => {
                    console.error('Error loading boarding points:', error);
                    showError('Failed to load boarding points');
                });

            // Add event listeners for validation
            if (boardingSelect) boardingSelect.addEventListener('change', updateBookButton);
            if (droppingSelect) droppingSelect.addEventListener('change', updateBookButton);
            if (boardingSelectMobile) boardingSelectMobile.addEventListener('change', updateBookButton);
            if (droppingSelectMobile) droppingSelectMobile.addEventListener('change', updateBookButton);
        }

        function populateBoardingPoints(points) {
            const select = document.getElementById('boarding_point_select');
            const selectMobile = document.getElementById('boarding_point_select_mobile');

            if (select) {
                select.innerHTML = '<option value="">@lang('Select Boarding Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;
                    option.textContent = point.CityPointName;
                    select.appendChild(option);
                });
            }

            if (selectMobile) {
                selectMobile.innerHTML = '<option value="">@lang('Select Boarding Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;
                    option.textContent = point.CityPointName;
                    selectMobile.appendChild(option);
                });
            }
        }

        function populateDroppingPoints(points) {
            const select = document.getElementById('dropping_point_select');
            const selectMobile = document.getElementById('dropping_point_select_mobile');

            if (select) {
                select.innerHTML = '<option value="">@lang('Select Dropping Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;
                    option.textContent = point.CityPointName;
                    select.appendChild(option);
                });
            }

            if (selectMobile) {
                selectMobile.innerHTML = '<option value="">@lang('Select Dropping Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;
                    option.textContent = point.CityPointName;
                    selectMobile.appendChild(option);
                });
            }
        }

        function showError(message) {
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');

            boardingSelect.innerHTML = `<option value="">${message}</option>`;
            droppingSelect.innerHTML = `<option value="">${message}</option>`;
        }

        function validatePassengerDetails() {
            const passengerNames = document.querySelectorAll('.passenger-name');
            const passengerAges = document.querySelectorAll('.passenger-age');
            const passengerGenders = document.querySelectorAll('.passenger-gender');

            let isValid = true;

            // Validate names
            passengerNames.forEach((input, index) => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            // Validate ages
            passengerAges.forEach((input, index) => {
                if (!input.value || input.value < 1 || input.value > 120) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            // Validate genders
            passengerGenders.forEach((input, index) => {
                if (!input.value) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                alert('@lang('Please fill in all passenger details correctly')');
            }

            return isValid;
        }

        function resetForm() {
            // Clear selected seats
            selectedSeats = [];
            baseFare = 0;
            commissionAmount = 0;

            // Remove selected class from all seats
            document.querySelectorAll('.seat.selected').forEach(seat => {
                seat.classList.remove('selected');
            });

            // Clear passenger details
            document.getElementById('passengerDetails').innerHTML =
                '<div class="text-muted text-center py-3"><i class="las la-info-circle"></i> Select seats to add passenger details</div>';

            // Reset commission input
            document.getElementById('commissionInput').value = 0;

            // Reset boarding/dropping points
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');
            const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
            const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

            if (boardingSelect) boardingSelect.value = '';
            if (droppingSelect) droppingSelect.value = '';
            if (boardingSelectMobile) boardingSelectMobile.value = '';
            if (droppingSelectMobile) droppingSelectMobile.value = '';

            // Update UI
            updateBookingSummary();
            updateBookButton();

            // Scroll to top
            window.scrollTo(0, 0);
        }
    </script>
@endpush

@push('style')
    <style>
        /* Seat Legend Styles */
        .seat-legend-item {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 8px;
        }

        .seat-legend-item.available {
            background-color: #fff;
            border: 1px solid #ccc;
        }

        .seat-legend-item.selected {
            background-color: #c8e6c9;
            border: 1px solid #81c784;
        }

        .seat-legend-item.booked {
            background-color: #e0e0e0;
            border: 1px solid #bdbdbd;
        }

        /* Passenger Detail Styles */
        .passenger-detail {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6 !important;
        }

        .passenger-detail h6 {
            color: #495057;
            font-weight: 600;
        }

        /* Booking Summary Styles */
        .booking-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .booking-summary h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 10px;
        }

        /* Form Styles */
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-primary:disabled {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        /* Input Group Styles */
        .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #495057;
        }

        /* Compact Passenger Cards */
        .passenger-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }

        .passenger-card:hover {
            border-color: #007bff;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.1);
        }

        .passenger-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e9ecef;
        }

        .passenger-number {
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .seat-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .passenger-fields {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 8px;
            align-items: end;
        }

        .field-group {
            display: flex;
            flex-direction: column;
        }

        .field-group .form-control {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 0.875rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .field-group .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .field-group .form-control::placeholder {
            color: #6c757d;
            font-size: 0.8rem;
        }

        /* Mobile Sticky Bar */
        .mobile-sticky-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #dee2e6;
            padding: 15px;
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Add bottom padding to body on mobile to prevent content from being hidden behind sticky bar */
        @media (max-width: 991px) {
            body {
                padding-bottom: 80px;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .passenger-fields {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .passenger-card {
                padding: 10px;
            }

            /* Ensure proper spacing for mobile layout */
            .order-1 {
                order: 1;
            }

            .order-2 {
                order: 2;
            }
        }

        /* Desktop Layout */
        @media (min-width: 992px) {
            .order-lg-1 {
                order: 1;
            }

            .order-lg-2 {
                order: 2;
            }
        }
    </style>
@endpush

```

Perfect! I've redesigned the buttons to show them in a single line with better spacing:

**Changes made:**
- Replaced the Bootstrap grid (`row`/`col-sm-6`) with flexbox (`d-flex gap-2`)
- Added `px-3` to container for horizontal padding on both sides
- Used `flex-fill` on each button so they equally share the width (50% each)
- Added `gap-2` to create spacing between the two buttons
- Removed unnecessary wrapper divs for cleaner code

Now both buttons are in a single line, each taking half the width with nice horizontal margins and gap between them! 🎯

---

**User**

No horizontal margin between buttons

---

**Cursor**

I'll remove the gap between the buttons:

```blade
@extends('agent.layouts.app')

@section('panel')
    <div class="container-fluid px-0">
        <!-- Booking Header -->
        <div class="card mb-2">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 text-dark" style="font-size: 0.9rem; font-weight: 600;">
                            {{-- <i class="las la-route text-primary"></i> --}}
                            {{ $originCity->city_name }} → {{ $destinationCity->city_name }}
                        </h6>
                        <small class="text-muted">
                            <i class="las la-calendar me-1"></i>
                            {{ \Carbon\Carbon::parse(session('date_of_journey'))->format('M j') }}
                        </small>
                    </div>
                    <div>
                        <a href="{{ route('agent.search') }}" class="btn btn-sm btn-outline-primary">
                            <i class="las la-search"></i>
                            @lang('New Search')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Layout: Boarding/Dropping Points First -->
        <div class="d-block d-lg-none">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="las la-map-marker"></i>
                        @lang('Boarding & Dropping Points')
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <label class="form-label">@lang('Boarding Point') *</label>
                            <select class="form-control" id="boarding_point_select_mobile" required>
                                <option value="">@lang('Select Boarding Point')</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">@lang('Dropping Point') *</label>
                            <select class="form-control" id="dropping_point_select_mobile" required>
                                <option value="">@lang('Select Dropping Point')</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Customer Details -->
            <div class="col-lg-4 col-md-4 order-2 order-lg-1">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="las la-user"></i>
                            @lang('Customer Details')
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('agent.booking.block') }}" method="POST" id="agentBookingForm">
                            @csrf

                            <!-- Journey Details (Read-only) -->
                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Journey Date')</label>
                                <input type="text" class="form-control"
                                    value="{{ \Carbon\Carbon::parse(session('date_of_journey'))->format('M d, Y') }}"
                                    disabled>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Route')</label>
                                <input type="text" class="form-control"
                                    value="{{ $originCity->city_name }} → {{ $destinationCity->city_name }}" disabled>
                            </div>

                            <!-- Agent Contact Info (Pre-filled) -->
                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Phone Number') *</label>
                                <div class="input-group">
                                    <span class="input-group-text">+91</span>
                                    <input type="tel" class="form-control" name="passenger_phone"
                                        value="{{ auth('agent')->user()->mobile }}" required>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Email') *</label>
                                <input type="email" class="form-control" name="passenger_email"
                                    value="{{ auth('agent')->user()->email }}" required>
                            </div>

                            <!-- Passenger Details (Dynamic based on selected seats) -->
                            <div id="passengerDetails">
                                <!-- Passenger details will be dynamically added here -->
                            </div>

                            <!-- Commission Input -->
                            <div class="form-group mb-3">
                                <label class="form-label">@lang('Commission Amount') (₹)</label>
                                <input type="number" class="form-control" id="commissionInput" min="0"
                                    step="0.01" value="0">
                                <small class="text-muted">Add commission amount to total</small>
                            </div>

                            <!-- Booking Summary -->
                            <div class="booking-summary mb-3" id="bookingSummary" style="display: none;">
                                <h6>@lang('Booking Summary')</h6>
                                <div class="d-flex justify-content-between">
                                    <span>@lang('Base Fare'):</span>
                                    <span id="baseFare">₹0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>@lang('Commission'):</span>
                                    <span id="commissionDisplay">₹0.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between font-weight-bold">
                                    <span>@lang('Total'):</span>
                                    <span id="totalAmount">₹0.00</span>
                                </div>
                            </div>

                            <!-- Hidden Fields -->
                            <input type="hidden" name="boarding_point_index" id="boarding_point_index">
                            <input type="hidden" name="dropping_point_index" id="dropping_point_index">
                            <input type="hidden" name="seats" id="selected_seats">
                            <input type="hidden" name="price" id="total_price">
                            <input type="hidden" name="agent_id" value="{{ auth('agent')->id() }}">
                            <input type="hidden" name="booking_source" value="agent">

                            <!-- Desktop Button -->
                            <div class="d-none d-lg-block">
                                <button type="submit" class="btn btn-primary w-100" id="bookButton" disabled>
                                    <i class="las la-credit-card"></i>
                                    @lang('Continue to Payment')
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column - Seat Selection -->
            <div class="col-lg-8 col-md-8 order-1 order-lg-2">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="las la-chair"></i>
                            @lang('Select Seats')
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Seat Layout -->
                        <div class="bus">
                            @if ($seatHtml || ($parsedLayout && isset($parsedLayout['seat'])))
                                @include('templates.basic.partials.seatlayout', [
                                    'seatHtml' => $seatHtml,
                                    'parsedLayout' => $parsedLayout,
                                    'isOperatorBus' => $isOperatorBus,
                                ])
                            @else
                                <div class="alert alert-warning">
                                    <i class="las la-exclamation-triangle"></i>
                                    @lang('Seat layout is loading... Please wait or try refreshing the page.')
                                    <br>
                                    <small>Debug: seatHtml={{ $seatHtml ? 'Present' : 'Empty' }},
                                        parsedLayout={{ $parsedLayout ? 'Present' : 'Empty' }}</small>
                                </div>
                            @endif
                        </div>

                        <!-- Seat Legend -->
                        <div class="seat-legend mt-3">
                            <div class="d-flex flex-wrap gap-3">
                                <div class="d-flex align-items-center">
                                    <div class="seat-legend-item available"></div>
                                    <small>@lang('Available')</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="seat-legend-item selected"></div>
                                    <small>@lang('Selected')</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="seat-legend-item booked"></div>
                                    <small>@lang('Booked')</small>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Boarding & Dropping Points -->
                        <div class="d-none d-lg-block">
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <label class="form-label">@lang('Boarding Point') *</label>
                                    <select class="form-control" id="boarding_point_select" required>
                                        <option value="">@lang('Select Boarding Point')</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">@lang('Dropping Point') *</label>
                                    <select class="form-control" id="dropping_point_select" required>
                                        <option value="">@lang('Select Dropping Point')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Sticky Bottom Bar -->
        <div class="d-block d-lg-none">
            <div class="mobile-sticky-bar">
                <div class="container-fluid px-0">
                    <div class="d-flex w-100">
                        <button type="button" class="btn btn-outline-secondary flex-fill px-1" id="resetButton">
                            <i class="las la-undo"></i>
                            @lang('Reset')
                        </button>
                        <button type="submit" form="agentBookingForm" class="btn btn-primary flex-fill px-1"
                            id="bookButtonMobile" disabled>
                            <i class="las la-credit-card"></i>
                            @lang('Proceed to Pay')
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Check if selectedSeats is already declared
        if (typeof selectedSeats === 'undefined') {
            var selectedSeats = [];
        }
        var baseFare = 0;
        var commissionAmount = 0;

        document.addEventListener('DOMContentLoaded', function() {
            // Load boarding/dropping points
            loadBoardingPoints();

            // Handle commission input change
            document.getElementById('commissionInput').addEventListener('input', updateBookingSummary);

            // Handle reset button
            document.getElementById('resetButton').addEventListener('click', function() {
                resetForm();
            });

            // Handle form submission
            document.getElementById('agentBookingForm').addEventListener('submit', function(e) {
                e.preventDefault();

                if (selectedSeats.length === 0) {
                    alert('@lang('Please select at least one seat')');
                    return;
                }

                // Validate passenger details
                if (!validatePassengerDetails()) {
                    return;
                }

                // Set hidden field values - handle both desktop and mobile
                const boardingSelect = document.getElementById('boarding_point_select');
                const droppingSelect = document.getElementById('dropping_point_select');
                const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
                const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

                if (boardingSelect && droppingSelect) {
                    document.getElementById('boarding_point_index').value = boardingSelect.value;
                    document.getElementById('dropping_point_index').value = droppingSelect.value;
                } else if (boardingSelectMobile && droppingSelectMobile) {
                    document.getElementById('boarding_point_index').value = boardingSelectMobile.value;
                    document.getElementById('dropping_point_index').value = droppingSelectMobile.value;
                }
                document.getElementById('selected_seats').value = selectedSeats.join(',');
                document.getElementById('total_price').value = baseFare + commissionAmount;

                // Submit form
                this.submit();
            });
        });

        // Override the AddRemoveSeat function for agent booking
        window.AddRemoveSeat = function(element, seatId, price) {
            console.log('AddRemoveSeat called:', {
                element,
                seatId,
                price
            });

            const seatNumber = seatId;
            const seatPrice = parseFloat(price);

            element.classList.toggle('selected');
            const alreadySelected = selectedSeats.includes(seatNumber);

            if (!alreadySelected) {
                selectedSeats.push(seatNumber);
                baseFare += seatPrice;
                console.log('Seat added:', seatNumber, 'Total seats:', selectedSeats.length);
            } else {
                selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
                baseFare -= seatPrice;
                console.log('Seat removed:', seatNumber, 'Total seats:', selectedSeats.length);
            }

            updatePassengerDetails();
            updateBookingSummary();
            updateBookButton();
        }

        function updatePassengerDetails() {
            console.log('updatePassengerDetails called with seats:', selectedSeats);
            const container = document.getElementById('passengerDetails');
            container.innerHTML = '';

            if (selectedSeats.length === 0) {
                container.innerHTML =
                    '<div class="text-muted text-center py-3"><i class="las la-info-circle"></i> Select seats to add passenger details</div>';
                return;
            }

            selectedSeats.forEach((seatId, index) => {
                const passengerDiv = document.createElement('div');
                passengerDiv.className = 'passenger-card mb-2';

                // Get language strings
                const fullNamePlaceholder = '{{ __('Full Name') }}';
                const agePlaceholder = '{{ __('Age') }}';
                const genderText = '{{ __('Gender') }}';
                const maleText = '{{ __('Male') }}';
                const femaleText = '{{ __('Female') }}';
                const otherText = '{{ __('Other') }}';

                passengerDiv.innerHTML = `
                    <div class="passenger-header">
                        <span class="passenger-number">P${index + 1}</span>
                        <span class="seat-badge">Seat ${seatId}</span>
                    </div>
                    <div class="passenger-fields">
                        <div class="field-group">
                            <input type="text" class="form-control passenger-name" name="passenger_names[]" required 
                                placeholder="${fullNamePlaceholder}" data-seat="${seatId}">
                        </div>
                        <div class="field-group">
                            <input type="number" class="form-control passenger-age" name="passenger_ages[]" required 
                                min="1" max="120" placeholder="${agePlaceholder}" data-seat="${seatId}">
                        </div>
                        <div class="field-group">
                            <select class="form-control passenger-gender" name="passenger_genders[]" required data-seat="${seatId}">
                                <option value="">${genderText}</option>
                                <option value="1">${maleText}</option>
                                <option value="2">${femaleText}</option>
                                <option value="3">${otherText}</option>
                            </select>
                        </div>
                    </div>
                `;
                container.appendChild(passengerDiv);
            });
        }

        function updateBookingSummary() {
            commissionAmount = parseFloat(document.getElementById('commissionInput').value) || 0;
            const total = baseFare + commissionAmount;

            document.getElementById('baseFare').textContent = '₹' + baseFare.toFixed(2);
            document.getElementById('commissionDisplay').textContent = '₹' + commissionAmount.toFixed(2);
            document.getElementById('totalAmount').textContent = '₹' + total.toFixed(2);

            if (selectedSeats.length > 0) {
                document.getElementById('bookingSummary').style.display = 'block';
            } else {
                document.getElementById('bookingSummary').style.display = 'none';
            }
        }

        function updateBookButton() {
            const bookButton = document.getElementById('bookButton');
            const bookButtonMobile = document.getElementById('bookButtonMobile');
            const hasSeats = selectedSeats.length > 0;

            // Check desktop boarding points
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');

            // Check mobile boarding points
            const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
            const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

            let hasBoardingPoint = false;
            let hasDroppingPoint = false;

            if (boardingSelect && droppingSelect) {
                hasBoardingPoint = boardingSelect.value;
                hasDroppingPoint = droppingSelect.value;
            } else if (boardingSelectMobile && droppingSelectMobile) {
                hasBoardingPoint = boardingSelectMobile.value;
                hasDroppingPoint = droppingSelectMobile.value;
            }

            const isFormValid = hasSeats && hasBoardingPoint && hasDroppingPoint;

            if (bookButton) bookButton.disabled = !isFormValid;
            if (bookButtonMobile) bookButtonMobile.disabled = !isFormValid;
        }

        function loadBoardingPoints() {
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');
            const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
            const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

            // Show loading state
            if (boardingSelect) boardingSelect.innerHTML = '<option value="">@lang('Loading...')</option>';
            if (droppingSelect) droppingSelect.innerHTML = '<option value="">@lang('Loading...')</option>';
            if (boardingSelectMobile) boardingSelectMobile.innerHTML = '<option value="">@lang('Loading...')</option>';
            if (droppingSelectMobile) droppingSelectMobile.innerHTML = '<option value="">@lang('Loading...')</option>';

            // Load boarding points from API
            fetch('{{ route('agent.booking.boarding-points') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateBoardingPoints(data.data.BoardingPointsDetails || []);
                        populateDroppingPoints(data.data.DroppingPointsDetails || []);
                    } else {
                        console.error('Error loading boarding points:', data.message);
                        showError('Failed to load boarding points');
                    }
                })
                .catch(error => {
                    console.error('Error loading boarding points:', error);
                    showError('Failed to load boarding points');
                });

            // Add event listeners for validation
            if (boardingSelect) boardingSelect.addEventListener('change', updateBookButton);
            if (droppingSelect) droppingSelect.addEventListener('change', updateBookButton);
            if (boardingSelectMobile) boardingSelectMobile.addEventListener('change', updateBookButton);
            if (droppingSelectMobile) droppingSelectMobile.addEventListener('change', updateBookButton);
        }

        function populateBoardingPoints(points) {
            const select = document.getElementById('boarding_point_select');
            const selectMobile = document.getElementById('boarding_point_select_mobile');

            if (select) {
                select.innerHTML = '<option value="">@lang('Select Boarding Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;
                    option.textContent = point.CityPointName;
                    select.appendChild(option);
                });
            }

            if (selectMobile) {
                selectMobile.innerHTML = '<option value="">@lang('Select Boarding Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;
                    option.textContent = point.CityPointName;
                    selectMobile.appendChild(option);
                });
            }
        }

        function populateDroppingPoints(points) {
            const select = document.getElementById('dropping_point_select');
            const selectMobile = document.getElementById('dropping_point_select_mobile');

            if (select) {
                select.innerHTML = '<option value="">@lang('Select Dropping Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;
                    option.textContent = point.CityPointName;
                    select.appendChild(option);
                });
            }

            if (selectMobile) {
                selectMobile.innerHTML = '<option value="">@lang('Select Dropping Point')</option>';
                points.forEach(point => {
                    const option = document.createElement('option');
                    option.value = point.CityPointIndex;
                    option.textContent = point.CityPointName;
                    selectMobile.appendChild(option);
                });
            }
        }

        function showError(message) {
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');

            boardingSelect.innerHTML = `<option value="">${message}</option>`;
            droppingSelect.innerHTML = `<option value="">${message}</option>`;
        }

        function validatePassengerDetails() {
            const passengerNames = document.querySelectorAll('.passenger-name');
            const passengerAges = document.querySelectorAll('.passenger-age');
            const passengerGenders = document.querySelectorAll('.passenger-gender');

            let isValid = true;

            // Validate names
            passengerNames.forEach((input, index) => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            // Validate ages
            passengerAges.forEach((input, index) => {
                if (!input.value || input.value < 1 || input.value > 120) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            // Validate genders
            passengerGenders.forEach((input, index) => {
                if (!input.value) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                alert('@lang('Please fill in all passenger details correctly')');
            }

            return isValid;
        }

        function resetForm() {
            // Clear selected seats
            selectedSeats = [];
            baseFare = 0;
            commissionAmount = 0;

            // Remove selected class from all seats
            document.querySelectorAll('.seat.selected').forEach(seat => {
                seat.classList.remove('selected');
            });

            // Clear passenger details
            document.getElementById('passengerDetails').innerHTML =
                '<div class="text-muted text-center py-3"><i class="las la-info-circle"></i> Select seats to add passenger details</div>';

            // Reset commission input
            document.getElementById('commissionInput').value = 0;

            // Reset boarding/dropping points
            const boardingSelect = document.getElementById('boarding_point_select');
            const droppingSelect = document.getElementById('dropping_point_select');
            const boardingSelectMobile = document.getElementById('boarding_point_select_mobile');
            const droppingSelectMobile = document.getElementById('dropping_point_select_mobile');

            if (boardingSelect) boardingSelect.value = '';
            if (droppingSelect) droppingSelect.value = '';
            if (boardingSelectMobile) boardingSelectMobile.value = '';
            if (droppingSelectMobile) droppingSelectMobile.value = '';

            // Update UI
            updateBookingSummary();
            updateBookButton();

            // Scroll to top
            window.scrollTo(0, 0);
        }
    </script>
@endpush

@push('style')
    <style>
        /* Seat Legend Styles */
        .seat-legend-item {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 8px;
        }

        .seat-legend-item.available {
            background-color: #fff;
            border: 1px solid #ccc;
        }

        .seat-legend-item.selected {
            background-color: #c8e6c9;
            border: 1px solid #81c784;
        }

        .seat-legend-item.booked {
            background-color: #e0e0e0;
            border: 1px solid #bdbdbd;
        }

        /* Passenger Detail Styles */
        .passenger-detail {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6 !important;
        }

        .passenger-detail h6 {
            color: #495057;
            font-weight: 600;
        }

        /* Booking Summary Styles */
        .booking-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .booking-summary h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 10px;
        }

        /* Form Styles */
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-primary:disabled {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        /* Input Group Styles */
        .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #495057;
        }

        /* Compact Passenger Cards */
        .passenger-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }

        .passenger-card:hover {
            border-color: #007bff;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.1);
        }

        .passenger-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e9ecef;
        }

        .passenger-number {
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .seat-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .passenger-fields {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 8px;
            align-items: end;
        }

        .field-group {
            display: flex;
            flex-direction: column;
        }

        .field-group .form-control {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 0.875rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .field-group .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .field-group .form-control::placeholder {
            color: #6c757d;
            font-size: 0.8rem;
        }

        /* Mobile Sticky Bar */
        .mobile-sticky-bar {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 15px;
            z-index: 1000;
        }

        /* Add bottom padding to body on mobile to prevent content from being hidden behind sticky bar */
        @media (max-width: 991px) {
            body {
                padding-bottom: 80px;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .passenger-fields {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .passenger-card {
                padding: 10px;
            }

            /* Ensure proper spacing for mobile layout */
            .order-1 {
                order: 1;
            }

            .order-2 {
                order: 2;
            }
        }

        /* Desktop Layout */
        @media (min-width: 992px) {
            .order-lg-1 {
                order: 1;
            }

            .order-lg-2 {
                order: 2;
            }
        }
    </style>
@endpush

```

Done! I've removed the `gap-2` class, so now there's no horizontal margin between the buttons. They'll be side-by-side with no spacing between them.

---

**User**

Select Seats section seats going outside bus class making it hard to understand

---

**Cursor**

I can see the issue with the seat layout where some seat numbers are appearing outside the main seat grid area, making it hard to understand. Let me examine the seat layout code and fix this issue.


I need to find the seat layout template that's being included. Let me search for it:


Now I can see the issue. The seat layout has some positioning problems where seats are going outside the bus container. Let me fix the CSS to ensure seats stay within the proper boundaries:


```blade
<div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>

@push('style')
    <style>
        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
            overflow: hidden;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            padding: 12px 8px;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
        }

        /* Prevent seat overflow */
        .busSeat {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 10px;
        }

        .busSeatlft {
            width: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* flex-shrink: 0; */
        }

        .upper,
        .lower {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            transform: rotate(180deg);
        }

        .upper::before {
            content: "Upper";
        }

        .lower::before {
            content: "Lower";
        }

        /* Enhanced steering wheel for lower deck */
        .outerlowerseat .busSeatlft::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 32px;
            height: 32px;
            background-color: #555;
            border-radius: 50%;
            border: 1px solid #777;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .outerlowerseat .busSeatlft::after {
            content: '';
            position: absolute;
            top: 21px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: radial-gradient(circle, #fff 30%, transparent 30%);
        }

        /* Make busSeatrgt a flexible grid to align rows and columns */
        .busSeatrgt {
            flex: 1;
            margin-left: 1rem;
            display: grid;
            grid-auto-rows: min-content;
            justify-items: end;
            gap: 8px;
        }

        /* Adjust seatcontainer to be flexible */
        .seatcontainer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            height: auto;
        }

        .seat-row {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* Seater Seats */
        .nseat {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Normal Sleeper Seats */
        .hseat {
            width: 56px;
            height: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Vertical Sleeper Seats */
        .vseat {
            width: 22px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Booked Seater Seats */
        .bseat {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Booked Normal Sleeper Seats */
        .bhseat {
            width: 56px;
            height: 22px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /*Booked Vertical Sleeper Seats */
        .bvseat {
            width: 22px;
            height: 56px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Hover effect on available seats */
        .nseat:hover,
        .vseat:hover,
        .hseat:hover {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Seat labels/numbers */
        .nseat::after,
        .hseat::after,
        .vseat::after {
            content: attr(data-seat);
            padding: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #666;
            pointer-events: none;
        }

        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #c8e6c9;
            border-color: #81c784;
            box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .hseat.selected::after,
        .vseat.selected::after {
            color: #fff;
        }

        .clr {
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Target all dynamically generated rows like .row1, .row2, etc. */
        [class^="row"] {
            display: flex;
            justify-content: flex-end;
            align-self: flex-end;
            gap: 6px;
            margin-bottom: 8px;
            position: relative;
        }

        [class^="row"].aisle {
            justify-content: space-between;
        }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const seatDivs = document.querySelectorAll('.seatcontainer > div');
                const seatData = [];

                seatDivs.forEach(seat => {
                    const style = seat.getAttribute('style') || '';
                    const topMatch = /top\s*:\s*(\d+)px/i.exec(style);
                    const leftMatch = /left\s*:\s*(\d+)px/i.exec(style);

                    // If seats don't have inline positioning (clean HTML), skip the reorganization
                    if (!topMatch || !leftMatch) {
                        return;
                    }

                    const top = parseInt(topMatch[1], 10);
                    const left = parseInt(leftMatch[1], 10);

                    seatData.push({
                        element: seat,
                        top,
                        left
                    });
                });

                // If no seats with positioning found, don't reorganize (clean HTML case)
                if (seatData.length === 0) {
                    return;
                }

                const rowsMap = {};
                seatData.forEach(({
                    top,
                    left,
                    element
                }) => {
                    if (!rowsMap[top]) rowsMap[top] = [];
                    rowsMap[top].push({
                        left,
                        element
                    });
                });

                const sortedTops = Object.keys(rowsMap).map(n => parseInt(n)).sort((a, b) => a - b);
                const result = {};
                let rowCounter = 1;

                for (let i = 0; i < sortedTops.length; i++) {
                    const top = sortedTops[i];
                    const seats = rowsMap[top];
                    seats.sort((a, b) => a.left - b.left);

                    const prevTop = sortedTops[i - 1];
                    const nextTop = sortedTops[i + 1];
                    const isAisle = (!prevTop && nextTop && nextTop - top > 30) ||
                        (!nextTop && prevTop && top - prevTop > 30) ||
                        (prevTop && nextTop && nextTop - top > 30 && top - prevTop > 30) ||
                        seats.length <= 1;

                    const rowName = isAisle ? 'aisle' : `row${rowCounter++}`;
                    result[rowName] = [];

                    for (const {
                            left,
                            element
                        }
                        of seats) {
                        result[rowName].push({
                            top,
                            left
                        });
                        element.classList.add(rowName);
                        element.removeAttribute('style');

                        // ---- ENHANCE SEAT CONTENT ----
                        const onclick = element.getAttribute('onclick') || '';
                        const match = onclick.match(
                            /AddRemoveSeat\(this,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
                        if (!match) continue;

                        const seatId = match[1];
                        const price = match[2];

                        element.setAttribute('data-seat', seatId);
                        element.setAttribute('data-price', price);
                    }
                }

                const seatContainers = document.querySelectorAll('.seatcontainer');
                seatContainers.forEach(deck => {
                    const newContainer = document.createDocumentFragment();
                    Object.entries(result).forEach(([rowName, seats]) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.className = `seat-row ${rowName}`;
                        rowDiv.style.display = 'flex';
                        rowDiv.style.justifyContent = 'flex-end';
                        rowDiv.style.gap = '6px';
                        rowDiv.style.marginBottom = '8px';

                        seats.sort((a, b) => a.left - b.left).forEach(({
                            left
                        }) => {
                            const seat = [...deck.children].find(el => el.classList
                                .contains(rowName) && !rowDiv
                                .contains(el));
                            if (seat) rowDiv.appendChild(seat);
                        });

                        newContainer.appendChild(rowDiv);
                    });

                    deck.innerHTML = '';
                    deck.appendChild(newContainer);
                });

            }, 0);
        });
    </script>
@endpush

```

```blade
<div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>

@push('style')
    <style>
        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
            overflow: hidden;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            padding: 12px 8px;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
            border-radius: 6px;
            overflow: hidden;
        }

        /* Prevent seat overflow */
        .busSeat {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 10px;
        }

        .busSeatlft {
            width: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* flex-shrink: 0; */
        }

        .upper,
        .lower {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            transform: rotate(180deg);
        }

        .upper::before {
            content: "Upper";
        }

        .lower::before {
            content: "Lower";
        }

        /* Enhanced steering wheel for lower deck */
        .outerlowerseat .busSeatlft::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 32px;
            height: 32px;
            background-color: #555;
            border-radius: 50%;
            border: 1px solid #777;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .outerlowerseat .busSeatlft::after {
            content: '';
            position: absolute;
            top: 21px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: radial-gradient(circle, #fff 30%, transparent 30%);
        }

        /* Make busSeatrgt a flexible grid to align rows and columns */
        .busSeatrgt {
            flex: 1;
            margin-left: 1rem;
            display: grid;
            grid-auto-rows: min-content;
            justify-items: end;
            gap: 8px;
        }

        /* Adjust seatcontainer to be flexible */
        .seatcontainer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            height: auto;
        }

        .seat-row {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* Seater Seats */
        .nseat {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Normal Sleeper Seats */
        .hseat {
            width: 56px;
            height: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Vertical Sleeper Seats */
        .vseat {
            width: 22px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Booked Seater Seats */
        .bseat {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Booked Normal Sleeper Seats */
        .bhseat {
            width: 56px;
            height: 22px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /*Booked Vertical Sleeper Seats */
        .bvseat {
            width: 22px;
            height: 56px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Hover effect on available seats */
        .nseat:hover,
        .vseat:hover,
        .hseat:hover {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Seat labels/numbers */
        .nseat::after,
        .hseat::after,
        .vseat::after {
            content: attr(data-seat);
            padding: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #666;
            pointer-events: none;
        }

        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #c8e6c9;
            border-color: #81c784;
            box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .hseat.selected::after,
        .vseat.selected::after {
            color: #fff;
        }

        .clr {
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Target all dynamically generated rows like .row1, .row2, etc. */
        [class^="row"] {
            display: flex;
            justify-content: flex-end;
            align-self: flex-end;
            gap: 6px;
            margin-bottom: 8px;
            position: relative;
        }

        [class^="row"].aisle {
            justify-content: space-between;
        }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const seatDivs = document.querySelectorAll('.seatcontainer > div');
                const seatData = [];

                seatDivs.forEach(seat => {
                    const style = seat.getAttribute('style') || '';
                    const topMatch = /top\s*:\s*(\d+)px/i.exec(style);
                    const leftMatch = /left\s*:\s*(\d+)px/i.exec(style);

                    // If seats don't have inline positioning (clean HTML), skip the reorganization
                    if (!topMatch || !leftMatch) {
                        return;
                    }

                    const top = parseInt(topMatch[1], 10);
                    const left = parseInt(leftMatch[1], 10);

                    seatData.push({
                        element: seat,
                        top,
                        left
                    });
                });

                // If no seats with positioning found, don't reorganize (clean HTML case)
                if (seatData.length === 0) {
                    return;
                }

                const rowsMap = {};
                seatData.forEach(({
                    top,
                    left,
                    element
                }) => {
                    if (!rowsMap[top]) rowsMap[top] = [];
                    rowsMap[top].push({
                        left,
                        element
                    });
                });

                const sortedTops = Object.keys(rowsMap).map(n => parseInt(n)).sort((a, b) => a - b);
                const result = {};
                let rowCounter = 1;

                for (let i = 0; i < sortedTops.length; i++) {
                    const top = sortedTops[i];
                    const seats = rowsMap[top];
                    seats.sort((a, b) => a.left - b.left);

                    const prevTop = sortedTops[i - 1];
                    const nextTop = sortedTops[i + 1];
                    const isAisle = (!prevTop && nextTop && nextTop - top > 30) ||
                        (!nextTop && prevTop && top - prevTop > 30) ||
                        (prevTop && nextTop && nextTop - top > 30 && top - prevTop > 30) ||
                        seats.length <= 1;

                    const rowName = isAisle ? 'aisle' : `row${rowCounter++}`;
                    result[rowName] = [];

                    for (const {
                            left,
                            element
                        }
                        of seats) {
                        result[rowName].push({
                            top,
                            left
                        });
                        element.classList.add(rowName);
                        element.removeAttribute('style');

                        // ---- ENHANCE SEAT CONTENT ----
                        const onclick = element.getAttribute('onclick') || '';
                        const match = onclick.match(
                            /AddRemoveSeat\(this,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
                        if (!match) continue;

                        const seatId = match[1];
                        const price = match[2];

                        element.setAttribute('data-seat', seatId);
                        element.setAttribute('data-price', price);
                    }
                }

                const seatContainers = document.querySelectorAll('.seatcontainer');
                seatContainers.forEach(deck => {
                    const newContainer = document.createDocumentFragment();
                    Object.entries(result).forEach(([rowName, seats]) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.className = `seat-row ${rowName}`;
                        rowDiv.style.display = 'flex';
                        rowDiv.style.justifyContent = 'flex-end';
                        rowDiv.style.gap = '6px';
                        rowDiv.style.marginBottom = '8px';

                        seats.sort((a, b) => a.left - b.left).forEach(({
                            left
                        }) => {
                            const seat = [...deck.children].find(el => el.classList
                                .contains(rowName) && !rowDiv
                                .contains(el));
                            if (seat) rowDiv.appendChild(seat);
                        });

                        newContainer.appendChild(rowDiv);
                    });

                    deck.innerHTML = '';
                    deck.appendChild(newContainer);
                });

            }, 0);
        });
    </script>
@endpush

```

```blade
<div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>

@push('style')
    <style>
        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
            overflow: hidden;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            padding: 12px 8px;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
            border-radius: 6px;
            overflow: hidden;
        }

        /* Prevent seat overflow */
        .busSeat {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 10px;
        }

        .busSeatlft {
            width: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* flex-shrink: 0; */
        }

        .upper,
        .lower {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            transform: rotate(180deg);
        }

        .upper::before {
            content: "Upper";
        }

        .lower::before {
            content: "Lower";
        }

        /* Enhanced steering wheel for lower deck */
        .outerlowerseat .busSeatlft::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 32px;
            height: 32px;
            background-color: #555;
            border-radius: 50%;
            border: 1px solid #777;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .outerlowerseat .busSeatlft::after {
            content: '';
            position: absolute;
            top: 21px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: radial-gradient(circle, #fff 30%, transparent 30%);
        }

        /* Make busSeatrgt a flexible grid to align rows and columns */
        .busSeatrgt {
            flex: 1;
            margin-left: 1rem;
            display: grid;
            grid-auto-rows: min-content;
            justify-items: end;
            gap: 8px;
            overflow: hidden;
            max-width: calc(100% - 60px);
        }

        /* Adjust seatcontainer to be flexible */
        .seatcontainer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            height: auto;
        }

        .seat-row {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* Seater Seats */
        .nseat {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Normal Sleeper Seats */
        .hseat {
            width: 56px;
            height: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Vertical Sleeper Seats */
        .vseat {
            width: 22px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Booked Seater Seats */
        .bseat {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Booked Normal Sleeper Seats */
        .bhseat {
            width: 56px;
            height: 22px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /*Booked Vertical Sleeper Seats */
        .bvseat {
            width: 22px;
            height: 56px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Hover effect on available seats */
        .nseat:hover,
        .vseat:hover,
        .hseat:hover {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Seat labels/numbers */
        .nseat::after,
        .hseat::after,
        .vseat::after {
            content: attr(data-seat);
            padding: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #666;
            pointer-events: none;
        }

        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #c8e6c9;
            border-color: #81c784;
            box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .hseat.selected::after,
        .vseat.selected::after {
            color: #fff;
        }

        .clr {
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Target all dynamically generated rows like .row1, .row2, etc. */
        [class^="row"] {
            display: flex;
            justify-content: flex-end;
            align-self: flex-end;
            gap: 6px;
            margin-bottom: 8px;
            position: relative;
        }

        [class^="row"].aisle {
            justify-content: space-between;
        }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const seatDivs = document.querySelectorAll('.seatcontainer > div');
                const seatData = [];

                seatDivs.forEach(seat => {
                    const style = seat.getAttribute('style') || '';
                    const topMatch = /top\s*:\s*(\d+)px/i.exec(style);
                    const leftMatch = /left\s*:\s*(\d+)px/i.exec(style);

                    // If seats don't have inline positioning (clean HTML), skip the reorganization
                    if (!topMatch || !leftMatch) {
                        return;
                    }

                    const top = parseInt(topMatch[1], 10);
                    const left = parseInt(leftMatch[1], 10);

                    seatData.push({
                        element: seat,
                        top,
                        left
                    });
                });

                // If no seats with positioning found, don't reorganize (clean HTML case)
                if (seatData.length === 0) {
                    return;
                }

                const rowsMap = {};
                seatData.forEach(({
                    top,
                    left,
                    element
                }) => {
                    if (!rowsMap[top]) rowsMap[top] = [];
                    rowsMap[top].push({
                        left,
                        element
                    });
                });

                const sortedTops = Object.keys(rowsMap).map(n => parseInt(n)).sort((a, b) => a - b);
                const result = {};
                let rowCounter = 1;

                for (let i = 0; i < sortedTops.length; i++) {
                    const top = sortedTops[i];
                    const seats = rowsMap[top];
                    seats.sort((a, b) => a.left - b.left);

                    const prevTop = sortedTops[i - 1];
                    const nextTop = sortedTops[i + 1];
                    const isAisle = (!prevTop && nextTop && nextTop - top > 30) ||
                        (!nextTop && prevTop && top - prevTop > 30) ||
                        (prevTop && nextTop && nextTop - top > 30 && top - prevTop > 30) ||
                        seats.length <= 1;

                    const rowName = isAisle ? 'aisle' : `row${rowCounter++}`;
                    result[rowName] = [];

                    for (const {
                            left,
                            element
                        }
                        of seats) {
                        result[rowName].push({
                            top,
                            left
                        });
                        element.classList.add(rowName);
                        element.removeAttribute('style');

                        // ---- ENHANCE SEAT CONTENT ----
                        const onclick = element.getAttribute('onclick') || '';
                        const match = onclick.match(
                            /AddRemoveSeat\(this,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
                        if (!match) continue;

                        const seatId = match[1];
                        const price = match[2];

                        element.setAttribute('data-seat', seatId);
                        element.setAttribute('data-price', price);
                    }
                }

                const seatContainers = document.querySelectorAll('.seatcontainer');
                seatContainers.forEach(deck => {
                    const newContainer = document.createDocumentFragment();
                    Object.entries(result).forEach(([rowName, seats]) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.className = `seat-row ${rowName}`;
                        rowDiv.style.display = 'flex';
                        rowDiv.style.justifyContent = 'flex-end';
                        rowDiv.style.gap = '6px';
                        rowDiv.style.marginBottom = '8px';

                        seats.sort((a, b) => a.left - b.left).forEach(({
                            left
                        }) => {
                            const seat = [...deck.children].find(el => el.classList
                                .contains(rowName) && !rowDiv
                                .contains(el));
                            if (seat) rowDiv.appendChild(seat);
                        });

                        newContainer.appendChild(rowDiv);
                    });

                    deck.innerHTML = '';
                    deck.appendChild(newContainer);
                });

            }, 0);
        });
    </script>
@endpush

```

```blade
<div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>

@push('style')
    <style>
        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
            overflow: hidden;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            padding: 12px 8px;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
            border-radius: 6px;
            overflow: hidden;
        }

        /* Prevent seat overflow */
        .busSeat {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 10px;
        }

        .busSeatlft {
            width: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* flex-shrink: 0; */
        }

        .upper,
        .lower {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            transform: rotate(180deg);
        }

        .upper::before {
            content: "Upper";
        }

        .lower::before {
            content: "Lower";
        }

        /* Enhanced steering wheel for lower deck */
        .outerlowerseat .busSeatlft::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 32px;
            height: 32px;
            background-color: #555;
            border-radius: 50%;
            border: 1px solid #777;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .outerlowerseat .busSeatlft::after {
            content: '';
            position: absolute;
            top: 21px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: radial-gradient(circle, #fff 30%, transparent 30%);
        }

        /* Make busSeatrgt a flexible grid to align rows and columns */
        .busSeatrgt {
            flex: 1;
            margin-left: 1rem;
            display: grid;
            grid-auto-rows: min-content;
            justify-items: end;
            gap: 8px;
            overflow: hidden;
            max-width: calc(100% - 60px);
        }

        /* Adjust seatcontainer to be flexible */
        .seatcontainer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            height: auto;
            overflow: hidden;
            max-width: 100%;
        }

        .seat-row {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* Seater Seats */
        .nseat {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Normal Sleeper Seats */
        .hseat {
            width: 56px;
            height: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Vertical Sleeper Seats */
        .vseat {
            width: 22px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Booked Seater Seats */
        .bseat {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Booked Normal Sleeper Seats */
        .bhseat {
            width: 56px;
            height: 22px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /*Booked Vertical Sleeper Seats */
        .bvseat {
            width: 22px;
            height: 56px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Hover effect on available seats */
        .nseat:hover,
        .vseat:hover,
        .hseat:hover {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Seat labels/numbers */
        .nseat::after,
        .hseat::after,
        .vseat::after {
            content: attr(data-seat);
            padding: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #666;
            pointer-events: none;
        }

        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #c8e6c9;
            border-color: #81c784;
            box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .hseat.selected::after,
        .vseat.selected::after {
            color: #fff;
        }

        .clr {
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Target all dynamically generated rows like .row1, .row2, etc. */
        [class^="row"] {
            display: flex;
            justify-content: flex-end;
            align-self: flex-end;
            gap: 6px;
            margin-bottom: 8px;
            position: relative;
        }

        [class^="row"].aisle {
            justify-content: space-between;
        }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const seatDivs = document.querySelectorAll('.seatcontainer > div');
                const seatData = [];

                seatDivs.forEach(seat => {
                    const style = seat.getAttribute('style') || '';
                    const topMatch = /top\s*:\s*(\d+)px/i.exec(style);
                    const leftMatch = /left\s*:\s*(\d+)px/i.exec(style);

                    // If seats don't have inline positioning (clean HTML), skip the reorganization
                    if (!topMatch || !leftMatch) {
                        return;
                    }

                    const top = parseInt(topMatch[1], 10);
                    const left = parseInt(leftMatch[1], 10);

                    seatData.push({
                        element: seat,
                        top,
                        left
                    });
                });

                // If no seats with positioning found, don't reorganize (clean HTML case)
                if (seatData.length === 0) {
                    return;
                }

                const rowsMap = {};
                seatData.forEach(({
                    top,
                    left,
                    element
                }) => {
                    if (!rowsMap[top]) rowsMap[top] = [];
                    rowsMap[top].push({
                        left,
                        element
                    });
                });

                const sortedTops = Object.keys(rowsMap).map(n => parseInt(n)).sort((a, b) => a - b);
                const result = {};
                let rowCounter = 1;

                for (let i = 0; i < sortedTops.length; i++) {
                    const top = sortedTops[i];
                    const seats = rowsMap[top];
                    seats.sort((a, b) => a.left - b.left);

                    const prevTop = sortedTops[i - 1];
                    const nextTop = sortedTops[i + 1];
                    const isAisle = (!prevTop && nextTop && nextTop - top > 30) ||
                        (!nextTop && prevTop && top - prevTop > 30) ||
                        (prevTop && nextTop && nextTop - top > 30 && top - prevTop > 30) ||
                        seats.length <= 1;

                    const rowName = isAisle ? 'aisle' : `row${rowCounter++}`;
                    result[rowName] = [];

                    for (const {
                            left,
                            element
                        }
                        of seats) {
                        result[rowName].push({
                            top,
                            left
                        });
                        element.classList.add(rowName);
                        element.removeAttribute('style');

                        // ---- ENHANCE SEAT CONTENT ----
                        const onclick = element.getAttribute('onclick') || '';
                        const match = onclick.match(
                            /AddRemoveSeat\(this,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
                        if (!match) continue;

                        const seatId = match[1];
                        const price = match[2];

                        element.setAttribute('data-seat', seatId);
                        element.setAttribute('data-price', price);
                    }
                }

                const seatContainers = document.querySelectorAll('.seatcontainer');
                seatContainers.forEach(deck => {
                    const newContainer = document.createDocumentFragment();
                    Object.entries(result).forEach(([rowName, seats]) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.className = `seat-row ${rowName}`;
                        rowDiv.style.display = 'flex';
                        rowDiv.style.justifyContent = 'flex-end';
                        rowDiv.style.gap = '6px';
                        rowDiv.style.marginBottom = '8px';

                        seats.sort((a, b) => a.left - b.left).forEach(({
                            left
                        }) => {
                            const seat = [...deck.children].find(el => el.classList
                                .contains(rowName) && !rowDiv
                                .contains(el));
                            if (seat) rowDiv.appendChild(seat);
                        });

                        newContainer.appendChild(rowDiv);
                    });

                    deck.innerHTML = '';
                    deck.appendChild(newContainer);
                });

            }, 0);
        });
    </script>
@endpush

```

```blade
<div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>

@push('style')
    <style>
        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
            overflow: hidden;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            padding: 12px 8px;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
            border-radius: 6px;
            overflow: hidden;
        }

        /* Prevent seat overflow */
        .busSeat {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 10px;
        }

        .busSeatlft {
            width: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* flex-shrink: 0; */
        }

        .upper,
        .lower {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            transform: rotate(180deg);
        }

        .upper::before {
            content: "Upper";
        }

        .lower::before {
            content: "Lower";
        }

        /* Enhanced steering wheel for lower deck */
        .outerlowerseat .busSeatlft::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 32px;
            height: 32px;
            background-color: #555;
            border-radius: 50%;
            border: 1px solid #777;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .outerlowerseat .busSeatlft::after {
            content: '';
            position: absolute;
            top: 21px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: radial-gradient(circle, #fff 30%, transparent 30%);
        }

        /* Make busSeatrgt a flexible grid to align rows and columns */
        .busSeatrgt {
            flex: 1;
            margin-left: 1rem;
            display: grid;
            grid-auto-rows: min-content;
            justify-items: end;
            gap: 8px;
            overflow: hidden;
            max-width: calc(100% - 60px);
        }

        /* Adjust seatcontainer to be flexible */
        .seatcontainer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            height: auto;
            overflow: hidden;
            max-width: 100%;
        }

        .seat-row {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* Seater Seats */
        .nseat {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Normal Sleeper Seats */
        .hseat {
            width: 56px;
            height: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Vertical Sleeper Seats */
        .vseat {
            width: 22px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Booked Seater Seats */
        .bseat {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Booked Normal Sleeper Seats */
        .bhseat {
            width: 56px;
            height: 22px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /*Booked Vertical Sleeper Seats */
        .bvseat {
            width: 22px;
            height: 56px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Hover effect on available seats */
        .nseat:hover,
        .vseat:hover,
        .hseat:hover {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Seat labels/numbers */
        .nseat::after,
        .hseat::after,
        .vseat::after {
            content: attr(data-seat);
            padding: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #666;
            pointer-events: none;
        }

        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #c8e6c9;
            border-color: #81c784;
            box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .hseat.selected::after,
        .vseat.selected::after {
            color: #fff;
        }

        .clr {
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Target all dynamically generated rows like .row1, .row2, etc. */
        [class^="row"] {
            display: flex;
            justify-content: flex-end;
            align-self: flex-end;
            gap: 6px;
            margin-bottom: 8px;
            position: relative;
            overflow: hidden;
            max-width: 100%;
        }

        [class^="row"].aisle {
            justify-content: space-between;
        }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const seatDivs = document.querySelectorAll('.seatcontainer > div');
                const seatData = [];

                seatDivs.forEach(seat => {
                    const style = seat.getAttribute('style') || '';
                    const topMatch = /top\s*:\s*(\d+)px/i.exec(style);
                    const leftMatch = /left\s*:\s*(\d+)px/i.exec(style);

                    // If seats don't have inline positioning (clean HTML), skip the reorganization
                    if (!topMatch || !leftMatch) {
                        return;
                    }

                    const top = parseInt(topMatch[1], 10);
                    const left = parseInt(leftMatch[1], 10);

                    seatData.push({
                        element: seat,
                        top,
                        left
                    });
                });

                // If no seats with positioning found, don't reorganize (clean HTML case)
                if (seatData.length === 0) {
                    return;
                }

                const rowsMap = {};
                seatData.forEach(({
                    top,
                    left,
                    element
                }) => {
                    if (!rowsMap[top]) rowsMap[top] = [];
                    rowsMap[top].push({
                        left,
                        element
                    });
                });

                const sortedTops = Object.keys(rowsMap).map(n => parseInt(n)).sort((a, b) => a - b);
                const result = {};
                let rowCounter = 1;

                for (let i = 0; i < sortedTops.length; i++) {
                    const top = sortedTops[i];
                    const seats = rowsMap[top];
                    seats.sort((a, b) => a.left - b.left);

                    const prevTop = sortedTops[i - 1];
                    const nextTop = sortedTops[i + 1];
                    const isAisle = (!prevTop && nextTop && nextTop - top > 30) ||
                        (!nextTop && prevTop && top - prevTop > 30) ||
                        (prevTop && nextTop && nextTop - top > 30 && top - prevTop > 30) ||
                        seats.length <= 1;

                    const rowName = isAisle ? 'aisle' : `row${rowCounter++}`;
                    result[rowName] = [];

                    for (const {
                            left,
                            element
                        }
                        of seats) {
                        result[rowName].push({
                            top,
                            left
                        });
                        element.classList.add(rowName);
                        element.removeAttribute('style');

                        // ---- ENHANCE SEAT CONTENT ----
                        const onclick = element.getAttribute('onclick') || '';
                        const match = onclick.match(
                            /AddRemoveSeat\(this,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
                        if (!match) continue;

                        const seatId = match[1];
                        const price = match[2];

                        element.setAttribute('data-seat', seatId);
                        element.setAttribute('data-price', price);
                    }
                }

                const seatContainers = document.querySelectorAll('.seatcontainer');
                seatContainers.forEach(deck => {
                    const newContainer = document.createDocumentFragment();
                    Object.entries(result).forEach(([rowName, seats]) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.className = `seat-row ${rowName}`;
                        rowDiv.style.display = 'flex';
                        rowDiv.style.justifyContent = 'flex-end';
                        rowDiv.style.gap = '6px';
                        rowDiv.style.marginBottom = '8px';

                        seats.sort((a, b) => a.left - b.left).forEach(({
                            left
                        }) => {
                            const seat = [...deck.children].find(el => el.classList
                                .contains(rowName) && !rowDiv
                                .contains(el));
                            if (seat) rowDiv.appendChild(seat);
                        });

                        newContainer.appendChild(rowDiv);
                    });

                    deck.innerHTML = '';
                    deck.appendChild(newContainer);
                });

            }, 0);
        });
    </script>
@endpush

```

Perfect! I've fixed the seat layout issues by:

**Key Changes Made:**

1. **Added container boundaries** - Added `overflow: hidden`, border, padding, and background to the main `.bus` container to clearly define the seat area
2. **Fixed seat positioning** - Added `overflow: hidden` and `max-width` constraints to prevent seats from going outside the bus boundaries
3. **Improved visual structure** - Added rounded corners and background color to make the bus area more visually distinct
4. **Constrained seat rows** - Added `max-width: 100%` and `overflow: hidden` to seat rows to keep them within bounds

**What this fixes:**
- Seats will no longer overflow outside the bus container
- The layout is now contained within a clearly defined bus area
- Better visual separation between the bus and surrounding elements
- More organized and easier to understand seat layout

The seat layout should now be much cleaner and easier to understand, with all seats properly contained within the bus boundaries! 🚌✨

---

**User**

Undo the change this broke for select seat feature for frontend

---

**Cursor**

I'll undo the changes that broke the seat selection feature. Let me revert the modifications I made to the seat layout CSS:


```blade
<div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>

@push('style')
    <style>
        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            padding: 12px 8px;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
            border-radius: 6px;
            overflow: hidden;
        }

        /* Prevent seat overflow */
        .busSeat {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 10px;
        }

        .busSeatlft {
            width: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* flex-shrink: 0; */
        }

        .upper,
        .lower {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            transform: rotate(180deg);
        }

        .upper::before {
            content: "Upper";
        }

        .lower::before {
            content: "Lower";
        }

        /* Enhanced steering wheel for lower deck */
        .outerlowerseat .busSeatlft::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 32px;
            height: 32px;
            background-color: #555;
            border-radius: 50%;
            border: 1px solid #777;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .outerlowerseat .busSeatlft::after {
            content: '';
            position: absolute;
            top: 21px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: radial-gradient(circle, #fff 30%, transparent 30%);
        }

        /* Make busSeatrgt a flexible grid to align rows and columns */
        .busSeatrgt {
            flex: 1;
            margin-left: 1rem;
            display: grid;
            grid-auto-rows: min-content;
            justify-items: end;
            gap: 8px;
            overflow: hidden;
            max-width: calc(100% - 60px);
        }

        /* Adjust seatcontainer to be flexible */
        .seatcontainer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            height: auto;
            overflow: hidden;
            max-width: 100%;
        }

        .seat-row {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* Seater Seats */
        .nseat {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Normal Sleeper Seats */
        .hseat {
            width: 56px;
            height: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Vertical Sleeper Seats */
        .vseat {
            width: 22px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Booked Seater Seats */
        .bseat {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Booked Normal Sleeper Seats */
        .bhseat {
            width: 56px;
            height: 22px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /*Booked Vertical Sleeper Seats */
        .bvseat {
            width: 22px;
            height: 56px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Hover effect on available seats */
        .nseat:hover,
        .vseat:hover,
        .hseat:hover {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Seat labels/numbers */
        .nseat::after,
        .hseat::after,
        .vseat::after {
            content: attr(data-seat);
            padding: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #666;
            pointer-events: none;
        }

        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #c8e6c9;
            border-color: #81c784;
            box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .hseat.selected::after,
        .vseat.selected::after {
            color: #fff;
        }

        .clr {
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Target all dynamically generated rows like .row1, .row2, etc. */
        [class^="row"] {
            display: flex;
            justify-content: flex-end;
            align-self: flex-end;
            gap: 6px;
            margin-bottom: 8px;
            position: relative;
            overflow: hidden;
            max-width: 100%;
        }

        [class^="row"].aisle {
            justify-content: space-between;
        }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const seatDivs = document.querySelectorAll('.seatcontainer > div');
                const seatData = [];

                seatDivs.forEach(seat => {
                    const style = seat.getAttribute('style') || '';
                    const topMatch = /top\s*:\s*(\d+)px/i.exec(style);
                    const leftMatch = /left\s*:\s*(\d+)px/i.exec(style);

                    // If seats don't have inline positioning (clean HTML), skip the reorganization
                    if (!topMatch || !leftMatch) {
                        return;
                    }

                    const top = parseInt(topMatch[1], 10);
                    const left = parseInt(leftMatch[1], 10);

                    seatData.push({
                        element: seat,
                        top,
                        left
                    });
                });

                // If no seats with positioning found, don't reorganize (clean HTML case)
                if (seatData.length === 0) {
                    return;
                }

                const rowsMap = {};
                seatData.forEach(({
                    top,
                    left,
                    element
                }) => {
                    if (!rowsMap[top]) rowsMap[top] = [];
                    rowsMap[top].push({
                        left,
                        element
                    });
                });

                const sortedTops = Object.keys(rowsMap).map(n => parseInt(n)).sort((a, b) => a - b);
                const result = {};
                let rowCounter = 1;

                for (let i = 0; i < sortedTops.length; i++) {
                    const top = sortedTops[i];
                    const seats = rowsMap[top];
                    seats.sort((a, b) => a.left - b.left);

                    const prevTop = sortedTops[i - 1];
                    const nextTop = sortedTops[i + 1];
                    const isAisle = (!prevTop && nextTop && nextTop - top > 30) ||
                        (!nextTop && prevTop && top - prevTop > 30) ||
                        (prevTop && nextTop && nextTop - top > 30 && top - prevTop > 30) ||
                        seats.length <= 1;

                    const rowName = isAisle ? 'aisle' : `row${rowCounter++}`;
                    result[rowName] = [];

                    for (const {
                            left,
                            element
                        }
                        of seats) {
                        result[rowName].push({
                            top,
                            left
                        });
                        element.classList.add(rowName);
                        element.removeAttribute('style');

                        // ---- ENHANCE SEAT CONTENT ----
                        const onclick = element.getAttribute('onclick') || '';
                        const match = onclick.match(
                            /AddRemoveSeat\(this,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
                        if (!match) continue;

                        const seatId = match[1];
                        const price = match[2];

                        element.setAttribute('data-seat', seatId);
                        element.setAttribute('data-price', price);
                    }
                }

                const seatContainers = document.querySelectorAll('.seatcontainer');
                seatContainers.forEach(deck => {
                    const newContainer = document.createDocumentFragment();
                    Object.entries(result).forEach(([rowName, seats]) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.className = `seat-row ${rowName}`;
                        rowDiv.style.display = 'flex';
                        rowDiv.style.justifyContent = 'flex-end';
                        rowDiv.style.gap = '6px';
                        rowDiv.style.marginBottom = '8px';

                        seats.sort((a, b) => a.left - b.left).forEach(({
                            left
                        }) => {
                            const seat = [...deck.children].find(el => el.classList
                                .contains(rowName) && !rowDiv
                                .contains(el));
                            if (seat) rowDiv.appendChild(seat);
                        });

                        newContainer.appendChild(rowDiv);
                    });

                    deck.innerHTML = '';
                    deck.appendChild(newContainer);
                });

            }, 0);
        });
    </script>
@endpush

```

```blade
<div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>

@push('style')
    <style>
        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            padding: 12px 8px;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
        }

        /* Prevent seat overflow */
        .busSeat {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 10px;
        }

        .busSeatlft {
            width: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* flex-shrink: 0; */
        }

        .upper,
        .lower {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            transform: rotate(180deg);
        }

        .upper::before {
            content: "Upper";
        }

        .lower::before {
            content: "Lower";
        }

        /* Enhanced steering wheel for lower deck */
        .outerlowerseat .busSeatlft::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 32px;
            height: 32px;
            background-color: #555;
            border-radius: 50%;
            border: 1px solid #777;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .outerlowerseat .busSeatlft::after {
            content: '';
            position: absolute;
            top: 21px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: radial-gradient(circle, #fff 30%, transparent 30%);
        }

        /* Make busSeatrgt a flexible grid to align rows and columns */
        .busSeatrgt {
            flex: 1;
            margin-left: 1rem;
            display: grid;
            grid-auto-rows: min-content;
            justify-items: end;
            gap: 8px;
            overflow: hidden;
            max-width: calc(100% - 60px);
        }

        /* Adjust seatcontainer to be flexible */
        .seatcontainer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            height: auto;
            overflow: hidden;
            max-width: 100%;
        }

        .seat-row {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* Seater Seats */
        .nseat {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Normal Sleeper Seats */
        .hseat {
            width: 56px;
            height: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Vertical Sleeper Seats */
        .vseat {
            width: 22px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Booked Seater Seats */
        .bseat {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Booked Normal Sleeper Seats */
        .bhseat {
            width: 56px;
            height: 22px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /*Booked Vertical Sleeper Seats */
        .bvseat {
            width: 22px;
            height: 56px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Hover effect on available seats */
        .nseat:hover,
        .vseat:hover,
        .hseat:hover {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Seat labels/numbers */
        .nseat::after,
        .hseat::after,
        .vseat::after {
            content: attr(data-seat);
            padding: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #666;
            pointer-events: none;
        }

        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #c8e6c9;
            border-color: #81c784;
            box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .hseat.selected::after,
        .vseat.selected::after {
            color: #fff;
        }

        .clr {
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Target all dynamically generated rows like .row1, .row2, etc. */
        [class^="row"] {
            display: flex;
            justify-content: flex-end;
            align-self: flex-end;
            gap: 6px;
            margin-bottom: 8px;
            position: relative;
            overflow: hidden;
            max-width: 100%;
        }

        [class^="row"].aisle {
            justify-content: space-between;
        }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const seatDivs = document.querySelectorAll('.seatcontainer > div');
                const seatData = [];

                seatDivs.forEach(seat => {
                    const style = seat.getAttribute('style') || '';
                    const topMatch = /top\s*:\s*(\d+)px/i.exec(style);
                    const leftMatch = /left\s*:\s*(\d+)px/i.exec(style);

                    // If seats don't have inline positioning (clean HTML), skip the reorganization
                    if (!topMatch || !leftMatch) {
                        return;
                    }

                    const top = parseInt(topMatch[1], 10);
                    const left = parseInt(leftMatch[1], 10);

                    seatData.push({
                        element: seat,
                        top,
                        left
                    });
                });

                // If no seats with positioning found, don't reorganize (clean HTML case)
                if (seatData.length === 0) {
                    return;
                }

                const rowsMap = {};
                seatData.forEach(({
                    top,
                    left,
                    element
                }) => {
                    if (!rowsMap[top]) rowsMap[top] = [];
                    rowsMap[top].push({
                        left,
                        element
                    });
                });

                const sortedTops = Object.keys(rowsMap).map(n => parseInt(n)).sort((a, b) => a - b);
                const result = {};
                let rowCounter = 1;

                for (let i = 0; i < sortedTops.length; i++) {
                    const top = sortedTops[i];
                    const seats = rowsMap[top];
                    seats.sort((a, b) => a.left - b.left);

                    const prevTop = sortedTops[i - 1];
                    const nextTop = sortedTops[i + 1];
                    const isAisle = (!prevTop && nextTop && nextTop - top > 30) ||
                        (!nextTop && prevTop && top - prevTop > 30) ||
                        (prevTop && nextTop && nextTop - top > 30 && top - prevTop > 30) ||
                        seats.length <= 1;

                    const rowName = isAisle ? 'aisle' : `row${rowCounter++}`;
                    result[rowName] = [];

                    for (const {
                            left,
                            element
                        }
                        of seats) {
                        result[rowName].push({
                            top,
                            left
                        });
                        element.classList.add(rowName);
                        element.removeAttribute('style');

                        // ---- ENHANCE SEAT CONTENT ----
                        const onclick = element.getAttribute('onclick') || '';
                        const match = onclick.match(
                            /AddRemoveSeat\(this,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
                        if (!match) continue;

                        const seatId = match[1];
                        const price = match[2];

                        element.setAttribute('data-seat', seatId);
                        element.setAttribute('data-price', price);
                    }
                }

                const seatContainers = document.querySelectorAll('.seatcontainer');
                seatContainers.forEach(deck => {
                    const newContainer = document.createDocumentFragment();
                    Object.entries(result).forEach(([rowName, seats]) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.className = `seat-row ${rowName}`;
                        rowDiv.style.display = 'flex';
                        rowDiv.style.justifyContent = 'flex-end';
                        rowDiv.style.gap = '6px';
                        rowDiv.style.marginBottom = '8px';

                        seats.sort((a, b) => a.left - b.left).forEach(({
                            left
                        }) => {
                            const seat = [...deck.children].find(el => el.classList
                                .contains(rowName) && !rowDiv
                                .contains(el));
                            if (seat) rowDiv.appendChild(seat);
                        });

                        newContainer.appendChild(rowDiv);
                    });

                    deck.innerHTML = '';
                    deck.appendChild(newContainer);
                });

            }, 0);
        });
    </script>
@endpush

```

```blade
<div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>

@push('style')
    <style>
        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            padding: 12px 8px;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
        }

        /* Prevent seat overflow */
        .busSeat {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 10px;
        }

        .busSeatlft {
            width: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* flex-shrink: 0; */
        }

        .upper,
        .lower {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            transform: rotate(180deg);
        }

        .upper::before {
            content: "Upper";
        }

        .lower::before {
            content: "Lower";
        }

        /* Enhanced steering wheel for lower deck */
        .outerlowerseat .busSeatlft::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 32px;
            height: 32px;
            background-color: #555;
            border-radius: 50%;
            border: 1px solid #777;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .outerlowerseat .busSeatlft::after {
            content: '';
            position: absolute;
            top: 21px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: radial-gradient(circle, #fff 30%, transparent 30%);
        }

        /* Make busSeatrgt a flexible grid to align rows and columns */
        .busSeatrgt {
            flex: 1;
            margin-left: 1rem;
            display: grid;
            grid-auto-rows: min-content;
            justify-items: end;
            gap: 8px;
        }

        /* Adjust seatcontainer to be flexible */
        .seatcontainer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            height: auto;
            overflow: hidden;
            max-width: 100%;
        }

        .seat-row {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* Seater Seats */
        .nseat {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Normal Sleeper Seats */
        .hseat {
            width: 56px;
            height: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Vertical Sleeper Seats */
        .vseat {
            width: 22px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Booked Seater Seats */
        .bseat {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Booked Normal Sleeper Seats */
        .bhseat {
            width: 56px;
            height: 22px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /*Booked Vertical Sleeper Seats */
        .bvseat {
            width: 22px;
            height: 56px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Hover effect on available seats */
        .nseat:hover,
        .vseat:hover,
        .hseat:hover {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Seat labels/numbers */
        .nseat::after,
        .hseat::after,
        .vseat::after {
            content: attr(data-seat);
            padding: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #666;
            pointer-events: none;
        }

        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #c8e6c9;
            border-color: #81c784;
            box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .hseat.selected::after,
        .vseat.selected::after {
            color: #fff;
        }

        .clr {
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Target all dynamically generated rows like .row1, .row2, etc. */
        [class^="row"] {
            display: flex;
            justify-content: flex-end;
            align-self: flex-end;
            gap: 6px;
            margin-bottom: 8px;
            position: relative;
            overflow: hidden;
            max-width: 100%;
        }

        [class^="row"].aisle {
            justify-content: space-between;
        }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const seatDivs = document.querySelectorAll('.seatcontainer > div');
                const seatData = [];

                seatDivs.forEach(seat => {
                    const style = seat.getAttribute('style') || '';
                    const topMatch = /top\s*:\s*(\d+)px/i.exec(style);
                    const leftMatch = /left\s*:\s*(\d+)px/i.exec(style);

                    // If seats don't have inline positioning (clean HTML), skip the reorganization
                    if (!topMatch || !leftMatch) {
                        return;
                    }

                    const top = parseInt(topMatch[1], 10);
                    const left = parseInt(leftMatch[1], 10);

                    seatData.push({
                        element: seat,
                        top,
                        left
                    });
                });

                // If no seats with positioning found, don't reorganize (clean HTML case)
                if (seatData.length === 0) {
                    return;
                }

                const rowsMap = {};
                seatData.forEach(({
                    top,
                    left,
                    element
                }) => {
                    if (!rowsMap[top]) rowsMap[top] = [];
                    rowsMap[top].push({
                        left,
                        element
                    });
                });

                const sortedTops = Object.keys(rowsMap).map(n => parseInt(n)).sort((a, b) => a - b);
                const result = {};
                let rowCounter = 1;

                for (let i = 0; i < sortedTops.length; i++) {
                    const top = sortedTops[i];
                    const seats = rowsMap[top];
                    seats.sort((a, b) => a.left - b.left);

                    const prevTop = sortedTops[i - 1];
                    const nextTop = sortedTops[i + 1];
                    const isAisle = (!prevTop && nextTop && nextTop - top > 30) ||
                        (!nextTop && prevTop && top - prevTop > 30) ||
                        (prevTop && nextTop && nextTop - top > 30 && top - prevTop > 30) ||
                        seats.length <= 1;

                    const rowName = isAisle ? 'aisle' : `row${rowCounter++}`;
                    result[rowName] = [];

                    for (const {
                            left,
                            element
                        }
                        of seats) {
                        result[rowName].push({
                            top,
                            left
                        });
                        element.classList.add(rowName);
                        element.removeAttribute('style');

                        // ---- ENHANCE SEAT CONTENT ----
                        const onclick = element.getAttribute('onclick') || '';
                        const match = onclick.match(
                            /AddRemoveSeat\(this,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
                        if (!match) continue;

                        const seatId = match[1];
                        const price = match[2];

                        element.setAttribute('data-seat', seatId);
                        element.setAttribute('data-price', price);
                    }
                }

                const seatContainers = document.querySelectorAll('.seatcontainer');
                seatContainers.forEach(deck => {
                    const newContainer = document.createDocumentFragment();
                    Object.entries(result).forEach(([rowName, seats]) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.className = `seat-row ${rowName}`;
                        rowDiv.style.display = 'flex';
                        rowDiv.style.justifyContent = 'flex-end';
                        rowDiv.style.gap = '6px';
                        rowDiv.style.marginBottom = '8px';

                        seats.sort((a, b) => a.left - b.left).forEach(({
                            left
                        }) => {
                            const seat = [...deck.children].find(el => el.classList
                                .contains(rowName) && !rowDiv
                                .contains(el));
                            if (seat) rowDiv.appendChild(seat);
                        });

                        newContainer.appendChild(rowDiv);
                    });

                    deck.innerHTML = '';
                    deck.appendChild(newContainer);
                });

            }, 0);
        });
    </script>
@endpush

```

```blade
<div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>

@push('style')
    <style>
        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            padding: 12px 8px;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
        }

        /* Prevent seat overflow */
        .busSeat {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 10px;
        }

        .busSeatlft {
            width: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* flex-shrink: 0; */
        }

        .upper,
        .lower {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            transform: rotate(180deg);
        }

        .upper::before {
            content: "Upper";
        }

        .lower::before {
            content: "Lower";
        }

        /* Enhanced steering wheel for lower deck */
        .outerlowerseat .busSeatlft::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 32px;
            height: 32px;
            background-color: #555;
            border-radius: 50%;
            border: 1px solid #777;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .outerlowerseat .busSeatlft::after {
            content: '';
            position: absolute;
            top: 21px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: radial-gradient(circle, #fff 30%, transparent 30%);
        }

        /* Make busSeatrgt a flexible grid to align rows and columns */
        .busSeatrgt {
            flex: 1;
            margin-left: 1rem;
            display: grid;
            grid-auto-rows: min-content;
            justify-items: end;
            gap: 8px;
        }

        /* Adjust seatcontainer to be flexible */
        .seatcontainer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            height: auto;
        }

        .seat-row {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* Seater Seats */
        .nseat {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Normal Sleeper Seats */
        .hseat {
            width: 56px;
            height: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Vertical Sleeper Seats */
        .vseat {
            width: 22px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Booked Seater Seats */
        .bseat {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Booked Normal Sleeper Seats */
        .bhseat {
            width: 56px;
            height: 22px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /*Booked Vertical Sleeper Seats */
        .bvseat {
            width: 22px;
            height: 56px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Hover effect on available seats */
        .nseat:hover,
        .vseat:hover,
        .hseat:hover {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Seat labels/numbers */
        .nseat::after,
        .hseat::after,
        .vseat::after {
            content: attr(data-seat);
            padding: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #666;
            pointer-events: none;
        }

        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #c8e6c9;
            border-color: #81c784;
            box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .hseat.selected::after,
        .vseat.selected::after {
            color: #fff;
        }

        .clr {
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Target all dynamically generated rows like .row1, .row2, etc. */
        [class^="row"] {
            display: flex;
            justify-content: flex-end;
            align-self: flex-end;
            gap: 6px;
            margin-bottom: 8px;
            position: relative;
            overflow: hidden;
            max-width: 100%;
        }

        [class^="row"].aisle {
            justify-content: space-between;
        }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const seatDivs = document.querySelectorAll('.seatcontainer > div');
                const seatData = [];

                seatDivs.forEach(seat => {
                    const style = seat.getAttribute('style') || '';
                    const topMatch = /top\s*:\s*(\d+)px/i.exec(style);
                    const leftMatch = /left\s*:\s*(\d+)px/i.exec(style);

                    // If seats don't have inline positioning (clean HTML), skip the reorganization
                    if (!topMatch || !leftMatch) {
                        return;
                    }

                    const top = parseInt(topMatch[1], 10);
                    const left = parseInt(leftMatch[1], 10);

                    seatData.push({
                        element: seat,
                        top,
                        left
                    });
                });

                // If no seats with positioning found, don't reorganize (clean HTML case)
                if (seatData.length === 0) {
                    return;
                }

                const rowsMap = {};
                seatData.forEach(({
                    top,
                    left,
                    element
                }) => {
                    if (!rowsMap[top]) rowsMap[top] = [];
                    rowsMap[top].push({
                        left,
                        element
                    });
                });

                const sortedTops = Object.keys(rowsMap).map(n => parseInt(n)).sort((a, b) => a - b);
                const result = {};
                let rowCounter = 1;

                for (let i = 0; i < sortedTops.length; i++) {
                    const top = sortedTops[i];
                    const seats = rowsMap[top];
                    seats.sort((a, b) => a.left - b.left);

                    const prevTop = sortedTops[i - 1];
                    const nextTop = sortedTops[i + 1];
                    const isAisle = (!prevTop && nextTop && nextTop - top > 30) ||
                        (!nextTop && prevTop && top - prevTop > 30) ||
                        (prevTop && nextTop && nextTop - top > 30 && top - prevTop > 30) ||
                        seats.length <= 1;

                    const rowName = isAisle ? 'aisle' : `row${rowCounter++}`;
                    result[rowName] = [];

                    for (const {
                            left,
                            element
                        }
                        of seats) {
                        result[rowName].push({
                            top,
                            left
                        });
                        element.classList.add(rowName);
                        element.removeAttribute('style');

                        // ---- ENHANCE SEAT CONTENT ----
                        const onclick = element.getAttribute('onclick') || '';
                        const match = onclick.match(
                            /AddRemoveSeat\(this,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
                        if (!match) continue;

                        const seatId = match[1];
                        const price = match[2];

                        element.setAttribute('data-seat', seatId);
                        element.setAttribute('data-price', price);
                    }
                }

                const seatContainers = document.querySelectorAll('.seatcontainer');
                seatContainers.forEach(deck => {
                    const newContainer = document.createDocumentFragment();
                    Object.entries(result).forEach(([rowName, seats]) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.className = `seat-row ${rowName}`;
                        rowDiv.style.display = 'flex';
                        rowDiv.style.justifyContent = 'flex-end';
                        rowDiv.style.gap = '6px';
                        rowDiv.style.marginBottom = '8px';

                        seats.sort((a, b) => a.left - b.left).forEach(({
                            left
                        }) => {
                            const seat = [...deck.children].find(el => el.classList
                                .contains(rowName) && !rowDiv
                                .contains(el));
                            if (seat) rowDiv.appendChild(seat);
                        });

                        newContainer.appendChild(rowDiv);
                    });

                    deck.innerHTML = '';
                    deck.appendChild(newContainer);
                });

            }, 0);
        });
    </script>
@endpush

```

```blade
<div class="bus">{!! renderSeatHTML($seatHtml, $parsedLayout ?? null, $isOperatorBus ?? false) !!}</div>

@push('style')
    <style>
        .bus {
            max-width: 460px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: auto;
            align-items: stretch;
        }

        .outerseat,
        .outerlowerseat {
            display: flex;
            padding: 12px 8px;
            position: relative;
            background: linear-gradient(145deg, #ebebeb, #e0e0e0);
            border: 1px solid #d5d5d5;
        }

        /* Prevent seat overflow */
        .busSeat {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 10px;
        }

        .busSeatlft {
            width: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* flex-shrink: 0; */
        }

        .upper,
        .lower {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            transform: rotate(180deg);
        }

        .upper::before {
            content: "Upper";
        }

        .lower::before {
            content: "Lower";
        }

        /* Enhanced steering wheel for lower deck */
        .outerlowerseat .busSeatlft::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 32px;
            height: 32px;
            background-color: #555;
            border-radius: 50%;
            border: 1px solid #777;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .outerlowerseat .busSeatlft::after {
            content: '';
            position: absolute;
            top: 21px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: radial-gradient(circle, #fff 30%, transparent 30%);
        }

        /* Make busSeatrgt a flexible grid to align rows and columns */
        .busSeatrgt {
            flex: 1;
            margin-left: 1rem;
            display: grid;
            grid-auto-rows: min-content;
            justify-items: end;
            gap: 8px;
        }

        /* Adjust seatcontainer to be flexible */
        .seatcontainer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
            height: auto;
        }

        .seat-row {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* Seater Seats */
        .nseat {
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Normal Sleeper Seats */
        .hseat {
            width: 56px;
            height: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Vertical Sleeper Seats */
        .vseat {
            width: 22px;
            height: 56px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Booked Seater Seats */
        .bseat {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Booked Normal Sleeper Seats */
        .bhseat {
            width: 56px;
            height: 22px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /*Booked Vertical Sleeper Seats */
        .bvseat {
            width: 22px;
            height: 56px;
            border-radius: 4px;
            border: 1px solid #bdbdbd;
            cursor: not-allowed;
            background-color: #e0e0e0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Hover effect on available seats */
        .nseat:hover,
        .vseat:hover,
        .hseat:hover {
            border-color: #4CAF50;
            background-color: #e8f5e9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Seat labels/numbers */
        .nseat::after,
        .hseat::after,
        .vseat::after {
            content: attr(data-seat);
            padding: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #666;
            pointer-events: none;
        }

        .nseat.selected,
        .hseat.selected,
        .vseat.selected {
            background-color: #c8e6c9;
            border-color: #81c784;
            box-shadow: 0 2px 6px rgba(233, 30, 99, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .hseat.selected::after,
        .vseat.selected::after {
            color: #fff;
        }

        .clr {
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Target all dynamically generated rows like .row1, .row2, etc. */
        [class^="row"] {
            display: flex;
            justify-content: flex-end;
            align-self: flex-end;
            gap: 6px;
            margin-bottom: 8px;
            position: relative;
        }

        [class^="row"].aisle {
            justify-content: space-between;
        }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const seatDivs = document.querySelectorAll('.seatcontainer > div');
                const seatData = [];

                seatDivs.forEach(seat => {
                    const style = seat.getAttribute('style') || '';
                    const topMatch = /top\s*:\s*(\d+)px/i.exec(style);
                    const leftMatch = /left\s*:\s*(\d+)px/i.exec(style);

                    // If seats don't have inline positioning (clean HTML), skip the reorganization
                    if (!topMatch || !leftMatch) {
                        return;
                    }

                    const top = parseInt(topMatch[1], 10);
                    const left = parseInt(leftMatch[1], 10);

                    seatData.push({
                        element: seat,
                        top,
                        left
                    });
                });

                // If no seats with positioning found, don't reorganize (clean HTML case)
                if (seatData.length === 0) {
                    return;
                }

                const rowsMap = {};
                seatData.forEach(({
                    top,
                    left,
                    element
                }) => {
                    if (!rowsMap[top]) rowsMap[top] = [];
                    rowsMap[top].push({
                        left,
                        element
                    });
                });

                const sortedTops = Object.keys(rowsMap).map(n => parseInt(n)).sort((a, b) => a - b);
                const result = {};
                let rowCounter = 1;

                for (let i = 0; i < sortedTops.length; i++) {
                    const top = sortedTops[i];
                    const seats = rowsMap[top];
                    seats.sort((a, b) => a.left - b.left);

                    const prevTop = sortedTops[i - 1];
                    const nextTop = sortedTops[i + 1];
                    const isAisle = (!prevTop && nextTop && nextTop - top > 30) ||
                        (!nextTop && prevTop && top - prevTop > 30) ||
                        (prevTop && nextTop && nextTop - top > 30 && top - prevTop > 30) ||
                        seats.length <= 1;

                    const rowName = isAisle ? 'aisle' : `row${rowCounter++}`;
                    result[rowName] = [];

                    for (const {
                            left,
                            element
                        }
                        of seats) {
                        result[rowName].push({
                            top,
                            left
                        });
                        element.classList.add(rowName);
                        element.removeAttribute('style');

                        // ---- ENHANCE SEAT CONTENT ----
                        const onclick = element.getAttribute('onclick') || '';
                        const match = onclick.match(
                            /AddRemoveSeat\(this,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
                        if (!match) continue;

                        const seatId = match[1];
                        const price = match[2];

                        element.setAttribute('data-seat', seatId);
                        element.setAttribute('data-price', price);
                    }
                }

                const seatContainers = document.querySelectorAll('.seatcontainer');
                seatContainers.forEach(deck => {
                    const newContainer = document.createDocumentFragment();
                    Object.entries(result).forEach(([rowName, seats]) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.className = `seat-row ${rowName}`;
                        rowDiv.style.display = 'flex';
                        rowDiv.style.justifyContent = 'flex-end';
                        rowDiv.style.gap = '6px';
                        rowDiv.style.marginBottom = '8px';

                        seats.sort((a, b) => a.left - b.left).forEach(({
                            left
                        }) => {
                            const seat = [...deck.children].find(el => el.classList
                                .contains(rowName) && !rowDiv
                                .contains(el));
                            if (seat) rowDiv.appendChild(seat);
                        });

                        newContainer.appendChild(rowDiv);
                    });

                    deck.innerHTML = '';
                    deck.appendChild(newContainer);
                });

            }, 0);
        });
    </script>
@endpush

```

Perfect! I've reverted all the changes that were breaking the seat selection feature. The seat layout CSS is now back to its original working state:

**Reverted Changes:**
- ✅ Removed `overflow: hidden` from `.bus` container
- ✅ Removed border, padding, and background styling that was interfering with seat functionality  
- ✅ Removed `overflow: hidden` and `max-width` constraints from `.busSeatrgt`
- ✅ Removed `overflow: hidden` and `max-width` from `.seatcontainer`
- ✅ Removed `overflow: hidden` and `max-width` from seat rows
- ✅ Removed border-radius from `.outerseat` and `.outerlowerseat`

The seat selection feature should now work properly again! The layout is back to its original functional state without the visual constraints that were interfering with the seat selection functionality.

