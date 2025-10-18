@extends('operator.layouts.app')

@section('panel')
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-0">Staff Management</h4>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('operator.staff.create') }}" class="btn btn--primary box--shadow1">
                <i class="fa fa-fw fa-plus"></i>@lang('Add New Staff')
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
                            <select class="form-control" id="roleFilter">
                                <option value="">All Roles</option>
                                <option value="driver">Driver</option>
                                <option value="conductor">Conductor</option>
                                <option value="attendant">Attendant</option>
                                <option value="manager">Manager</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="searchInput"
                                placeholder="Search by name, employee ID, phone...">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn--primary" onclick="applyFilters()">Filter</button>
                        </div>
                    </div>

                    <!-- Staff Table -->
                    <div class="table-responsive">
                        <table class="table table--light style--two" id="staffTable">
                            <thead>
                                <tr>
                                    <th>@lang('Employee ID')</th>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Role')</th>
                                    <th>@lang('Phone')</th>
                                    <th>@lang('WhatsApp')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($staff as $member)
                                    <tr>
                                        <td data-label="@lang('Employee ID')">
                                            <strong>{{ $member->employee_id }}</strong>
                                        </td>
                                        <td data-label="@lang('Name')">
                                            <div class="d-flex align-items-center">
                                                @if ($member->profile_photo)
                                                    <img src="{{ asset('storage/' . $member->profile_photo) }}"
                                                        alt="Profile" class="rounded-circle me-2" width="32"
                                                        height="32">
                                                @else
                                                    <div class="bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center"
                                                        style="width: 32px; height: 32px;">
                                                        <span
                                                            class="text-white fw-bold">{{ substr($member->first_name, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-bold">{{ $member->full_name }}</div>
                                                    <small class="text-muted">{{ $member->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="@lang('Role')">
                                            <span class="badge badge--info">{{ ucfirst($member->role) }}</span>
                                        </td>
                                        <td data-label="@lang('Phone')">{{ $member->phone }}</td>
                                        <td data-label="@lang('WhatsApp')">
                                            @if ($member->whatsapp_number)
                                                <span class="badge badge--success">Enabled</span>
                                            @else
                                                <span class="badge badge--secondary">Disabled</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Status')">
                                            @if ($member->is_active)
                                                <span class="badge badge--success">Active</span>
                                            @else
                                                <span class="badge badge--danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Actions')">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('operator.staff.show', $member->id) }}"
                                                    class="btn btn-sm btn--info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('operator.staff.edit', $member->id) }}"
                                                    class="btn btn-sm btn--warning" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('operator.staff.toggle-status', $member->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="btn btn-sm {{ $member->is_active ? 'btn--secondary' : 'btn--success' }}"
                                                        title="{{ $member->is_active ? 'Deactivate' : 'Activate' }}">
                                                        <i class="fa fa-{{ $member->is_active ? 'ban' : 'check' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('operator.staff.destroy', $member->id) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this staff member?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn--danger" title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">@lang('No staff members found.')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $staff->links() }}
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
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput').value;

            const url = new URL(window.location);
            if (role) url.searchParams.set('role', role);
            else url.searchParams.delete('role');

            if (status) url.searchParams.set('status', status);
            else url.searchParams.delete('status');

            if (search) url.searchParams.set('search', search);
            else url.searchParams.delete('search');

            window.location.href = url.toString();
        }

        // Auto-apply filters on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    </script>
@endpush
