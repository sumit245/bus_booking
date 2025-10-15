@extends('operator.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Bus Details') - {{ $bus->display_name }}</h4>
                </div>
                <div class="card-body">
                    <!-- Basic Bus Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>@lang('Bus Number')</label>
                                <p><strong>{{ $bus->bus_number }}</strong></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>@lang('Travel Name')</label>
                                <p><span class="badge badge--primary">{{ $bus->travel_name }}</span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>@lang('Bus Type')</label>
                                <p><span class="badge badge--info">{{ $bus->bus_type }}</span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>@lang('Service Name')</label>
                                <p>{{ $bus->service_name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>@lang('Current Route')</label>
                                <p>
                                    @if ($bus->currentRoute)
                                        <span class="badge badge--success">
                                            {{ $bus->currentRoute->originCity->city_name ?? 'N/A' }} →
                                            {{ $bus->currentRoute->destinationCity->city_name ?? 'N/A' }}
                                        </span>
                                    @else
                                        <span class="badge badge--secondary">@lang('Not Assigned')</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>@lang('Status')</label>
                                <p>
                                    @if ($bus->status)
                                        <span class="badge badge--success">@lang('Active')</span>
                                    @else
                                        <span class="badge badge--danger">@lang('Inactive')</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if ($bus->description)
                            <div class="col-md-12">
                                <div class="info-item">
                                    <label>@lang('Description')</label>
                                    <p>{{ $bus->description }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Seat Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">@lang('Seat Information')</h5>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-card__icon">
                                    <i class="las la-chair"></i>
                                </div>
                                <div class="stat-card__content">
                                    <h3>{{ $bus->total_seats }}</h3>
                                    <p>@lang('Total Seats')</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-card__icon">
                                    <i class="las la-check-circle"></i>
                                </div>
                                <div class="stat-card__content">
                                    <h3>{{ $bus->available_seats }}</h3>
                                    <p>@lang('Available Seats')</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-card__icon">
                                    <i class="las la-percentage"></i>
                                </div>
                                <div class="stat-card__content">
                                    <h3>{{ $bus->occupancy_percentage }}%</h3>
                                    <p>@lang('Occupancy')</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-card__icon">
                                    <i class="las la-ticket-alt"></i>
                                </div>
                                <div class="stat-card__content">
                                    <h3>{{ $bus->max_seats_per_ticket }}</h3>
                                    <p>@lang('Max Per Ticket')</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">@lang('Pricing Information')</h5>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Base Price')</label>
                                <p><strong>{{ $bus->formatted_base_price }}</strong></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Published Price')</label>
                                <p><strong>{{ $bus->formatted_published_price }}</strong></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Offered Price')</label>
                                <p><strong>{{ $bus->formatted_offered_price }}</strong></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Agent Commission')</label>
                                <p>{{ $bus->agent_commission ? '₹' . number_format($bus->agent_commission, 2) : 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Tax')</label>
                                <p>{{ $bus->tax ? '₹' . number_format($bus->tax, 2) : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Other Charges')</label>
                                <p>{{ $bus->other_charges ? '₹' . number_format($bus->other_charges, 2) : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Discount')</label>
                                <p>{{ $bus->discount ? '₹' . number_format($bus->discount, 2) : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Service Charges')</label>
                                <p>{{ $bus->service_charges ? '₹' . number_format($bus->service_charges, 2) : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('TDS')</label>
                                <p>{{ $bus->tds ? '₹' . number_format($bus->tds, 2) : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- GST Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">@lang('GST Information')</h5>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('CGST Amount')</label>
                                <p>{{ $bus->cgst_amount ? '₹' . number_format($bus->cgst_amount, 2) : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('CGST Rate')</label>
                                <p>{{ $bus->cgst_rate ? $bus->cgst_rate . '%' : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('IGST Amount')</label>
                                <p>{{ $bus->igst_amount ? '₹' . number_format($bus->igst_amount, 2) : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('IGST Rate')</label>
                                <p>{{ $bus->igst_rate ? $bus->igst_rate . '%' : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('SGST Amount')</label>
                                <p>{{ $bus->sgst_amount ? '₹' . number_format($bus->sgst_amount, 2) : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('SGST Rate')</label>
                                <p>{{ $bus->sgst_rate ? $bus->sgst_rate . '%' : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Taxable Amount')</label>
                                <p>{{ $bus->taxable_amount ? '₹' . number_format($bus->taxable_amount, 2) : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bus Features -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">@lang('Bus Features')</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>@lang('Features')</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @if ($bus->id_proof_required)
                                        <span class="badge badge--info">@lang('ID Proof Required')</span>
                                    @endif
                                    @if ($bus->is_drop_point_mandatory)
                                        <span class="badge badge--warning">@lang('Drop Point Mandatory')</span>
                                    @endif
                                    @if ($bus->live_tracking_available)
                                        <span class="badge badge--success">@lang('Live Tracking')</span>
                                    @endif
                                    @if ($bus->m_ticket_enabled)
                                        <span class="badge badge--primary">@lang('M-Ticket')</span>
                                    @endif
                                    @if ($bus->partial_cancellation_allowed)
                                        <span class="badge badge--secondary">@lang('Partial Cancellation')</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>@lang('Amenities')</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @if ($bus->amenities)
                                        @foreach ($bus->amenities as $amenity)
                                            <span class="badge badge--info">{{ $amenity }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">@lang('No amenities specified')</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bus Details -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">@lang('Bus Details')</h5>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Fuel Type')</label>
                                <p>{{ $bus->fuel_type }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Manufacturing Year')</label>
                                <p>{{ $bus->manufacturing_year ? $bus->manufacturing_year . ' (' . $bus->age . ' years old)' : 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Documentation -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">@lang('Documentation')</h5>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>@lang('Insurance Number')</label>
                                <p>{{ $bus->insurance_number ?: 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>@lang('Insurance Expiry')</label>
                                <p>
                                    @if ($bus->insurance_expiry)
                                        {{ showDateTime($bus->insurance_expiry, 'd M, Y') }}
                                        @if ($bus->insurance_expiry->isPast())
                                            <span class="badge badge--danger">@lang('Expired')</span>
                                        @elseif($bus->insurance_expiry->diffInDays() <= 30)
                                            <span class="badge badge--warning">@lang('Expiring Soon')</span>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>@lang('Permit Number')</label>
                                <p>{{ $bus->permit_number ?: 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>@lang('Permit Expiry')</label>
                                <p>
                                    @if ($bus->permit_expiry)
                                        {{ showDateTime($bus->permit_expiry, 'd M, Y') }}
                                        @if ($bus->permit_expiry->isPast())
                                            <span class="badge badge--danger">@lang('Expired')</span>
                                        @elseif($bus->permit_expiry->diffInDays() <= 30)
                                            <span class="badge badge--warning">@lang('Expiring Soon')</span>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>@lang('Fitness Certificate')</label>
                                <p>{{ $bus->fitness_certificate ?: 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>@lang('Fitness Expiry')</label>
                                <p>
                                    @if ($bus->fitness_expiry)
                                        {{ showDateTime($bus->fitness_expiry, 'd M, Y') }}
                                        @if ($bus->fitness_expiry->isPast())
                                            <span class="badge badge--danger">@lang('Expired')</span>
                                        @elseif($bus->fitness_expiry->diffInDays() <= 30)
                                            <span class="badge badge--warning">@lang('Expiring Soon')</span>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Route History -->
                    @if ($bus->routeHistory->count() > 0)
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">@lang('Route History')</h5>
                                <div class="table-responsive">
                                    <table class="table table--light style--two">
                                        <thead>
                                            <tr>
                                                <th>@lang('Route')</th>
                                                <th>@lang('Assigned Date')</th>
                                                <th>@lang('Unassigned Date')</th>
                                                <th>@lang('Duration')</th>
                                                <th>@lang('Status')</th>
                                                <th>@lang('Notes')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($bus->routeHistory as $history)
                                                <tr>
                                                    <td>
                                                        <span class="badge badge--primary">
                                                            {{ $history->route->originCity->city_name ?? 'N/A' }} →
                                                            {{ $history->route->destinationCity->city_name ?? 'N/A' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ showDateTime($history->assigned_date, 'd M, Y') }}</td>
                                                    <td>{{ $history->unassigned_date ? showDateTime($history->unassigned_date, 'd M, Y') : '-' }}
                                                    </td>
                                                    <td>{{ $history->duration }} @lang('days')</td>
                                                    <td>
                                                        @if ($history->is_active)
                                                            <span class="badge badge--success">@lang('Current')</span>
                                                        @else
                                                            <span class="badge badge--secondary">@lang('Past')</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $history->notes ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Seat Layout Management Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">@lang('Seat Layout Management')</h5>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('operator.buses.seat-layouts.index', $bus) }}"
                                            class="btn btn--primary btn-sm">
                                            <i class="las la-chair"></i> @lang('Manage Layouts')
                                        </a>
                                        <a href="{{ route('operator.buses.seat-layouts.create', $bus) }}"
                                            class="btn btn--success btn-sm">
                                            <i class="las la-plus"></i> @lang('Create Layout')
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    @php
                                        $activeLayout = $bus->activeSeatLayout;
                                        $totalLayouts = $bus->seatLayouts()->count();
                                    @endphp

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="stat-card">
                                                <div class="stat-card__icon">
                                                    <i class="las la-chair"></i>
                                                </div>
                                                <div class="stat-card__content">
                                                    <h3>{{ $totalLayouts }}</h3>
                                                    <p>@lang('Total Layouts')</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="stat-card">
                                                <div class="stat-card__icon">
                                                    <i class="las la-check-circle"></i>
                                                </div>
                                                <div class="stat-card__content">
                                                    <h3>{{ $activeLayout ? 1 : 0 }}</h3>
                                                    <p>@lang('Active Layout')</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($activeLayout)
                                        <div class="mt-4">
                                            <h6>@lang('Current Active Layout')</h6>
                                            <div class="alert alert-success">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $activeLayout->layout_name }}</strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            @lang('Total Seats'): {{ $activeLayout->total_seats }} |
                                                            @lang('Upper Deck'): {{ $activeLayout->upper_deck_seats }} |
                                                            @lang('Lower Deck'): {{ $activeLayout->lower_deck_seats }}
                                                        </small>
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        <a href="{{ route('operator.buses.seat-layouts.show', [$bus, $activeLayout]) }}"
                                                            class="btn btn-sm btn--info">
                                                            <i class="las la-eye"></i> @lang('View')
                                                        </a>
                                                        <a href="{{ route('operator.buses.seat-layouts.edit', [$bus, $activeLayout]) }}"
                                                            class="btn btn-sm btn--primary">
                                                            <i class="las la-edit"></i> @lang('Edit')
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-4">
                                            <div class="alert alert-warning">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="las la-exclamation-triangle"></i>
                                                        <strong>@lang('No Active Seat Layout')</strong>
                                                        <br>
                                                        <small>@lang('This bus does not have an active seat layout. Create one to enable seat booking.')</small>
                                                    </div>
                                                    <a href="{{ route('operator.buses.seat-layouts.create', $bus) }}"
                                                        class="btn btn--success">
                                                        <i class="las la-plus"></i> @lang('Create Layout')
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($totalLayouts > 0)
                                        <div class="mt-4">
                                            <h6>@lang('All Layouts')</h6>
                                            <div class="table-responsive">
                                                <table class="table table--light style--two">
                                                    <thead>
                                                        <tr>
                                                            <th>@lang('Layout Name')</th>
                                                            <th>@lang('Total Seats')</th>
                                                            <th>@lang('Status')</th>
                                                            <th>@lang('Created')</th>
                                                            <th>@lang('Actions')</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($bus->seatLayouts()->orderBy('created_at', 'desc')->get() as $layout)
                                                            <tr>
                                                                <td>
                                                                    <strong>{{ $layout->layout_name }}</strong>
                                                                    @if ($layout->is_active)
                                                                        <span
                                                                            class="badge badge--success ms-2">@lang('Active')</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $layout->total_seats }}</td>
                                                                <td>
                                                                    @if ($layout->is_active)
                                                                        <span
                                                                            class="badge badge--success">@lang('Active')</span>
                                                                    @else
                                                                        <span
                                                                            class="badge badge--secondary">@lang('Inactive')</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ showDateTime($layout->created_at, 'd M, Y') }}</td>
                                                                <td>
                                                                    <div class="btn-group" role="group">
                                                                        <a href="{{ route('operator.buses.seat-layouts.show', [$bus, $layout]) }}"
                                                                            class="btn btn-sm btn--info"
                                                                            title="@lang('View')">
                                                                            <i class="las la-eye"></i>
                                                                        </a>
                                                                        <a href="{{ route('operator.buses.seat-layouts.edit', [$bus, $layout]) }}"
                                                                            class="btn btn-sm btn--primary"
                                                                            title="@lang('Edit')">
                                                                            <i class="las la-edit"></i>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="text-center">
                        <a href="{{ route('operator.buses.edit', $bus->id) }}" class="btn btn--primary">
                            <i class="la la-pen"></i> @lang('Edit Bus')
                        </a>
                        <a href="{{ route('operator.buses.index') }}" class="btn btn--secondary">
                            <i class="la la-angle-double-left"></i> @lang('Back to List')
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .info-item {
            margin-bottom: 20px;
        }

        .info-item label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            display: block;
        }

        .info-item p {
            margin: 0;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .stat-card__icon {
            font-size: 2rem;
            color: #007bff;
            margin-bottom: 10px;
        }

        .stat-card__content h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
            color: #495057;
        }

        .stat-card__content p {
            margin: 5px 0 0 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
@endpush

@push('breadcrumb-plugins')
    <a href="{{ route('operator.buses.index') }}" class="btn btn-sm btn--primary box--shadow1 text--small">
        <i class="las la-angle-double-left"></i>@lang('Go Back')
    </a>
@endpush
