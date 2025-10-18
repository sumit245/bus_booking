@extends('operator.layouts.app')

@section('panel')
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-0">@lang('Booking Details') - #{{ $booking->id }}</h4>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('operator.bookings.index') }}" class="btn btn--secondary box--shadow1">
                <i class="fa fa-fw fa-arrow-left"></i>@lang('Back to Bookings')
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card b-radius--10">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">@lang('Booking Information')</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>@lang('Booking ID'):</strong></td>
                                    <td>#{{ $booking->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Status'):</strong></td>
                                    <td>
                                        @if ($booking->status === 'active')
                                            <span class="badge badge--success">@lang('Active')</span>
                                        @elseif($booking->status === 'cancelled')
                                            <span class="badge badge--danger">@lang('Cancelled')</span>
                                        @else
                                            <span class="badge badge--secondary">@lang('Expired')</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Created'):</strong></td>
                                    <td>{{ $booking->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Last Updated'):</strong></td>
                                    <td>{{ $booking->updated_at->format('M d, Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">@lang('Journey Details')</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>@lang('Date Range'):</strong></td>
                                    <td>{{ $booking->date_range }}</td>
                                </tr>
                                @if ($booking->is_date_range)
                                    <tr>
                                        <td><strong>@lang('Type'):</strong></td>
                                        <td><span class="badge badge--info">@lang('Date Range')</span></td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><strong>@lang('Total Seats'):</strong></td>
                                    <td><span class="badge badge--primary">{{ $booking->total_seats_blocked }}
                                            @lang('seats')</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">@lang('Bus Information')</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>@lang('Travel Name'):</strong></td>
                                    <td>{{ $booking->operatorBus->travel_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Bus Type'):</strong></td>
                                    <td>{{ $booking->operatorBus->bus_type }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Total Seats'):</strong></td>
                                    <td>{{ $booking->operatorBus->total_seats }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">@lang('Route Information')</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>@lang('Route'):</strong></td>
                                    <td>{{ $booking->operatorRoute->originCity->city_name }} →
                                        {{ $booking->operatorRoute->destinationCity->city_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('Route Name'):</strong></td>
                                    <td>{{ $booking->operatorRoute->route_name }}</td>
                                </tr>
                                @if ($booking->busSchedule)
                                    <tr>
                                        <td><strong>@lang('Schedule'):</strong></td>
                                        <td>{{ $booking->busSchedule->schedule_name ?: 'Schedule #' . $booking->busSchedule->id }}
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="text-muted">@lang('Blocked Seats')</h6>
                            <div class="seat-display">
                                @if (is_array($booking->blocked_seats))
                                    @foreach ($booking->blocked_seats as $seat)
                                        <span class="badge badge--warning seat-badge">{{ $seat }}</span>
                                    @endforeach
                                @else
                                    <span class="badge badge--warning seat-badge">{{ $booking->blocked_seats }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($booking->booking_reason || $booking->notes)
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="text-muted">@lang('Additional Information')</h6>
                                @if ($booking->booking_reason)
                                    <p><strong>@lang('Reason'):</strong> {{ $booking->booking_reason }}</p>
                                @endif
                                @if ($booking->notes)
                                    <p><strong>@lang('Notes'):</strong> {{ $booking->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card b-radius--10">
                <div class="card-body">
                    <h6 class="text-muted mb-3">@lang('Actions')</h6>

                    <div class="d-grid gap-2">
                        <a href="{{ route('operator.bookings.edit', $booking) }}" class="btn btn--warning">
                            <i class="fa fa-edit"></i> @lang('Edit Booking')
                        </a>

                        <form action="{{ route('operator.bookings.toggle-status', $booking) }}" method="POST"
                            class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="btn {{ $booking->status === 'active' ? 'btn--secondary' : 'btn--success' }} w-100">
                                <i class="fa fa-{{ $booking->status === 'active' ? 'ban' : 'check' }}"></i>
                                {{ $booking->status === 'active' ? 'Cancel Booking' : 'Activate Booking' }}
                            </button>
                        </form>

                        <form action="{{ route('operator.bookings.destroy', $booking) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Are you sure you want to delete this booking? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger w-100">
                                <i class="fa fa-trash"></i> @lang('Delete Booking')
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card b-radius--10 mt-3">
                <div class="card-body">
                    <h6 class="text-muted mb-3">@lang('Booking Summary')</h6>
                    <div class="summary-item">
                        <span class="text-muted">@lang('Seats Blocked'):</span>
                        <strong>{{ $booking->total_seats_blocked }}</strong>
                    </div>
                    <div class="summary-item">
                        <span class="text-muted">@lang('Amount'):</span>
                        <strong>₹0.00</strong> <small class="text-muted">(@lang('No charge'))</small>
                    </div>
                    <div class="summary-item">
                        <span class="text-muted">@lang('Duration'):</span>
                        <strong>{{ $booking->created_at->diffForHumans() }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .seat-display {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            min-height: 50px;
        }

        .seat-badge {
            margin: 2px;
            font-size: 0.9em;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-item:last-child {
            border-bottom: none;
        }
    </style>
@endpush
