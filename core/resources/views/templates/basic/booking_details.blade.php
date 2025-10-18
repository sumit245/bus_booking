@extends($activeTemplate . $layout)

@section('content')
    <div class="booking-details-container">
        <!-- Back Button -->
        <div class="action-bar">
            <a href="{{ route('user.dashboard') }}" class="btn-back">
                <i class="las la-arrow-left"></i>
                Back to Dashboard
            </a>
            <div class="action-buttons">
                @if ($booking->status == 1)
                    <button type="button" class="btn-cancel" onclick="cancelBooking({{ $booking->id }})">
                        <i class="las la-times"></i>
                        Cancel Booking
                    </button>
                @endif
                <button type="button" class="btn-print" onclick="printTicket()">
                    <i class="las la-print"></i>
                    Print Ticket
                </button>
            </div>
        </div>

        <!-- Professional Ticket Layout -->
        <div class="ticket-container">
            <div class="ticket" id="ticket-to-print">
                <!-- Ticket Header -->
                <div class="ticket-header">
                    <div class="company-info">
                        <h1 class="company-name">{{ $general->sitename ?? 'Bus Booking' }}</h1>
                        <p class="company-tagline">Your Journey, Our Priority</p>
                    </div>
                    <div class="ticket-status">
                        @if ($booking->status == 1)
                            <span class="status-badge confirmed">CONFIRMED</span>
                        @elseif($booking->status == 2)
                            <span class="status-badge pending">PENDING</span>
                        @else
                            <span class="status-badge cancelled">CANCELLED</span>
                        @endif
                    </div>
                </div>

                <!-- Ticket Body -->
                <div class="ticket-body">
                    <div class="ticket-grid">
                        <!-- Left Column - Journey Details -->
                        <div class="ticket-section">
                            <h3 class="section-title">Journey Details</h3>

                            <div class="info-row">
                                <span class="label">Route:</span>
                                <span class="value route-info">
                                    {{ $booking->origin_city ?? 'N/A' }} → {{ $booking->destination_city ?? 'N/A' }}
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="label">Date:</span>
                                <span
                                    class="value">{{ \Carbon\Carbon::parse($booking->date_of_journey)->format('M d, Y') }}</span>
                            </div>

                            @if ($booking->departure_time)
                                <div class="info-row">
                                    <span class="label">Departure:</span>
                                    <span class="value">{{ $booking->departure_time }}</span>
                                </div>
                            @endif

                            @if ($booking->travel_name)
                                <div class="info-row">
                                    <span class="label">Operator:</span>
                                    <span class="value">{{ $booking->travel_name }}</span>
                                </div>
                            @endif

                            @if ($booking->bus_type)
                                <div class="info-row">
                                    <span class="label">Bus Type:</span>
                                    <span class="value">{{ $booking->bus_type }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Right Column - Booking & Passenger -->
                        <div class="ticket-section">
                            <h3 class="section-title">Booking Information</h3>

                            <div class="info-row">
                                <span class="label">PNR:</span>
                                <span class="value pnr-number">{{ $booking->pnr_number }}</span>
                            </div>

                            @if ($booking->operator_pnr)
                                <div class="info-row">
                                    <span class="label">Operator PNR:</span>
                                    <span class="value">{{ $booking->operator_pnr }}</span>
                                </div>
                            @endif

                            <div class="info-row">
                                <span class="label">Passenger:</span>
                                <span class="value">{{ $booking->passenger_name ?? 'N/A' }}</span>
                            </div>

                            <div class="info-row">
                                <span class="label">Phone:</span>
                                <span class="value">{{ $booking->passenger_phone ?? 'N/A' }}</span>
                            </div>

                            <div class="info-row">
                                <span class="label">Seats:</span>
                                <span class="value seat-numbers">
                                    @if (is_array($booking->seats))
                                        {{ implode(', ', $booking->seats) }}
                                    @else
                                        {{ $booking->seats ?? 'N/A' }}
                                    @endif
                                </span>
                            </div>

                            <div class="info-row total-row">
                                <span class="label">Total Amount:</span>
                                <span class="value total-amount">
                                    {{ $general->cur_sym }}{{ number_format($booking->sub_total, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Boarding & Dropping Points -->
                    @if ($booking->boarding_point_details || $booking->dropping_point_details)
                        <div class="points-section">
                            <div class="points-grid">
                                <!-- Boarding Point -->
                                <div class="point-details">
                                    <h4 class="point-title">Boarding Point</h4>
                                    @if ($booking->boarding_point_details)
                                        @php
                                            $boardingDetails = json_decode($booking->boarding_point_details, true);
                                        @endphp
                                        @if ($boardingDetails)
                                            <div class="point-info">
                                                <div class="point-name">{{ $boardingDetails['CityPointName'] ?? 'N/A' }}
                                                </div>
                                                <div class="point-location">
                                                    {{ $boardingDetails['CityPointLocation'] ?? 'N/A' }}</div>
                                                @if (isset($boardingDetails['CityPointTime']))
                                                    <div class="point-time">
                                                        <i class="las la-clock"></i>
                                                        {{ \Carbon\Carbon::parse($boardingDetails['CityPointTime'])->format('h:i A') }}
                                                    </div>
                                                @endif
                                                @if (isset($boardingDetails['CityPointContactNumber']))
                                                    <div class="point-contact">
                                                        <i class="las la-phone"></i>
                                                        {{ $boardingDetails['CityPointContactNumber'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <div class="point-info">No boarding point details</div>
                                    @endif
                                </div>

                                <!-- Dropping Point -->
                                <div class="point-details">
                                    <h4 class="point-title">Dropping Point</h4>
                                    @if ($booking->dropping_point_details)
                                        @php
                                            $droppingDetails = json_decode($booking->dropping_point_details, true);
                                        @endphp
                                        @if ($droppingDetails)
                                            <div class="point-info">
                                                <div class="point-name">{{ $droppingDetails['CityPointName'] ?? 'N/A' }}
                                                </div>
                                                <div class="point-location">
                                                    {{ $droppingDetails['CityPointLocation'] ?? 'N/A' }}</div>
                                                @if (isset($droppingDetails['CityPointTime']))
                                                    <div class="point-time">
                                                        <i class="las la-clock"></i>
                                                        {{ \Carbon\Carbon::parse($droppingDetails['CityPointTime'])->format('h:i A') }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <div class="point-info">No dropping point details</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Important Instructions -->
                    <div class="instructions-section">
                        <h4 class="instructions-title">Important Instructions</h4>
                        <ul class="instructions-list">
                            <li>Please arrive at the boarding point 15 minutes before departure time</li>
                            <li>Carry a valid photo ID for verification</li>
                            <li>Show this ticket to the conductor before boarding</li>
                            <li>Keep this ticket safe until the end of your journey</li>
                        </ul>
                    </div>
                </div>

                <!-- Ticket Footer -->
                <div class="ticket-footer">
                    <div class="footer-info">
                        <p class="booking-date">
                            Booked on: {{ \Carbon\Carbon::parse($booking->created_at)->format('M d, Y h:i A') }}
                        </p>
                        <p class="support-info">
                            For support: {{ $general->phone ?? '+91-9876543210' }} |
                            {{ $general->email ?? 'support@busbooking.com' }}
                        </p>
                    </div>
                    <div class="qr-placeholder">
                        <div class="qr-code">
                            <div class="qr-text">QR Code</div>
                            <div class="qr-small">{{ $booking->pnr_number }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this booking?</p>
                    <div class="mb-3">
                        <label for="cancellationReason" class="form-label">Reason for cancellation (optional)</label>
                        <textarea class="form-control" id="cancellationReason" rows="3"
                            placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Booking</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelBtn">Yes, Cancel Booking</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        // Simple and clean JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Cancel booking function
            window.cancelBooking = function(bookingId) {
                const modal = document.getElementById('cancelBookingModal');
                if (modal) {
                    modal.style.display = 'block';
                }
            };

            // Cancel button handler
            const cancelBtn = document.getElementById('confirmCancelBtn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    const reason = document.getElementById('cancellationReason').value;
                    const btn = this;

                    btn.disabled = true;
                    btn.innerHTML = 'Cancelling...';

                    // Use fetch for AJAX
                    fetch('{{ route('user.booking.cancel', $booking->id) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                cancellation_reason: reason
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert('Success: ' + data.message);
                                const modal = document.getElementById('cancelBookingModal');
                                if (modal) modal.style.display = 'none';
                                setTimeout(() => {
                                    window.location.href = '{{ route('user.dashboard') }}';
                                }, 1000);
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            alert('Error: Failed to cancel booking');
                            console.error('Error:', error);
                        })
                        .finally(() => {
                            btn.disabled = false;
                            btn.innerHTML = 'Yes, Cancel Booking';
                        });
                });
            }
        });

        // Simple print function
        function printTicket() {
            window.print();
        }
    </script>
@endpush

@push('style')
    <style>
        :root {
            --primary-color: #D63942;
            --primary-hover: #c32d36;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
            --text-muted: #6c757d;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .booking-details-container {
            background: var(--light-bg);
            min-height: 100vh;
            padding: 2rem 0;
        }

        /* Action Bar */
        .action-bar {
            background: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn-back {
            background: var(--light-bg);
            color: var(--text-muted);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid var(--border-color);
        }

        .btn-back:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
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
        }

        .btn-cancel {
            background: transparent;
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        .btn-cancel:hover {
            background: var(--danger-color);
            color: white;
        }

        .btn-print {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-print:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        /* Ticket Container */
        .ticket-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .ticket {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-hover);
            overflow: hidden;
            border: 2px solid var(--border-color);
        }

        /* Ticket Header */
        .ticket-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .company-name {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .company-tagline {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0.5rem 0 0 0;
        }

        .status-badge {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-badge.confirmed {
            background: var(--success-color);
            color: white;
        }

        .status-badge.pending {
            background: var(--warning-color);
            color: #333;
        }

        .status-badge.cancelled {
            background: var(--danger-color);
            color: white;
        }

        /* Ticket Body */
        .ticket-body {
            padding: 2rem;
        }

        .ticket-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            font-weight: 600;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px dotted var(--border-color);
        }

        .label {
            font-weight: 600;
            color: var(--text-muted);
        }

        .value {
            color: #333;
            text-align: right;
            font-weight: 500;
        }

        .pnr-number {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .total-row {
            border-top: 2px solid var(--primary-color);
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .total-amount {
            font-weight: 700;
            color: var(--success-color);
            font-size: 1.25rem;
        }

        /* Points Section */
        .points-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: var(--light-bg);
            border-radius: 15px;
        }

        .points-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .point-title {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .point-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .point-location {
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .point-time,
        .point-contact {
            font-size: 0.9rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Instructions Section */
        .instructions-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #e3f2fd;
            border-radius: 15px;
        }

        .instructions-title {
            color: #1976d2;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .instructions-list {
            list-style: none;
            padding-left: 0;
        }

        .instructions-list li {
            margin-bottom: 0.75rem;
            padding-left: 1.5rem;
            position: relative;
            color: #333;
        }

        .instructions-list li:before {
            content: "•";
            color: #1976d2;
            font-weight: bold;
            position: absolute;
            left: 0;
            font-size: 1.2rem;
        }

        /* Ticket Footer */
        .ticket-footer {
            background: var(--light-bg);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid var(--primary-color);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-info p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .qr-code {
            text-align: center;
            padding: 1rem;
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            background: white;
        }

        .qr-text {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .qr-small {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .booking-details-container {
                padding: 1rem 0;
            }

            .action-bar {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }

            .ticket-header {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }

            .ticket-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .points-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .ticket-footer {
                flex-direction: column;
                text-align: center;
            }

            .company-name {
                font-size: 1.5rem;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }

            .value {
                text-align: left;
            }
        }

        /* Hide action bar when printing */
        @media print {
            .action-bar {
                display: none !important;
            }
        }
    </style>
@endpush
