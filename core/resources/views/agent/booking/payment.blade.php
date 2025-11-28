@extends('agent.layouts.app')

@section('panel')
    @php
        $passengers = is_string($ticket->passengers_detail)
            ? json_decode($ticket->passengers_detail, true)
            : $ticket->passengers_detail;
        $seats = is_array($ticket->seats) ? $ticket->seats : json_decode($ticket->seats, true);
    @endphp

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="payment-card">
                    <!-- Header -->
                    <div class="payment-header">
                        <h5 class="mb-0">
                            <i class="las la-credit-card"></i>
                            @lang('Complete Payment')
                        </h5>
                    </div>

                    <!-- Main Content -->
                    <div class="payment-body">
                        <!-- Title -->
                        <h6 class="section-title">@lang('Booking Details')</h6>

                        <!-- Ticket Info Grid -->
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">@lang('Ticket ID')</span>
                                <span class="info-value highlight">{{ $ticket->id }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">@lang('Route')</span>
                                <span class="info-value">{{ $ticket->origin_city }} → {{ $ticket->destination_city }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">@lang('Journey Date')</span>
                                <span
                                    class="info-value">{{ \Carbon\Carbon::parse($ticket->date_of_journey)->format('M d, Y') }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">@lang('Seats')</span>
                                <span class="info-value">{{ is_array($seats) ? implode(', ', $seats) : $seats }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">@lang('Passengers')</span>
                                <span class="info-value">{{ is_array($passengers) ? count($passengers) : 1 }}</span>
                            </div>
                        </div>

                        <!-- Journey Details Section -->
                        @if (isset($ticket->travel_name) || isset($ticket->bus_type))
                            <div class="detail-section">
                                <h6 class="section-subtitle">@lang('Journey Details')</h6>
                                <div class="journey-grid">
                                    @if (isset($ticket->travel_name))
                                        <div class="detail-item">
                                            <span class="detail-label">@lang('Travel Name')</span>
                                            <span class="detail-value">{{ $ticket->travel_name }}</span>
                                        </div>
                                    @endif
                                    @if (isset($ticket->bus_type))
                                        <div class="detail-item">
                                            <span class="detail-label">@lang('Bus Type')</span>
                                            <span class="detail-value">{{ $ticket->bus_type }}</span>
                                        </div>
                                    @endif
                                    @if (isset($ticket->departure_time))
                                        <div class="detail-item">
                                            <span class="detail-label">@lang('Departure Time')</span>
                                            <span class="detail-value">{{ $ticket->departure_time }}</span>
                                        </div>
                                    @endif
                                    @if (isset($ticket->arrival_time))
                                        <div class="detail-item">
                                            <span class="detail-label">@lang('Arrival Time')</span>
                                            <span class="detail-value">{{ $ticket->arrival_time }}</span>
                                        </div>
                                    @endif
                                    @if (isset($ticket->boarding_point))
                                        <div class="detail-item">
                                            <span class="detail-label">@lang('Boarding Point')</span>
                                            <span class="detail-value">{{ $ticket->boarding_point }}</span>
                                        </div>
                                    @endif
                                    @if (isset($ticket->dropping_point))
                                        <div class="detail-item">
                                            <span class="detail-label">@lang('Dropping Point')</span>
                                            <span class="detail-value">{{ $ticket->dropping_point }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Passenger Details -->
                        @if (is_array($passengers) && count($passengers) > 0)
                            <div class="detail-section">
                                <h6 class="section-subtitle">@lang('Passenger Details')</h6>
                                <div class="passenger-list">
                                    @foreach ($passengers as $index => $passenger)
                                        <div class="passenger-item">
                                            <div>
                                                <div class="passenger-name">
                                                    {{ $passenger['name'] ?? ($passenger['FirstName'] ?? '') . ' ' . ($passenger['LastName'] ?? '') }}
                                                </div>
                                                <div class="passenger-meta">
                                                    @if (isset($passenger['age']) || isset($passenger['Age']))
                                                        Age: {{ $passenger['age'] ?? $passenger['Age'] }}
                                                    @endif
                                                    @if (isset($passenger['gender']) || isset($passenger['Gender']))
                                                        | Gender:
                                                        {{ is_numeric($passenger['gender'] ?? $passenger['Gender']) ? (($passenger['gender'] ?? $passenger['Gender']) == 1 ? 'Male' : 'Female') : $passenger['gender'] ?? $passenger['Gender'] }}
                                                    @endif
                                                </div>
                                            </div>
                                            @if (isset($seats[$index]))
                                                <span class="seat-badge">Seat {{ $seats[$index] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Fare Breakdown & Cancellation -->
                        <div class="fare-cancel-wrapper">
                            <!-- Cancellation Policy -->
                            @if (isset($cancellation_policy) && count($cancellation_policy) > 0)
                                <div class="cancel-section">
                                    <h6 class="section-subtitle">@lang('Cancellation Policy')</h6>
                                    <ul class="policy-list">
                                        @foreach ($cancellation_policy as $policy)
                                            <li>{{ $policy['PolicyString'] }} - {{ $policy['CancellationCharge'] }}%
                                                charge</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Fare Breakdown -->
                            <div class="fare-section">
                                <div class="fare-item">
                                    <span class="fare-label">@lang('Base Fare')</span>
                                    <span class="fare-value">₹{{ number_format($ticket->sub_total, 2) }}</span>
                                </div>
                                @if (isset($ticket->agent_commission_amount) && $ticket->agent_commission_amount > 0)
                                    <div class="fare-item">
                                        <span class="fare-label">@lang('Agent Commission')</span>
                                        <span
                                            class="fare-value">₹{{ number_format($ticket->agent_commission_amount, 2) }}</span>
                                    </div>
                                @endif
                                @if (isset($ticket->service_charge) && $ticket->service_charge > 0)
                                    <div class="fare-item">
                                        <span class="fare-label">@lang('Service Charge')</span>
                                        <span class="fare-value">₹{{ number_format($ticket->service_charge, 2) }}</span>
                                    </div>
                                @endif
                                @if (isset($ticket->platform_fee) && $ticket->platform_fee > 0)
                                    <div class="fare-item">
                                        <span class="fare-label">@lang('Platform Fee')</span>
                                        <span class="fare-value">₹{{ number_format($ticket->platform_fee, 2) }}</span>
                                    </div>
                                @endif
                                @if (isset($ticket->gst) && $ticket->gst > 0)
                                    <div class="fare-item">
                                        <span class="fare-label">@lang('GST')</span>
                                        <span class="fare-value">₹{{ number_format($ticket->gst, 2) }}</span>
                                    </div>
                                @endif
                                <div class="fare-item fare-total">
                                    <span class="fare-label">@lang('Total Amount')</span>
                                    <span
                                        class="fare-value">₹{{ number_format($ticket->total_amount + ($ticket->agent_commission_amount ?? 0), 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Button -->
                        <div class="payment-action">
                            <button id="razorpay-button" class="btn-pay">
                                <i class="las la-lock"></i>
                                @lang('Pay Now')
                                ₹{{ number_format($ticket->total_amount + ($ticket->agent_commission_amount ?? 0), 2) }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.getElementById('razorpay-button').onclick = function(e) {
            e.preventDefault();

            var options = {
                "key": "{{ $razorpay_key }}",
                "amount": "{{ $amount }}", // Amount in paise
                "currency": "{{ $currency }}",
                "name": "{{ config('app.name') }}",
                "description": "Bus Ticket Booking - {{ $ticket->from_city }} to {{ $ticket->to_city }}",
                "order_id": "{{ $order_id }}",
                "handler": function(response) {
                    // Payment successful - verify and complete booking
                    fetch("{{ route('agent.booking.confirm') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_signature: response.razorpay_signature,
                                ticket_id: {{ $ticket->id }}
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message
                                alert('Payment successful! Ticket booked.');

                                // Open ticket in new tab
                                window.open("{{ url('/users/print-ticket') }}/" + data.ticket_id,
                                    '_blank');

                                // Redirect current tab to my bookings
                                window.location.href = "{{ route('agent.bookings') }}";
                            } else {
                                alert('Payment verification failed: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while processing your payment.');
                        });
                },
                "prefill": {
                    "email": "{{ auth('agent')->user()->email }}",
                    "contact": "{{ auth('agent')->user()->phone }}"
                },
                "theme": {
                    "color": "#009688"
                },
                "modal": {
                    "ondismiss": function() {
                        console.log('Payment cancelled by user');
                    }
                }
            };

            var rzp = new Razorpay(options);
            rzp.open();
        };
    </script>
@endpush

@push('style')
    <style>
        /* Payment Card Container */
        .payment-card {
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .payment-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: #fff;
            padding: 20px 25px;
            border-bottom: 3px solid #0056b3;
        }

        .payment-header h5 {
            color: #fff;
            margin: 0;
            font-weight: 600;
        }

        .payment-body {
            padding: 25px;
        }

        /* Section Titles */
        .section-title {
            color: #333;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }

        .section-subtitle {
            color: #007bff;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }

        .info-value.highlight {
            color: #007bff;
            font-size: 16px;
        }

        /* Detail Section */
        .detail-section {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .journey-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .detail-value {
            font-size: 13px;
            color: #333;
            font-weight: 600;
        }

        /* Passenger List */
        .passenger-list {
            display: grid;
            gap: 10px;
        }

        .passenger-item {
            background: #fff;
            padding: 12px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e0e0e0;
        }

        .passenger-name {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 3px;
        }

        .passenger-meta {
            font-size: 12px;
            color: #666;
        }

        .seat-badge {
            background: #007bff;
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Fare & Cancellation Wrapper */
        .fare-cancel-wrapper {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .cancel-section {
            flex: 1;
            min-width: 250px;
        }

        .policy-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .policy-list li {
            font-size: 11px;
            color: #666;
            padding: 4px 0 4px 15px;
            position: relative;
            line-height: 1.5;
        }

        .policy-list li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #007bff;
            font-weight: bold;
        }

        /* Fare Section */
        .fare-section {
            flex-shrink: 0;
            min-width: 250px;
            max-width: 300px;
        }

        .fare-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
        }

        .fare-label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }

        .fare-value {
            font-size: 14px;
            color: #333;
            font-weight: 600;
            text-align: right;
        }

        .fare-total {
            border-top: 2px solid #007bff;
            margin-top: 8px;
            padding-top: 10px;
        }

        .fare-total .fare-label {
            font-size: 14px;
            font-weight: 700;
            color: #007bff;
        }

        .fare-total .fare-value {
            font-size: 18px;
            font-weight: 700;
            color: #007bff;
        }

        /* Payment Action */
        .payment-action {
            text-align: center;
            margin-top: 30px;
        }

        .btn-pay {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: #fff;
            border: none;
            padding: 15px 50px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
        }

        .btn-pay i {
            margin-right: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .payment-body {
                padding: 15px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .journey-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .fare-cancel-wrapper {
                flex-direction: column;
            }

            .fare-section {
                max-width: 100%;
            }

            .btn-pay {
                width: 100%;
                padding: 12px 30px;
                font-size: 16px;
            }
        }
    </style>
@endpush
