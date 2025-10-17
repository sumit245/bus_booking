@extends('operator.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Crew Assignment Details')</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">@lang('Bus')</th>
                                    <td>{{ $crewAssignment->operatorBus->travel_name }} -
                                        {{ $crewAssignment->operatorBus->bus_number }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('Assignment Date')</th>
                                    <td>{{ \Carbon\Carbon::parse($crewAssignment->assignment_date)->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('Status')</th>
                                    <td>
                                        <span
                                            class="badge badge-{{ $crewAssignment->status == 'active' ? 'success' : ($crewAssignment->status == 'completed' ? 'info' : 'warning') }}">
                                            {{ ucfirst($crewAssignment->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">@lang('Start Time')</th>
                                    <td>{{ $crewAssignment->shift_start_time ? \Carbon\Carbon::parse($crewAssignment->shift_start_time)->format('h:i A') : 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>@lang('End Time')</th>
                                    <td>{{ $crewAssignment->shift_end_time ? \Carbon\Carbon::parse($crewAssignment->shift_end_time)->format('h:i A') : 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>@lang('Created At')</th>
                                    <td>{{ $crewAssignment->created_at->format('d M Y h:i A') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <h5>@lang('Assigned Crew')</h5>
                    <div class="row">
                        @if ($crewAssignment->staff)
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">@lang(ucfirst($crewAssignment->role))</h6>
                                        <p class="card-text">
                                            <strong>{{ $crewAssignment->staff->first_name }}
                                                {{ $crewAssignment->staff->last_name }}</strong><br>
                                            <small class="text-muted">{{ $crewAssignment->staff->phone }}</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($crewAssignment->notes)
                        <hr>
                        <h5>@lang('Notes')</h5>
                        <p>{{ $crewAssignment->notes }}</p>
                    @endif

                    <div class="form-group mt-4">
                        <a href="{{ route('operator.crew.edit', $crewAssignment->id) }}"
                            class="btn btn-primary">@lang('Edit Assignment')</a>
                        <a href="{{ route('operator.crew.index') }}" class="btn btn-secondary">@lang('Back to List')</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
