@extends('operator.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>

                        <span class="ml-3">
                            <h4 class="card-title mb-0">@lang('Bus Details') - {{ $bus->display_name }}

                                <span class="badge badge-pill badge-{{ $bus->status ? 'success' : 'danger' }}"
                                    style="width: 10px; height: 10px; padding: 0; border-radius: 50%; display: inline-block;"
                                    title="{{ $bus->status ? 'Active' : 'Inactive' }}"></span>
                            </h4>
                        </span>
                        <a href="{{ route('operator.buses.index') }}" class="btn btn-secondary btn-sm">
                            <i class="la la-angle-double-left"></i> @lang('Back')
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('operator.buses.edit', $bus->id) }}" class="btn btn-warning btn-sm mr-2">
                            <i class="la la-pen"></i> @lang('Edit')
                        </a>
                        <a href="{{ route('operator.buses.cancellation-policy.show', $bus->id) }}"
                            class="btn btn-info btn-sm">
                            <i class="la la-ban"></i> @lang('Cancellation Policy')
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="text-primary mb-3">@lang('Basic Information')</h5>

                            <!-- Basic Information -->
                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Bus Number'):</strong><br>{{ $bus->bus_number }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Travel Name'):</strong><br><span
                                            class="badge badge--primary">{{ $bus->travel_name }}</span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Bus Type'):</strong><br><span
                                            class="badge badge--info">{{ $bus->bus_type }}</span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Service Name'):</strong><br>{{ $bus->service_name }}</p>
                                </div>
                            </div>
 
                            <!-- Seat Information -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5 class="text-primary mb-3">@lang('Seat Information')</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <p class="mb-2"><strong>@lang('Total Seats'):</strong><br><i
                                                    class="las la-chair"></i>
                                                {{ $bus->total_seats }}</p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2"><strong>@lang('Available Seats'):</strong><br><i
                                                    class="las la-check-circle"></i> {{ $bus->available_seats }}</p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2"><strong>@lang('Occupancy'):</strong><br><i
                                                    class="las la-percentage"></i> {{ $bus->occupancy_percentage }}%</p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2"><strong>@lang('Max Per Ticket'):</strong><br><i
                                                    class="las la-ticket-alt"></i> {{ $bus->max_seats_per_ticket }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Information -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5 class="text-primary mb-3">@lang('Pricing Information')</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('Base Price'):</strong><br>{{ $bus->formatted_base_price }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('Published Price'):</strong><br>{{ $bus->formatted_published_price }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('Offered Price'):</strong><br>{{ $bus->formatted_offered_price }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('Agent Commission'):</strong><br>{{ $bus->agent_commission ? '₹' . number_format($bus->agent_commission, 2) : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('Tax'):</strong><br>{{ $bus->tax ? '₹' . number_format($bus->tax, 2) : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('Other Charges'):</strong><br>{{ $bus->other_charges ? '₹' . number_format($bus->other_charges, 2) : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('Discount'):</strong><br>{{ $bus->discount ? '₹' . number_format($bus->discount, 2) : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('Service Charges'):</strong><br>{{ $bus->service_charges ? '₹' . number_format($bus->service_charges, 2) : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('TDS'):</strong><br>{{ $bus->tds ? '₹' . number_format($bus->tds, 2) : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- GST Information -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5 class="text-primary mb-3">@lang('GST Information')</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('CGST Amount'):</strong><br>{{ $bus->cgst_amount ? '₹' . number_format($bus->cgst_amount, 2) : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('CGST Rate'):</strong><br>{{ $bus->cgst_rate ? $bus->cgst_rate . '%' : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('IGST Amount'):</strong><br>{{ $bus->igst_amount ? '₹' . number_format($bus->igst_amount, 2) : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('IGST Rate'):</strong><br>{{ $bus->igst_rate ? $bus->igst_rate . '%' : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('SGST Amount'):</strong><br>{{ $bus->sgst_amount ? '₹' . number_format($bus->sgst_amount, 2) : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('SGST Rate'):</strong><br>{{ $bus->sgst_rate ? $bus->sgst_rate . '%' : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('Taxable Amount'):</strong><br>{{ $bus->taxable_amount ? '₹' . number_format($bus->taxable_amount, 2) : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bus Features -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5 class="text-primary mb-3">@lang('Bus Features & Amenities')</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-2"><strong>@lang('Features'):</strong><br>
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
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-2"><strong>@lang('Amenities'):</strong><br>
                                                @if ($bus->amenities)
                                                    @foreach ($bus->amenities as $amenity)
                                                        <span class="badge badge--info">{{ $amenity }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">@lang('No amenities specified')</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bus Details -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5 class="text-primary mb-3">@lang('Vehicle Details')</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('Fuel Type'):</strong><br>{{ $bus->fuel_type }}
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-2">
                                                <strong>@lang('Manufacturing Year'):</strong><br>{{ $bus->manufacturing_year ? $bus->manufacturing_year . ' (' . $bus->age . ' years old)' : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Documentation -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5 class="text-primary mb-3">@lang('Documentation')</h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p class="mb-2">
                                                <strong>@lang('Insurance Number'):</strong><br>{{ $bus->insurance_number ?: 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-2"><strong>@lang('Insurance Expiry'):</strong><br>
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
                                        <div class="col-md-4">
                                            <p class="mb-2">
                                                <strong>@lang('Permit Number'):</strong><br>{{ $bus->permit_number ?: 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <p class="mb-2"><strong>@lang('Permit Expiry'):</strong><br>
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
                                        <div class="col-md-4">
                                            <p class="mb-2">
                                                <strong>@lang('Fitness Certificate'):</strong><br>{{ $bus->fitness_certificate ?: 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-2"><strong>@lang('Fitness Expiry'):</strong><br>
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
                            </div>

                            <!-- Route History -->
                            @if ($bus->routeHistory->count() > 0)
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h5 class="text-primary mb-3">@lang('Route History')</h5>
                                        <div class="table-responsive mb-3">
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
                                                                    <span
                                                                        class="badge badge--success">@lang('Current')</span>
                                                                @else
                                                                    <span
                                                                        class="badge badge--secondary">@lang('Past')</span>
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
                                    <h5 class="text-primary mb-3">@lang('Seat Layout')</h5>
                                    @php
                                        $activeLayout = $bus->activeSeatLayout;
                                    @endphp
                                    @if ($activeLayout)
                                        <div class="alert alert-success d-flex justify-content-between align-items-center p-4">
                                            <div>
                                                <strong>{{ $activeLayout->layout_name }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    @lang('Total Seats'): {{ $activeLayout->total_seats }} |
                                                    @lang('Upper Deck'): {{ $activeLayout->upper_deck_seats }} |
                                                    @lang('Lower Deck'): {{ $activeLayout->lower_deck_seats }}
                                                </small>
                                            </div>
                                            <div>
                                                <a href="{{ route('operator.buses.seat-layouts.show', [$bus, $activeLayout]) }}"
                                                    class="btn btn-sm btn--info mr-2">
                                                    <i class="las la-eye"></i> @lang('View')
                                                </a>
                                                <a href="{{ route('operator.buses.seat-layouts.edit', [$bus, $activeLayout]) }}"
                                                    class="btn btn-sm btn--primary">
                                                    <i class="las la-edit"></i> @lang('Edit')
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-warning d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="las la-exclamation-triangle"></i>
                                                <strong>@lang('No Seat Layout')</strong>
                                                <br>
                                                <small>@lang('This bus does not have a seat layout. Create one to enable seat booking.')</small>
                                            </div>
                                            <a href="{{ route('operator.buses.seat-layouts.create', $bus) }}"
                                                class="btn btn--success">
                                                <i class="las la-plus"></i> @lang('Create Layout')
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
