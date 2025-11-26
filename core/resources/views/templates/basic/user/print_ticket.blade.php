<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ getImage('assets/images/logoIcon/favicon.png') }}">
    <title>
        {{ $general && method_exists($general, 'sitename') ? $general->sitename($pageTitle ?? 'Print Ticket') : $pageTitle ?? 'Print Ticket' }}
    </title>

    <style>
        @media screen,
        print {
            body {
                box-sizing: border-box;
                background-color: #eee;
                font-family: "Quicksand", sans-serif;
            }

            h1,
            h2,
            h3,
            h4,
            h5,
            h6 {
                margin: 0;
                color: #456;
            }

            p {
                color: #678;
                margin-top: 10px;
                margin-bottom: 10px;
            }

            ul {
                padding: 0;
                margin: 0;
                list-style: none;
            }

            .d-flex {
                display: flex;
            }

            .flex-wrap {
                flex-wrap: wrap;
            }

            .justify-content-between {
                justify-content: space-between;
            }

            .justify-content-center {
                justify-content: center;
            }

            .cmn-btn {
                position: relative;
                background: #fa9e1b;
                color: white;
                padding: 12px 30px;
                border-radius: 5px;
                font-size: 14px;
                font-weight: 600;
                z-index: 2;
                overflow: hidden;
                -webkit-transition: all ease 0.5s;
                -moz-transition: all ease 0.5s;
                transition: all ease 0.5s;
                outline: none;
                box-shadow: none;
                border: none;
                margin-top: 20px;
                cursor: pointer;
            }

            .print-btn {
                text-align: center;
            }

            .ticket-wrapper {
                width: 7.5in;
                margin: 0 auto;
                padding: 20px;
                border-radius: 10px;
                background: #fff;
                box-shadow: 0 5px 35px rgba(0, 0, 0, .1);
            }

            .ticket-inner {
                border: 2px solid #ccd;
                padding: 30px;
                border-radius: 5px;
                padding-bottom: 0px;
            }

            .ticket-header {
                text-align: center;
            }

            .ticket-header .title {
                font-size: 22px;
            }


            @media (min-width:992px) {
                .ticket-body-part {
                    width: 50%;
                }
            }

            .ticket-info {
                display: flex;
                flex-wrap: wrap;
                align-items: center;

            }


            .ticket-body {
                padding: 20px;
                font-size: 15px;
            }

            .text-right {
                text-align: right;
            }

            .text-left {
                text-align: left;
            }

            .ticket-logo {
                width: 120px;
                margin: 0 auto 15px;
            }

            .ticket-logo img {
                width: 100%;
            }

            .border {
                border: 1px solid #eef !important;
            }

            .info {
                margin-bottom: 15px;
            }

            .journey-details {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
                margin-bottom: 20px;
                border: 1px solid #eef;
            }

            .journey-details h5 {
                margin-bottom: 15px;
                color: #456;
                font-weight: 600;
            }

            .journey-info {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                padding-bottom: 5px;
                border-bottom: 1px dashed #eef;
            }

            .journey-info:last-child {
                border-bottom: none;
            }

            .journey-info .label {
                font-weight: bold;
                color: #456;
            }

            .journey-info .value {
                color: #678;
            }

            .terms-conditions {
                margin-top: 20px;
                padding: 15px;
                background-color: #f8f9fa;
                border: 1px solid #eef;
                border-radius: 5px;
                font-size: 12px;
            }

            .terms-conditions h5 {
                margin-bottom: 10px;
                color: #456;
                font-weight: 600;
            }

            .terms-conditions ul {
                list-style-type: disc;
                padding-left: 20px;
            }

            .terms-conditions li {
                margin-bottom: 5px;
                color: #678;
            }

            .company-info {
                text-align: center;
                margin-top: 20px;
                padding-top: 10px;
                border-top: 1px solid #eef;
                font-size: 12px;
                color: #678;
            }

            .qr-code {
                text-align: center;
                margin-top: 20px;
            }

            .qr-code img {
                width: 100px;
                height: 100px;
            }
        }

        @media print {
            .p-50 {
                padding: 0 50px;
            }
        }
    </style>
