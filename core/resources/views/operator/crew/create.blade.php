@extends('operator.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Assign Crew to Bus')</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('operator.crew.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Select Bus') <span class="text-danger">*</span></label>
                                    <select name="operator_bus_id" class="form-control" required>
                                        <option value="">@lang('Select Bus')</option>
                                        @foreach ($buses as $bus)
                                            <option value="{{ $bus->id }}"
                                                {{ old('operator_bus_id') == $bus->id ? 'selected' : '' }}>
                                                {{ $bus->travel_name }} - {{ $bus->bus_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('operator_bus_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Assignment Date') <span class="text-danger">*</span></label>
                                    <input type="date" name="assignment_date" class="form-control"
                                        value="{{ old('assignment_date', date('Y-m-d')) }}" required>
                                    @error('assignment_date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Driver')</label>
                                    <select name="driver_id" class="form-control">
                                        <option value="">@lang('Select Driver')</option>
                                        @foreach ($drivers as $driver)
                                            <option value="{{ $driver->id }}"
                                                {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                                {{ $driver->first_name }} {{ $driver->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('driver_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Conductor')</label>
                                    <select name="conductor_id" class="form-control">
                                        <option value="">@lang('Select Conductor')</option>
                                        @foreach ($conductors as $conductor)
                                            <option value="{{ $conductor->id }}"
                                                {{ old('conductor_id') == $conductor->id ? 'selected' : '' }}>
                                                {{ $conductor->first_name }} {{ $conductor->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('conductor_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Attendant')</label>
                                    <select name="attendant_id" class="form-control">
                                        <option value="">@lang('Select Attendant')</option>
                                        @foreach ($attendants as $attendant)
                                            <option value="{{ $attendant->id }}"
                                                {{ old('attendant_id') == $attendant->id ? 'selected' : '' }}>
                                                {{ $attendant->first_name }} {{ $attendant->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('attendant_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Start Time')</label>
                                    <input type="time" name="start_time" class="form-control"
                                        value="{{ old('start_time') }}">
                                    @error('start_time')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('End Time')</label>
                                    <input type="time" name="end_time" class="form-control"
                                        value="{{ old('end_time') }}">
                                    @error('end_time')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>@lang('Notes')</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="@lang('Any additional notes...')">{{ old('notes') }}</textarea>
                            @error('notes')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">@lang('Assign Crew')</button>
                            <a href="{{ route('operator.crew.index') }}" class="btn btn-secondary">@lang('Cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
