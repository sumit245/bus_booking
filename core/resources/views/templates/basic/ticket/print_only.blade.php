<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket - {{ $ticket->pnr_number ?? 'N/A' }}</title>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }

        /* Hide everything except ticket when printing */
        @media print {
            @page {
                size: A4;
                margin: 0.5cm;
            }

            body {
                padding: 0;
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .ticket-container {
                page-break-inside: avoid;
                box-shadow: none;
                border: none;
            }
        }

        /* Ticket Container */
        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Header Section */
        .ticket-header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .ticket-header .logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 10px;
        }

        .ticket-header h1 {
            color: #007bff;
            font-size: 24px;
            margin: 10px 0 5px;
            font-weight: 700;
        }

        .ticket-header .subtitle {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .status-badge.confirmed {
            background: #28a745;
            color: #fff;
        }

        .status-badge.cancelled {
            background: #dc3545;
            color: #fff;
        }

        .status-badge.pending {
            background: #ffc107;
            color: #000;
        }

        /* Ticket Info Grid */
        .ticket-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }

        .info-value.highlight {
            color: #007bff;
            font-size: 18px;
        }

        /* Journey Section */
        .journey-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            border-left: 4px solid #007bff;
        }

        .journey-section h3 {
            color: #007bff;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .journey-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .journey-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #ddd;
        }

        .journey-item:last-child {
            border-bottom: none;
        }

        .journey-label {
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }

        .journey-value {
            font-size: 14px;
            color: #333;
            font-weight: 600;
            text-align: right;
        }

        /* Passenger Section */
        .passenger-section {
            margin: 25px 0;
        }

        .passenger-section h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 700;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .passenger-list {
            display: grid;
            gap: 10px;
        }

        .passenger-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .passenger-name {
            font-weight: 600;
            color: #333;
        }

        .seat-badge {
            background: #007bff;
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        /* QR Code Section */
        .qr-section {
            text-align: center;
            margin: 25px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .qr-code {
            margin: 15px auto;
            width: 150px;
            height: 150px;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            display: inline-block;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
        }

        /* Terms Section */
        .terms-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .terms-section h4 {
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
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
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
        .ticket-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 11px;
            color: #888;
        }

        /* Print Button (Hidden when printing) */
        .print-actions {
            text-align: center;
            margin: 20px 0;
        }

        .btn-print {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin: 0 10px;
            transition: background 0.3s;
        }

        .btn-print:hover {
            background: #0056b3;
        }

        .btn-download {
            background: #28a745;
        }

        .btn-download:hover {
            background: #218838;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ticket-container {
                padding: 20px;
            }

            .ticket-info {
                grid-template-columns: 1fr;
            }

            .journey-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <!-- Header -->
        <div class="ticket-header">
            @if(isset($logoUrl) && $logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo" class="logo">
            @endif
            <h1>{{ $companyName ?? 'Bus Booking' }}</h1>
            <p class="subtitle">E-Ticket / Reservation Voucher</p>
            @if(isset($ticket->status))
                <span class="status-badge {{ $ticket->status == 1 ? 'confirmed' : ($ticket->status == 3 ? 'cancelled' : 'pending') }}">
                    {{ $ticket->status == 1 ? 'Confirmed' : ($ticket->status == 3 ? 'Cancelled' : 'Pending') }}
                </span>
            @endif
        </div>

        <!-- Ticket Info -->
        <div class="ticket-info">
            <div class="info-item">
                <span class="info-label">PNR Number</span>
                <span class="info-value highlight">{{ $ticket->pnr_number ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Booking Date</span>
                <span class="info-value">{{ isset($ticket->created_at) ? \Carbon\Carbon::parse($ticket->created_at)->format('d M Y, h:i A') : 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Passenger Name</span>
                <span class="info-value">{{ $ticket->passenger_name ?? ($ticket->passengers[0]['FirstName'] ?? 'N/A') }} {{ $ticket->passengers[0]['LastName'] ?? '' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Contact Number</span>
                <span class="info-value">{{ $ticket->passenger_phone ?? ($ticket->passengers[0]['Phoneno'] ?? 'N/A') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Amount</span>
                <span class="info-value highlight">₹{{ number_format($ticket->total_fare ?? $ticket->sub_total ?? 0, 2) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Seats</span>
                <span class="info-value">
                    @if(isset($ticket->seats) && is_array($ticket->seats))
                        {{ implode(', ', $ticket->seats) }}
                    @elseif(isset($ticket->seats))
                        {{ $ticket->seats }}
                    @else
                        N/A
                    @endif
                </span>
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
                    <span class="journey-value">{{ isset($ticket->date_of_journey) ? \Carbon\Carbon::parse($ticket->date_of_journey)->format('d M Y, l') : 'N/A' }}</span>
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
        @if(isset($ticket->passengers) && is_array($ticket->passengers) && count($ticket->passengers) > 0)
        <div class="passenger-section">
            <h3>Passenger Details</h3>
            <div class="passenger-list">
                @foreach($ticket->passengers as $passenger)
                <div class="passenger-item">
                    <div>
                        <div class="passenger-name">{{ ($passenger['FirstName'] ?? '') }} {{ ($passenger['LastName'] ?? '') }}</div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                            @if(isset($passenger['Age'])) Age: {{ $passenger['Age'] }} @endif
                            @if(isset($passenger['Gender'])) | Gender: {{ $passenger['Gender'] == 1 ? 'Male' : 'Female' }} @endif
                        </div>
                    </div>
                    @if(isset($passenger['Seat']['SeatName']))
                    <span class="seat-badge">Seat {{ $passenger['Seat']['SeatName'] }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- QR Code (if available) -->
        @if(isset($qrCodeUrl) && $qrCodeUrl)
        <div class="qr-section">
            <h4>Scan for Ticket Verification</h4>
            <div class="qr-code">
                <img src="{{ $qrCodeUrl }}" alt="QR Code">
            </div>
            <p style="font-size: 11px; color: #666; margin-top: 10px;">PNR: {{ $ticket->pnr_number ?? 'N/A' }}</p>
        </div>
        @endif

        <!-- Terms and Conditions -->
        <div class="terms-section">
            <h4>Important Terms & Conditions</h4>
            <ul>
                <li>Please arrive at the boarding point at least 15 minutes before departure time.</li>
                <li>This ticket is non-transferable. Valid ID proof required for verification.</li>
                <li>Cancellation policy applies as per company terms and conditions.</li>
                <li>Passengers must carry a valid ID proof matching the booking details.</li>
                <li>Company reserves the right to change bus type or departure time in unavoidable circumstances.</li>
                <li>Luggage allowance: 15kg per passenger. Extra luggage charges may apply.</li>
                <li>For assistance, contact customer support.</li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="ticket-footer">
            <p>{{ $companyName ?? 'Bus Booking' }} | E-Ticket Generated on {{ now()->format('d M Y, h:i A') }}</p>
            <p style="margin-top: 5px;">© {{ date('Y') }} All rights reserved.</p>
        </div>
    </div>

    <!-- Print Actions (Hidden when printing) -->
    <div class="print-actions no-print">
        <button class="btn-print" onclick="window.print()">Print Ticket</button>
        <button class="btn-print btn-download" onclick="downloadAsPDF()">Download PDF</button>
    </div>

    <!-- PDF Download Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadAsPDF() {
            const element = document.querySelector('.ticket-container');
            const opt = {
                margin: 0.5,
                filename: 'ticket_{{ $ticket->pnr_number ?? 'ticket' }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>

