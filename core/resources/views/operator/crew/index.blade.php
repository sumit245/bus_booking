@extends('operator.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title">Crew Assignments</h4>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('operator.crew.create') }}" class="btn btn-primary">
                                    <i class="las la-plus"></i> Assign Crew
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <input type="date" class="form-control" id="dateFilter"
                                    value="{{ request('date', now()->toDateString()) }}">
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="busFilter">
                                    <option value="">All Buses</option>
                                    @foreach ($buses as $bus)
                                        <option value="{{ $bus->id }}"
                                            {{ request('bus_id') == $bus->id ? 'selected' : '' }}>
                                            {{ $bus->travel_name }} ({{ $bus->bus_type }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="roleFilter">
                                    <option value="">All Roles</option>
                                    <option value="driver" {{ request('role') == 'driver' ? 'selected' : '' }}>Driver
                                    </option>
                                    <option value="conductor" {{ request('role') == 'conductor' ? 'selected' : '' }}>
                                        Conductor</option>
                                    <option value="attendant" {{ request('role') == 'attendant' ? 'selected' : '' }}>
                                        Attendant</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                                        Inactive</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>
                                        Completed</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                        Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary" onclick="applyFilters()">Filter</button>
                            </div>
                        </div>

                        <!-- Assignments Table -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Bus</th>
                                        <th>Staff Member</th>
                                        <th>Role</th>
                                        <th>Shift Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assignments as $assignment)
                                        <tr>
                                            <td>{{ $assignment->assignment_date->format('d M Y') }}</td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">
                                                        {{ $assignment->operatorBus->travel_name ?? 'N/A' }}</div>
                                                    <small
                                                        class="text-muted">{{ $assignment->operatorBus->bus_type ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">{{ $assignment->staff->full_name ?? 'N/A' }}</div>
                                                    <small
                                                        class="text-muted">{{ $assignment->staff->employee_id ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ ucfirst($assignment->role) }}</span>
                                            </td>
                                            <td>
                                                @if ($assignment->shift_start_time && $assignment->shift_end_time)
                                                    {{ \Carbon\Carbon::parse($assignment->shift_start_time)->format('H:i') }}
                                                    -
                                                    {{ \Carbon\Carbon::parse($assignment->shift_end_time)->format('H:i') }}
                                                @else
                                                    <span class="text-muted">Not set</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($assignment->status === 'active')
                                                    <span class="badge bg-success">Active</span>
                                                @elseif($assignment->status === 'completed')
                                                    <span class="badge bg-info">Completed</span>
                                                @elseif($assignment->status === 'cancelled')
                                                    <span class="badge bg-danger">Cancelled</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('operator.crew.show', $assignment->id) }}"
                                                        class="btn btn-sm btn-info" title="View">
                                                        <i class="las la-eye"></i>
                                                    </a>
                                                    <a href="{{ route('operator.crew.edit', $assignment->id) }}"
                                                        class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="las la-edit"></i>
                                                    </a>
                                                    <form action="{{ route('operator.crew.destroy', $assignment->id) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to delete this assignment?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="las la-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No crew assignments found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $assignments->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        function applyFilters() {
            const date = document.getElementById('dateFilter').value;
            const bus = document.getElementById('busFilter').value;
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value;

            const url = new URL(window.location);
            if (date) url.searchParams.set('date', date);
            else url.searchParams.delete('date');

            if (bus) url.searchParams.set('bus_id', bus);
            else url.searchParams.delete('bus_id');

            if (role) url.searchParams.set('role', role);
            else url.searchParams.delete('role');

            if (status) url.searchParams.set('status', status);
            else url.searchParams.delete('status');

            window.location.href = url.toString();
        }
    </script>
@endpush