</head>

<body>

    <div id="block1">
        <div class="ticket-wrapper">
            <div class="ticket-inner">
                <div class="ticket-header">
                    <div class="ticket-logo"><img src="{{ getImage(imagePath()['logoIcon']['path'] . '/logo.png') }}"
                            alt="Logo"></div>
                    <div class="ticket-header-content">
                        <h4 class="title">
                            {{ __(@$ticket->trip->assignedVehicle->vehicle->nick_name ?? (@$ticket->trip->title ?? 'Bus Ticket')) }}
                        </h4>
                        <p class="info">@lang('E-Ticket/ Reservation Voucher')</p>
                    </div>
                </div>
                <div class="border"></div>
                <div class="ticket-body d-flex flex-wrap">
                    <div class="p-50 ticket-body-part">
                        <table class="">
                            <tbody>
                                <tr>
                                    <td class="text-right">
                                        <p class="title">@lang('PNR Number')</p>
                                    </td>
                                    <td>
                                        <b>:</b>
                                    </td>
                                    <td class="text-left">
                                        <h5 class="value">{{ __($ticket->pnr_number) }}</h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-right">
                                        <p class="title">@lang('Name')</p>
                                    </td>
                                    <td>
                                        <b>:</b>
                                    </td>
                                    <td class="text-left">
                                        <h5 class="value">
                                            @if (isset($ticket->passenger_name))
                                                {{ __($ticket->passenger_name) }}
                                            @elseif(isset($ticket->user) && $ticket->user)
                                                {{ __($ticket->user->fullname) }}
                                            @else
                                                Guest User
                                            @endif
                                        </h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-right">
                                        <p class="title">@lang('Contact')</p>
                                    </td>
                                    <td>
                                        <b>:</b>
                                    </td>
                                    <td class="text-left">
                                        <h5 class="value">
                                            @if (isset($ticket->passenger_phone))
                                                {{ __($ticket->passenger_phone) }}
                                            @elseif(isset($ticket->user) && $ticket->user && $ticket->user->mobile)
                                                {{ __($ticket->user->mobile) }}
                                            @else
                                                N/A
                                            @endif
                                        </h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-right">
                                        <p class="title">@lang('Journey Date')</p>
                                    </td>
                                    <td>
                                        <b>:</b>
                                    </td>
                                    <td class="text-left">
                                        <h5 class="value">
                                            {{ $ticket->formatted_date ?? showDateTime($ticket->date_of_journey, 'F d, Y') }}
                                        </h5>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-50 ticket-body-part">
                        <table class="">
                            <tbody>
                                <tr>
                                    <td class="text-right">
                                        <p class="title">@lang('Journey Day')</p>
                                    </td>
                                    <td>
                                        <b>:</b>
                                    </td>
                                    <td class="text-left">
                                        <h5 class="value">
                                            {{ $ticket->journey_day ?? showDateTime($ticket->date_of_journey, 'l') }}
                                        </h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-right">
                                        <p class="title">@lang('Total Seats')</p>
                                    </td>
                                    <td>
                                        <b>:</b>
                                    </td>
                                    <td class="text-left">
                                        <h5 class="value">{{ is_array($ticket->seats) ? sizeof($ticket->seats) : 1 }}
                                        </h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-right">
                                        <p class="title">@lang('Seat Numbers')</p>
                                    </td>
                                    <td>
                                        <b>:</b>
                                    </td>
                                    <td class="text-left">
                                        <h5 class="value">
                                            {{ is_array($ticket->seats) ? __(implode(', ', $ticket->seats)) : __($ticket->seats) }}
                                        </h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-right">
                                        <p class="title">@lang('Base Fare')</p>
                                    </td>
                                    <td>
                                        <b>:</b>
                                    </td>
                                    <td class="text-left">
                                        <h5 class="value">
                                            {{ isset($general->cur_sym) ? __($general->cur_sym) : '₹' }}{{ showAmount($ticket->sub_total) }}
                                        </h5>
                                    </td>
                                </tr>
                                @if (isset($ticket->service_charge) && $ticket->service_charge > 0)
                                    <tr>
                                        <td class="text-right">
                                            <p class="title">@lang('Service Charge')</p>
                                        </td>
                                        <td>
                                            <b>:</b>
                                        </td>
                                        <td class="text-left">
                                            <h5 class="value">
                                                {{ isset($general->cur_sym) ? __($general->cur_sym) : '₹' }}{{ number_format($ticket->service_charge, 2) }}
                                            </h5>
                                        </td>
                                    </tr>
                                @endif
                                @if (isset($ticket->platform_fee) && $ticket->platform_fee > 0)
                                    <tr>
                                        <td class="text-right">
                                            <p class="title">@lang('Platform Fee')</p>
                                        </td>
                                        <td>
                                            <b>:</b>
                                        </td>
                                        <td class="text-left">
                                            <h5 class="value">
                                                {{ isset($general->cur_sym) ? __($general->cur_sym) : '₹' }}{{ number_format($ticket->platform_fee, 2) }}
                                            </h5>
                                        </td>
                                    </tr>
                                @endif
                                @if (isset($ticket->gst) && $ticket->gst > 0)
                                    <tr>
                                        <td class="text-right">
                                            <p class="title">@lang('GST')</p>
                                        </td>
                                        <td>
                                            <b>:</b>
                                        </td>
                                        <td class="text-left">
                                            <h5 class="value">
                                                {{ isset($general->cur_sym) ? __($general->cur_sym) : '₹' }}{{ number_format($ticket->gst, 2) }}
                                            </h5>
                                        </td>
                                    </tr>
                                @endif
                                <tr style="border-top: 2px solid #333;">
                                    <td class="text-right">
                                        <p class="title"><strong>@lang('Total Amount')</strong></p>
                                    </td>
                                    <td>
                                        <b>:</b>
                                    </td>
                                    <td class="text-left">
                                        <h5 class="value" style="color: #007bff; font-weight: bold;">
                                            {{ isset($general->cur_sym) ? __($general->cur_sym) : '₹' }}{{ showAmount($ticket->total_amount ?? ($ticket->sub_total ?? 0)) }}
                                        </h5>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Journey Details Section -->
                <div class="journey-details">
                    <h5>@lang('Journey Details')</h5>

                    @php
                        // Parse bus details from JSON if available
                        $busDetails = null;
                        if (!empty($ticket->bus_details)) {
                            $busDetails = json_decode($ticket->bus_details, true);
                        }

                        // Parse boarding point details from JSON if available
                        $boardingPointDetails = null;
                        if (!empty($ticket->boarding_point_details)) {
                            $boardingPointDetails = json_decode($ticket->boarding_point_details, true);
                        } else {
                            // Try to get boarding points from API response
                            $apiResponse = !empty($ticket->api_response)
                                ? json_decode($ticket->api_response, true)
                                : null;
                            if ($apiResponse && isset($apiResponse['Result']['BoardingPointsDetails'])) {
                                foreach ($apiResponse['Result']['BoardingPointsDetails'] as $point) {
                                    if ($point['CityPointIndex'] == $ticket->pickup_point) {
                                        $boardingPointDetails = $point;
                                        break;
                                    }
                                }
                            }
                        }

                        // Parse dropping point details from JSON if available
                        $droppingPointDetails = null;
                        if (!empty($ticket->dropping_point_details)) {
                            $droppingPointDetails = json_decode($ticket->dropping_point_details, true);
                        } else {
                            // Try to get dropping points from API response
                            $apiResponse = !empty($ticket->api_response)
                                ? json_decode($ticket->api_response, true)
                                : null;
                            if ($apiResponse && isset($apiResponse['Result']['DroppingPointsDetails'])) {
                                foreach ($apiResponse['Result']['DroppingPointsDetails'] as $point) {
                                    if ($point['CityPointIndex'] == $ticket->dropping_point) {
                                        $droppingPointDetails = $point;
                                        break;
                                    }
                                }
                            }
                        }

                        // Format departure and arrival times
                        $departureTime = null;
                        $arrivalTime = null;

                        if (isset($busDetails['departure_time'])) {
                            $departureTime = date('h:i A', strtotime($busDetails['departure_time']));
                        } elseif ($ticket->departure_time && $ticket->departure_time != '00:00:00') {
                            $departureTime = date('h:i A', strtotime($ticket->departure_time));
                        }

                        if (isset($busDetails['arrival_time'])) {
                            $arrivalTime = date('h:i A', strtotime($busDetails['arrival_time']));
                        } elseif ($ticket->arrival_time && $ticket->arrival_time != '00:00:00') {
                            $arrivalTime = date('h:i A', strtotime($ticket->arrival_time));
                        }

                        // Get pickup and dropping counter details
                        $pickupCounter = null;
                        $droppingCounter = null;

                        if ($ticket->pickup_point) {
                            $pickupCounter = \App\Models\Counter::find($ticket->pickup_point);
                        }

                        if ($ticket->dropping_point) {
                            $droppingCounter = \App\Models\Counter::find($ticket->dropping_point);
                        }
                    @endphp

                    <!-- <div class="journey-info">
                        <span class="label">@lang('Bus Type'):</span>
                        <span class="value">{{ $ticket->bus_type ?? ($busDetails['bus_type'] ?? __(@$ticket->trip->fleetType->name ?? 'N/A')) }}</span>
                    </div> -->

                    <div class="journey-info">
                        <span class="label">@lang('Bus Name'):</span>
                        <span
                            class="value">{{ $ticket->travel_name ?? ($busDetails['travel_name'] ?? __(@$ticket->trip->title ?? 'N/A')) }}</span>
                    </div>

                    <div class="journey-info">
                        <span class="label">@lang('Pickup Time'):</span>
                        <span class="value">{{ $departureTime ?? __(@$ticket->trip->start_time ?? 'N/A') }}</span>
                    </div>

                    <div class="journey-info">
                        <span class="label">@lang('Drop Time'):</span>
                        <span class="value">{{ $arrivalTime ?? __(@$ticket->trip->end_time ?? 'N/A') }}</span>
                    </div>

                    <!-- <div class="journey-info">
                        <span class="label">@lang('Pickup Point'):</span>
                        <span class="value">
                            @if (isset($boardingPointDetails['CityPointName']))
{{ __($boardingPointDetails['CityPointName']) }}
@elseif($pickupCounter)
{{ __($pickupCounter->name) }}
@else
{{ __('N/A') }}
@endif
                        </span>
                    </div> -->

                    <div class="journey-info">
                        <span class="label">@lang('Pickup Location'):</span>
                        <span class="value">
                            @if (isset($boardingPointDetails['CityPointLocation']))
                                {{ __($boardingPointDetails['CityPointLocation']) }}
                            @elseif($pickupCounter && $pickupCounter->address)
                                {{ __($pickupCounter->address) }}
                            @else
                                {{ __('N/A') }}
                            @endif
                        </span>
                    </div>

                    <!-- <div class="journey-info">
                        <span class="label">@lang('Pickup Time'):</span>
                        <span class="value">
                            @if (isset($boardingPointDetails['CityPointTime']))
{{ __(date('h:i A', strtotime($boardingPointDetails['CityPointTime']))) }}
@else
{{ $departureTime ?? __('N/A') }}
@endif
                        </span>
                    </div> -->

                    <!-- <div class="journey-info">
                        <span class="label">@lang('Dropping Point'):</span>
                        <span class="value">
                            @if (isset($droppingPointDetails['CityPointName']))
{{ __($droppingPointDetails['CityPointName']) }}
@elseif($droppingCounter)
{{ __($droppingCounter->name) }}
@else
{{ __('N/A') }}
@endif
                        </span>
                    </div> -->

                    <div class="journey-info">
                        <span class="label">@lang('Dropping Location'):</span>
                        <span class="value">
                            @if (isset($droppingPointDetails['CityPointLocation']))
                                {{ __($droppingPointDetails['CityPointLocation']) }}
                            @elseif($droppingCounter && $droppingCounter->address)
                                {{ __($droppingCounter->address) }}
                            @else
                                {{ __('N/A') }}
                            @endif
                        </span>
                    </div>

                    <!-- <div class="journey-info">
                        <span class="label">@lang('Dropping Time'):</span>
                        <span class="value">
                            @if (isset($droppingPointDetails['CityPointTime']))
{{ __(date('h:i A', strtotime($droppingPointDetails['CityPointTime']))) }}
@else
{{ $arrivalTime ?? __('N/A') }}
@endif
                        </span>
                    </div> -->

                    <!-- @if ($ticket->operator_pnr)
