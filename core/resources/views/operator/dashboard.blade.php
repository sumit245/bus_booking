@extends('operator.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-xl-3 col-lg-6 col-sm-6">
            <div class="dashboard-w1 b-radius--10 box-shadow" data-bg="ff758e" data-before="ff4c6b"
                style="background: linear-gradient(135deg, #ff758e 0%, #ff4c6b 100%);">
                <div class="icon">
                    <i class="la la-route"></i>
                </div>
                <div class="details">

                    <h2 class="amount mb-2 font-weight-bold">{{ $stats['total_routes'] }}</h2>
                    <h6 class="mb-3">@lang('Total Routes')</h6>
                    <a href="{{ route('operator.routes.index') }}"
                        class="btn btn-sm btn-outline-light">@lang('View All')</a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-sm-6">
            <div class="dashboard-w1 b-radius--10 box-shadow" data-bg="54a69d" data-before="2c7870"
                style="background: linear-gradient(135deg, #54a69d 0%, #2c7870 100%);">
                <div class="details">
                    <h2 class="amount mb-2 font-weight-bold">{{ $stats['total_buses'] }}</h2>
                    <h6 class="mb-3">@lang('Total Buses')</h6>
                    <a href="{{ route('operator.buses.index') }}" class="btn btn-sm btn-outline-light">@lang('View All')</a>
                </div>
                <div class="icon">
                    <i class="la la-bus"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-sm-6">
            <div class="dashboard-w1 b-radius--10 box-shadow" data-bg="f09339" data-before="eb4b2b"
                style="background: linear-gradient(135deg, #f09339 0%, #eb4b2b 100%);">
                <div class="details">
                    <h2 class="amount mb-2 font-weight-bold">{{ $stats['total_bookings'] }}</h2>
                    <h6 class="mb-3">@lang('Total Bookings')</h6>
                    <a href="#" class="btn btn-sm btn-outline-light">@lang('View All')</a>
                </div>
                <div class="icon">
                    <i class="la la-ticket-alt"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-sm-6">
            <div class="dashboard-w1 b-radius--10 box-shadow" data-bg="667eea" data-before="764ba2"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="details">
                    <h2 class="amount mb-2 font-weight-bold">₹{{ number_format($stats['total_revenue'], 2) }}</h2>
                    <h6 class="mb-3">@lang('Total Revenue')</h6>
                    <a href="#" class="btn btn-sm btn-outline-light">@lang('View All')</a>
                </div>
                <div class="icon">
                    <i class="la la-money-bill-wave"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">@lang('Recent Bookings')</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Booking ID')</th>
                                    <th>@lang('Route')</th>
                                    <th>@lang('Passenger')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Status')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        @lang('No bookings found')
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">@lang('Quick Actions')</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('operator.routes.create') }}" class="btn btn--primary btn-block">
                            <i class="la la-plus"></i> @lang('Add New Route')
                        </a>
                        <a href="{{ route('operator.buses.create') }}" class="btn btn--success btn-block"
                            title="@lang('Add a new bus to your fleet')">
                            <i class="la la-bus"></i> @lang('Add New Bus')
                        </a>
                        <a href="{{ route('operator.buses.index') }}" class="btn btn--info btn-block">
                            <i class="la la-bus"></i> @lang('Manage Buses')
                        </a>
                        <a href="#" class="btn btn--warning btn-block">
                            <i class="la la-ticket-alt"></i> @lang('View Bookings')
                        </a>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="card-title">@lang('Profile Status')</h4>
                </div>
                <div class="card-body">
                    <div class="profile-status">
                        <div class="status-item">
                            <span class="status-label">@lang('Basic Details')</span>
                            <span
                                class="status-badge {{ $operator->basic_details_completed ? 'badge--success' : 'badge--danger' }}">
                                {{ $operator->basic_details_completed ? __('Completed') : __('Pending') }}
                            </span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">@lang('Company Details')</span>
                            <span
                                class="status-badge {{ $operator->company_details_completed ? 'badge--success' : 'badge--danger' }}">
                                {{ $operator->company_details_completed ? __('Completed') : __('Pending') }}
                            </span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">@lang('Documents')</span>
                            <span
                                class="status-badge {{ $operator->documents_completed ? 'badge--success' : 'badge--danger' }}">
                                {{ $operator->documents_completed ? __('Completed') : __('Pending') }}
                            </span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">@lang('Bank Details')</span>
                            <span
                                class="status-badge {{ $operator->bank_details_completed ? 'badge--success' : 'badge--danger' }}">
                                {{ $operator->bank_details_completed ? __('Completed') : __('Pending') }}
                            </span>
                        </div>
                    </div>

                    @if (!$operator->all_details_completed)
                        <div class="mt-3">
                            <a href="{{ route('operator.profile') }}" class="btn btn--primary btn-sm">
                                @lang('Complete Profile')
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .profile-status .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .profile-status .status-item:last-child {
            border-bottom: none;
        }

        .status-label {
            font-weight: 500;
            color: #495057;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge--success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge--danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
@endpush
