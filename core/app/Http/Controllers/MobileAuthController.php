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
                ->orderBy('created_at', 'desc')
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
                ->first();

            if (!$booking) {
                return redirect()->route('user.dashboard')->with('error', 'Booking not found or you do not have permission to view this booking.');
            }

            $pageTitle = 'Booking Details';
            $layout = 'layouts.frontend';

            return view($this->activeTemplate . 'booking_details', compact('pageTitle', 'layout', 'booking'));
        } catch (\Exception $e) {
            Log::error('Error in showBooking', [
                'booking_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('user.dashboard')->with('error', 'An error occurred while loading booking details.');
        }
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
