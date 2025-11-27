@extends($activeTemplate . $layout)

@section('content')
    <div class="booking-details-container">
        <!-- Action Bar (Hidden when printing) -->
        <div class="action-bar no-print">
            <a href="{{ route('user.dashboard') }}" class="btn-back">
                <i class="las la-arrow-left"></i>
                Back to Dashboard
            </a>
            <div class="action-buttons">
                @if ($ticket->status == 1)
                    <button type="button" class="btn-cancel" onclick="cancelBooking({{ $ticket->id }})">
                        <i class="las la-times"></i>
                        Cancel Booking
                    </button>
                @endif
                <button type="button" class="btn-print" onclick="window.print()">
                    <i class="las la-print"></i>
                    Print Ticket
                </button>
            </div>
        </div>

        <!-- Modern Ticket Layout -->
        <div class="ticket-container">
            <!-- Header -->
            <div class="ticket-header">
                <div class="header-left">
                    @if (isset($logoUrl) && $logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="logo">
                    @endif
                </div>
                <div class="header-right">
                    <p class="subtitle">E-Ticket / Reservation Voucher</p>
                    @if (isset($ticket->status))
                        <span
                            class="status-badge {{ $ticket->status == 1 ? 'confirmed' : ($ticket->status == 3 ? 'cancelled' : 'pending') }}">
                            {{ $ticket->status == 1 ? 'Confirmed' : ($ticket->status == 3 ? 'Cancelled' : 'Pending') }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Ticket Info -->
            <div class="ticket-info">
                <div class="info-item">
                    <span class="info-label">PNR Number</span>
                    <span class="info-value highlight">{{ $ticket->pnr_number ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Booking Date</span>
                    <span
                        class="info-value">{{ isset($ticket->created_at) ? \Carbon\Carbon::parse($ticket->created_at)->format('d M Y, h:i A') : 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Passenger Name</span>
                    <span class="info-value">{{ $ticket->passenger_name }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Contact Number</span>
                    <span
                        class="info-value">{{ $ticket->passenger_phone ?? ($ticket->passengers[0]['Phoneno'] ?? 'N/A') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Seats</span>
                    <span class="info-value">
                        @if (isset($ticket->seats) && is_array($ticket->seats))
                            {{ implode(', ', $ticket->seats) }}
                        @elseif(isset($ticket->seats))
                            {{ $ticket->seats }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Amount</span>
                    <span
                        class="info-value highlight">₹{{ number_format($ticket->total_amount ?? ($ticket->total_fare ?? ($ticket->sub_total ?? 0)), 2) }}</span>
                </div>
            </div>

            <!-- Journey Details -->
            <div class="journey-section">
                <h3>Journey Details</h3>
                <div class="journey-details">
                    <div class="journey-item">
                        <span class="journey-label">Travel Name</span>
                        <span class="journey-value">{{ $ticket->travel_name ?? 'N/A' }}</span>
                    </div>
                    <div class="journey-item">
                        <span class="journey-label">Bus Type</span>
                        <span class="journey-value">{{ $ticket->bus_type ?? 'N/A' }}</span>
                    </div>
                    <div class="journey-item">
                        <span class="journey-label">Date of Journey</span>
                        <span
                            class="journey-value">{{ isset($ticket->date_of_journey) ? \Carbon\Carbon::parse($ticket->date_of_journey)->format('d M Y, l') : 'N/A' }}</span>
                    </div>
                    <div class="journey-item">
                        <span class="journey-label">Departure Time</span>
                        <span class="journey-value">{{ $ticket->departure_time ?? 'N/A' }}</span>
                    </div>
                    <div class="journey-item">
                        <span class="journey-label">Arrival Time</span>
                        <span class="journey-value">{{ $ticket->arrival_time ?? 'N/A' }}</span>
                    </div>
                    <div class="journey-item">
                        <span class="journey-label">Duration</span>
                        <span class="journey-value">{{ $ticket->duration ?? 'N/A' }}</span>
                    </div>
                    <div class="journey-item">
                        <span class="journey-label">Boarding Point</span>
                        <span class="journey-value">{{ $ticket->boarding_point ?? 'N/A' }}</span>
                    </div>
                    <div class="journey-item">
                        <span class="journey-label">Dropping Point</span>
                        <span class="journey-value">{{ $ticket->dropping_point ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Passenger Details -->
            @if (isset($ticket->passengers) && is_array($ticket->passengers) && count($ticket->passengers) > 0)
                <div class="passenger-section">
                    <h3>Passenger Details</h3>
                    <div class="passenger-list">
                        @foreach ($ticket->passengers as $passenger)
                            <div class="passenger-item">
                                <div>
                                    <div class="passenger-name">{{ $passenger['FirstName'] ?? '' }}
                                        {{ $passenger['LastName'] ?? '' }}</div>
                                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                        @if (isset($passenger['Age']))
                                            Age: {{ $passenger['Age'] }}
                                        @endif
                                        @if (isset($passenger['Gender']))
                                            | Gender: {{ $passenger['Gender'] == 1 ? 'Male' : 'Female' }}
                                        @endif
                                    </div>
                                </div>
                                @if (isset($passenger['Seat']['SeatName']))
                                    <span class="seat-badge">Seat {{ $passenger['Seat']['SeatName'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Terms and Fare Breakdown -->
            <div class="terms-fare-wrapper">
                <!-- Terms and Conditions -->
                <div class="terms-section">
                    <h6>Terms & Conditions</h6>
                    <ul>
                        <li>Please arrive at the boarding point at least 15 minutes before departure time.</li>
                        <li>This ticket is non-transferable. Valid ID proof required for verification.</li>
                        <li>Cancellation policy applies as per company terms and conditions.</li>
                        <li>Passengers must carry a valid ID proof matching the booking details.</li>
                        <li>Company reserves the right to change bus type or departure time in unavoidable circumstances.
                        </li>
                        <li>Luggage allowance: 15kg per passenger. Extra luggage charges may apply.</li>
                        <li>For assistance, contact customer support.</li>
                    </ul>
                </div>
                <!-- Fare Breakdown -->
                <div class="fare-breakdown-list">
                    <div class="fare-item">
                        <span class="fare-label">Sub Total</span>
                        <span class="fare-value">₹{{ number_format($ticket->sub_total ?? 0, 2) }}</span>
                    </div>
                    @if (isset($ticket->service_charge) && $ticket->service_charge > 0)
                        <div class="fare-item">
                            <span class="fare-label">Service Charge ({{ $ticket->service_charge_percentage ?? 0 }}%)</span>
                            <span class="fare-value">₹{{ number_format($ticket->service_charge, 2) }}</span>
                        </div>
                    @endif
                    @if (isset($ticket->platform_fee) && $ticket->platform_fee > 0)
                        <div class="fare-item">
                            <span class="fare-label">Platform Fee ({{ $ticket->platform_fee_percentage ?? 0 }}% +
                                ₹{{ number_format($ticket->platform_fee_fixed ?? 0, 2) }})</span>
                            <span class="fare-value">₹{{ number_format($ticket->platform_fee, 2) }}</span>
                        </div>
                    @endif
                    @if (isset($ticket->gst) && $ticket->gst > 0)
                        <div class="fare-item">
                            <span class="fare-label">GST ({{ $ticket->gst_percentage ?? 0 }}%)</span>
                            <span class="fare-value">₹{{ number_format($ticket->gst, 2) }}</span>
                        </div>
                    @endif
                    <div class="fare-item fare-total">
                        <span class="fare-label">Total Amount</span>
                        <span
                            class="fare-value">₹{{ number_format($ticket->total_amount ?? ($ticket->total_fare ?? ($ticket->sub_total ?? 0)), 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer-wrapper">
                <div class="ticket-footer footer-left">
                    <p>Ghumantoo | E-Ticket Generated on {{ now()->format('d M Y, h:i A') }}</p>
                    <p style="margin-top: 5px;">© {{ date('Y') }} All rights reserved.</p>
                </div>
                <div class="ticket-footer footer-right">
                    <p>Download Ghumantoo From Play Store</p>
                    <i class="fab fa-google-play" style="font-size: 24px; color: #007bff; margin-top: 5px;"></i>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Cancel booking function with SweetAlert
        window.cancelBooking = function(bookingId) {
            Swal.fire({
                title: 'Cancel Booking?',
                text: 'Are you sure you want to cancel this booking? This action cannot be undone.',
                icon: 'warning',
                input: 'textarea',
                inputLabel: 'Cancellation Reason (Optional)',
                inputPlaceholder: 'Enter reason for cancellation...',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Cancel Booking',
                cancelButtonText: 'No, Keep Booking',
                showLoaderOnConfirm: true,
                preConfirm: (reason) => {
                    // Prepare the data
                    const ticket = @json($ticket);
                    const cancelMeta = @json($cancelMeta);
                    const requestData = {
                        UserIp: '{{ request()->ip() }}',
                        SearchTokenId: cancelMeta.search_token_id || '',
                        BookingId: cancelMeta.booking_id || '',
                        SeatId: cancelMeta.seat_id || '',
                        Remarks: reason || 'Cancelled by customer'
                    };

                    console.log('Cancellation request data:', requestData);

                    // Call API with correct base URL
                    const apiUrl = '{{ url('/api/users/cancel-ticket') }}';
                    console.log('API URL:', apiUrl);

                    return fetch(apiUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(requestData)
                        })
                        .then(response => {
                            console.log('Response status:', response.status);

                            // First, try to get the text to see what we're receiving
                            return response.text().then(text => {
                                console.log('Response text:', text);

                                try {
                                    const data = JSON.parse(text);
                                    if (!response.ok) {
                                        throw new Error(data.message ||
                                            'Failed to cancel booking');
                                    }
                                    if (!data.success) {
                                        throw new Error(data.message ||
                                            'Failed to cancel booking');
                                    }
                                    return data;
                                } catch (e) {
                                    console.error('JSON parse error:', e);
                                    console.error('Response was:', text);
                                    throw new Error(
                                        'Server returned invalid response. Please check the console for details.'
                                    );
                                }
                            });
                        })
                        .catch(error => {
                            console.error('Cancellation error:', error);
                            Swal.showValidationMessage(
                                `Request failed: ${error.message}`
                            );
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({
                        title: 'Cancelled!',
                        text: result.value.message || 'Your booking has been cancelled successfully.',
                        icon: 'success',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        window.location.href = '{{ route('user.dashboard') }}';
                    });
                }
            });
        };
    </script>
@endpush

@push('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .booking-details-container {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }

        /* Action Bar Styles */
        .action-bar {
            background: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn-back {
            background: #f8f9fa;
            color: #6c757d;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back:hover {
            background: #D63942;
            color: white;
            border-color: #D63942;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-cancel,
        .btn-print {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn-cancel {
            background: transparent;
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-cancel:hover {
            background: #dc3545;
            color: white;
        }

        .btn-print {
            background: #D63942;
            border-color: #D63942;
            color: white;
        }

        .btn-print:hover {
            background: #c32d36;
            border-color: #c32d36;
        }

        /* Ticket Container */
        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Header Section */
        .ticket-header {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .ticket-header .logo {
            max-width: 120px;
            height: auto;
            flex-shrink: 0;
        }

        .ticket-header .header-center {
            flex-grow: 1;
            text-align: center;
            padding: 0 20px;
        }

        .ticket-header .header-center .subtitle {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
            line-height: 1.4;
        }

        .ticket-header .header-right {
            flex-shrink: 0;
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin: 0;
        }

        .status-badge.confirmed {
            background: transparent;
            color: #28a745;
            border: 1px solid #28a745;
        }

        .status-badge.cancelled {
            background: transparent;
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .status-badge.pending {
            background: transparent;
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        /* Ticket Info Grid */
        .ticket-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px 20px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        .info-label {
            font-size: 9px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .info-value {
            font-size: 12px;
            color: #333;
            font-weight: 600;
        }

        .info-value.highlight {
            color: #007bff;
            font-size: 14px;
        }

        /* Journey Section */
        .journey-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px 4px;
            margin: 20px 0;
        }

        .journey-section h3 {
            color: #007bff;
            font-size: 16px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .journey-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px 20px;
        }

        .journey-item {
            display: flex;
            flex-direction: column;
        }

        .journey-label {
            font-size: 9px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .journey-value {
            font-size: 13px;
            color: #333;
            font-weight: 600;
        }

        /* Passenger Section */
        .passenger-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px 4px;
            margin: 20px 0;
        }

        .passenger-section h3 {
            color: #007bff;
            font-size: 16px;
            margin-bottom: 12px;
            font-weight: 700;
            padding-left: 4px;
        }

        .passenger-list {
            display: grid;
            gap: 8px;
        }

        .passenger-item {
            padding: 12px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .passenger-name {
            font-weight: 600;
            font-size: 13px;
            color: #333;
        }

        .passenger-item>div>div:last-child {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }

        .seat-badge {
            background: #007bff;
            color: #fff;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        /* Fare Breakdown List */
        .fare-breakdown-list {
            display: flex;
            flex-direction: column;
            gap: 2px;
            max-width: 280px;
            margin-left: 0;
        }

        .terms-fare-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 30px;
            margin: 20px 0;
        }

        .fare-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .fare-label {
            font-size: 11px;
            color: #666;
            font-weight: 500;
        }

        .fare-value {
            font-size: 13px;
            color: #333;
            font-weight: 600;
            text-align: right;
            min-width: 80px;
        }

        .fare-total {
            border-top: 2px solid #007bff;
            margin-top: 8px;
            padding-top: 10px;
        }

        .fare-total .fare-label {
            font-size: 13px;
            font-weight: 700;
            color: #007bff;
        }

        .fare-total .fare-value {
            font-size: 16px;
            font-weight: 700;
            color: #007bff;
        }

        /* Terms Section */
        .terms-section h6 {
            font-size: 14px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .terms-section ul {
            list-style: none;
            padding-left: 0;
        }

        .terms-section li {
            font-size: 0.5rem;
            color: #666;
            padding-left: 15px;
            position: relative;
        }

        .terms-section li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #007bff;
        }

        /* Footer */
        .footer-wrapper {
            display: flex !important;
            justify-content: space-between !important;
            align-items: flex-start !important;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .ticket-footer {
            font-size: 11px;
            color: #888;
        }

        .footer-left {
            text-align: left;
            flex: 1;
        }

        .footer-right {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .booking-details-container {
                padding: 20px;
            }

            .action-bar {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }

            .ticket-container {
                padding: 15px;
            }

            .ticket-header {
                flex-direction: row;
                justify-content: space-between align-items: flex-start;
                gap: 15px;
            }

            .ticket-header .header-center {
                text-align: left;
                padding: 0;
            }

            .ticket-header .header-right {
                text-align: left;
            }

            .ticket-info {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .journey-details {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .terms-fare-wrapper {
                flex-direction: column;
                gap: 20px;
            }

            .fare-breakdown-list {
                max-width: 100%;
            }

            .journey-section,
            .passenger-section {
                padding: 12px;
            }

            /*
                            .footer-wrapper {
                                flex-direction: column;
                                text-align: center;
                            } */
        }

        /* Hide action bar and adjust print view */
        @media print {
            @page {
                size: A4;
                margin: 0.5cm;
            }

            body {
                padding: 0;
                background: #fff;
            }

            /* Hide everything except ticket-container */
            body * {
                visibility: hidden;
            }

            .ticket-container,
            .ticket-container * {
                visibility: visible;
            }

            .ticket-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .booking-details-container {
                background: #fff;
                padding: 20px;
            }

            .no-print,
            .action-bar {
                display: none !important;
                visibility: hidden !important;
            }

            .ticket-container {
                page-break-inside: avoid;
                box-shadow: none;
                border: 2px solid #e0e0e0;
                max-width: 100%;
            }

            /* Copy exact normal styles for print */
            .ticket-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                border-bottom: 3px solid #007bff !important;
                padding-bottom: 20px !important;
                margin-bottom: 25px !important;
            }

            .ticket-info {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 15px 20px !important;
                margin-bottom: 20px !important;
            }

            .journey-section {
                background: #f8f9fa !important;
                border-radius: 8px !important;
                padding: 12px 4px !important;
                margin: 20px 0 !important;
            }

            .journey-details {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 12px 20px !important;
            }

            .passenger-section {
                background: #f8f9fa !important;
                border-radius: 8px !important;
                padding: 12px 4px !important;
                margin: 20px 0 !important;
            }

            .terms-fare-wrapper {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                gap: 20px !important;
                margin: 20px 0 !important;
                width: 100% !important;
            }

            .terms-section {
                flex: 1 !important;
                max-width: 60% !important;
            }

            .fare-breakdown-list {
                display: flex !important;
                flex-direction: column !important;
                gap: 2px !important;
                min-width: 250px !important;
                max-width: 35% !important;
                margin-left: 0 !important;
                flex-shrink: 0 !important;
            }

            .footer-wrapper {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                border-top: 1px solid #e0e0e0 !important;
                margin-top: 25px !important;
                padding-top: 20px !important;
            }

            .footer-left {
                text-align: left !important;
                flex: 1 !important;
            }

            .footer-right {
                text-align: right !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-end !important;
            }

            .ticket-footer {
                font-size: 11px !important;
                color: #888 !important;
            }
        }
    </style>
@endpush
