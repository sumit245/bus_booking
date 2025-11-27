@extends('operator.layouts.app')

@section('panel')
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-0">@lang('My Bookings')</h4>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('operator.bookings.create') }}" class="btn btn--primary box--shadow1">
                <i class="fa fa-fw fa-plus"></i>@lang('Block Seats')
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-control" id="busFilter">
                                <option value="">@lang('All Buses')</option>
                                @foreach ($buses as $bus)
                                    <option value="{{ $bus->id }}"
                                        {{ request('bus_id') == $bus->id ? 'selected' : '' }}>
                                        {{ $bus->travel_name }} ({{ $bus->bus_type }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="routeFilter">
                                <option value="">@lang('All Routes')</option>
                                @foreach ($routes as $route)
                                    <option value="{{ $route->id }}"
                                        {{ request('route_id') == $route->id ? 'selected' : '' }}>
                                        {{ $route->originCity->city_name }} → {{ $route->destinationCity->city_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="statusFilter">
                                <option value="">@lang('All Status')</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                                    @lang('Active')</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                    @lang('Cancelled')</option>
                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>
                                    @lang('Expired')</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" id="dateFrom" value="{{ request('date_from') }}"
                                placeholder="@lang('From Date')">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn--primary" onclick="applyFilters()">@lang('Filter')</button>
                        </div>
                    </div>

                    <!-- Bookings Table -->
                    <div class="table-responsive">
                        <table class="table table--light style--two" id="bookingsTable">
                            <thead>
                                <tr>
                                    <th>@lang('Booking ID')</th>
                                    <th>@lang('Bus')</th>
                                    <th>@lang('Route')</th>
                                    <th>@lang('Blocked Seats')</th>
                                    <th>@lang('Date Range')</th>
                                    <th>@lang('Reason')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Created')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookings as $booking)
                                    <tr>
                                        <td data-label="@lang('Booking ID')">
                                            <strong>#{{ $booking->id }}</strong>
                                        </td>
                                        <td data-label="@lang('Bus')">
                                            <div>
                                                <div class="fw-bold">{{ $booking->operatorBus->travel_name }}</div>
                                                <small class="text-muted">{{ $booking->operatorBus->bus_type }}</small>
                                            </div>
                                        </td>
                                        <td data-label="@lang('Route')">
                                            {{ $booking->operatorRoute->originCity }} →
                                            {{ $booking->operatorRoute->destinationCity }}
                                        </td>
                                        <td data-label="@lang('Blocked Seats')">
                                            <span class="badge badge--info">{{ $booking->total_seats_blocked }}
                                                seats</span>
                                            <br><small class="text-muted">{{ $booking->seats_list }}</small>
                                        </td>
                                        <td data-label="@lang('Date Range')">
                                            <strong>{{ $booking->date_range }}</strong>
                                            @if ($booking->is_date_range)
                                                <br><small class="text-muted">@lang('Date Range')</small>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Reason')">
                                            {{ $booking->booking_reason ?: 'N/A' }}
                                        </td>
                                        <td data-label="@lang('Status')">
                                            @if ($booking->status === 'active')
                                                <span class="badge badge--success">@lang('Active')</span>
                                            @elseif($booking->status === 'cancelled')
                                                <span class="badge badge--danger">@lang('Cancelled')</span>
                                            @else
                                                <span class="badge badge--secondary">@lang('Expired')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Created')">
                                            {{ $booking->created_at->format('M d, Y') }}
                                        </td>
                                        <td data-label="@lang('Actions')">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('operator.bookings.show', $booking) }}"
                                                    class="btn btn-sm btn--info" title="@lang('View')">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('operator.bookings.edit', $booking) }}"
                                                    class="btn btn-sm btn--warning" title="@lang('Edit')">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('operator.bookings.toggle-status', $booking) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="btn btn-sm {{ $booking->status === 'active' ? 'btn--secondary' : 'btn--success' }}"
                                                        title="{{ $booking->status === 'active' ? 'Cancel' : 'Activate' }}">
                                                        <i
                                                            class="fa fa-{{ $booking->status === 'active' ? 'ban' : 'check' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('operator.bookings.destroy', $booking) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this booking?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn--danger"
                                                        title="@lang('Delete')">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">@lang('No bookings found.')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $bookings->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        function applyFilters() {
            const busId = document.getElementById('busFilter').value;
            const routeId = document.getElementById('routeFilter').value;
            const status = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;

            const params = new URLSearchParams();
            if (busId) params.append('bus_id', busId);
            if (routeId) params.append('route_id', routeId);
            if (status) params.append('status', status);
            if (dateFrom) params.append('date_from', dateFrom);

            const url = new URL(window.location);
            url.search = params.toString();
            window.location.href = url.toString();
        }

        // Auto-apply filters on change
        document.getElementById('busFilter').addEventListener('change', applyFilters);
        document.getElementById('routeFilter').addEventListener('change', applyFilters);
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('dateFrom').addEventListener('change', applyFilters);
    </script>
@endpush
