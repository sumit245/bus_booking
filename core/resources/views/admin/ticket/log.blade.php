@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card b-radius--10 ">
                <div class="card-body">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('User')</th>
                                    <th>@lang('PNR Number')</th>
                                    <th>@lang('Journey Date')</th>
                                    <th>@lang('Trip')</th>
                                    <th>@lang('Pickup Point')</th>
                                    <th>@lang('Dropping Point')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Ticket Count')</th>
                                    <th>@lang('Fare')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($tickets as $item)
                                <tr>
                                    <td data-label="@lang('User')">
                                        <span class="font-weight-bold">{{ __(@$item->user->fullname) }}</span>
                                        <br>
                                        <span class="small"> <a href="{{ route('admin.users.detail', $item->user_id) }}"><span>@</span>{{ __(@$item->user->username) }}</a> </span>
                                    </td>
                                    <td data-label="@lang('PNR Number')">
                                        <span class="text-muted">{{ __($item->pnr_number) }}</span>
                                    </td>
                                    <td data-label="@lang('Journey Date')">
                                        {{ __(showDateTime($item->date_of_journey, 'd M, Y')) }}
                                    </td>
                                    <td data-label="@lang('Trip')">
                        @php
                            $fleetType = $item->trip?->fleetType?->name ?? $item->fleet_type ?? '';
                            $startFrom = $item->trip?->startFrom?->name ?? $item->start_from ?? '';
                            $endTo = $item->trip?->endTo?->name ?? $item->end_to ?? '';
                            
                            // Fallback to JSON data if available
                            if (empty($fleetType) && !empty($item->trip_details)) {
                                $tripDetails = json_decode($item->trip_details, true);
                                $fleetType = $tripDetails['fleet_type'] ?? $tripDetails['FleetType'] ?? '';
                            }
                            
                            if (empty($startFrom) && !empty($item->trip_details)) {
                                $tripDetails = json_decode($item->trip_details, true);
                                $startFrom = $tripDetails['start_from'] ?? $tripDetails['StartFrom'] ?? '';
                            }
                            
                            if (empty($endTo) && !empty($item->trip_details)) {
                                $tripDetails = json_decode($item->trip_details, true);
                                $endTo = $tripDetails['end_to'] ?? $tripDetails['EndTo'] ?? '';
                            }
                        @endphp
                        <span class="font-weight-bold">{{ !empty($fleetType) ? $fleetType : 'N/A' }}</span>
                        <br>
                        <span class="font-weight-bold">
                            {{ !empty($startFrom) ? $startFrom : 'N/A' }} - {{ !empty($endTo) ? $endTo : 'N/A' }}
                        </span>
                    </td>
                    <td data-label="@lang('Pickup Point')">
                        @php
                            $boardingPoint = json_decode($item->boarding_point_details, true);
                            if (isset($boardingPoint['CityPointAddress'])) {
                                echo $boardingPoint['CityPointAddress'];
                            } elseif (isset($boardingPoint['name'])) {
                                echo $boardingPoint['name'];
                            } elseif ($item->pickup_point) {
                                echo $item->pickup_point;
                            } else {
                                echo 'N/A';
                            }
                        @endphp
                    </td>
                    <td data-label="@lang('Dropping Point')">
                        @php
                            $droppingPoint = json_decode($item->dropping_point_details, true);
                            if (isset($droppingPoint['CityPointLocation'])) {
                                echo $droppingPoint['CityPointLocation'];
                            } elseif (isset($droppingPoint['name'])) {
                                echo $droppingPoint['name'];
                            } elseif ($item->dropping_point) {
                                echo $item->dropping_point;
                            } else {
                                echo 'N/A';
                            }
                        @endphp
                    </td>
                                    <td data-label="@lang('Status')">
                                        @if ($item->status == 1)
                                            <span class="badge badge--success font-weight-normal text--samll">@lang('Booked')</span>
                                        @elseif($item->status == 2)
                                            <span class="badge badge--warning font-weight-normal text--samll">@lang('Pending')</span>
                                        @else
                                            <span class="badge badge--danger font-weight-normal text--samll">@lang('Rejected')</span>
                                        @endif
                                    </td>
                                    <td data-label="@lang('Ticket Count')">
                                        {{ is_countable($item->seats) ? count($item->seats) : (is_array(json_decode($item->seats, true)) ? count(json_decode($item->seats, true)) : (is_numeric($item->seats) ? $item->seats : 0)) }}
                                    </td>
                                    <td data-label="@lang('Fare')">
                                        {{ __(showAmount($item->sub_total)) }} {{ __($general->cur_text) }}
                                    </td>
                                    <td data-label="@lang('Action')">
                                        <a href="javascript:void(0)" class="btn btn-sm btn--primary text--small viewTicket" 
                                           data-id="{{ $item->id }}" data-pnr="{{ $item->pnr_number }}">
                                            <i class="fa fa-eye"></i> @lang('View')
                                        </a>
                                        <a href="javascript:void(0)" class="btn btn-sm btn--info text--small printTicket"
                                           data-id="{{ $item->id }}" data-pnr="{{ $item->pnr_number }}">
                                            <i class="fa fa-print"></i> @lang('Print')
                                        </a>
                                        @if($item->status == 1 || $item->status == 2)
                                            <a href="javascript:void(0)" class="btn btn-sm btn--danger text--small cancelTicket"
                                               data-id="{{ $item->id }}" data-pnr="{{ $item->pnr_number }}">
                                                <i class="fa fa-times"></i> @lang('Cancel')
                                            </a>
                                        @endif
                                        @if($item->status == 1)
                                            <a href="javascript:void(0)" class="btn btn-sm btn--warning text--small refundTicket"
                                               data-id="{{ $item->id }}" data-pnr="{{ $item->pnr_number }}">
                                                <i class="fa fa-undo"></i> @lang('Refund')
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer py-4">
                    {{ paginateLinks($tickets) }}
                </div>
            </div>
        </div>
    </div>
