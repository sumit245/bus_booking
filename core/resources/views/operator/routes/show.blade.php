@extends('operator.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Route Details') - {{ $route->display_name }}</h4>
                </div>
                <div class="card-body">
                    <!-- Basic Route Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>@lang('Route Name')</label>
                                <p><strong>{{ $route->route_name }}</strong></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Origin City')</label>
                                <p><span class="badge badge--primary">{{ $route->originCity->city_name ?? 'N/A' }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Destination City')</label>
                                <p><span
                                        class="badge badge--success">{{ $route->destinationCity->city_name ?? 'N/A' }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Distance')</label>
                                <p>{{ $route->formatted_distance }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Base Fare')</label>
                                <p>{{ $route->formatted_base_fare }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Estimated Duration')</label>
                                <p>{{ $route->formatted_duration }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>@lang('Status')</label>
                                <p>
                                    @if ($route->status)
                                        <span class="badge badge--success">@lang('Active')</span>
                                    @else
                                        <span class="badge badge--danger">@lang('Inactive')</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if ($route->description)
                            <div class="col-md-12">
                                <div class="info-item">
                                    <label>@lang('Description')</label>
                                    <p>{{ $route->description }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Boarding Points -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">@lang('Boarding Points') ({{ $route->boardingPoints->count() }})</h5>
                            <div class="table-responsive">
                                <table class="table table--light style--two">
                                    <thead>
                                        <tr>
                                            <th>@lang('Index')</th>
                                            <th>@lang('Point Name')</th>
                                            <th>@lang('Location')</th>
                                            <th>@lang('Address')</th>
                                            <th>@lang('Landmark')</th>
                                            <th>@lang('Contact')</th>
                                            <th>@lang('Time')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($route->boardingPoints as $point)
                                            <tr>
                                                <td><span class="badge badge--info">{{ $point->point_index }}</span></td>
                                                <td><strong>{{ $point->point_name }}</strong></td>
                                                <td>{{ $point->point_location }}</td>
                                                <td>{{ $point->point_address }}</td>
                                                <td>{{ $point->point_landmark ?? 'N/A' }}</td>
                                                <td>{{ $point->contact_number ?? 'N/A' }}</td>
                                                <td><span class="badge badge--primary">{{ $point->formatted_time }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">@lang('No boarding points found')</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Dropping Points -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">@lang('Dropping Points') ({{ $route->droppingPoints->count() }})</h5>
                            <div class="table-responsive">
                                <table class="table table--light style--two">
                                    <thead>
                                        <tr>
                                            <th>@lang('Index')</th>
                                            <th>@lang('Point Name')</th>
                                            <th>@lang('Location')</th>
                                            <th>@lang('Address')</th>
                                            <th>@lang('Landmark')</th>
                                            <th>@lang('Contact')</th>
                                            <th>@lang('Time')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($route->droppingPoints as $point)
                                            <tr>
                                                <td><span class="badge badge--warning">{{ $point->point_index }}</span>
                                                </td>
                                                <td><strong>{{ $point->point_name }}</strong></td>
                                                <td>{{ $point->point_location }}</td>
                                                <td>{{ $point->point_address ?? 'N/A' }}</td>
                                                <td>{{ $point->point_landmark ?? 'N/A' }}</td>
                                                <td>{{ $point->contact_number ?? 'N/A' }}</td>
                                                <td><span class="badge badge--success">{{ $point->formatted_time }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">@lang('No dropping points found')</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Route Statistics -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-card__icon">
                                    <i class="las la-route"></i>
                                </div>
                                <div class="stat-card__content">
                                    <h3>{{ $route->boardingPoints->count() + $route->droppingPoints->count() }}</h3>
                                    <p>@lang('Total Points')</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-card__icon">
                                    <i class="las la-clock"></i>
                                </div>
                                <div class="stat-card__content">
                                    <h3>{{ $route->formatted_duration }}</h3>
                                    <p>@lang('Duration')</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-card__icon">
                                    <i class="las la-road"></i>
                                </div>
                                <div class="stat-card__content">
                                    <h3>{{ $route->formatted_distance }}</h3>
                                    <p>@lang('Distance')</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-card__icon">
                                    <i class="las la-money-bill-wave"></i>
                                </div>
                                <div class="stat-card__content">
                                    <h3>{{ $route->formatted_base_fare }}</h3>
                                    <p>@lang('Base Fare')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="text-center">
                        <a href="{{ route('operator.routes.edit', $route->id) }}" class="btn btn--primary">
                            <i class="la la-pen"></i> @lang('Edit Route')
                        </a>
                        <a href="{{ route('operator.routes.index') }}" class="btn btn--secondary">
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
    <a href="{{ route('operator.routes.index') }}" class="btn btn-sm btn--primary box--shadow1 text--small">
        <i class="las la-angle-double-left"></i>@lang('Go Back')
    </a>
@endpush
