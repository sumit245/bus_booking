@extends('operator.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Edit Attendance')</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('operator.attendance.update', $attendance->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Staff Member')</label>
                                    <input type="text" class="form-control"
                                        value="{{ $attendance->staff->first_name }} {{ $attendance->staff->last_name }} ({{ ucfirst($attendance->staff->role) }})"
                                        readonly>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Date') <span class="text-danger">*</span></label>
                                    <input type="date" name="date" class="form-control"
                                        value="{{ old('date', $attendance->date) }}" required>
                                    @error('date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Check In Time')</label>
                                    <input type="time" name="check_in_time" class="form-control"
                                        value="{{ old('check_in_time', $attendance->check_in_time) }}">
                                    @error('check_in_time')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Check Out Time')</label>
                                    <input type="time" name="check_out_time" class="form-control"
                                        value="{{ old('check_out_time', $attendance->check_out_time) }}">
                                    @error('check_out_time')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Status') <span class="text-danger">*</span></label>
                                    <select name="status" class="form-control" required>
                                        <option value="">@lang('Select Status')</option>
                                        <option value="present"
                                            {{ old('status', $attendance->status) == 'present' ? 'selected' : '' }}>
                                            @lang('Present')</option>
                                        <option value="absent"
                                            {{ old('status', $attendance->status) == 'absent' ? 'selected' : '' }}>
                                            @lang('Absent')</option>
                                        <option value="late"
                                            {{ old('status', $attendance->status) == 'late' ? 'selected' : '' }}>
                                            @lang('Late')</option>
                                        <option value="half_day"
                                            {{ old('status', $attendance->status) == 'half_day' ? 'selected' : '' }}>
                                            @lang('Half Day')</option>
                                    </select>
                                    @error('status')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Overtime Hours')</label>
                                    <input type="number" name="overtime_hours" class="form-control" step="0.5"
                                        min="0" value="{{ old('overtime_hours', $attendance->overtime_hours) }}"
                                        placeholder="0">
                                    @error('overtime_hours')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Deduction Amount')</label>
                                    <input type="number" name="deduction_amount" class="form-control" step="0.01"
                                        min="0" value="{{ old('deduction_amount', $attendance->deduction_amount) }}"
                                        placeholder="0.00">
                                    @error('deduction_amount')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>@lang('Remarks')</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="@lang('Any remarks about attendance...')">{{ old('remarks', $attendance->remarks) }}</textarea>
                            @error('remarks')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">@lang('Update Attendance')</button>
                            <a href="{{ route('operator.attendance.index') }}"
                                class="btn btn-secondary">@lang('Cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
