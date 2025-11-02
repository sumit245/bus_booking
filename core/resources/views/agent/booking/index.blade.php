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
                                <th>Booking ID</th>
                                <th>Ticket No</th>
                                <th>Journey Date</th>
                                <th>Amount</th>
                                <th>Commission</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $booking)
                                @php $bt = $booking->bookedTicket; @endphp
                                <tr>
                                    <td>{{ $loop->iteration + ($bookings->currentPage() - 1) * $bookings->perPage() }}</td>
                                    <td>{{ optional($bt)->booking_id ?? '-' }}</td>
                                    <td>{{ optional($bt)->ticket_no ?? '-' }}</td>
                                    <td>{{ optional($bt)->date_of_journey ?? '-' }}</td>
                                    <td>{{ optional($bt)->total_amount ?? '-' }}</td>
                                    <td>{{ number_format($booking->total_commission_earned ?? 0, 2) }}</td>
                                    <td>{{ ucfirst($booking->booking_status ?? '-') }}</td>
                                    <td>
                                        <a href="{{ route('agent.bookings.show', $booking->id) }}"
                                            class="btn btn-sm btn-outline-primary">View</a>
                                        @if ($booking->booking_status !== 'cancelled')
                                            <form method="POST"
                                                action="{{ route('agent.bookings.cancel', $booking->id) }}"
                                                style="display:inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Cancel this booking?')">Cancel</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No bookings found</td>
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
@endsection
