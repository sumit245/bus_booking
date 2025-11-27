@extends($activeTemplate . $layout)

@section('content')
    <div class="user-dashboard">
        <!-- Modern Header Section -->
        <div class="dashboard-header">
            <div class="header-content">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="las la-user-circle"></i>
                    </div>
                    <div class="user-details">
                        <h1 class="user-name">Welcome back, {{ auth()->user()->firstname }}!</h1>
                        <p class="user-mobile">
                            <i class="las la-mobile-alt"></i>
                            {{ auth()->user()->mobile }}
                        </p>
                        <div class="user-actions">
                            <button class="btn-profile-edit" onclick="openProfileModal()">
                                <i class="las la-edit"></i> Edit Profile
                            </button>
                            <form method="POST" action="{{ route('user.logout') }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn-link"
                                    style="background: none; border: none; padding: 0; color: inherit; cursor: pointer; text-decoration: none;">
                                    @lang('Logout')
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="{{ route('home') }}" class="btn-book-bus">
                        <i class="las la-bus"></i>
                        Book New Trip
                    </a>
                </div>
            </div>
        </div>

        <!-- Modern Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-total">
                <div class="stat-icon">
                    <i class="las la-ticket-alt"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number">{{ $bookings->total() }}</h3>
                    <p class="stat-label">Total Bookings</p>
                </div>
            </div>
            <div class="stat-card stat-confirmed">
                <div class="stat-icon">
                    <i class="las la-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number">{{ $bookings->where('status', 1)->count() }}</h3>
                    <p class="stat-label">Confirmed</p>
                </div>
            </div>
            <div class="stat-card stat-pending">
                <div class="stat-icon">
                    <i class="las la-clock"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number">{{ $bookings->where('status', 2)->count() }}</h3>
                    <p class="stat-label">Pending</p>
                </div>
            </div>
            <div class="stat-card stat-cancelled">
                <div class="stat-icon">
                    <i class="las la-times-circle"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number">{{ $bookings->where('status', 0)->count() }}</h3>
                    <p class="stat-label">Cancelled</p>
                </div>
            </div>
        </div>

        <!-- Modern Bookings Section -->
        <div class="bookings-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="las la-list"></i>
                    My Bookings
                </h2>
                <div class="section-actions">
                    <button class="btn-filter" onclick="toggleFilters()">
                        <i class="las la-filter"></i> Filter
                    </button>
                </div>
            </div>

            @if ($bookings->count() > 0)
                <div class="bookings-grid">
                    @foreach ($bookings as $booking)
                        @php
                            // Parse boarding and dropping point details
                            $boardingPointDetails = null;
                            if ($booking->boarding_point_details) {
                                $boardingDetails = is_string($booking->boarding_point_details)
                                    ? json_decode($booking->boarding_point_details, true)
                                    : $booking->boarding_point_details;
                                if (is_array($boardingDetails)) {
                                    $boardingPointDetails = isset($boardingDetails[0])
                                        ? $boardingDetails[0]
                                        : $boardingDetails;
                                }
                            }

                            $droppingPointDetails = null;
                            if ($booking->dropping_point_details) {
                                $droppingDetails = is_string($booking->dropping_point_details)
                                    ? json_decode($booking->dropping_point_details, true)
                                    : $booking->dropping_point_details;
                                if (is_array($droppingDetails)) {
                                    $droppingPointDetails = isset($droppingDetails[0])
                                        ? $droppingDetails[0]
                                        : $droppingDetails;
                                }
                            }

                            // Format seats
                            $seats = $booking->seats;
                            $seatDisplay = 'N/A';
                            if (is_string($seats)) {
                                $decoded = json_decode($seats, true);
                                if (is_array($decoded)) {
                                    $seatDisplay = implode(', ', $decoded);
                                } else {
                                    $seatDisplay = $seats;
                                }
                            } elseif (is_object($seats)) {
                                $converted = json_decode(json_encode($seats), true);
                                if (is_array($converted) && !empty($converted)) {
                                    $seatDisplay = implode(', ', $converted);
                                }
                            } elseif (is_array($seats) && !empty($seats)) {
                                $seatDisplay = implode(', ', $seats);
                            }
                        @endphp

                        <div class="booking-card">
                            <!-- Top Header: Status only -->
                            <div class="booking-header">
                                <div class="status-section">
                                    @if ($booking->status == 1)
                                        <span class="status-badge status-confirmed">CONFIRMED</span>
                                    @elseif($booking->status == 2)
                                        <span class="status-badge status-pending">PENDING</span>
                                    @else
                                        <span class="status-badge status-cancelled">CANCELLED</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Compact Two-Column Content -->
                            <div class="booking-content">
                                <div class="content-left">
                                    <div class="pnr-line">
                                        <span class="pnr-label">PNR</span>
                                        <span class="pnr-number">{{ $booking->pnr_number }}</span>
                                    </div>
                                    @if ($booking->operator_pnr)
                                        <div class="ticket-id-line">Ticket ID: {{ $booking->operator_pnr }}</div>
                                    @endif
                                    <div class="route-inline">
                                        <span class="city-name">{{ $booking->origin_city ?? 'N/A' }}</span>
                                        <i class="las la-arrow-right"></i>
                                        <span class="city-name">{{ $booking->destination_city ?? 'N/A' }}</span>
                                    </div>
                                    <div class="operator-type">
                                        <span>{{ $booking->travel_name ?? 'N/A' }}</span>
                                        <span class="type-sep">•</span>
                                        <span>{{ $booking->bus_type ?? 'N/A' }}</span>
                                    </div>
                                    <div class="amount-block">
                                        <span class="amount-label">Total Amount</span>
                                        <span
                                            class="amount-value">{{ $general->cur_sym }}{{ number_format($booking->total_amount ?? $booking->sub_total, 2) }}</span>
                                    </div>
                                </div>
                                <div class="content-right">
                                    <div class="date-line">
                                        {{ \Carbon\Carbon::parse($booking->date_of_journey)->format('d M, Y') }}</div>
                                    <div class="boarding-line">Boarding details:
                                        {{ $boardingPointDetails['CityPointName'] ?? 'N/A' }}</div>
                                    <div class="dropping-line">Dropping Details:
                                        {{ $droppingPointDetails['CityPointName'] ?? 'N/A' }}</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="card-actions">
                                <div class="action-buttons">
                                    @if ($booking->status == 1)
                                        <button type="button" class="btn-action btn-cancel"
                                            onclick="cancelBooking(this, {{ $booking->id }})"
                                            data-booking-id="{{ $booking->operator_pnr ?? ($booking->pnr_number ?? $booking->id) }}"
                                            data-search-token-id="{{ $booking->search_token_id ?? '' }}"
                                            data-seat-id="{{ is_array($seats) && !empty($seats) ? $seats[0] : (is_string($seatDisplay) ? explode(', ', $seatDisplay)[0] ?? $seatDisplay : $seatDisplay) }}">
                                            <i class="las la-times"></i>
                                            Cancel
                                        </button>
                                    @endif
                                    <a href="{{ route('user.booking.show', $booking->id) }}" class="btn-action btn-view">
                                        <i class="las la-eye"></i>
                                        View Booking
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="pagination-wrapper">
                    {{ $bookings->links() }}
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="las la-ticket-alt"></i>
                    </div>
                    <h3>No Bookings Yet</h3>
                    <p>You haven't made any bookings yet. Start your journey by booking a bus!</p>
                    <a href="{{ route('search') }}" class="btn-primary">
                        <i class="las la-search"></i>
                        Search Buses
                    </a>
                </div>
            @endif
        </div>

        <!-- Modern Support Section -->
        <div class="support-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="las la-question-circle"></i>
                    Need Help?
                </h2>
            </div>

            <div class="support-grid">
                <div class="support-card" onclick="openSupport('phone')">
                    <div class="support-icon">
                        <i class="las la-phone"></i>
                    </div>
                    <h4>Customer Support</h4>
                    <p>Call us for any booking related queries</p>
                    <span class="support-action">Call Now</span>
                </div>

                <div class="support-card" onclick="openSupport('chat')">
                    <div class="support-icon">
                        <i class="las la-comments"></i>
                    </div>
                    <h4>Live Chat</h4>
                    <p>Chat with our support team 24/7</p>
                    <span class="support-action">Start Chat</span>
                </div>

                <div class="support-card" onclick="openSupport('email')">
                    <div class="support-icon">
                        <i class="las la-envelope"></i>
                    </div>
                    <h4>Email Support</h4>
                    <p>Send us your queries via email</p>
                    <span class="support-action">Send Email</span>
                </div>

                <div class="support-card" onclick="downloadApp()">
                    <div class="support-icon">
                        <i class="las la-mobile-alt"></i>
                    </div>
                    <h4>Download App</h4>
                    <p>Get our mobile app for better experience</p>
                    <span class="support-action">Download</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Edit Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="profileForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="firstname" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname"
                                        value="{{ auth()->user()->firstname }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lastname" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname"
                                        value="{{ auth()->user()->lastname }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="{{ auth()->user()->email }}">
                            <small class="text-muted">Email address for notifications</small>
                        </div>
                        <div class="form-group">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control" id="mobile" name="mobile"
                                value="{{ auth()->user()->mobile }}" readonly>
                            <small class="text-muted">Mobile number cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter your address">{{ is_string(auth()->user()->address) ? auth()->user()->address : (auth()->user()->address ? json_encode(auth()->user()->address) : '') }}</textarea>
                            <small class="text-muted">Your complete address</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveProfileBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this booking?</p>
                    <div class="mb-3">
                        <label for="cancellationReason" class="form-label">Reason for cancellation (optional)</label>
                        <textarea class="form-control" id="cancellationReason" rows="3"
                            placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Booking</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelBtn">Yes, Cancel Booking</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        let bookingToCancel = null;

        // Profile Management
        function openProfileModal() {
            $('#profileModal').modal('show');
        }

        $('#saveProfileBtn').on('click', function() {
            const $btn = $(this);
            const formData = {
                _token: '{{ csrf_token() }}',
                firstname: $('#firstname').val(),
                lastname: $('#lastname').val(),
                email: $('#email').val(),
                address: $('#address').val()
            };

            $btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Saving...');

            $.ajax({
                url: '{{ route('user.profile.update') }}',
                type: 'POST',
                data: formData,
                success: function(response) {
                    iziToast.success({
                        title: 'Success',
                        message: 'Profile updated successfully',
                        position: 'topRight'
                    });
                    $('#profileModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    iziToast.error({
                        title: 'Error',
                        message: xhr.responseJSON?.message || 'Failed to update profile',
                        position: 'topRight'
                    });
                },
                complete: function() {
                    $btn.prop('disabled', false).html('Save Changes');
                }
            });
        });

        // Support Functions
        function openSupport(type) {
            switch (type) {
                case 'phone':
                    window.open('tel:+919876543210', '_self');
                    break;
                case 'chat':
                    // Integrate with your live chat system
                    alert('Live chat feature will be available soon!');
                    break;
                case 'email':
                    window.open('mailto:support@busbooking.com?subject=Booking Support', '_self');
                    break;
            }
        }

        function downloadApp() {
            // Show app download options
            iziToast.info({
                title: 'Download App',
                message: 'Mobile app coming soon! Stay tuned for updates.',
                position: 'topRight'
            });
        }

        // Filter functionality
        function toggleFilters() {
            // Implement filter functionality
            alert('Filter functionality coming soon!');
        }

        // Booking Cancellation
        function cancelBooking(el, bookingId) {
            bookingToCancel = bookingId;
            window.bookingCancelPayload = {
                BookingId: el?.dataset?.bookingId || null,
                SeatId: el?.dataset?.seatId || null,
                SearchTokenId: el?.dataset?.searchTokenId || null
            };
            $('#cancelBookingModal').modal('show');
        }

        $('#confirmCancelBtn').on('click', function() {
            if (!bookingToCancel) return;

            const reason = $('#cancellationReason').val();
            const $btn = $(this);

            $btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Cancelling...');

            $.ajax({
                url: "{{ url('api/users/cancel-ticket') }}",
                type: 'POST',
                data: {
                    UserIp: '{{ request()->ip() }}',
                    SearchTokenId: window.bookingCancelPayload?.SearchTokenId,
                    BookingId: window.bookingCancelPayload?.BookingId,
                    SeatId: window.bookingCancelPayload?.SeatId,
                    Remarks: reason
                },
                success: function(response) {
                    if (response.success) {
                        alert('Success: ' + (response.message || 'Booking cancelled successfully'));
                        $('#cancelBookingModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to cancel booking'));
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Failed to cancel booking'));
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="las la-times"></i> Cancel');
                }
            });
        });
    </script>
