<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use App\Models\ReferralSetting;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ReferralController extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * GET /api/users/referral-data
     * Get user's referral code and settings
     */
    public function getReferralData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find user by mobile number
            $user = User::where('mobile', $request->mobile_number)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get or create referral code for user
            $referralCode = $this->referralService->getOrCreateUserReferralCode($user->id);

            // Get current settings
            $settings = ReferralSetting::current();

            // Build referral link
            $baseUrlPlay = 'https://play.google.com/store/apps/details?id=com.dashandots.vindhyashribus';
            $referralLink = "{$baseUrlPlay}&ref={$referralCode->code}";

            // Get reward percentage (for display)
            $rewardPercentage = 0;
            if ($settings->reward_type === 'percent_of_ticket') {
                $rewardPercentage = $settings->percent_of_ticket;
            } elseif ($settings->reward_type === 'percent') {
                $rewardPercentage = $settings->percent_share;
            }

            return response()->json([
                'success' => true,
                'referralCode' => $referralCode->code,
                'rewardPercentage' => (float) $rewardPercentage,
                'shareMessage' => $settings->share_message ?? 'Join Ghumantoo and get amazing bus booking deals!',
                'referralLink' => $referralLink
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting referral data', [
                'mobile_number' => $request->mobile_number,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get referral data'
            ], 500);
        }
    }

    /**
     * GET /api/users/referral-stats
     * Get user's referral statistics
     */
    public function getReferralStats(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find user by mobile number
            $user = User::where('mobile', $request->mobile_number)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get referral statistics
            $stats = $this->referralService->getUserReferralStats($user->id);

            return response()->json([
                'success' => true,
                'totalReferrals' => $stats['total_referrals'],
                'successfulInstalls' => $stats['successful_installs'],
                'totalEarnings' => $stats['total_earnings'],
                'pendingEarnings' => $stats['pending_earnings']
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting referral stats', [
                'mobile_number' => $request->mobile_number,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get referral stats'
            ], 500);
        }
    }

    /**
     * GET /api/users/referral-history
     * Get user's referral history
     */
    public function getReferralHistory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required|string',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find user by mobile number
            $user = User::where('mobile', $request->mobile_number)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $limit = $request->input('limit', 10);

            // Get referral history
            $history = $this->referralService->getUserReferralHistory($user->id, $limit);

            return response()->json([
                'success' => true,
                'recentReferrals' => $history
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting referral history', [
                'mobile_number' => $request->mobile_number,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get referral history'
            ], 500);
        }
    }

    /**
     * POST /api/referral/install
     * Record app installation event
     */
    public function recordInstall(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'referral_code' => 'required|string|size:6',
                'device_id' => 'nullable|string',
                'source' => 'nullable|string|in:pwa,app,web'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $event = $this->referralService->recordInstall(
                $request->referral_code,
                $request->device_id,
                null // userId is null during install, will be linked later
            );

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to record install. Code may be invalid or already used.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Install recorded successfully',
                'event_id' => $event->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error recording install', [
                'referral_code' => $request->referral_code,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record install'
            ], 500);
        }
    }

    /**
     * POST /api/referral/click
     * Record referral link click
     */
    public function recordClick(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'referral_code' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $click = $this->referralService->recordClick(
                $request->referral_code,
                $request
            );

            if (!$click) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid referral code'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Click recorded successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error recording click', [
                'referral_code' => $request->referral_code,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record click'
            ], 500);
        }
    }

    /**
     * POST /api/referral/signup
     * Record signup with referral code
     */
    public function recordSignup(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'referral_code' => 'required|string|size:6',
                'mobile_number' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find user by mobile number
            $user = User::where('mobile', $request->mobile_number)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $event = $this->referralService->recordSignup(
                $request->referral_code,
                $user->id
            );

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to record signup. Code may be invalid, self-referral, or limit exceeded.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Signup recorded successfully',
                'event_id' => $event->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error recording signup', [
                'referral_code' => $request->referral_code,
                'mobile_number' => $request->mobile_number,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record signup'
            ], 500);
        }
    }

    /**
     * GET /api/referral/settings
     * Get public referral settings
     */
    public function getSettings()
    {
        try {
            $settings = ReferralSetting::current();

            return response()->json([
                'success' => true,
                'is_enabled' => $settings->is_enabled,
                'reward_type' => $settings->reward_type,
                'reward_percentage' => $settings->reward_type === 'percent_of_ticket'
                    ? (float) $settings->percent_of_ticket
                    : (float) $settings->percent_share,
                'min_booking_amount' => (float) $settings->min_booking_amount,
                'share_message' => $settings->share_message,
                'terms_and_conditions' => $settings->terms_and_conditions
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting referral settings', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get settings'
            ], 500);
        }
    }
}
