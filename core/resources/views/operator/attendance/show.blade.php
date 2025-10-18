@extends('operator.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Attendance Details')</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">@lang('Staff Member')</th>
                                    <td>{{ $attendance->staff->first_name }} {{ $attendance->staff->last_name }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('Role')</th>
                                    <td>{{ ucfirst($attendance->staff->role) }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('Date')</th>
                                    <td>{{ \Carbon\Carbon::parse($attendance->date)->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('Status')</th>
                                    <td>
                                        <span
                                            class="badge badge-{{ $attendance->status == 'present' ? 'success' : ($attendance->status == 'absent' ? 'danger' : ($attendance->status == 'late' ? 'warning' : 'info')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $attendance->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">@lang('Check In Time')</th>
                                    <td>{{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') : 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>@lang('Check Out Time')</th>
                                    <td>{{ $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A') : 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>@lang('Overtime Hours')</th>
                                    <td>{{ $attendance->overtime_hours ?? '0' }} @lang('hours')</td>
                                </tr>
                                <tr>
                                    <th>@lang('Deduction Amount')</th>
                                    <td>₹{{ number_format($attendance->deduction_amount ?? 0, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if ($attendance->remarks)
                        <hr>
                        <h5>@lang('Remarks')</h5>
                        <p>{{ $attendance->remarks }}</p>
                    @endif

                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <strong>@lang('Created At'):</strong> {{ $attendance->created_at->format('d M Y h:i A') }}
                            </small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">
                                <strong>@lang('Updated At'):</strong> {{ $attendance->updated_at->format('d M Y h:i A') }}
                            </small>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <a href="{{ route('operator.attendance.edit', $attendance->id) }}"
                            class="btn btn-primary">@lang('Edit Attendance')</a>
                        <a href="{{ route('operator.attendance.index') }}" class="btn btn-secondary">@lang('Back to List')</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
