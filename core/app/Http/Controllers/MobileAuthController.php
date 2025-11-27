<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use App\Models\BookedTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class MobileAuthController extends Controller
{
    protected $activeTemplate;

    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function showMobileLogin()
    {
        $pageTitle = 'Mobile Login';
        $layout = 'layouts.frontend';

        return view($this->activeTemplate . 'mobile_login', compact('pageTitle', 'layout'));
    }

    public function sendMobileOtp(Request $request)
    {
        Log::info("Sending OTP to", ["phone" => $request->all()]);
        try {
            // Generate OTP
            $otp = (string) rand(100000, 999999);
            try {
                Otp::updateOrCreate(
                    ['mobile_number' => $request->mobile],
                    [
                        'otp' => $otp,
                        'expires_at' => Carbon::now()->addMinutes(3),
                    ]
                );

            } catch (\Exception $e) {
                Log::error('Error while updating or creating OTP record', ['error' => $e->getMessage()]);
            }


            // Send OTP via WhatsApp API
            sendOtp($request->mobile, $otp, 'Guest');

            return response()->json([
                'message' => 'OTP sent successfully to ' . $request->mobile,
                'status' => 'success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP: ' . $e->getMessage(),
                'status' => 'error',
            ], 500);
        }
    }

    public function verifyMobileOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'otp' => 'required|string|min:6|max:6'
        ]);

        $mobile = $request->mobile;
        $otp = $request->otp;

        $otpRecord = Otp::where('mobile_number', $request->mobile)->first();

        if (!$otpRecord) {
            return response()->json([
                'message' => 'OTP not found. Please request a new OTP.',
                'status' => 'error',
            ], 404);
        }

        if ($otpRecord->expires_at < Carbon::now()) {
            return response()->json([
                'message' => 'OTP expired. Please request a new OTP.',
                'status' => 'error',
            ], 400);
        }

        if ($otpRecord->otp !== $request->otp) {
            return response()->json([
                'message' => 'Invalid OTP. Please try again.',
                'status' => 'error',
            ], 400);
        }

        // Find or create user
        $user = User::where('mobile', $mobile)->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'firstname' => 'User',
                'lastname' => '',
                'mobile' => $mobile,
                'email' => $mobile . '@mobile.user',
                'password' => Hash::make($mobile . '123'),
                'status' => 1,
                'ev' => 1,
                'sv' => 1,
                'ts' => 1
            ]);
        }

        // Login user
        Auth::login($user);

        // Delete OTP record
        $otpRecord->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged in successfully.',
            'redirect' => route('user.dashboard')
        ]);
    }

    public function dashboard()
    {
        try {
            $pageTitle = 'User Dashboard';
            $layout = 'layouts.frontend';

            // Check if user is authenticated
            if (!auth()->check()) {
                return redirect()->route('mobile.login')->with('error', 'Please login to access dashboard.');
            }

            // Get user's bookings with pagination
            $bookings = BookedTicket::where('passenger_phone', auth()->user()->mobile)
                ->latest()
                ->paginate(10);

            // Get general settings
            $general = \App\Models\GeneralSetting::first();

            // Log for debugging
            Log::info('Dashboard loaded', [
                'user_id' => auth()->id(),
                'user_mobile' => auth()->user()->mobile,
                'bookings_count' => $bookings->count()
            ]);

            return view($this->activeTemplate . 'user_dashboard', compact('pageTitle', 'layout', 'bookings', 'general'));
        } catch (\Exception $e) {
            Log::error('Error in dashboard', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('mobile.login')->with('error', 'An error occurred while loading dashboard.');
        }
    }

    public function showBooking($id)
    {
        try {
            // Check if user is authenticated
            if (!auth()->check()) {
                return redirect()->route('mobile.login')->with('error', 'Please login to view booking details.');
            }

            $booking = BookedTicket::where('id', $id)
                ->where('passenger_phone', auth()->user()->mobile)
                ->with(['trip.fleetType', 'user'])
                ->first();

            if (!$booking) {
                return redirect()->route('user.dashboard')->with('error', 'Booking not found or you do not have permission to view this booking.');
            }

            // Format ticket data (copy logic from TicketController)
            $ticket = $this->formatTicketData($booking);

            // Get company details
            $general = \App\Models\GeneralSetting::first();
            $companyName = $general->sitename ?? 'Bus Booking';
            $logoUrl = getImage(imagePath()['logoIcon']['path'] . '/logo.png');

            // Prepare cancel metadata for the view
            $searchTokenId = $booking->search_token_id ?? null;
            $bookingIdentifier = $booking->api_booking_id ?? $booking->booking_id ?? $booking->operator_pnr ?? $booking->pnr_number ?? (string) $booking->id;
            $seatId = is_array($booking->seats) ? ($booking->seats[0] ?? null) : (is_string($booking->seats) ? (explode(',', $booking->seats)[0] ?? $booking->seats) : null);
            $cancelMeta = [
                'search_token_id' => $searchTokenId,
                'booking_id' => $bookingIdentifier,
                'seat_id' => $seatId,
            ];

            $pageTitle = 'Booking Details';
            $layout = 'layouts.frontend';

            return view($this->activeTemplate . 'booking_details', compact('pageTitle', 'layout', 'ticket', 'companyName', 'logoUrl', 'cancelMeta'));
        } catch (\Exception $e) {
            Log::error('Error in showBooking', [
                'booking_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('user.dashboard')->with('error', 'An error occurred while loading booking details.');
        }
    }

    /**
     * Format ticket data for display (copied from TicketController logic)
     */
    private function formatTicketData($ticket)
    {
        // Get seats
        $seats = is_array($ticket->seats) ? $ticket->seats : (is_string($ticket->seats) ? explode(',', $ticket->seats) : []);

        // Parse passenger names
        $nameParts = explode(' ', $ticket->passenger_name ?? '', 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        // Parse boarding and dropping point details
        $boardingPointDetails = null;
        if ($ticket->boarding_point_details) {
            $boardingDetails = is_string($ticket->boarding_point_details)
                ? json_decode($ticket->boarding_point_details, true)
                : $ticket->boarding_point_details;

            if (is_array($boardingDetails)) {
                if (isset($boardingDetails[0]) && is_array($boardingDetails[0])) {
                    $boardingPointDetails = $boardingDetails;
                } elseif (isset($boardingDetails['CityPointName']) || isset($boardingDetails['CityPointIndex'])) {
                    $boardingPointDetails = [$boardingDetails];
                }
            }
        }

        $droppingPointDetails = null;
        if ($ticket->dropping_point_details) {
            $droppingDetails = is_string($ticket->dropping_point_details)
                ? json_decode($ticket->dropping_point_details, true)
                : $ticket->dropping_point_details;

            if (is_array($droppingDetails)) {
                if (isset($droppingDetails[0]) && is_array($droppingDetails[0])) {
                    $droppingPointDetails = $droppingDetails;
                } elseif (isset($droppingDetails['CityPointName']) || isset($droppingDetails['CityPointIndex'])) {
                    $droppingPointDetails = [$droppingDetails];
                }
            }
        }

        // Format boarding and dropping points as strings
        $boardingPointString = $ticket->origin_city ?? '';
        if ($boardingPointDetails && isset($boardingPointDetails[0])) {
            $bp = $boardingPointDetails[0];
            $boardingPointString = ($bp['CityPointName'] ?? '') .
                (isset($bp['CityPointLocation']) && $bp['CityPointLocation'] !== ($bp['CityPointName'] ?? '')
                    ? ', ' . $bp['CityPointLocation']
                    : '');
        }

        $droppingPointString = $ticket->destination_city ?? '';
        if ($droppingPointDetails && isset($droppingPointDetails[0])) {
            $dp = $droppingPointDetails[0];
            $droppingPointString = ($dp['CityPointName'] ?? '') .
                (isset($dp['CityPointLocation']) && $dp['CityPointLocation'] !== ($dp['CityPointName'] ?? '')
                    ? ', ' . $dp['CityPointLocation']
                    : '');
        }

        // Format times
        $departureTime = $ticket->departure_time && $ticket->departure_time != '00:00:00'
            ? \Carbon\Carbon::parse($ticket->departure_time)->format('h:i A')
            : 'N/A';

        $arrivalTime = $ticket->arrival_time && $ticket->arrival_time != '00:00:00'
            ? \Carbon\Carbon::parse($ticket->arrival_time)->format('h:i A')
            : 'N/A';

        // Calculate duration
        $duration = 'N/A';
        if ($ticket->arrival_time && $ticket->departure_time && $ticket->arrival_time != '00:00:00' && $ticket->departure_time != '00:00:00') {
            $duration = \Carbon\Carbon::parse($ticket->arrival_time)
                ->diff(\Carbon\Carbon::parse($ticket->departure_time))
                ->format('%H:%I');
        }

        // Build passengers array
        $passengers = [];
        foreach ($seats as $index => $seat) {
            $isLead = ($index === 0);
            $passengers[] = [
                'LeadPassenger' => $isLead,
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'Age' => $isLead ? $ticket->passenger_age : null,
                'Gender' => $ticket->passenger_gender ?? 1,
                'Phoneno' => $isLead ? $ticket->passenger_phone : null,
                'Email' => $isLead ? $ticket->passenger_email : null,
                'Seat' => [
                    'SeatName' => $seat,
                ],
            ];
        }

        // Return as object with all fields including fee breakdown
        return (object) [
            'id' => $ticket->id,
            'pnr_number' => $ticket->pnr_number,
            'passenger_name' => $ticket->passenger_name ?? ($firstName . ' ' . $lastName),
            'passenger_phone' => $ticket->passenger_phone ?? null,
            'passenger_email' => $ticket->passenger_email ?? null,
            'travel_name' => $ticket->travel_name ?? ($ticket->trip && $ticket->trip->fleetType ? $ticket->trip->fleetType->name : 'N/A'),
            'bus_type' => $ticket->bus_type ?? 'N/A',
            'date_of_journey' => $ticket->date_of_journey ? \Carbon\Carbon::parse($ticket->date_of_journey)->format('Y-m-d') : null,
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime,
            'duration' => $duration,
            'boarding_point' => $boardingPointString,
            'dropping_point' => $droppingPointString,
            'seats' => $seats,
            'passengers' => $passengers,
            'sub_total' => $ticket->sub_total ?? 0,
            'service_charge' => $ticket->service_charge ?? 0,
            'service_charge_percentage' => $ticket->service_charge_percentage ?? 0,
            'platform_fee' => $ticket->platform_fee ?? 0,
            'platform_fee_percentage' => $ticket->platform_fee_percentage ?? 0,
            'platform_fee_fixed' => $ticket->platform_fee_fixed ?? 0,
            'gst' => $ticket->gst ?? 0,
            'gst_percentage' => $ticket->gst_percentage ?? 0,
            'total_amount' => $ticket->total_amount ?? ($ticket->sub_total ?? 0),
            'total_fare' => $ticket->total_amount ?? ($ticket->sub_total ?? 0),
            'status' => $ticket->status,
            'created_at' => $ticket->created_at,
        ];
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500'
        ]);

        $user = auth()->user();
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->email = $request->email;
        $user->address = $request->address;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully'
        ]);
    }

    public function cancelBooking(Request $request, $id)
    {
        $booking = BookedTicket::where('id', $id)
            ->where('passenger_phone', auth()->user()->mobile)
            ->firstOrFail();

        if ($booking->status == 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'This booking is already cancelled.'
            ], 400);
        }

        try {
            // Check if this is an operator bus or third-party bus
            $isOperatorBus = str_starts_with($booking->search_token_id ?? '', 'OP_') ||
                str_starts_with($booking->result_index ?? '', 'OP_');

            if ($isOperatorBus) {
                // For operator buses, just update the status
                $booking->status = 0;
                $booking->cancellation_remarks = $request->cancellation_reason;
                $booking->cancelled_at = now();
                $booking->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Booking cancelled successfully.'
                ]);
            } else {
                // For third-party buses, call the API
                $cancellationResult = cancelAPITicket(
                    request()->ip(),
                    $booking->search_token_id,
                    $booking->api_booking_id,
                    $booking->api_ticket_no,
                    $request->cancellation_reason ?? 'User requested cancellation'
                );

                if (isset($cancellationResult['Error']) && $cancellationResult['Error']['ErrorCode'] != 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $cancellationResult['Error']['ErrorMessage'] ?? 'Failed to cancel booking'
                    ], 400);
                }

                // Update booking status
                $booking->status = 0;
                $booking->cancellation_remarks = $request->cancellation_reason;
                $booking->cancelled_at = now();
                $booking->api_response = json_encode($cancellationResult);
                $booking->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Booking cancelled successfully.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Booking cancellation failed', [
                'booking_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel booking. Please try again.'
            ], 500);
        }
    }

    public function logout()
    {
        Auth::logout();
        Session::flush();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
            'redirect' => route('home')
        ]);
    }
}
