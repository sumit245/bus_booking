@extends('admin.layouts.app')

@section('panel')
    <div class="container-fluid">
        <!-- Search Form -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="las la-search text-primary"></i>
                    @lang('Search Buses')
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.booking.results') }}" id="searchForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="origin_city_id" class="form-label">@lang('From') *</label>
                            <select class="form-control select2" id="origin_city_id" name="OriginId" required>
                                <option value="">@lang('Select Departure City')</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->city_id }}"
                                        {{ old('OriginId') == $city->city_id ? 'selected' : '' }}>
                                        {{ $city->city_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('OriginId')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="destination_city_id" class="form-label">@lang('To') *</label>
                            <select class="form-control select2" id="destination_city_id" name="DestinationId" required>
                                <option value="">@lang('Select Destination City')</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->city_id }}"
                                        {{ old('DestinationId') == $city->city_id ? 'selected' : '' }}>
                                        {{ $city->city_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('DestinationId')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="date_of_journey" class="form-label">@lang('Journey Date') *</label>
                            <input type="date" class="form-control @error('DateOfJourney') is-invalid @enderror"
                                id="date_of_journey" name="DateOfJourney"
                                value="{{ old('DateOfJourney', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                            @error('DateOfJourney')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="passengers" class="form-label">@lang('Passengers') *</label>
                            <select class="form-control" id="passengers" name="passengers" required>
                                @for ($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}"
                                        {{ old('passengers', 1) == $i ? 'selected' : '' }}>
                                        {{ $i }} @lang('Passenger'){{ $i > 1 ? 's' : '' }}
                                    </option>
                                @endfor
                            </select>
                            @error('passengers')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5" id="searchBtn">
                                <i class="las la-search"></i>
                                @lang('Search Buses')
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Search Options -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="las la-bolt text-warning"></i>
                    @lang('Quick Search')
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <button class="btn btn-outline-primary btn-block quick-search-btn" data-days="0">
                            <i class="las la-calendar-day"></i>
                            @lang('Today')
                        </button>
                    </div>
                    <div class="col-md-4 mb-3">
                        <button class="btn btn-outline-success btn-block quick-search-btn" data-days="1">
                            <i class="las la-calendar-plus"></i>
                            @lang('Tomorrow')
                        </button>
                    </div>
                    <div class="col-md-4 mb-3">
                        <button class="btn btn-outline-info btn-block quick-search-btn" data-days="7">
                            <i class="las la-calendar-week"></i>
                            @lang('Next Week')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof $ === 'undefined') {
                console.error('jQuery is not loaded');
                return;
            }

            $(document).ready(function() {
                // Initialize Select2 with custom matcher for city search (match initial 3 characters only)
                $('.select2').select2({
                    placeholder: function() {
                        return $(this).data('placeholder') || '@lang('Select an option')';
                    },
                    matcher: function(params, data) {
                        // If search term is empty, show all options
                        if (!params.term || params.term.trim() === '') {
                            return data;
                        }
                        
                        // Normalize search term
                        const term = params.term.toLowerCase().trim();
                        
                        // If search term is less than 3 characters, show all options (user is still typing)
                        if (term.length < 3) {
                            return data;
                        }
                        
                        // Get text from multiple possible sources
                        let text = '';
                        if (data.text) {
                            text = data.text;
                        } else if (data.element) {
                            // For option elements, get text from the element
                            if (data.element.textContent) {
                                text = data.element.textContent;
                            } else if ($(data.element).length) {
                                text = $(data.element).text();
                            } else if (data.element.text) {
                                text = data.element.text;
                            }
                        } else if (data.id && data.id !== '') {
                            // Fallback: try to get text from the option element by value
                            // Find the select element that contains this Select2 instance
                            const $select = $('#origin_city_id, #destination_city_id').filter(function() {
                                return $(this).data('select2') !== undefined;
                            }).first();
                            if ($select.length) {
                                const $option = $select.find('option[value="' + data.id + '"]');
                                if ($option.length) {
                                    text = $option.text();
                                }
                            }
                        }
                        
                        // Normalize text for comparison
                        text = (text || '').toLowerCase().trim();
                        
                        // Only match if text starts with search term (initial 3+ characters)
                        if (text && text.startsWith(term)) {
                            return data;
                        }
                        
                        // No match
                        return null;
                    }
                });

                // Quick search functionality
                $('.quick-search-btn').click(function() {
                    const days = $(this).data('days');
                    const date = new Date();
                    date.setDate(date.getDate() + days);
                    const dateString = date.toISOString().split('T')[0];

                    $('#date_of_journey').val(dateString);
                });

                // Form validation
                $('#searchForm').on('submit', function(e) {
                    const fromCity = $('#origin_city_id').val();
                    const toCity = $('#destination_city_id').val();

                    if (!fromCity || !toCity) {
                        e.preventDefault();
                        notify('error', 'Please select departure and destination cities');
                        return false;
                    }

                    if (fromCity === toCity) {
                        e.preventDefault();
                        notify('error', 'Departure and destination cities cannot be the same');
                        return false;
                    }

                    // Show loading state
                    $('#searchBtn').html(
                        '<i class="las la-spinner la-spin"></i> @lang('Searching...')').prop(
                        'disabled', true);
                });

                // Auto-focus first field
                $('#origin_city_id').focus();
            });
        });
    </script>
@endpush

