@extends($activeTemplate.'layouts.master')
@section('content')
<!-- booking history Starts Here -->
<section class="dashboard-section padding-top padding-bottom">
    <div class="container">
        <div class="dashboard-wrapper">
            <div class="booking-table-wrapper">
                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>@lang('PNR Number')</th>
                            <th>@lang('Bus Type')</th>
                            <th>@lang('Starting Point')</th>
                            <th>@lang('Dropping Point')</th>
                            <th>@lang('Journey Date')</th>
                            <th>@lang('Pickup Time')</th>
                            <th>@lang('Booked Seats')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Fare')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookedTickets as $item)
                        @php
                            // Parse bus details from JSON if available
                            $busDetails = null;
                            if (!empty($item->bus_details)) {
                                $busDetails = json_decode($item->bus_details, true);
                            }
                            
                            // Parse boarding point details from JSON if available
                            $boardingPointDetails = null;
                            if (!empty($item->boarding_point_details)) {
                                $boardingPointDetails = json_decode($item->boarding_point_details, true);
                            }
                            
                            // Parse dropping point details from JSON if available
                            $droppingPointDetails = null;
                            if (!empty($item->dropping_point_details)) {
                                $droppingPointDetails = json_decode($item->dropping_point_details, true);
                            }
                            
                            // Format departure time
                            $departureTime = null;
                            if (isset($busDetails['departure_time'])) {
                                $departureTime = date('h:i A', strtotime($busDetails['departure_time']));
                            } elseif ($item->departure_time && $item->departure_time != '00:00:00') {
                                $departureTime = date('h:i A', strtotime($item->departure_time));
                            }
                        @endphp
                        <tr>
                            <td class="ticket-no" data-label="@lang('PNR Number')">{{ __($item->pnr_number) }}</td>
                            <td data-label="@lang('Bus Type')">
                                {{ $item->bus_type ?? ($busDetails['bus_type'] ?? ($item->trip?->fleetType?->has_ac ? 'AC' : 'Non-Ac')) }}
                            </td>
                            <td class="pickup" data-label="Starting Point">
                                @if(isset($boardingPointDetails['CityPointName']))
                                    {{ __($boardingPointDetails['CityPointName']) }}
                                @elseif(isset($item->pickup->name))
                                    {{ __($item->pickup->name) }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="drop" data-label="Dropping Point">
                                @if(isset($droppingPointDetails['CityPointName']))
                                    {{ __($droppingPointDetails['CityPointName']) }}
                                @elseif(isset($item->drop->name))
                                    {{ __($item->drop->name) }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="date" data-label="Journey Date">{{ __(showDateTime($item->date_of_journey , 'd M, Y')) }}</td>
                            <td class="time" data-label="Pickup Time">
                                @if(isset($boardingPointDetails['CityPointTime']))
                                    {{ __(date('h:i A', strtotime($boardingPointDetails['CityPointTime']))) }}
                                @elseif($departureTime)
                                    {{ $departureTime }}
                                @elseif($item->trip?->schedule?->start_from)
                                    {{ __(showDateTime($item->trip->schedule->start_from, 'H:i a')) }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="seats" data-label="Booked Seats">
                                {{ is_array($item->seats) ? implode(',', $item->seats) : __($item->seats ?? '--') }}
                            </td>
                            <td data-label="@lang('Status')">
                                @if($item->status == 1)
                                <span class="badge badge--success"> @lang('Booked')</span>
                                @elseif($item->status == 2)
                                <span class="badge badge--warning"> @lang('Pending')</span>
                                @elseif($item->status == 3)
                                <span class="badge badge--danger"> @lang('Cancelled')</span>
                                @else
                                <span class="badge badge--danger"> @lang('Rejected')</span>
                                @endif
                            </td>
                            <td class="fare" data-label="Fare">{{ __(showAmount($item->sub_total)) }} {{ __($general->cur_text) }}</td>
                            <td class="action" data-label="Action">
                                <div class="action-button-wrapper">
                                    @if ($item->date_of_journey >= \Carbon\Carbon::today()->format('Y-m-d') && $item->status == 1)
                                        <a href="{{ route('user.ticket.print', $item->id) }}" target="_blank" class="print"><i class="las la-print"></i></a>
                                        
                                        <!-- Add Cancel Button -->
                                        <a href="javascript:void(0)" class="cancel-ticket text-danger" 
                                           data-id="{{ $item->id }}" 
                                           data-pnr="{{ $item->pnr_number }}"
                                           data-seats="{{ is_array($item->seats) ? implode(',', $item->seats) : $item->seats }}"
                                           data-bs-toggle="modal" 
                                           data-bs-target="#cancelModal">
                                            <i class="las la-times-circle"></i>
                                        </a>
                                    @else
                                        <a href="javascript:void(0)" class="checkinfo" data-info="{{ $item }}" data-bs-toggle="modal" data-bs-target="#infoModal"><i class="las la-info-circle"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="text-center" colspan="100%">{{ $emptyMessage }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($bookedTickets->hasPages())
                {{ paginateLinks($bookedTickets) }}
            @endif
        </div>
    </div>
</section>
<!-- booking history end Here -->

<!-- Info Modal -->
<div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"> @lang('Ticket Booking History')</h5>
                <button type="button" class="w-auto btn--close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <div class="modal-body p-4">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--danger w-auto btn--sm px-3" data-bs-dismiss="modal">
                    @lang('Close')
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Ticket Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"> @lang('Cancel Ticket')</h5>
                <button type="button" class="w-auto btn--close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <div class="modal-body p-4">
                <form id="cancelTicketForm">
                    <input type="hidden" name="ticket_id" id="ticket_id">
                    
                    <div class="alert alert-warning">
                        <p><i class="las la-exclamation-triangle"></i> @lang('Are you sure you want to cancel this ticket?')</p>
                        <p class="mb-0">@lang('This action cannot be undone.')</p>
                    </div>
                    
                    <div class="ticket-details mb-3">
                        <p><strong>@lang('PNR Number'):</strong> <span id="cancel-pnr"></span></p>
                        <p><strong>@lang('Seats'):</strong> <span id="cancel-seats"></span></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="remarks" class="form-label">@lang('Reason for Cancellation')</label>
                        <textarea name="remarks" id="remarks" class="form--control" rows="3" placeholder="@lang('Please provide a reason for cancellation')"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--dark w-auto btn--sm px-3" data-bs-dismiss="modal">
                    @lang('Close')
                </button>
                <button type="button" class="btn btn--danger w-auto btn--sm px-3" id="confirmCancelBtn">
                    @lang('Cancel Ticket')
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .modal-body p:not(:last-child){
        border-bottom: 1px dashed #ebebeb;
        padding:5px 0;
    }
    .action-button-wrapper a {
        margin: 0 5px;
        font-size: 18px;
    }
    .cancel-ticket {
        color: #dc3545;
    }
    .cancel-ticket:hover {
        color: #bd2130;
    }
</style>
@endpush

@push('script')
<script>
    "use strict"

    $('.checkinfo').on('click', function() {
        var info = $(this).data('info');
        var modal = $('#infoModal');
        
        // Parse JSON data if available
        var busDetails = null;
        if (info.bus_details) {
            try {
                busDetails = typeof info.bus_details === 'object' ? info.bus_details : JSON.parse(info.bus_details);
            } catch (e) {
                console.error('Error parsing bus details:', e);
            }
        }
        
        var boardingPointDetails = null;
        if (info.boarding_point_details) {
            try {
                boardingPointDetails = typeof info.boarding_point_details === 'object' ? info.boarding_point_details : JSON.parse(info.boarding_point_details);
            } catch (e) {
                console.error('Error parsing boarding point details:', e);
            }
        }
        
        var droppingPointDetails = null;
        if (info.dropping_point_details) {
            try {
                droppingPointDetails = typeof info.dropping_point_details === 'object' ? info.dropping_point_details : JSON.parse(info.dropping_point_details);
            } catch (e) {
                console.error('Error parsing dropping point details:', e);
            }
        }
        
        // Format departure and arrival times
        var departureTime = null;
        var arrivalTime = null;
        
        if (busDetails && busDetails.departure_time) {
            departureTime = new Date(busDetails.departure_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        } else if (info.departure_time && info.departure_time !== '00:00:00') {
            departureTime = new Date('2000-01-01T' + info.departure_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }
        
        if (busDetails && busDetails.arrival_time) {
            arrivalTime = new Date(busDetails.arrival_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        } else if (info.arrival_time && info.arrival_time !== '00:00:00') {
            arrivalTime = new Date('2000-01-01T' + info.arrival_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }
        
        // Get pickup and dropping point names
        var pickupName = boardingPointDetails ? boardingPointDetails.CityPointName : (info.pickup ? info.pickup.name : 'N/A');
        var droppingName = droppingPointDetails ? droppingPointDetails.CityPointName : (info.drop ? info.drop.name : 'N/A');
        
        // Get bus type and travel name
        var busType = info.bus_type || (busDetails ? busDetails.bus_type : 'N/A');
        var travelName = info.travel_name || (busDetails ? busDetails.travel_name : 'N/A');
        
        var html = `
            <p class="d-flex flex-wrap justify-content-between pt-0"><strong>@lang('Journey Date')</strong>  <span>${info.date_of_journey}</span></p>
            <p class="d-flex flex-wrap justify-content-between"><strong>@lang('PNR Number')</strong>  <span>${info.pnr_number}</span></p>
            <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Bus Type')</strong>  <span>${busType}</span></p>
            <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Bus Name')</strong>  <span>${travelName}</span></p>
            <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Pickup Point')</strong>  <span>${pickupName}</span></p>
            <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Dropping Point')</strong>  <span>${droppingName}</span></p>
            ${departureTime ? `<p class="d-flex flex-wrap justify-content-between"><strong>@lang('Departure Time')</strong>  <span>${departureTime}</span></p>` : ''}
            ${arrivalTime ? `<p class="d-flex flex-wrap justify-content-between"><strong>@lang('Arrival Time')</strong>  <span>${arrivalTime}</span></p>` : ''}
            <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Fare')</strong>  <span>${parseInt(info.sub_total).toFixed(2)} {{ __($general->cur_text) }}</span></p>
            <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Status')</strong>  <span>${info.status == 1 ? '<span class="badge badge--success">@lang('Successful')</span>' : info.status == 2 ? '<span class="badge badge--warning">@lang('Pending')</span>' : info.status == 3 ? '<span class="badge badge--danger">@lang('Cancelled')</span>' : '<span class="badge badge--danger">@lang('Rejected')</span>'}</span></p>
        `;
        modal.find('.modal-body').html(html);
    });
    
    // Handle cancel ticket button click
    $('.cancel-ticket').on('click', function() {
        var ticketId = $(this).data('id');
        var pnr = $(this).data('pnr');
        var seats = $(this).data('seats');
        
        $('#ticket_id').val(ticketId);
        $('#cancel-pnr').text(pnr);
        $('#cancel-seats').text(seats);
    });
    
    // Handle confirm cancel button click
    $('#confirmCancelBtn').on('click', function() {
        var $btn = $(this);
        var ticketId = $('#ticket_id').val();
        var remarks = $('#remarks').val();
        
        // Disable button and show loading state
        $btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> @lang('Processing')');
        
        // Send AJAX request to cancel the ticket
        $.ajax({
            url: "{{ route('user.ticket.cancel') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                ticket_id: ticketId,
                remarks: remarks
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert('Ticket cancelled successfully');
                    
                    // Close the modal
                    $('#cancelModal').modal('hide');
                    
                    // Reload the page to show updated status
                    location.reload();
                } else {
                    // Show error message
                    alert(response.message || 'Failed to cancel ticket');
                    $btn.prop('disabled', false).text('@lang('Cancel Ticket')');
                }
            },
            error: function(xhr) {
                // Show error message
                alert(xhr.responseJSON?.message || 'An error occurred. Please try again.');
                $btn.prop('disabled', false).text('@lang('Cancel Ticket')');
            }
        });
    });
</script>
@endpush
