@extends('agent.layouts.app')

@section('panel')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="las la-credit-card"></i>
                            @lang('Complete Payment')
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Booking Summary -->
                        <div class="booking-summary mb-4">
                            <h6>@lang('Booking Details')</h6>
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>@lang('Ticket ID'):</strong></td>
                                        <td>{{ $ticket->id }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>@lang('Route'):</strong></td>
                                        <td>{{ $ticket->origin_city }} → {{ $ticket->destination_city }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>@lang('Journey Date'):</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($ticket->date_of_journey)->format('M d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>@lang('Seats'):</strong></td>
                                        <td>{{ is_array($ticket->seats) ? implode(', ', $ticket->seats) : $ticket->seats }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>@lang('Passengers'):</strong></td>
                                        <td>
                                            @php
                                                $passengers = is_string($ticket->passengers_detail)
                                                    ? json_decode($ticket->passengers_detail, true)
                                                    : $ticket->passengers_detail;
                                            @endphp
                                            {{ is_array($passengers) ? count($passengers) : 1 }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>@lang('Base Fare'):</strong></td>
                                        <td>₹{{ number_format($ticket->subtotal, 2) }}</td>
                                    </tr>
                                    @if ($ticket->commission_amount > 0)
                                        <tr>
                                            <td><strong>@lang('Commission'):</strong></td>
                                            <td class="text-success">₹{{ number_format($ticket->commission_amount, 2) }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($ticket->platform_fee > 0)
                                        <tr>
                                            <td><strong>@lang('Platform Fee'):</strong></td>
                                            <td>₹{{ number_format($ticket->platform_fee, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if ($ticket->gst > 0)
                                        <tr>
                                            <td><strong>@lang('GST'):</strong></td>
                                            <td>₹{{ number_format($ticket->gst, 2) }}</td>
                                        </tr>
                                    @endif
                                    <tr class="border-top">
                                        <td><strong>@lang('Total Amount'):</strong></td>
                                        <td><strong>₹{{ number_format($ticket->total_amount, 2) }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Payment Button -->
                        <div class="text-center">
                            <button id="razorpay-button" class="btn btn-primary btn-lg px-5">
                                <i class="las la-lock"></i>
                                @lang('Pay Now') ₹{{ number_format($ticket->total_amount, 2) }}
                            </button>
                        </div>

                        <!-- Cancellation Policy -->
                        @if (isset($cancellation_policy) && count($cancellation_policy) > 0)
                            <div class="mt-4">
                                <h6>@lang('Cancellation Policy')</h6>
                                <div class="alert alert-info">
                                    <ul class="mb-0">
                                        @foreach ($cancellation_policy as $policy)
                                            <li>
                                                {{ $policy['PolicyString'] }}
                                                - {{ $policy['CancellationCharge'] }}% charge
                                                ({{ $policy['TimeBeforeDept'] }})
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
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
                                // Redirect to print ticket page using the PNR from response
                                window.location.href = "{{ url('/user/booked-ticket/print') }}/" + data
                                    .ticket_id;
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
        .booking-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .table-borderless td {
            padding: 8px 0;
        }

        .text-success {
            color: #28a745 !important;
        }
    </style>
@endpush
