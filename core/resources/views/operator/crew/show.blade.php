@extends('operator.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <a href="{{ route('operator.crew.index') }}" class="btn btn-secondary btn-sm">
                            <i class="las la-arrow-left"></i> @lang('Back')
                        </a>
                        <span class="ml-3">
                            <h4 class="card-title mb-0 d-inline">@lang('Crew Assignment Details')</h4>
                        </span>
                    </div>
                    <div>
                        <a href="{{ route('operator.crew.edit', $crewAssignment->id) }}" class="btn btn-warning btn-sm">
                            <i class="las la-edit"></i> @lang('Edit')
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Assignment Information -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="text-primary mb-3">@lang('Assignment Information')</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-2">
                                        <strong>@lang('Bus'):</strong><br>{{ $crewAssignment->operatorBus->travel_name }}
                                        - {{ $crewAssignment->operatorBus->bus_number }}</p>
                                </div>
                                <div class="col-md-2">
                                    <p class="mb-2">
                                        <strong>@lang('Assignment Date'):</strong><br>{{ \Carbon\Carbon::parse($crewAssignment->assignment_date)->format('d M Y') }}
                                    </p>
                                </div>
                                <div class="col-md-2">
                                    <p class="mb-2"><strong>@lang('Status'):</strong><br>
                                        <span
                                            class="badge badge-{{ $crewAssignment->status == 'active' ? 'success' : ($crewAssignment->status == 'completed' ? 'info' : 'warning') }}">
                                            {{ ucfirst($crewAssignment->status) }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-2">
                                    <p class="mb-2">
                                        <strong>@lang('Start Time'):</strong><br>{{ $crewAssignment->shift_start_time ? \Carbon\Carbon::parse($crewAssignment->shift_start_time)->format('h:i A') : 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-md-2">
                                    <p class="mb-2">
                                        <strong>@lang('End Time'):</strong><br>{{ $crewAssignment->shift_end_time ? \Carbon\Carbon::parse($crewAssignment->shift_end_time)->format('h:i A') : 'N/A' }}
                                    </p>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <strong>@lang('Created At'):</strong><br>{{ $crewAssignment->created_at->format('d M Y h:i A') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assigned Crew -->
                    @if ($crewAssignment->staff)
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h5 class="text-primary mb-3">@lang('Assigned Crew')</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <p class="mb-2"><strong>@lang('Role'):</strong><br><span
                                                class="badge badge-info">{{ ucfirst($crewAssignment->role) }}</span></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2">
                                            <strong>@lang('Name'):</strong><br>{{ $crewAssignment->staff->first_name }}
                                            {{ $crewAssignment->staff->last_name }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-2">
                                            <strong>@lang('Phone'):</strong><br>{{ $crewAssignment->staff->phone }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Notes -->
                    @if ($crewAssignment->notes)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">@lang('Notes')</h5>
                                <div class="alert alert-info">
                                    {{ $crewAssignment->notes }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
