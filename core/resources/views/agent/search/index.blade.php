@extends('agent.layouts.app')

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
                <form method="GET" action="{{ route('agent.search.results') }}" id="searchForm">
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
                                value="{{ old('date_of_journey', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                            @error('date_of_journey')
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

        <!-- Commission Information -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="las la-percentage text-success"></i>
                    @lang('Commission Information')
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="commission-preview">
                            <h6 class="text-muted">@lang('Commission Structure')</h6>
                            <div id="commission-preview-content">
                                <p class="text-muted">@lang('Commission will be calculated based on booking amount')</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="commission-calculator">
                            <h6 class="text-muted">@lang('Calculate Commission')</h6>
                            <div class="input-group">
                                <input type="number" class="form-control" id="commission-amount"
                                    placeholder="@lang('Enter booking amount')" min="0" step="0.01">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="calculate-commission">
                                        @lang('Calculate')
                                    </button>
                                </div>
                            </div>
                            <div id="commission-result" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure jQuery is available
            if (typeof $ === 'undefined') {
                console.error('jQuery is not loaded');
                return;
            }

            // Wait for jQuery to be fully loaded
            $(document).ready(function() {
                // Initialize Select2
                $('.select2').select2({
                    placeholder: function() {
                        return $(this).data('placeholder') || '@lang('Select an option')';
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

                // Commission calculation
                $('#calculate-commission').click(function() {
                    const amount = $('#commission-amount').val();
                    if (!amount || amount <= 0) {
                        $('#commission-result').html(
                            '<div class="alert alert-warning">@lang('Please enter a valid amount')</div>');
                        return;
                    }

                    $.ajax({
                        url: '{{ route('agent.api.commission.calculate') }}',
                        method: 'POST',
                        data: {
                            booking_amount: amount,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                const commission = response.commission;
                                const netAmount = response.net_amount_paid;
                                const totalCommission = response
                                    .total_commission_earned;

                                let resultHtml = '<div class="alert alert-success">';
                                resultHtml += '<strong>@lang('Commission Details:')</strong><br>';
                                resultHtml +=
                                    `@lang('Commission Amount:'): ₹${totalCommission.toFixed(2)}<br>`;
                                resultHtml +=
                                    `@lang('Commission Type:'): ${commission.commission_type}<br>`;
                                if (commission.commission_percentage > 0) {
                                    resultHtml +=
                                        `@lang('Commission Rate:'): ${commission.commission_percentage}%<br>`;
                                }
                                resultHtml +=
                                    `@lang('Net Amount to Pay:'): ₹${netAmount.toFixed(2)}<br>`;
                                resultHtml += '</div>';

                                $('#commission-result').html(resultHtml);
                            } else {
                                $('#commission-result').html(
                                    '<div class="alert alert-danger">@lang('Error calculating commission')</div>'
                                );
                            }
                        },
                        error: function() {
                            $('#commission-result').html(
                                '<div class="alert alert-danger">@lang('Error calculating commission')</div>'
                            );
                        }
                    });
                });

                // Form validation
                $('#searchForm').on('submit', function(e) {
                    const fromCity = $('#origin_city_id').val();
                    const toCity = $('#destination_city_id').val();

                    if (!fromCity || !toCity) {
                        e.preventDefault();
                        alert('Please select departure and destination cities');
                        return false;
                    }

                    if (fromCity === toCity) {
                        e.preventDefault();
                        alert('Departure and destination cities cannot be the same');
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
