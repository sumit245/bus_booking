@extends('agent.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="las la-chart-line text-primary"></i> My Earnings</h6>
                <div>
                    <a href="{{ route('agent.earnings', array_merge(request()->query(), ['export' => 1])) }}"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="las la-file-download"></i> Export
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('agent.earnings') }}" class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button class="btn btn-primary mr-2">Filter</button>
                        <a href="{{ route('agent.earnings') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Booking ID</th>
                                <th>Ticket No</th>
                                <th>Commission</th>
                                <th>Payment Status</th>
                                <th>Paid At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $tx)
                                @php $bt = $tx->bookedTicket; @endphp
                                <tr>
                                    <td>{{ $loop->iteration + ($transactions->currentPage() - 1) * $transactions->perPage() }}
                                    </td>
                                    <td>{{ $tx->created_at->toDateString() }}</td>
                                    <td>{{ optional($bt)->booking_id ?? '-' }}</td>
                                    <td>{{ optional($bt)->ticket_no ?? '-' }}</td>
                                    <td>{{ number_format($tx->total_commission_earned ?? 0, 2) }}</td>
                                    <td>{{ ucfirst($tx->payment_status ?? '-') }}</td>
                                    <td>{{ optional($tx->commission_paid_at)->toDateString() ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No transactions found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