<div class="journey-info">
                        <span class="label">@lang('Operator PNR'):</span>
                        <span class="value">{{ __($ticket->operator_pnr) }}</span>
                    </div> -->
                    @endif
                </div>

                <!-- Terms and Conditions Section -->
                <div class="terms-conditions">
                    <h5>@lang('Terms and Conditions')</h5>
                    <ul>
                        <li>Please arrive at the boarding point at least 15 minutes before the scheduled departure time.
                        </li>
                        <li>This ticket is non-refundable and non-transferable.</li>
                        <!-- <li>Cancellation policy: Cancellations made 24 hours before departure may be eligible for a partial refund as per Ghumantoo's policy.</li> -->
                        <li>Passengers must carry a valid ID proof for verification.</li>
                        <li>Ghumantoo reserves the right to change the bus type, departure time, or seat allocation in
                            case of unavoidable circumstances.</li>
                        <li>Luggage allowance: 15kg per passenger. Extra luggage may incur additional charges.</li>
                        <li>Consumption of alcohol, smoking, and carrying illegal substances is strictly prohibited.
                        </li>
                        <li>Ghumantoo is not responsible for any loss or damage to personal belongings.</li>
                        <li>For any assistance, please contact our customer support at +91-XXXXXXXXXX.</li>
                    </ul>
                </div>

                <!-- Company Info Section -->
                <div class="company-info">
                    <p>Ghumantoo Bus Services | Your Trusted Travel Partner</p>
                    <p>Email: support@ghumantoo.com | Website: www.ghumantoo.com</p>
                    <p>© {{ date('Y') }} Ghumantoo. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="print-btn">
        <button type="button" class="cmn-btn btn-download" id="demo">@lang('Download Ticket')</button>
    </div>


    @php
        $fileName = slug(optional($ticket->user)->username ?? 'guest') . '_' . time();
    @endphp
    <!-- jquery -->
    <script src="{{ asset($activeTemplateTrue . 'js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset($activeTemplateTrue . 'js/html2pdf.bundle.min.js') }}"></script>
    <script>
        "use strict";
        const options = {
            margin: 0.3,
            filename: `{{ $fileName }}`,
            image: {
                type: 'jpeg',
                quality: 0.98
            },
            html2canvas: {
                scale: 2
            },
            jsPDF: {
                unit: 'in',
                format: 'A4',
                orientation: 'landscape'
            }
        }

        var objstr = document.getElementById('block1').innerHTML;
        var strr = objstr;
        $(document).on('click', '.btn-download', function(e) {
            e.preventDefault();
            var element = document.getElementById('demo');
            html2pdf().from(strr).set(options).save();
        });
    </script>
</body>

</html>
