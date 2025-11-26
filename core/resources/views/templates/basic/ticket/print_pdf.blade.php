<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>E-Ticket - {{ $ticket->pnr_number ?? 'N/A' }}</title>
    <style>
        /* PDF-optimized styles using tables instead of flexbox/grid */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            color: #333;
            line-height: 1.6;
            font-size: 11px;
        }

        .ticket-container {
            width: 100%;
            max-width: 750px;
            margin: 0 auto;
            padding: 25px;
            border: 2px solid #e0e0e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Header Section */
        .ticket-header {
            width: 100%;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .logo {
            max-width: 120px;
            height: auto;
        }

        .header-subtitle {
            text-align: right;
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 5px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            text-align: center;
            margin-top: 5px;
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

        /* Info Grid using Table */
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 15px 20px;
            vertical-align: top;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-label {
            font-size: 9px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 12px;
            color: #333;
            font-weight: 600;
            display: block;
        }

        .info-value.highlight {
            color: #007bff;
            font-size: 14px;
            font-weight: 600;
        }

        /* Section Headers */
        .section-header {
            background: #f8f9fa;
            padding: 12px 15px;
            margin: 20px 0 15px 0;
            border-radius: 8px;
        }

        .section-header h3 {
            color: #007bff;
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        /* Journey Details Table */
        .journey-table {
            width: 100%;
            margin-bottom: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .journey-table td {
            padding: 12px 20px;
            border-bottom: 1px solid #e8e8e8;
        }

        .journey-label {
            font-size: 9px;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 4px;
        }

        .journey-value {
            font-size: 13px;
            color: #333;
            font-weight: 600;
        }

        /* Passenger Table */
        .passenger-table {
            width: 100%;
            margin-bottom: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .passenger-row {
            padding: 12px 15px;
            border-bottom: 1px solid #e8e8e8;
        }

        .passenger-row td {
            padding: 12px 15px;
        }

        .passenger-name {
            font-size: 13px;
            color: #333;
            font-weight: 600;
            display: block;
        }

        .passenger-info {
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
            display: inline-block;
        }

        /* Fare Breakdown */
        .fare-table {
            width: 100%;
            max-width: 280px;
            float: right;
            margin-top: 0;
        }

        .fare-table td {
            padding: 6px 0;
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
            color: #007bff;
            font-weight: 700;
        }

        .fare-total .fare-value {
            font-size: 16px;
            color: #007bff;
            font-weight: 700;
        }

        /* Terms Section */
        .terms-section {
            clear: both;
            padding-top: 15px;
            margin-top: 20px;
            width: 60%;
            float: left;
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
            margin: 0;
        }

        .terms-section li {
            font-size: 9px;
            color: #666;
            padding-left: 15px;
            position: relative;
            margin-bottom: 5px;
            line-height: 1.6;
        }

        .terms-section li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #007bff;
        }

        /* Footer */
        .ticket-footer {
            width: 100%;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            clear: both;
        }

        .ticket-footer td {
            font-size: 11px;
            color: #888;
            padding: 5px 0;
        }

        .text-right {
            text-align: right;
        }

        /* Terms and Fare wrapper */
        .terms-fare-wrapper {
            width: 100%;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="ticket-container">
        <!-- Header -->
        <table class="ticket-header">
            <tr>
                <td width="20%" style="vertical-align: middle;">
                    @if (isset($logoUrl) && $logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="logo">
                    @endif
                </td>
                <td width="80%" style="text-align: right; vertical-align: middle;">
                    <div class="header-subtitle">E-Ticket / Reservation Voucher</div>
                    @if (isset($ticket->status))
                        <span
                            class="status-badge {{ $ticket->status == 1 ? 'confirmed' : ($ticket->status == 3 ? 'cancelled' : 'pending') }}">
                            {{ $ticket->status == 1 ? 'CONFIRMED' : ($ticket->status == 3 ? 'CANCELLED' : 'PENDING') }}
                        </span>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Ticket Info Grid -->
        <table class="info-table">
            <tr>
                <td width="33%">
                    <span class="info-label">PNR Number</span>
                    <span class="info-value highlight">{{ $ticket->pnr_number ?? 'N/A' }}</span>
                </td>
                <td width="33%">
                    <span class="info-label">Booking Date</span>
                    <span
                        class="info-value">{{ isset($ticket->created_at) ? \Carbon\Carbon::parse($ticket->created_at)->format('d M Y, h:i A') : 'N/A' }}</span>
                </td>
                <td width="34%">
                    <span class="info-label">Passenger Name</span>
                    <span class="info-value">{{ $ticket->passenger_name ?? 'N/A' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="info-label">Contact Number</span>
                    <span
                        class="info-value">{{ $ticket->passenger_phone ?? ($ticket->passengers[0]['Phoneno'] ?? 'N/A') }}</span>
                </td>
                <td>
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
                </td>
                <td>
                    <span class="info-label">Total Amount</span>
                    <span
                        class="info-value highlight">₹{{ number_format($ticket->total_amount ?? ($ticket->total_fare ?? ($ticket->sub_total ?? 0)), 2) }}</span>
                </td>
            </tr>
        </table>

        <!-- Journey Details -->
        <div class="section-header">
            <h3>Journey Details</h3>
        </div>
        <table class="journey-table">
            <tr>
                <td width="33%">
                    <span class="journey-label">Travel Name</span>
                    <span class="journey-value">{{ $ticket->travel_name ?? 'N/A' }}</span>
                </td>
                <td width="33%">
                    <span class="journey-label">Bus Type</span>
                    <span class="journey-value">{{ $ticket->bus_type ?? 'N/A' }}</span>
                </td>
                <td width="34%">
                    <span class="journey-label">Date of Journey</span>
                    <span
                        class="journey-value">{{ isset($ticket->date_of_journey) ? \Carbon\Carbon::parse($ticket->date_of_journey)->format('d M Y, l') : 'N/A' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="journey-label">Departure Time</span>
                    <span class="journey-value">{{ $ticket->departure_time ?? 'N/A' }}</span>
                </td>
                <td>
                    <span class="journey-label">Arrival Time</span>
                    <span class="journey-value">{{ $ticket->arrival_time ?? 'N/A' }}</span>
                </td>
                <td>
                    <span class="journey-label">Duration</span>
                    <span class="journey-value">{{ $ticket->duration ?? 'N/A' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="journey-label">Boarding Point</span>
                    <span class="journey-value">{{ $ticket->boarding_point ?? 'N/A' }}</span>
                </td>
                <td colspan="2">
                    <span class="journey-label">Dropping Point</span>
                    <span class="journey-value">{{ $ticket->dropping_point ?? 'N/A' }}</span>
                </td>
            </tr>
        </table>

        <!-- Passenger Details -->
        @if (isset($ticket->passengers) && is_array($ticket->passengers) && count($ticket->passengers) > 0)
            <div class="section-header">
                <h3>Passenger Details</h3>
            </div>
            <table class="passenger-table">
                @foreach ($ticket->passengers as $passenger)
                    <tr class="passenger-row">
                        <td width="70%">
                            <span class="passenger-name">{{ $passenger['FirstName'] ?? '' }}
                                {{ $passenger['LastName'] ?? '' }}</span>
                            <div class="passenger-info">
                                @if (isset($passenger['Age']))
                                    Age: {{ $passenger['Age'] }}
                                @endif
                                @if (isset($passenger['Gender']))
                                    | Gender: {{ $passenger['Gender'] == 1 ? 'Male' : 'Female' }}
                                @endif
                            </div>
                        </td>
                        <td width="30%" style="text-align: right;">
                            @if (isset($passenger['Seat']['SeatName']))
                                <span class="seat-badge">Seat {{ $passenger['Seat']['SeatName'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif

        <!-- Terms and Fare Breakdown Wrapper -->
        <div class="terms-fare-wrapper">
            <!-- Terms and Conditions -->
            <div class="terms-section">
                <h4>Terms & Conditions</h4>
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
            <table class="fare-table">
                <tr>
                    <td class="fare-label">Sub Total</td>
                    <td class="fare-value">₹{{ number_format($ticket->sub_total ?? 0, 2) }}</td>
                </tr>
                @if (isset($ticket->service_charge) && $ticket->service_charge > 0)
                    <tr>
                        <td class="fare-label">Service Charge ({{ $ticket->service_charge_percentage ?? 0 }}%)</td>
                        <td class="fare-value">₹{{ number_format($ticket->service_charge, 2) }}</td>
                    </tr>
                @endif
                @if (isset($ticket->platform_fee) && $ticket->platform_fee > 0)
                    <tr>
                        <td class="fare-label">Platform Fee ({{ $ticket->platform_fee_percentage ?? 0 }}% +
                            ₹{{ number_format($ticket->platform_fee_fixed ?? 0, 2) }})</td>
                        <td class="fare-value">₹{{ number_format($ticket->platform_fee, 2) }}</td>
                    </tr>
                @endif
                @if (isset($ticket->gst) && $ticket->gst > 0)
                    <tr>
                        <td class="fare-label">GST ({{ $ticket->gst_percentage ?? 0 }}%)</td>
                        <td class="fare-value">₹{{ number_format($ticket->gst, 2) }}</td>
                    </tr>
                @endif
                <tr class="fare-total">
                    <td class="fare-label">Total Amount</td>
                    <td class="fare-value">
                        ₹{{ number_format($ticket->total_amount ?? ($ticket->total_fare ?? ($ticket->sub_total ?? 0)), 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <table class="ticket-footer">
            <tr>
                <td width="50%">
                    Ghumantoo | E-Ticket Generated on {{ now()->format('d M Y, h:i A') }}<br>
                    © {{ date('Y') }} All rights reserved.
                </td>
                <td width="50%" class="text-right">
                    Download Ghumantoo From Play Store<br>
                    For support: contact@ghumantoo.com
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
