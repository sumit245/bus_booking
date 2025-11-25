<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Cookie;

class TrackReferralCode
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if referral code is in the URL (?ref=ABC123)
        if ($request->has('ref')) {
            $refCode = $request->input('ref');

            // Validate code format (6 characters)
            if (strlen($refCode) === 6 && ctype_alnum($refCode)) {
                // Store in session for 72 hours
                session(['referral_code' => strtoupper($refCode)]);

                // Also set a cookie as backup (72 hours = 4320 minutes)
                Cookie::queue('referral_code', strtoupper($refCode), 4320);

                // Record the click
                $this->referralService->recordClick($refCode, $request);
            }
        }

        return $next($request);
    }
}
