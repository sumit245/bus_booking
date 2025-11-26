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
                border: 2px solid #e0e0e0;
                max-width: 100%;
            }

            /* Copy exact normal styles */
            .ticket-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                border-bottom: 3px solid #007bff !important;
                padding-bottom: 20px !important;
                margin-bottom: 25px !important;
            }

            .ticket-header .logo {
                max-width: 120px !important;
                height: auto !important;
                flex-shrink: 0 !important;
            }

            .ticket-header .header-center {
                flex-grow: 1 !important;
                text-align: center !important;
                padding: 0 20px !important;
            }

            .ticket-header .header-center .subtitle {
                color: #666 !important;
                font-size: 14px !important;
                text-transform: uppercase !important;
                letter-spacing: 1px !important;
                margin: 0 !important;
                line-height: 1.4 !important;
            }

            .ticket-header .header-right {
                flex-shrink: 0 !important;
                text-align: right !important;
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

            .ticket-info {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 15px 20px !important;
                margin-bottom: 20px !important;
            }

            .info-item {
                display: flex !important;
                flex-direction: column !important;
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

            .journey-section {
                background: #f8f9fa !important;
                border-radius: 8px !important;
                padding: 12px 4px !important;
                margin: 20px 0 !important;
            }

            .journey-section h3 {
                color: #007bff !important;
                font-size: 16px !important;
                margin-bottom: 12px !important;
                font-weight: 700 !important;
            }

            .journey-details {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 12px 20px !important;
            }

            .journey-item {
                display: flex !important;
                flex-direction: column !important;
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

            .passenger-section {
                background: #f8f9fa !important;
                border-radius: 8px !important;
                padding: 12px 4px !important;
                margin: 20px 0 !important;
            }

            .passenger-section h3 {
                color: #007bff !important;
                font-size: 16px !important;
                margin-bottom: 12px !important;
                font-weight: 700 !important;
                padding-left: 4px !important;
            }

            .passenger-list {
                display: grid !important;
                gap: 8px !important;
            }

            .passenger-item {
                padding: 12px !important;
                border-radius: 6px !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
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

            .terms-fare-wrapper {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                gap: 30px !important;
                margin: 20px 0 !important;
            }

            .fare-breakdown-list {
                display: flex !important;
                flex-direction: column !important;
                gap: 2px !important;
                max-width: 280px !important;
                margin-left: 0 !important;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            /* padding: 6px 0; */
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
            /* margin-top: 12px; */
            /* padding-top: 20px; */
            /* border-top: 2px solid #e0e0e0; */
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
            font-size: 0.5rem;
            color: #666;
            /* margin-bottom: 5px; */
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
                padding: 15px;
            }

            .ticket-header {
                flex-direction: column;
                align-items: flex-start;
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
        }
    </style>
</head>

<body>
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
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2
                },
                jsPDF: {
                    unit: 'in',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>

</html>
