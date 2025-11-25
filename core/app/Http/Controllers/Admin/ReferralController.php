<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralSetting;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\ReferralReward;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReferralController extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Show referral settings
     */
    public function settings()
    {
        $pageTitle = 'Referral Settings';
        $settings = ReferralSetting::current();

        return view('admin.referral.settings', compact('pageTitle', 'settings'));
    }

    /**
     * Update referral settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'reward_type' => 'required|in:fixed,percent,percent_of_ticket',
            'fixed_amount' => 'nullable|numeric|min:0',
            'percent_share' => 'nullable|numeric|min:0|max:100',
            'percent_of_ticket' => 'nullable|numeric|min:0|max:100',
            'min_booking_amount' => 'nullable|numeric|min:0',
            'reward_credit_days' => 'nullable|integer|min:0',
            'daily_cap_per_referrer' => 'nullable|integer|min:1',
            'max_referrals_per_user' => 'nullable|integer|min:1',
            'points_per_currency' => 'nullable|integer|min:1',
            'share_message' => 'nullable|string|max:500',
            'terms_and_conditions' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        $settings = ReferralSetting::current();

        // Handle checkboxes (they don't send value if unchecked)
        $settings->is_enabled = $request->has('is_enabled') ? 1 : 0;
        $settings->use_point_system = $request->has('use_point_system') ? 1 : 0;
        $settings->reward_on_install = $request->has('reward_on_install') ? 1 : 0;
        $settings->reward_on_signup = $request->has('reward_on_signup') ? 1 : 0;
        $settings->reward_on_first_booking = $request->has('reward_on_first_booking') ? 1 : 0;
        $settings->reward_referrer = $request->has('reward_referrer') ? 1 : 0;
        $settings->reward_referee = $request->has('reward_referee') ? 1 : 0;

        // Update other fields
        $settings->reward_type = $request->reward_type;
        $settings->fixed_amount = $request->fixed_amount ?? 0;
        $settings->percent_share = $request->percent_share ?? 0;
        $settings->percent_of_ticket = $request->percent_of_ticket ?? 0;
        $settings->min_booking_amount = $request->min_booking_amount ?? 0;
        $settings->reward_credit_days = $request->reward_credit_days ?? 0;
        $settings->points_per_currency = $request->points_per_currency ?? 1;
        $settings->daily_cap_per_referrer = $request->daily_cap_per_referrer;
        $settings->max_referrals_per_user = $request->max_referrals_per_user;
        $settings->share_message = $request->share_message;
        $settings->terms_and_conditions = $request->terms_and_conditions;
        $settings->notes = $request->notes;

        $settings->save();

        $notify[] = ['success', 'Referral settings updated successfully'];
        return back()->withNotify($notify);
    }

    /**
     * Analytics dashboard
     */
    public function analytics()
    {
        $pageTitle = 'Referral Analytics';

        // Global metrics
        $totalCodes = ReferralCode::where('is_active', true)->count();
        $totalClicks = DB::table('referral_clicks')->count();
        $totalInstalls = ReferralEvent::where('type', 'install')->count();
        $totalSignups = ReferralEvent::where('type', 'signup')->count();
        $totalBookings = ReferralEvent::where('type', 'booking')->count();

        $totalRewards = ReferralReward::where('status', 'confirmed')->sum('amount_awarded');
        $pendingRewards = ReferralReward::where('status', 'pending')->sum('amount_awarded');

        // Conversion rates
        $clickToInstall = $totalClicks > 0 ? round(($totalInstalls / $totalClicks) * 100, 2) : 0;
        $installToSignup = $totalInstalls > 0 ? round(($totalSignups / $totalInstalls) * 100, 2) : 0;
        $signupToBooking = $totalSignups > 0 ? round(($totalBookings / $totalSignups) * 100, 2) : 0;

        // Recent activity (last 30 days)
        $startDate = Carbon::now()->subDays(30);

        $dailySignups = ReferralEvent::where('type', 'signup')
            ->where('triggered_at', '>=', $startDate)
            ->selectRaw('DATE(triggered_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Top referrers
        $topReferrers = ReferralCode::with('user')
            ->where('is_active', true)
            ->orderBy('total_signups', 'desc')
            ->limit(10)
            ->get();

        // Recent events
        $recentEvents = ReferralEvent::with(['referrer', 'referee', 'referralCode'])
            ->orderBy('triggered_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.referral.analytics', compact(
            'pageTitle',
            'totalCodes',
            'totalClicks',
            'totalInstalls',
            'totalSignups',
            'totalBookings',
            'totalRewards',
            'pendingRewards',
            'clickToInstall',
            'installToSignup',
            'signupToBooking',
            'dailySignups',
            'topReferrers',
            'recentEvents'
        ));
    }

    /**
     * Referral codes list
     */
    public function codes(Request $request)
    {
        $pageTitle = 'Referral Codes';

        $query = ReferralCode::with('user')->orderBy('created_at', 'desc');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('firstname', 'like', "%{$search}%")
                            ->orWhere('lastname', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status);
        }

        $codes = $query->paginate(getPaginate());

        return view('admin.referral.codes', compact('pageTitle', 'codes'));
    }

    /**
     * Code details
     */
    public function codeDetails($id)
    {
        $pageTitle = 'Referral Code Details';
        $code = ReferralCode::with('user')->findOrFail($id);

        // Get events for this code
        $events = ReferralEvent::with(['referee', 'rewards'])
            ->where('referral_code_id', $id)
            ->orderBy('triggered_at', 'desc')
            ->paginate(getPaginate());

        // Get statistics
        $stats = [
            'total_clicks' => $code->total_clicks,
            'total_installs' => $code->total_installs,
            'total_signups' => $code->total_signups,
            'total_bookings' => $code->total_bookings,
            'total_earnings' => $code->total_earnings,
            'conversion_rate' => $code->total_clicks > 0
                ? round(($code->total_signups / $code->total_clicks) * 100, 2)
                : 0
        ];

        return view('admin.referral.code_details', compact('pageTitle', 'code', 'events', 'stats'));
    }

    /**
     * Toggle code status
     */
    public function toggleCodeStatus($id)
    {
        $code = ReferralCode::findOrFail($id);
        $code->is_active = !$code->is_active;
        $code->save();

        $status = $code->is_active ? 'activated' : 'deactivated';
        $notify[] = ['success', "Referral code {$status} successfully"];

        return back()->withNotify($notify);
    }

    /**
     * Rewards list
     */
    public function rewards(Request $request)
    {
        $pageTitle = 'Referral Rewards';

        $query = ReferralReward::with(['beneficiary', 'event.referrer'])
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('beneficiary', function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $rewards = $query->paginate(getPaginate());

        return view('admin.referral.rewards', compact('pageTitle', 'rewards'));
    }

    /**
     * Confirm pending reward
     */
    public function confirmReward($id)
    {
        $reward = ReferralReward::findOrFail($id);

        if ($reward->status !== 'pending') {
            $notify[] = ['error', 'Only pending rewards can be confirmed'];
            return back()->withNotify($notify);
        }

        $reward->confirm();

        $notify[] = ['success', 'Reward confirmed successfully'];
        return back()->withNotify($notify);
    }

    /**
     * Reverse reward
     */
    public function reverseReward(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        $reward = ReferralReward::findOrFail($id);

        if ($reward->status === 'reversed') {
            $notify[] = ['error', 'Reward is already reversed'];
            return back()->withNotify($notify);
        }

        $reward->reverse($request->reason);

        // Update referral code earnings
        $reward->event->referralCode->increment('total_earnings', -$reward->amount_awarded);

        $notify[] = ['success', 'Reward reversed successfully'];
        return back()->withNotify($notify);
    }
}
