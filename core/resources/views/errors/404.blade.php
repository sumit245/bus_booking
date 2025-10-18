@extends($activeTemplate . 'layouts.frontend')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card">
                <div class="card-body">
                    <h1 class="text-danger mb-4">404</h1>
                    <h3 class="mb-4">{{ $exception->getMessage() ?: 'Page Not Found' }}</h3>
                    <p>We couldn't find any buses for your search criteria.</p>
                    <div class="mt-4">
                        <a href="{{ route('home') }}" class="btn btn--base">Go to Homepage</a>
                        <a href="javascript:history.back()" class="btn btn-outline-secondary ml-3">Go Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection