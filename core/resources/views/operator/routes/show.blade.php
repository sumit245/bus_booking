@extends('operator.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">@lang('Route Details') - {{ $route->display_name }}
                            <span class="badge badge-pill badge-{{ $route->status ? 'success' : 'danger' }}"
                                style="width: 10px; height: 10px; padding: 0; border-radius: 50%; display: inline-block;"
                                title="{{ $route->status ? 'Active' : 'Inactive' }}"></span>
                        </h4>
                        <a href="{{ route('operator.routes.index') }}" class="btn btn-secondary btn-sm">
                            <i class="la la-angle-double-left"></i> @lang('Back')
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('operator.routes.edit', $route->id) }}" class="btn btn-warning btn-sm">
                            <i class="la la-pen"></i> @lang('Edit')
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Basic Route Information -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="text-primary mb-3">@lang('Route Information')</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>@lang('Route Name')</strong><br>{{ $route->route_name }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Origin City')</strong><br>{{ $route->originCity->city_name ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Destination City')</strong><br>{{ $route->destinationCity->city_name ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>@lang('Distance')</strong><br>{{ $route->formatted_distance }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Base Fare')</strong><br>{{ $route->formatted_base_fare }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Estimated Duration')</strong><br>{{ $route->formatted_duration }}
                                    </p>
                                </div>
                                @if ($route->description)
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>@lang('Description')</strong><br>{{ $route->description }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Total Points')</strong><br><i class="las la-route"></i>
                                        {{ $route->boardingPoints->count() + $route->droppingPoints->count() }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Duration')</strong><br><i class="las la-clock"></i>
                                        {{ $route->formatted_duration }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Distance')</strong><br><i class="las la-road"></i>
                                        {{ $route->formatted_distance }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>@lang('Base Fare')</strong><br><i
                                            class="las la-money-bill-wave"></i> {{ $route->formatted_base_fare }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Boarding Points -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">@lang('Boarding Points') ({{ $route->boardingPoints->count() }})</h5>
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
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">@lang('Dropping Points') ({{ $route->droppingPoints->count() }})</h5>
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
                    {{-- <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="text-primary mb-3">@lang('Route Information')</h5>

                        </div>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>
@endsection
