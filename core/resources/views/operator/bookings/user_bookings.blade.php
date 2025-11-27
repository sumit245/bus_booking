@extends('operator.layouts.app')

@section('panel')
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-0">@lang('User Bookings')</h4>
        </div>
        <div class="col-md-4 text-right">
            <button onclick="exportToExcel()" class="btn btn--success">
                <i class="las la-file-excel"></i> @lang('Export to Excel')
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <!-- Filters -->
                    <!-- First Row: Bus, Route, Status -->
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">@lang('Bus')</label>
                            <select class="form-control" id="busFilter">
                                <option value="">@lang('All Buses')</option>
                                @foreach ($buses as $bus)
                                    <option value="{{ $bus->id }}"
                                        {{ request('bus_id') == $bus->id ? 'selected' : '' }}>
                                        {{ $bus->travel_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">@lang('Route')</label>
                            <select class="form-control" id="routeFilter">
                                <option value="">@lang('All Routes')</option>
                                @foreach ($routes as $route)
                                    <option value="{{ $route->id }}"
                                        {{ request('route_id') == $route->id ? 'selected' : '' }}>
                                        {{ optional($route->originCity)->city_name }} →
                                        {{ optional($route->destinationCity)->city_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">@lang('Status')</label>
                            <select class="form-control" id="statusFilter">
                                <option value="">@lang('All Status')</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>@lang('Booked')
                                </option>
                                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>@lang('Cancelled')
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Second Row: From Date, To Date, Rows Per Page -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label"
                                style="font-size: 12px; margin-bottom: 4px;">@lang('From Date')</label>
                            <input type="date" class="form-control" id="dateFrom" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"
                                style="font-size: 12px; margin-bottom: 4px;">@lang('To Date')</label>
                            <input type="date" class="form-control" id="dateTo" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"
                                style="font-size: 12px; margin-bottom: 4px;">@lang('Rows per page')</label>
                            <select class="form-control" id="perPageSelect">
                                <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15</option>
                                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bookings Table -->
                    <div class="table-responsive">
                        <table class="table table--light style--two" id="bookingsTable">
                            <thead>
                                <tr>
                                    <th>
                                        <a href="#" onclick="sortBy('id')" class="text-white">
                                            @lang('Booking ID')
                                            <i class="las la-sort"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="#" onclick="sortBy('travel_name')" class="text-white">
                                            @lang('Bus')
                                            <i class="las la-sort"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="#" onclick="sortBy('origin_city')" class="text-white">
                                            @lang('Route')
                                            <i class="las la-sort"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="#" onclick="sortBy('ticket_count')" class="text-white">
                                            @lang('Seats')
                                            <i class="las la-sort"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="#" onclick="sortBy('date_of_journey')" class="text-white">
                                            @lang('Journey Date')
                                            <i class="las la-sort"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="#" onclick="sortBy('total_amount')" class="text-white">
                                            @lang('Amount')
                                            <i class="las la-sort"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="#" onclick="sortBy('created_at')" class="text-white">
                                            @lang('Created')
                                            <i class="las la-sort"></i>
                                        </a>
                                    </th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookings as $booking)
                                    @php
                                        $boardingDetails = json_decode($booking->boarding_point_details, true);
                                        $droppingDetails = json_decode($booking->dropping_point_details, true);
                                        $boardingPoint = isset($boardingDetails[0]['PointLocation'])
                                            ? $boardingDetails[0]['PointLocation']
                                            : '';
                                        $droppingPoint = isset($droppingDetails[0]['PointLocation'])
                                            ? $droppingDetails[0]['PointLocation']
                                            : '';
                                    @endphp
                                    <tr>
                                        <td data-label="@lang('Booking ID')">
                                            <span class="badge"
                                                style="width: 10px; height: 10px; border-radius: 50%; display: inline-block; background-color: {{ $booking->status == 1 ? '#28a745' : '#dc3545' }};"></span>
                                            <strong>#{{ $booking->id }}</strong>
                                        </td>
                                        <td data-label="@lang('Bus')">
                                            <div>
                                                <div class="fw-bold">{{ $booking->travel_name }}</div>
                                                <small class="text-muted">{{ $booking->bus_type }}</small>
                                            </div>
                                        </td>
                                        <td data-label="@lang('Route')">
                                            <div>
                                                <div>{{ $booking->origin_city }} → {{ $booking->destination_city }}</div>
                                                @if ($boardingPoint || $droppingPoint)
                                                    <small class="text-muted">
                                                        @if ($boardingPoint)
                                                            {{ $boardingPoint }}
                                                        @endif
                                                        @if ($boardingPoint && $droppingPoint)
                                                            →
                                                        @endif
                                                        @if ($droppingPoint)
                                                            {{ $droppingPoint }}
                                                        @endif
                                                    </small>
                                                @endif
                                            </div>
                                        </td>
                                        <td data-label="@lang('Seats Booked')">
                                            <span
                                                class="badge badge--info">{{ (int) ($booking->ticket_count ?? 0) }}</span>
                                        </td>
                                        <td data-label="@lang('Journey Date')">
                                            {{ $booking->date_of_journey }}
                                        </td>
                                        <td data-label="@lang('Amount')">
                                            <div>
                                                <div class="fw-bold">{{ showAmount($booking->total_amount ?? 0) }}</div>
                                                <small class="text-muted">@lang('Paid:')
                                                    {{ showAmount($booking->paid_amount ?? 0) }}</small>
                                            </div>
                                        </td>
                                        <td data-label="@lang('Created')">
                                            {{ optional($booking->created_at)->format('M d, Y') }}
                                        </td>
                                        <td data-label="@lang('Actions')">
                                            <a href="{{ route('user.print.ticket', $booking->id) }}" target="_blank"
                                                class="btn btn-sm btn--primary" title="@lang('View Ticket')">
                                                <i class="las la-ticket-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">@lang('No bookings found.')</td>
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
            const dateTo = document.getElementById('dateTo').value;
            const perPage = document.getElementById('perPageSelect').value;

            const params = new URLSearchParams(window.location.search);

            if (busId) params.set('bus_id', busId);
            else params.delete('bus_id');
            if (routeId) params.set('route_id', routeId);
            else params.delete('route_id');
            if (status) params.set('status', status);
            else params.delete('status');
            if (dateFrom) params.set('date_from', dateFrom);
            else params.delete('date_from');
            if (dateTo) params.set('date_to', dateTo);
            else params.delete('date_to');
            if (perPage) params.set('per_page', perPage);
            else params.delete('per_page');

            // Keep existing sort parameters
            const sortBy = params.get('sort_by');
            const sortOrder = params.get('sort_order');

            const url = new URL(window.location);
            url.search = params.toString();
            window.location.href = url.toString();
        }

        function sortBy(column) {
            event.preventDefault();
            const params = new URLSearchParams(window.location.search);
            const currentSort = params.get('sort_by');
            const currentOrder = params.get('sort_order') || 'asc';

            // Toggle order if same column, otherwise default to asc
            let newOrder = 'asc';
            if (currentSort === column) {
                newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            }

            params.set('sort_by', column);
            params.set('sort_order', newOrder);

            window.location.href = '?' + params.toString();
        }

        function exportToExcel() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', '1');
            window.location.href = '?' + params.toString();
        }

        document.getElementById('busFilter').addEventListener('change', applyFilters);
        document.getElementById('routeFilter').addEventListener('change', applyFilters);
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('dateFrom').addEventListener('change', applyFilters);
        document.getElementById('dateTo').addEventListener('change', applyFilters);
        document.getElementById('perPageSelect').addEventListener('change', applyFilters);
    </script>
@endpush
