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
                            <th>@lang('AC / Non-Ac')</th>
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
                        <tr>
                            <td class="ticket-no" data-label="@lang('PNR Number')">{{ __($item->pnr_number) }}</td>
                            <td data-label="@lang('AC / Non-Ac')">
                                {{ $item->trip?->fleetType?->has_ac ? 'AC' : 'Non-Ac' }}
                            </td>
                            <td class="pickup" data-label="Starting Point">{{ __($item->pickup->name) }}</td>
                            <td class="drop" data-label="Dropping Point">{{ __($item->drop->name) }}</td>
                            <td class="date" data-label="Journey Date">{{ __(showDateTime($item->date_of_journey , 'd M, Y')) }}</td>
                            <td class="time" data-label="Pickup Time">
                                {{ $item->trip?->schedule?->start_from ? __(showDateTime($item->trip->schedule->start_from, 'H:i a')) : '--' }}
                            </td>
                            <td class="seats" data-label="Booked Seats">
                                {{ __($item->seats ?? '--') }}
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
                                           data-seats="{{ $item->seats }}"
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
        var html = '';
        html += `
                    <p class="d-flex flex-wrap justify-content-between pt-0"><strong>@lang('Journey Date')</strong>  <span>${info.date_of_journey}</span></p>
                    <p class="d-flex flex-wrap justify-content-between"><strong>@lang('PNR Number')</strong>  <span>${info.pnr_number}</span></p>
                    <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Route')</strong>  <span>${info.trip.start_from.name} @lang('to') ${info.trip.end_to.name}</span></p>
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