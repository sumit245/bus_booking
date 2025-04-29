@extends('admin.layouts.app')

@section('panel')
    @php
        $currentMarkup = (object)[
            'title' => 'Holiday Surcharge',
            'type' => 'fixed', // Type can still exist if needed internally
            'amount' => 500.00
        ];
    @endphp

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card b-radius--10">
                <div class="card-body">
                    <!-- Title -->
                    <h5 class="mb-3">@lang($currentMarkup->title)</h5>

                    <!-- Current Amount Box -->
                    <div class="alert alert-secondary text-center font-weight-bold mb-4">
                        @lang('Current Markup Amount'): {{ number_format($currentMarkup->amount, 2) }}
                    </div>

                    <!-- Update Form Box -->
                    <form action="/admin/markup/update" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="amount" class="font-weight-bold">@lang('Update Markup Amount')</label>
                            <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="{{ $currentMarkup->amount }}" required>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <!-- Save Button -->
                            <button type="submit" class="btn btn--primary mr-3">@lang('Save')</button>

                            <!-- Cancel Button -->
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

    /* Styling for buttons */
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

    /* Adding spacing and alignment */
    .d-flex .btn {
        margin-left: 5px;
    }
</style>
@endpush