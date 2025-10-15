@extends('operator.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">{{ $pageTitle }}</h4>
                        <div class="d-flex gap-2">
                            <a href="{{ route('operator.buses.show', $bus) }}" class="btn btn-outline-secondary">
                                <i class="las la-arrow-left"></i> Back to Bus
                            </a>
                            <a href="{{ route('operator.buses.seat-layouts.create', $bus) }}" class="btn btn-primary">
                                <i class="las la-plus"></i> Create New Layout
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if ($seatLayouts->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Layout Name</th>
                                            <th>Bus Type</th>
                                            <th>Total Seats</th>
                                            <th>Upper Deck</th>
                                            <th>Lower Deck</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($seatLayouts as $layout)
                                            <tr>
                                                <td>
                                                    <strong>{{ $layout->layout_name }}</strong>
                                                    @if ($layout->is_active)
                                                        <span class="badge bg-success ms-2">Active</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge {{ $layout->deck_type == 'single' ? 'bg-info' : 'bg-primary' }}">
                                                        {{ ucfirst($layout->deck_type) }} Decker
                                                    </span>
                                                </td>
                                                <td>{{ $layout->total_seats }}</td>
                                                <td>{{ $layout->upper_deck_seats }}</td>
                                                <td>{{ $layout->lower_deck_seats }}</td>
                                                <td>
                                                    @if ($layout->is_active)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>{{ $layout->created_at->format('M d, Y') }}</td>
                                                <td>
                                                    <div class="button--group">
                                                        <a href="{{ route('operator.buses.seat-layouts.show', [$bus, $layout]) }}"
                                                            class="btn btn-sm btn--primary" title="@lang('View')">
                                                            <i class="la la-eye"></i>
                                                        </a>
                                                        <a href="{{ route('operator.buses.seat-layouts.edit', [$bus, $layout]) }}"
                                                            class="btn btn-sm btn--success" title="@lang('Edit')">
                                                            <i class="la la-pen"></i>
                                                        </a>
                                                        @if (!$layout->is_active)
                                                            <form method="POST"
                                                                action="{{ route('operator.buses.seat-layouts.toggle-status', [$bus, $layout]) }}"
                                                                style="display: inline-block;"
                                                                onsubmit="return confirm('Activate this seat layout?')">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="btn btn-sm btn--success"
                                                                    title="@lang('Activate')">
                                                                    <i class="la la-check"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        <form method="POST"
                                                            action="{{ route('operator.buses.seat-layouts.destroy', [$bus, $layout]) }}"
                                                            style="display: inline-block;"
                                                            onsubmit="return confirm('Delete this seat layout? This action cannot be undone.')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn--danger"
                                                                title="@lang('Delete')">
                                                                <i class="la la-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="las la-chair text-muted" style="font-size: 4rem;"></i>
                                </div>
                                <h5 class="text-muted">No Seat Layouts Found</h5>
                                <p class="text-muted">Create your first seat layout to get started.</p>
                                <a href="{{ route('operator.buses.seat-layouts.create', $bus) }}" class="btn btn-primary">
                                    <i class="las la-plus"></i> Create First Layout
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
