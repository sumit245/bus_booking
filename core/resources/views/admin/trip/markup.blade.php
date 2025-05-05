@extends('admin.layouts.app')

@section('panel')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card b-radius--10">
            <div class="card-body">
                <!-- Title -->
                <h5 class="mb-3">@lang($currentMarkup->title ?? 'Markup Settings')</h5>

                <!-- Current Settings Summary -->
                <!-- <div class="alert alert-secondary text-center font-weight-bold mb-4">
                    <p>@lang('Flat Markup'): {{ number_format($currentMarkup->flat_markup ?? 0, 2) }}</p></br>
                    <p>@lang('Percentage Markup (above threshold)'): {{ number_format($currentMarkup->percentage_markup ?? 0, 2) }}%</p>
                    <p>@lang('Threshold Amount'): {{ number_format($currentMarkup->threshold ?? 0, 2) }}</p>
                </div> -->

                <!-- Update Form -->
                <form action="{{ route('markup.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="font-weight-bold">@lang('Flat Markup (Fixed)')</label>
                        <input type="number" step="0.01" name="flat_markup" class="form-control"
                            value="{{ old('flat_markup', $currentMarkup->flat_markup ?? '') }}" required>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">@lang('Percentage Markup (Above Minimum)')</label>
                        <input type="number" step="0.01" name="percentage_markup" class="form-control"
                            value="{{ old('percentage_markup', $currentMarkup->percentage_markup ?? '') }}" required>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">@lang('Minimum Threshold')</label>
                        <input type="number" step="0.01" name="threshold" class="form-control"
                            value="{{ old('threshold', $currentMarkup->threshold ?? '') }}" required>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn--primary mr-3">@lang('Update')</button>
                        <a href="{{ url()->previous() }}" class="btn btn--danger">@lang('Cancel')</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .alert-secondary {
        background-color: #f4f6f9;
        border-color: #d6d8db;
        color: #333;
        border-radius: 5px;
    }

    .btn--primary {
        background-color: #007bff;
        color: white;
    }

    .btn--primary:hover {
        background-color: #0056b3;
        color: white;
    }

    .btn--danger {
        background-color: #dc3545;
        color: white;
    }

    .btn--danger:hover {
        background-color: #c82333;
        color: white;
    }

    .d-flex .btn {
        margin-left: 5px;
    }
</style>
@endpush
