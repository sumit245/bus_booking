<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class OtpController extends Controller
{
    /**
     * Send OTP to user's WhatsApp
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'name' => 'nullable|string'
        ]);

        Log::info('OTP Request: ' . $request->all());
        try {
            // Extract phone number (remove country code if present)
            $phone = $request->phone;
            if (strpos($phone, '+91') === 0) {
                $phone = substr($phone, 3);
            } else if (strpos($phone, '91') === 0 && strlen($phone) > 10) {
                $phone = substr($phone, 2);
            }

            // Generate OTP
            $otp = (string) rand(100000, 999999);

            // Store OTP in database
            $otpVerification = OtpVerification::updateOrCreate(
                ['phone' => $phone],
                [
                    'otp' => $otp,
                    'expires_at' => now()->addMinutes(10)
                ]
            );

            // Send OTP via WhatsApp
            $userName = $request->name ?: 'Guest';
            sendOtp($phone, $otp, $userName);
            Log::info('Received OTP');
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify OTP and login/register user
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string'
        ]);

        try {
            // Extract phone number (remove country code if present)
            $phone = $request->phone;
            if (strpos($phone, '+91') === 0) {
                $phone = substr($phone, 3);
            } else if (strpos($phone, '91') === 0 && strlen($phone) > 10) {
                $phone = substr($phone, 2);
            }

            // Check if OTP exists and is valid
            $otpVerification = OtpVerification::where('phone', $phone)
                ->where('otp', $request->otp)
                ->where('expires_at', '>', now())
                ->first();

            if (!$otpVerification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ], 400);
            }

            // Mark OTP as verified in session
            Session::put('otp_verified_phone', $phone);

            // Check if user exists with this phone number
            $fullPhone = '91' . $phone;
            $user = User::where('mobile', $fullPhone)->first();

            $userLoggedIn = false;

            // If user exists, log them in
            if ($user) {
                // Set email and SMS as verified since they verified via WhatsApp OTP
                $user->ev = 1; // Email verified
                $user->sv = 1; // SMS verified
                $user->save();
                
                Auth::login($user);
                $userLoggedIn = true;
            } else {
                // Create new user if doesn't exist
                $fullPhone = '91' . $phone;
                $user = User::create([
                    'firstname' => 'User',
                    'lastname' => '',
                    'mobile' => $fullPhone,
                    'email' => $fullPhone . '@mobile.user',
                    'password' => Hash::make($fullPhone . '123'),
                    'status' => 1,
                    'ev' => 1, // Email verified (via WhatsApp)
                    'sv' => 1, // SMS verified (via WhatsApp)
                    'ts' => 1
                ]);
                Auth::login($user);
                $userLoggedIn = true;
            }

            // Delete the OTP record
            $otpVerification->delete();

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully',
                'user_logged_in' => $userLoggedIn
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify OTP: ' . $e->getMessage()
            ], 500);
        }
    }
}
