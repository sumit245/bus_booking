<?php

namespace App\Services;

use App\Models\ReferralSetting;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\ReferralReward;
use App\Models\ReferralClick;
use App\Models\User;
use App\Models\BookedTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReferralService
{
    /**
     * Record a referral link click
     */
    public function recordClick(string $refCode, Request $request): ?ReferralClick
    {
        try {
            $referralCode = ReferralCode::where('code', $refCode)
                ->where('is_active', true)
                ->first();

            if (!$referralCode) {
                Log::warning('Invalid referral code clicked', ['code' => $refCode]);
                return null;
            }

            $click = ReferralClick::create([
                'referral_code_id' => $referralCode->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer_url' => $request->header('referer'),
                'clicked_at' => now()
            ]);

            $referralCode->incrementClicks();

            Log::info('Referral click recorded', [
                'code' => $refCode,
                'ip' => $request->ip()
            ]);

            return $click;
        } catch (\Exception $e) {
            Log::error('Error recording referral click', [
                'code' => $refCode,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Record app installation event
     */
    public function recordInstall(string $refCode, ?string $deviceId = null, ?int $userId = null): ?ReferralEvent
    {
        try {
            $settings = ReferralSetting::current();

            if (!$settings->is_enabled || !$settings->reward_on_install) {
                return null;
            }

            $referralCode = ReferralCode::where('code', $refCode)
                ->where('is_active', true)
                ->first();

            if (!$referralCode) {
                Log::warning('Invalid referral code for install', ['code' => $refCode]);
                return null;
            }

            // Check for duplicate install from same device
            if ($deviceId) {
                $existingInstall = ReferralEvent::where('referral_code_id', $referralCode->id)
                    ->where('type', 'install')
                    ->where('context_json->device_id', $deviceId)
                    ->first();

                if ($existingInstall) {
                    Log::warning('Duplicate install attempt', [
                        'code' => $refCode,
                        'device_id' => $deviceId
                    ]);
                    return null;
                }
            }

            $event = ReferralEvent::create([
                'referrer_user_id' => $referralCode->user_id,
                'referee_user_id' => $userId,
                'referral_code_id' => $referralCode->id,
                'type' => 'install',
                'context_json' => [
                    'device_id' => $deviceId,
                    'source' => $referralCode->source
                ],
                'triggered_at' => now()
            ]);

            $referralCode->incrementInstalls();

            // Award rewards if configured
            $this->processRewards($event, 'install');

            Log::info('Install event recorded', [
                'code' => $refCode,
                'device_id' => $deviceId,
                'user_id' => $userId
            ]);

            return $event;
        } catch (\Exception $e) {
            Log::error('Error recording install', [
                'code' => $refCode,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Record signup event and link user to referral code
     */
    public function recordSignup(string $refCode, int $userId): ?ReferralEvent
    {
        try {
            $settings = ReferralSetting::current();

            if (!$settings->is_enabled) {
                return null;
            }

            $referralCode = ReferralCode::where('code', $refCode)
                ->where('is_active', true)
                ->first();

            if (!$referralCode) {
                Log::warning('Invalid referral code for signup', ['code' => $refCode]);
                return null;
            }

            $user = User::find($userId);
            if (!$user) {
                Log::error('User not found for signup', ['user_id' => $userId]);
                return null;
            }

            // Prevent self-referral
            if ($referralCode->user_id === $userId) {
                Log::warning('Self-referral attempt blocked', [
                    'code' => $refCode,
                    'user_id' => $userId
                ]);
                return null;
            }

            // Check daily cap
            if ($this->hasExceededDailyCap($referralCode)) {
                Log::warning('Daily referral cap exceeded', [
                    'code' => $refCode,
                    'referrer_id' => $referralCode->user_id
                ]);
                return null;
            }

            // Check lifetime max
            if ($this->hasExceededLifetimeMax($referralCode)) {
                Log::warning('Lifetime referral max exceeded', [
                    'code' => $refCode,
                    'referrer_id' => $referralCode->user_id
                ]);
                return null;
            }

            // Link user to referral code
            $user->update([
                'referred_by' => $referralCode->user_id,
                'referral_code_id' => $referralCode->id
            ]);

            $event = ReferralEvent::create([
                'referrer_user_id' => $referralCode->user_id,
                'referee_user_id' => $userId,
                'referral_code_id' => $referralCode->id,
                'type' => 'signup',
                'context_json' => [
                    'mobile' => $user->mobile,
                    'email' => $user->email
                ],
                'triggered_at' => now()
            ]);

            $referralCode->incrementSignups();

            // Award rewards if configured
            if ($settings->reward_on_signup) {
                $this->processRewards($event, 'signup');
            }

            Log::info('Signup event recorded', [
                'code' => $refCode,
                'user_id' => $userId,
                'referrer_id' => $referralCode->user_id
            ]);

            return $event;
        } catch (\Exception $e) {
            Log::error('Error recording signup', [
                'code' => $refCode,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Record first booking event and award rewards
     */
    public function recordFirstBooking(int $userId, int $ticketId, float $amount): ?ReferralEvent
    {
        try {
            $settings = ReferralSetting::current();

            if (!$settings->is_enabled || !$settings->reward_on_first_booking) {
                return null;
            }

            $user = User::find($userId);
            if (!$user || !$user->referral_code_id) {
                // User wasn't referred
                return null;
            }

            // Check if user has already completed first booking
            if ($user->has_completed_first_booking) {
                Log::info('User already completed first booking', ['user_id' => $userId]);
                return null;
            }

            // Check minimum booking amount
            if ($amount < $settings->min_booking_amount) {
                Log::info('Booking amount below minimum for reward', [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'minimum' => $settings->min_booking_amount
                ]);
                return null;
            }

            $referralCode = ReferralCode::find($user->referral_code_id);
            if (!$referralCode || !$referralCode->is_active) {
                return null;
            }

            $event = ReferralEvent::create([
                'referrer_user_id' => $user->referred_by,
                'referee_user_id' => $userId,
                'referral_code_id' => $referralCode->id,
                'type' => 'booking',
                'ticket_id' => $ticketId,
                'context_json' => [
                    'booking_amount' => $amount,
                    'ticket_id' => $ticketId
                ],
                'triggered_at' => now()
            ]);

            $referralCode->incrementBookings();

            // Mark user as having completed first booking
            $user->update(['has_completed_first_booking' => true]);

            // Award rewards
            $this->processRewards($event, 'booking', $amount);

            Log::info('First booking event recorded', [
                'user_id' => $userId,
                'ticket_id' => $ticketId,
                'amount' => $amount,
                'referrer_id' => $user->referred_by
            ]);

            return $event;
        } catch (\Exception $e) {
            Log::error('Error recording first booking', [
                'user_id' => $userId,
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Process and award rewards based on settings
     */
    protected function processRewards(ReferralEvent $event, string $eventType, float $basisAmount = 0): void
    {
        try {
            $settings = ReferralSetting::current();

            // Determine reward amount based on type
            $rewardAmount = $this->calculateRewardAmount($settings, $basisAmount);

            if ($rewardAmount <= 0) {
                return;
            }

            // Award to referrer if configured
            if ($settings->reward_referrer && $event->referrer_user_id) {
                $this->awardReward(
                    $event,
                    $event->referrer_user_id,
                    $settings->reward_type,
                    $basisAmount,
                    $rewardAmount,
                    $settings->reward_credit_days
                );
            }

            // Award to referee if configured
            if ($settings->reward_referee && $event->referee_user_id) {
                $this->awardReward(
                    $event,
                    $event->referee_user_id,
                    $settings->reward_type,
                    $basisAmount,
                    $rewardAmount,
                    $settings->reward_credit_days
                );
            }
        } catch (\Exception $e) {
            Log::error('Error processing rewards', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate reward amount based on settings
     */
    protected function calculateRewardAmount(ReferralSetting $settings, float $basisAmount = 0): float
    {
        switch ($settings->reward_type) {
            case 'fixed':
                return $settings->fixed_amount;

            case 'percent':
                // Percent of a configured base amount
                return ($settings->percent_share / 100) * $basisAmount;

            case 'percent_of_ticket':
                // Percent of booking amount
                return ($settings->percent_of_ticket / 100) * $basisAmount;

            default:
                return 0;
        }
    }

    /**
     * Award reward to a user
     */
    protected function awardReward(
        ReferralEvent $event,
        int $beneficiaryUserId,
        string $rewardType,
        float $basisAmount,
        float $amount,
        int $creditDays = 0
    ): ReferralReward {
        $status = $creditDays > 0 ? 'pending' : 'confirmed';
        $creditedAt = $creditDays > 0 ? now()->addDays($creditDays) : now();

        $reward = ReferralReward::create([
            'referral_event_id' => $event->id,
            'beneficiary_user_id' => $beneficiaryUserId,
            'reward_type' => $rewardType,
            'basis_amount' => $basisAmount,
            'amount_awarded' => $amount,
            'status' => $status,
            'credited_at' => $status === 'confirmed' ? $creditedAt : null
        ]);

        // Update referral code total earnings
        $event->referralCode->addEarnings($amount);

        Log::info('Reward awarded', [
            'event_id' => $event->id,
            'beneficiary_id' => $beneficiaryUserId,
            'amount' => $amount,
            'status' => $status
        ]);

        return $reward;
    }

    /**
     * Reverse rewards for a booking (e.g., on cancellation/refund)
     */
    public function reverseRewardsForBooking(int $ticketId, string $reason = 'Booking cancelled'): int
    {
        try {
            $event = ReferralEvent::where('ticket_id', $ticketId)
                ->where('type', 'booking')
                ->first();

            if (!$event) {
                return 0;
            }

            $rewards = ReferralReward::where('referral_event_id', $event->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->get();

            $reversedCount = 0;
            foreach ($rewards as $reward) {
                $reward->reverse($reason);

                // Subtract from referral code total earnings
                $event->referralCode->increment('total_earnings', -$reward->amount_awarded);

                $reversedCount++;
            }

            Log::info('Rewards reversed for booking', [
                'ticket_id' => $ticketId,
                'event_id' => $event->id,
                'count' => $reversedCount,
                'reason' => $reason
            ]);

            return $reversedCount;
        } catch (\Exception $e) {
            Log::error('Error reversing rewards', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get or create referral code for a user
     */
    public function getOrCreateUserReferralCode(int $userId, string $source = 'app', ?string $deviceId = null): ReferralCode
    {
        $user = User::find($userId);

        // Check if user already has a referral code
        $referralCode = ReferralCode::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if ($referralCode) {
            return $referralCode;
        }

        // Create new referral code
        $referralCode = ReferralCode::create([
            'user_id' => $userId,
            'code' => ReferralCode::generateUniqueCode(),
            'source' => $source,
            'device_id' => $deviceId,
            'is_active' => true
        ]);

        Log::info('Referral code created for user', [
            'user_id' => $userId,
            'code' => $referralCode->code
        ]);

        return $referralCode;
    }

    /**
     * Get referral statistics for a user
     */
    public function getUserReferralStats(int $userId): array
    {
        $referralCode = ReferralCode::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$referralCode) {
            return [
                'total_referrals' => 0,
                'successful_installs' => 0,
                'total_earnings' => 0,
                'pending_earnings' => 0
            ];
        }

        $totalReferrals = ReferralEvent::where('referral_code_id', $referralCode->id)
            ->where('type', 'signup')
            ->count();

        $successfulInstalls = ReferralEvent::where('referral_code_id', $referralCode->id)
            ->where('type', 'install')
            ->count();

        $totalEarnings = ReferralReward::where('beneficiary_user_id', $userId)
            ->where('status', 'confirmed')
            ->sum('amount_awarded');

        $pendingEarnings = ReferralReward::where('beneficiary_user_id', $userId)
            ->where('status', 'pending')
            ->sum('amount_awarded');

        return [
            'total_referrals' => $totalReferrals,
            'successful_installs' => $successfulInstalls,
            'total_earnings' => (float) $totalEarnings,
            'pending_earnings' => (float) $pendingEarnings
        ];
    }

    /**
     * Get referral history for a user
     */
    public function getUserReferralHistory(int $userId, int $limit = 10): array
    {
        $referralCode = ReferralCode::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$referralCode) {
            return [];
        }

        $events = ReferralEvent::where('referral_code_id', $referralCode->id)
            ->where('type', 'signup')
            ->with(['referee', 'rewards'])
            ->orderBy('triggered_at', 'desc')
            ->limit($limit)
            ->get();

        return $events->map(function ($event) {
            $reward = $event->rewards()
                ->where('beneficiary_user_id', $event->referrer_user_id)
                ->first();

            $status = 'Pending';
            $earning = 0;

            if ($reward) {
                $earning = $reward->amount_awarded;

                if ($reward->status === 'confirmed') {
                    $status = 'Completed';
                } elseif ($reward->status === 'reversed') {
                    $status = 'Failed';
                }
            }

            return [
                'id' => $event->id,
                'name' => $event->referee ? $event->referee->fullname : 'Unknown',
                'date' => $event->triggered_at->format('Y-m-d'),
                'status' => $status,
                'earning' => $earning
            ];
        })->toArray();
    }

    /**
     * Check if referrer has exceeded daily cap
     */
    protected function hasExceededDailyCap(ReferralCode $referralCode): bool
    {
        $settings = ReferralSetting::current();

        if (!$settings->daily_cap_per_referrer) {
            return false;
        }

        $todayCount = ReferralEvent::where('referral_code_id', $referralCode->id)
            ->where('type', 'signup')
            ->whereDate('triggered_at', today())
            ->count();

        return $todayCount >= $settings->daily_cap_per_referrer;
    }

    /**
     * Check if referrer has exceeded lifetime max
     */
    protected function hasExceededLifetimeMax(ReferralCode $referralCode): bool
    {
        $settings = ReferralSetting::current();

        if (!$settings->max_referrals_per_user) {
            return false;
        }

        $totalCount = ReferralEvent::where('referral_code_id', $referralCode->id)
            ->where('type', 'signup')
            ->count();

        return $totalCount >= $settings->max_referrals_per_user;
    }

    /**
     * Confirm pending rewards that are due
     */
    public function confirmDueRewards(): int
    {
        try {
            $dueRewards = ReferralReward::where('status', 'pending')
                ->whereNotNull('credited_at')
                ->where('credited_at', '<=', now())
                ->get();

            $confirmedCount = 0;
            foreach ($dueRewards as $reward) {
                $reward->confirm();
                $confirmedCount++;
            }

            if ($confirmedCount > 0) {
                Log::info('Confirmed due rewards', ['count' => $confirmedCount]);
            }

            return $confirmedCount;
        } catch (\Exception $e) {
            Log::error('Error confirming due rewards', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
