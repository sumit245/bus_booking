@extends('operator.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">{{ $pageTitle }}</h4>
                        <a href="{{ route('operator.buses.seat-layouts.index', $bus) }}" class="btn btn-secondary btn-sm">
                            <i class="las la-arrow-left"></i> @lang('Back')
                        </a>
                        {{-- <span class="ml-3"> --}}

                        {{-- </span> --}}
                    </div>
                    <div>
                        <a href="{{ route('operator.buses.seat-layouts.edit', [$bus, $seatLayout]) }}"
                            class="btn btn-warning btn-sm">
                            <i class="las la-edit"></i> @lang('Edit')
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Layout Details -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="text-primary mb-3">@lang('Layout Details')</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Layout Name')</strong><br>{{ $seatLayout->layout_name }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Bus Type')</strong><br>
                                        <span
                                            class="badge badge-{{ $seatLayout->deck_type == 'single' ? 'info' : 'primary' }}">
                                            {{ ucfirst($seatLayout->deck_type) }} Decker
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Total Seats')</strong><br>{{ $seatLayout->total_seats }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Status')</strong><br>
                                        @if ($seatLayout->is_active)
                                            <span class="badge badge-success">@lang('Active')</span>
                                        @else
                                            <span class="badge badge-secondary">@lang('Inactive')</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="row mt-2">
                                @if ($seatLayout->deck_type == 'double')
                                    <div class="col-md-3">
                                        <p class="mb-2">
                                            <strong>@lang('Upper Deck Seats')</strong><br>{{ $seatLayout->upper_deck_seats }}
                                        </p>
                                    </div>
                                @endif
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang($seatLayout->deck_type == 'single' ? 'Main Deck Seats' : 'Lower Deck Seats')</strong><br>{{ $seatLayout->lower_deck_seats }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Created')</strong><br>{{ $seatLayout->created_at->format('M d, Y H:i') }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Updated')</strong><br>{{ $seatLayout->updated_at->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seat Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="text-primary mb-3">@lang('Seat Statistics')</h5>
                            @php
                                $seatStats = [
                                    'seater' => 0,
                                    'sleeper' => 0,
                                    'total_price' => 0,
                                ];

                                if ($seatLayout->layout_data) {
                                    foreach (['upper_deck', 'lower_deck'] as $deck) {
                                        if (isset($seatLayout->layout_data[$deck]['seats'])) {
                                            foreach ($seatLayout->layout_data[$deck]['seats'] as $seat) {
                                                if ($seat['category'] === 'seater') {
                                                    $seatStats['seater']++;
                                                } else {
                                                    $seatStats['sleeper']++;
                                                }
                                                $seatStats['total_price'] += $seat['price'] ?? 0;
                                            }
                                        }
                                    }
                                }
                            @endphp
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Seater Seats')</strong><br>{{ $seatStats['seater'] }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Sleeper Seats')</strong><br>{{ $seatStats['sleeper'] }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Average Price')</strong><br>₹{{ $seatLayout->total_seats > 0 ? number_format($seatStats['total_price'] / $seatLayout->total_seats, 2) : '0.00' }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Total Revenue Potential')</strong><br>₹{{ number_format($seatStats['total_price'], 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Layout Preview -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">@lang('Layout Preview')</h5>
                            <div class="bus-layout-preview">
                                {!! $seatLayout->html_layout !!}
                            </div>
                        </div>
                    </div>

                    <!-- Generated HTML Layout -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">@lang('Generated HTML Layout')</h5>
                            <small class="text-muted">This is the HTML structure that will be used by the booking
                                system</small>
                            <div class="border p-3 bg-light mt-2" style="max-height: 400px; overflow-y: auto;">
                                <pre class="text-dark mb-0" style="white-space: pre-wrap; font-size: 12px;">{{ $seatLayout->html_layout }}</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Processed Layout Data -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">@lang('Processed Layout Data')</h5>
                            <small class="text-muted">This is the JSON structure used by the frontend</small>
                            <div class="border p-3 bg-light mt-2" style="max-height: 400px; overflow-y: auto;">
                                <pre class="text-dark mb-0" style="white-space: pre-wrap; font-size: 12px;">{{ json_encode($seatLayout->processed_layout, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        /* Bus Layout Preview Styles */
        .bus-layout-preview .outerseat,
        .bus-layout-preview .outerlowerseat {
            display: flex;
            margin-bottom: 20px;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: visible;
            min-height: fit-content;
            height: auto;
        }

        .bus-layout-preview .outerlowerseat {
            margin-bottom: 0;
        }

        .bus-layout-preview .busSeatlft {
            width: 60px;
            background-color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            flex-shrink: 0;
            min-height: 200px;
        }

        .bus-layout-preview .busSeatrgt {
            flex: 1;
            position: relative;
            padding: 10px;
            min-height: 200px;
            height: auto;
        }

        .bus-layout-preview .seatcontainer {
            position: relative;
            min-height: 200px;
            height: auto;
            width: 100%;
        }

        .bus-layout-preview .nseat,
        .bus-layout-preview .hseat,
        .bus-layout-preview .vseat {
            position: absolute;
            border: 2px solid;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1.1;
            padding: 3px;
            box-sizing: border-box;
        }

        .bus-layout-preview .nseat {
            width: 45px;
            height: 40px;
            background-color: #fff;
            border-color: #666;
            color: #333;
        }

        .bus-layout-preview .hseat {
            width: 60px;
            height: 40px;
            background-color: #e3f2fd;
            border-color: #1976d2;
            color: #1976d2;
        }

        .bus-layout-preview .vseat {
            width: 40px;
            height: 80px;
            background-color: #f3e5f5;
            border-color: #7b1fa2;
            color: #7b1fa2;
        }

        /* Deck Labels */
        .deck-section {
            margin-bottom: 20px;
        }

        .deck-label {
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .layout-preview {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .deck-preview {
            margin-bottom: 30px;
        }

        .deck-title {
            font-weight: bold;
            color: #0e0e0f;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #dee2e6;
        }

        .deck-container {
            background-color: #fff;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            min-height: 200px;
        }

        .seat-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 5px;
        }

        .seat-preview-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid #333;
            border-radius: 4px;
            padding: 5px;
            min-width: 40px;
            min-height: 35px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .seat-preview-item:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .seat-preview-item.nseat {
            background-color: #fff;
            border-color: #666;
            color: #333;
        }

        .seat-preview-item.hseat {
            background-color: #e3f2fd;
            border-color: #1976d2;
            color: #1976d2;
            min-width: 50px;
        }

        .seat-preview-item.vseat {
            background-color: #f3e5f5;
            border-color: #7b1fa2;
            color: #7b1fa2;
            min-height: 45px;
        }

        .seat-label {
            font-size: 10px;
            font-weight: bold;
            line-height: 1;
        }

        .seat-price {
            font-size: 8px;
            opacity: 0.8;
            line-height: 1;
        }

        /* Legend */
        .legend {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .legend-color {
            width: 20px;
            height: 15px;
            margin-right: 8px;
            border: 1px solid #333;
            border-radius: 2px;
        }
    </style>
@endpush