@endsection
@push('breadcrumb-plugins')
<a class="btn btn--primary mx-2" type="button" href="{{route('ticket')}}">Book Ticket</a>
<form action="{{route('admin.vehicle.ticket.search', $scope ?? str_replace('admin.vehicle.ticket.', '', request()->route()->getName()))}}" method="GET" class="form-inline float-sm-right bg--white">
    <div class="input-group has_append">
        <input type="text" name="search" class="form-control" placeholder="@lang('Search PNR Number')" value="{{ $search ?? '' }}">
        <div class="input-group-append">
            <button class="btn btn--primary" type="submit"><i class="fa fa-search"></i></button>
        </div>
    </div>
</form>
@endpush

{{-- Ticket Detail Modal --}}
<div class="modal fade" id="ticketDetailModal" tabindex="-1" role="dialog" aria-labelledby="ticketDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketDetailModalLabel">@lang('Ticket Details')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="printableArea">
                <div class="ticket-header text-center mb-4">
                    <img src="{{ getImage(imagePath()['logoIcon']['path'] .'/logo.png') }}" alt="Logo" class="mb-3" style="max-width: 200px;">
                    <h3>@lang('Bus Ticket')</h3>
                </div>
                <div class="ticket-info">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <strong>@lang('PNR Number'):</strong>
                                <span id="pnrNumber"></span>
                            </div>
                            <div class="info-item mb-3">
                                <strong>@lang('Passenger'):</strong>
                                <span id="passengerName"></span>
                            </div>
                            <div class="info-item mb-3">
                                <strong>@lang('Journey Date'):</strong>
                                <span id="journeyDate"></span>
                            </div>
                            <div class="info-item mb-3">
                                <strong>@lang('Booking Date'):</strong>
                                <span id="bookingDate"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <strong>@lang('Bus Type'):</strong>
                                <span id="busType"></span>
                            </div>
                            <div class="info-item mb-3">
                                <strong>@lang('Route'):</strong>
                                <span id="route"></span>
                            </div>
                            <div class="info-item mb-3">
                                <strong>@lang('Pickup Point'):</strong>
                                <span id="pickupPoint"></span>
                            </div>
                            <div class="info-item mb-3">
                                <strong>@lang('Dropping Point'):</strong>
                                <span id="droppingPoint"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="info-item mb-3">
                                <strong>@lang('Seat(s)'):</strong>
                                <span id="seats"></span>
                            </div>
                            <div class="info-item mb-3">
                                <strong>@lang('Fare'):</strong>
                                <span id="fare"></span>
                            </div>
                            <div class="info-item mb-3">
                                <strong>@lang('Status'):</strong>
                                <span id="ticketStatus"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ticket-footer mt-4 text-center">
                    <p>@lang('Thank you for choosing our service!')</p>
                    <p class="small">@lang('This is a computer-generated ticket and does not require a physical signature.')</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                <button type="button" class="btn btn--info printBtn">@lang('Print Ticket')</button>
                <button type="button" class="btn btn--danger cancelBtn d-none">@lang('Cancel Ticket')</button>
                <button type="button" class="btn btn--warning refundBtn d-none">@lang('Refund Ticket')</button>
            </div>
        </div>
    </div>
</div>

{{-- Cancel Confirmation Modal --}}
<div class="modal fade" id="cancelConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.vehicle.ticket.cancel') }}" method="POST" id="cancelForm">
                @csrf
                <input type="hidden" name="ticket_id" id="cancel_ticket_id">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Cancel Ticket Confirmation')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>@lang('Are you sure you want to cancel this ticket?')</p>
                    <div class="form-group">
                        <label for="cancel_remarks">@lang('Cancellation Remarks')</label>
                        <textarea name="remarks" id="cancel_remarks" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('No')</button>
                    <button type="submit" class="btn btn--danger">@lang('Yes')</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Refund Confirmation Modal --}}