@endpush

@push('style')
    <style>
        :root {
            --primary-color: #D63942;
            --primary-hover: #c32d36;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
            --text-muted: #6c757d;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .user-dashboard {
            background: var(--light-bg);
            min-height: 100vh;
            padding: 2rem 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Modern Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            border-radius: 8px;
            margin-bottom: 2rem;
            overflow: hidden;
            box-shadow: var(--shadow-hover);
        }

        .header-content {
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .user-details h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .user-mobile {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .user-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-profile-edit,
        .btn-logout {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            background: transparent;
        }

        .btn-profile-edit:hover,
        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
        }

        .btn-book-bus {
            background: white;
            color: var(--primary-color);
            padding: 1rem 2rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .btn-book-bus:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            color: var(--primary-color);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 6px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-card.stat-confirmed {
            border-left-color: var(--success-color);
        }

        .stat-card.stat-pending {
            border-left-color: var(--warning-color);
        }

        .stat-card.stat-cancelled {
            border-left-color: var(--danger-color);
        }

        .stat-card .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: var(--light-bg);
        }

        .stat-total .stat-icon {
            background: rgba(214, 57, 66, 0.1);
            color: var(--primary-color);
        }

        .stat-confirmed .stat-icon {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .stat-pending .stat-icon {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }

        .stat-cancelled .stat-icon {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .stat-label {
            color: var(--text-muted);
            font-weight: 500;
            margin: 0;
        }

        /* Bookings Section */
        .bookings-section {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--light-bg);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .btn-filter {
            background: var(--light-bg);
            border: 2px solid var(--border-color);
            color: var(--text-muted);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        /* Bookings Grid */
        .bookings-grid {
            display: grid;
            gap: 1rem;
        }

        .booking-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .booking-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary-color);
        }

        /* PNR Header Section */
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.75rem;
            background: transparent;
        }

        .pnr-section {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .pnr-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .pnr-number {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
            letter-spacing: 0.5px;
        }

        .ticket-id {
            font-size: 0.75rem;
            color: var(--text-muted);
            background: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            display: inline-block;
        }

        .status-section {
            display: flex;
            align-items: center;
        }

        .status-badge {
            padding: 0.25rem 0.6rem;
            border-radius: 16px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-confirmed {
            background: var(--success-color);
            color: white;
        }

        .status-pending {
            background: var(--warning-color);
            color: #333;
        }

        .status-cancelled {
            background: var(--danger-color);
            color: white;
        }

        /* Route Section */
        .route-section {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .route-cities {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 0.5rem;
            align-items: center;
        }

        .city-block {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .city-name {
            font-size: 1rem;
            font-weight: 700;
            color: #333;
        }

        .point-name {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .point-time {
            font-size: 0.7rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .route-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        /* Compact two-column content */
        .booking-content {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 0.5rem;
            padding: 0.75rem;
        }

        .content-left,
        .content-right {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .content-right {
            align-items: flex-end;
            text-align: right;
        }

        .pnr-line {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .route-inline {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
        }

        .operator-type {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #333;
            font-size: 0.9rem;
        }

        .ticket-id-line {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .card-actions {
            padding: 0.75rem;
            display: flex;
            justify-content: flex-end;
        }

        /* Travel Info Grid */
        .travel-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            padding: 0.75rem;
            background: var(--light-bg);
            border-bottom: 1px solid var(--border-color);
        }

        .info-col {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            font-size: 0.65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 0.9rem;
            color: #333;
            font-weight: 600;
        }

        .seats-value {
            color: var(--primary-color);
            font-weight: 700;
        }

        /* Card Footer */
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: white;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .amount-section {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .amount-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .amount-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: nowrap;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: 2px solid;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            white-space: nowrap;
            width: 180px;
            text-align: center;
        }

        .btn-view {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-view:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            color: white;
        }

        .btn-cancel {
            background: transparent;
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        .btn-cancel:hover {
            background: var(--danger-color);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            color: white;
            transform: translateY(-2px);
        }

        /* Support Section */
        .support-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .support-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .support-card {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .support-card:hover {
            background: white;
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .support-icon {
            width: 80px;
            height: 80px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin: 0 auto 1rem;
        }

        .support-card h4 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .support-card p {
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .support-action {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Pagination */
        .pagination-wrapper {
            margin-top: 2rem;
            text-align: center;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .user-dashboard {
                padding: 1rem 0.5rem;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }

            .user-info {
                flex-direction: column;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .support-grid {
                grid-template-columns: 1fr;
            }

            .booking-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .pnr-number {
                font-size: 1.2rem;
            }

            .route-cities {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .route-arrow {
                transform: rotate(90deg);
            }

            .travel-info-grid {
                grid-template-columns: 1fr;
            }

            .card-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .action-buttons {
                width: 100%;
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }

            .booking-content {
                grid-template-columns: 1fr;
            }

            .content-right {
                align-items: flex-start;
                text-align: left;
            }
        }
    </style>
@endpush
