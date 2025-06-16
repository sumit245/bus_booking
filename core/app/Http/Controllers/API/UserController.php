<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Send OTP to the user's mobile number.
     */
    public function sendOTP(Request $request)
    {
        $this->validatePhone($request);

        try {
            // Generate OTP
            $otp = (string) rand(100000, 999999);
            try {
                Log::info('Attempting to update or create OTP record', ['mobile_number' => $request->mobile_number]);
                Otp::updateOrCreate(
                    ['mobile_number' => $request->mobile_number],
                    [
                        'otp'        => $otp,
                        'expires_at' => Carbon::now()->addMinutes(3),
                    ]
                );
                Log::info('OTP record updated or created successfully', ['mobile_number' => $request->mobile_number]);
            } catch (\Exception $e) {
                Log::error('Error while updating or creating OTP record', ['error' => $e->getMessage()]);
            }


            // Send OTP via WhatsApp API
            sendOtp($request->mobile_number, $request->user_name, $otp);

            return response()->json([
                'message' => 'OTP sent successfully to ' . $request->mobile_number,
                'status'  => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP: ' . $e->getMessage(),
                'status'  => 500,
            ], 500);
        }
    }

    /**
     * Verify the OTP and log in the user.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|regex:/^[6-9]\d{9}$/',
            'otp'           => 'required|digits:6',
        ]);

        $otpRecord = Otp::where('mobile_number', $request->mobile_number)->first();

        if (!$otpRecord) {
            return response()->json([
                'message' => 'OTP not found. Please request a new OTP.',
                'status'  => 404,
            ], 404);
        }

        if ($otpRecord->expires_at < Carbon::now()) {
            return response()->json([
                'message' => 'OTP expired. Please request a new OTP.',
                'status'  => 400,
            ], 400);
        }

        if ($otpRecord->otp !== $request->otp) {
            return response()->json([
                'message' => 'Invalid OTP. Please try again.',
                'status'  => 400,
            ], 400);
        }

        Log::info("Working fine");
        // OTP is verified, create or fetch the user
        $user = User::firstOrCreate(
            ['mobile' => $request->mobile_number],
            ['username' => $request->user_name]
        );
        // Log in the user
        Auth::login($user);

        // Delete OTP record
        $otpRecord->delete();
        Log::info($user);
        return response()->json([
            'message' => 'Logged in successfully.',
            'status'  => 200,
            'data'    => [
                'user'  => $user,
                'token' => $user->createToken('mobile-app')->plainTextToken,
            ],
        ], 200);
    }

    /**
     * Validate the phone number format.
     */
    protected function validatePhone(Request $request)
    {
        $request->validate([
            'mobile_number' => ['required', 'regex:/^[6-9]\d{9}$/'],
        ]);
    }
}
