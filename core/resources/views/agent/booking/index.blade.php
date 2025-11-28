@extends('agent.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="las la-ticket-alt text-primary"></i> {{ $pageTitle ?? 'My Bookings' }}</h6>
                <div>
                    <a href="{{ route('agent.bookings', array_merge(request()->query(), ['export' => 1])) }}"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="las la-file-download"></i> Export
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form class="row mb-3" method="GET" action="{{ route('agent.bookings') }}">
                    <input type="hidden" name="tab" value="{{ request('tab', $tab ?? 'upcoming') }}">

                    <div class="col-md-3 mb-2">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Search</label>
                        <input type="search" name="q" class="form-control" placeholder="Booking ID or Ticket No"
                            value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button class="btn btn-primary mr-2">Filter</button>
                        <a href="{{ route('agent.bookings') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab', $tab ?? 'upcoming') === 'upcoming' ? 'active' : '' }}"
                            href="{{ route('agent.bookings', array_merge(request()->except('tab'), ['tab' => 'upcoming'])) }}">Upcoming</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') === 'past' ? 'active' : '' }}"
                            href="{{ route('agent.bookings', array_merge(request()->except('tab'), ['tab' => 'past'])) }}">Past</a>
                    </li>
                </ul>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                {{-- <th>Booking ID</th> --}}
                                {{-- <th>Ticket No</th> --}}
                                <th>Boarding Point</th>
                                <th>Dropping Point</th>
                                <th>Journey Date</th>
                                <th>Commission (₹)</th>
                                {{-- <th>Status</th> --}}
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $bt)
                                @php
                                    $boardingPoint = json_decode($bt->boarding_point_details ?? '[]', true);
                                    $droppingPoint = json_decode($bt->dropping_point_details ?? '[]', true);
                                    $boardingPointName =
                                        !empty($boardingPoint) && isset($boardingPoint[0]['CityPointName'])
                                            ? $boardingPoint[0]['CityPointName'] .
                                                ', ' .
                                                ($boardingPoint[0]['CityPointLocation'] ?? '')
                                            : '-';
                                    $droppingPointName =
                                        !empty($droppingPoint) && isset($droppingPoint[0]['CityPointName'])
                                            ? $droppingPoint[0]['CityPointName'] .
                                                ', ' .
                                                ($droppingPoint[0]['CityPointLocation'] ?? '')
                                            : '-';
                                    $statusColor =
                                        $bt->status == 1 ? 'success' : ($bt->status == 3 ? 'danger' : 'warning');
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge badge-{{ $statusColor }}"
                                            style="width: 8px; height: 8px; border-radius: 50%; display: inline-block;"
                                            title="{{ $bt->status == 1 ? 'Confirmed' : ($bt->status == 2 ? 'Cancelled' : 'Pending') }}"></span>
                                        <small>
                                            {{ $bt->booking_id ?? '-' }}
                                        </small>

                                    </td>
                                    {{-- <td>{{ $bt->booking_id ?? '-' }}</td> --}}
                                    {{-- <td>{{ $bt->ticket_no ?? '-' }}</td> --}}
                                    <td>
                                        <small style="font-size:0.7rem;">{{ $boardingPointName }}</small>
                                    </td>
                                    <td>
                                        <small style="font-size:0.7rem;">{{ $droppingPointName }}</small>
                                    </td>
                                    <td>
                                        <small style="font-size:0.7rem;">
                                            {{ $bt->date_of_journey ? \Carbon\Carbon::parse($bt->date_of_journey)->format('d M Y') : '-' }}
                                        </small>
                                    </td>
                                    <td>
                                        <small
                                            style="font-size:0.7rem;">{{ number_format($bt->agent_commission_amount ?? 0, 2) }}
                                        </small>

                                    </td>
                                    {{-- <td>

                                    </td> --}}
                                    <td>
                                        <a href="{{ url('/users/print-ticket/' . $bt->id) }}" target="_blank"
                                            class="btn btn-sm btn-outline-primary">View</a>
                                        @if ($bt->status !== 2)
                                            @php
                                                $seats = is_array($bt->seats)
                                                    ? $bt->seats
                                                    : json_decode($bt->seats, true);
                                                $firstSeat = is_array($seats) && !empty($seats) ? $seats[0] : '';
                                            @endphp
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="cancelBooking(this, {{ $bt->id }})"
                                                data-booking-id="{{ $bt->operator_pnr ?? ($bt->pnr_number ?? $bt->id) }}"
                                                data-search-token-id="{{ $bt->search_token_id ?? '' }}"
                                                data-seat-id="{{ $firstSeat }}">
                                                Cancel
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No bookings found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $bookings->links() }}
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
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this booking?</p>
                    <div class="mb-3">
                        <label for="cancellationReason" class="form-label">Reason for cancellation (optional)</label>
                        <textarea class="form-control" id="cancellationReason" rows="3" placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Keep Booking</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelBtn">Yes, Cancel Booking</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function($) {
            'use strict';

            let bookingToCancel = null;

            // Booking Cancellation
            window.cancelBooking = function(el, bookingId) {
                bookingToCancel = bookingId;
                window.bookingCancelPayload = {
                    BookingId: el?.dataset?.bookingId || null,
                    SeatId: el?.dataset?.seatId || null,
                    SearchTokenId: el?.dataset?.searchTokenId || null
                };
                console.log('Cancellation payload:', window.bookingCancelPayload);
                $('#cancelBookingModal').modal('show');
            };

            $(document).ready(function() {
                $('#confirmCancelBtn').on('click', function() {
                    if (!bookingToCancel) return;

                    const reason = $('#cancellationReason').val();
                    const $btn = $(this);

                    $btn.prop('disabled', true).html(
                        '<i class="las la-spinner la-spin"></i> Cancelling...');

                    $.ajax({
                        url: "{{ url('api/users/cancel-ticket') }}",
                        type: 'POST',
                        data: {
                            UserIp: '{{ request()->ip() }}',
                            SearchTokenId: window.bookingCancelPayload?.SearchTokenId,
                            BookingId: window.bookingCancelPayload?.BookingId,
                            SeatId: window.bookingCancelPayload?.SeatId,
                            Remarks: reason
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Success: ' + (response.message ||
                                    'Booking cancelled successfully'));
                                $('#cancelBookingModal').modal('hide');
                                location.reload();
                            } else {
                                alert('Error: ' + (response.message ||
                                    'Failed to cancel booking'));
                            }
                        },
                        error: function(xhr) {
                            alert('Error: ' + (xhr.responseJSON?.message ||
                                'Failed to cancel booking'));
                        },
                        complete: function() {
                            $btn.prop('disabled', false).html('Yes, Cancel Booking');
                        }
                    });
                });
            });
        })(jQuery);
    </script>
@endpush