<div class="modal fade" id="refundConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.vehicle.ticket.refund') }}" method="POST" id="refundForm">
                @csrf
                <input type="hidden" name="ticket_id" id="refund_ticket_id">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Refund Ticket Confirmation')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>@lang('Are you sure you want to refund this ticket?')</p>
                    <div class="form-group">
                        <label for="refund_amount">@lang('Refund Amount')</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control" name="amount" id="refund_amount" required>
                            <div class="input-group-append">
                                <span class="input-group-text">{{ __($general->cur_text) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="refund_remarks">@lang('Refund Remarks')</label>
                        <textarea class="form-control" name="remarks" id="refund_remarks" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('No')</button>
                    <button type="submit" class="btn btn--warning">@lang('Yes, Refund')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
    (function($) {
        "use strict";
        
        $('.viewTicket, .printTicket').on('click', function() {
            var modal = $('#ticketDetailModal');
            var ticketId = $(this).data('id');
            var pnr = $(this).data('pnr');
            
            // AJAX call to get ticket details
            $.ajax({
                url: "{{ route('admin.vehicle.ticket.details') }}",
                type: "GET",
                data: {
                    id: ticketId,
                    pnr: pnr
                },
                success: function(response) {
                    if(response.success) {
                        var ticket = response.ticket;
                        
                        // Populate modal with ticket details
                        $('#pnrNumber').text(ticket.pnr_number);
                        $('#passengerName').text(ticket.user ? ticket.user.fullname : 'N/A');
                        $('#journeyDate').text(ticket.formatted_journey_date);
                        $('#bookingDate').text(ticket.formatted_booking_date);
                        $('#busType').text(ticket.fleet_type);
                        $('#route').text(ticket.start_from + ' - ' + ticket.end_to);
                        $('#pickupPoint').text(ticket.pickup_point);
                        $('#droppingPoint').text(ticket.dropping_point);
                        $('#seats').text(ticket.seat_numbers);
                        $('#fare').text(ticket.formatted_fare + ' ' + '{{ __($general->cur_text) }}');
                        
                        // Set ticket status
                        var statusText = '';
                        if(ticket.status == 1) {
                            statusText = '<span class="badge badge-success">@lang("Booked")</span>';
                            $('.cancelBtn, .refundBtn').removeClass('d-none');
                        } else if(ticket.status == 2) {
                            statusText = '<span class="badge badge-warning">@lang("Pending")</span>';
                            $('.cancelBtn').removeClass('d-none');
                            $('.refundBtn').addClass('d-none');
                        } else {
                            statusText = '<span class="badge badge-danger">@lang("Rejected")</span>';
                            $('.cancelBtn, .refundBtn').addClass('d-none');
                        }
                        $('#ticketStatus').html(statusText);
                        
                        // Set ticket IDs for cancel and refund
                        $('#cancel_ticket_id').val(ticket.id);
                        $('#refund_ticket_id').val(ticket.id);
                        $('#refund_amount').val(ticket.sub_total);
                        
                        // Show modal
                        modal.modal('show');
                    } else {
                        notify('error', response.message || 'Something went wrong!');
                    }
                },
                error: function(xhr) {
                    notify('error', 'Could not load ticket details');
                }
            });
        });
        
        // Print ticket
        $('.printBtn').on('click', function() {
            var printContents = document.getElementById('printableArea').innerHTML;
            var originalContents = document.body.innerHTML;
            
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            
            // Reinitialize the modal after printing
            $('#ticketDetailModal').modal('show');
        });
        
        // Cancel ticket button
        $('.cancelTicket').on('click', function() {
            var ticketId = $(this).data('id');
            $('#cancel_ticket_id').val(ticketId);
            $('#cancelConfirmModal').modal('show');
        });
        
        // Refund ticket button
        $('.refundTicket').on('click', function() {
            var ticketId = $(this).data('id');
            
            // Get ticket details for refund
            $.ajax({
                url: "{{ route('admin.vehicle.ticket.details') }}",
                type: "GET",
                data: {
                    id: ticketId
                },
                success: function(response) {
                    if(response.success) {
                        $('#refund_ticket_id').val(response.ticket.id);
                        $('#refund_amount').val(response.ticket.sub_total);
                        $('#refundConfirmModal').modal('show');
                    }
                }
            });
        });
        
        // Modal cancel button
        $('.cancelBtn').on('click', function() {
            var ticketId = $('#cancel_ticket_id').val();
            $('#ticketDetailModal').modal('hide');
            
            // Update form action with the correct ID
            var cancelAction = $('#cancelForm').attr('action');
            cancelAction = cancelAction.replace('__id__', ticketId);
            $('#cancelForm').attr('action', cancelAction);
            
            $('#cancelConfirmModal').modal('show');
        });
        
        // Modal refund button
        $('.refundBtn').on('click', function() {
            var ticketId = $('#refund_ticket_id').val();
            $('#ticketDetailModal').modal('hide');
            
            // Update form action with the correct ID
            var refundAction = $('#refundForm').attr('action');
            refundAction = refundAction.replace('__id__', ticketId);
            $('#refundForm').attr('action', refundAction);
            
            $('#refundConfirmModal').modal('show');
        });
    })(jQuery);
</script>
@endpush
