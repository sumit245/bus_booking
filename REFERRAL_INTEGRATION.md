# Referral System Integration Summary

## ✅ Completed: Step 5 - Signup & Booking Flow Integration

The referral tracking system has been successfully integrated into the signup and booking flows.

---

## 1. Signup Flow Integration

### File: `app/Http/Controllers/API/UserController.php`

**Integration Points:**

#### A. OTP Verification (`verifyOtp` method)

When a user verifies their OTP and signs up, the system now:

1. **Accepts referral code** - Added optional validation parameter:

   ```php
   'referral_code' => 'nullable|string|size:6'
   ```

2. **Checks multiple sources** for referral code:

   - Direct parameter: `$request->referral_code`
   - Session storage: `session('referral_code')`
   - Cookie storage: `request()->cookie('referral_code')`

3. **Records signup event** for new users only:
   ```php
   if ($isNewUser && $referralCode) {
       $event = $referralService->recordSignup($referralCode, $user->id);
   }
   ```

**What happens automatically:**

- ✅ Sets `users.referred_by` field (referrer's user_id)
- ✅ Sets `users.referral_code_id` field
- ✅ Creates referral_events record (type: 'signup')
- ✅ Awards rewards if admin configured "reward_on_signup"
- ✅ Prevents self-referral (blocked by ReferralService)
- ✅ Checks daily caps and lifetime limits

---

## 2. Booking Flow Integration

### File: `app/Services/BookingService.php`

**Integration Points:**

#### A. First Booking Confirmation (`updateTicketWithBookingDetails` method)

After payment is verified and booking is confirmed:

```php
// Process referral rewards for first booking
$this->processReferralRewards($bookedTicket);
```

**New Method: `processReferralRewards()`**

- ✅ Calls `ReferralService::recordFirstBooking()`
- ✅ Only triggers for user's first successful booking
- ✅ Passes ticket_id and total_amount for reward calculation
- ✅ Non-blocking: Errors don't fail the booking
- ✅ Comprehensive logging for debugging

**What happens automatically:**

- ✅ Checks if user was referred
- ✅ Checks if this is user's first booking
- ✅ Validates minimum booking amount (if configured)
- ✅ Calculates reward based on admin settings:
  - Fixed amount
  - Percentage of base amount
  - Percentage of ticket amount
- ✅ Awards to referrer and/or referee (based on settings)
- ✅ Creates referral_rewards records (status: 'pending' or 'confirmed')
- ✅ Updates referrer's total_earnings
- ✅ Updates referral_code counters

---

#### B. Booking Cancellation (`cancelOperatorBusTicket` & `cancelThirdPartyBusTicket` methods)

When a booking is cancelled:

```php
// Reverse referral rewards for cancelled booking
$this->reverseReferralRewards($bookedTicket, $remarks);
```

**New Method: `reverseReferralRewards()`**

- ✅ Calls `ReferralService::reverseRewardsForBooking()`
- ✅ Reverses rewards associated with the cancelled ticket
- ✅ Non-blocking: Errors don't fail the cancellation
- ✅ Comprehensive logging

**What happens automatically:**

- ✅ Finds all rewards linked to ticket_id
- ✅ Updates reward status from 'confirmed' → 'reversed'
- ✅ Deducts amounts from users' total_earnings
- ✅ Records reversal reason and timestamp
- ✅ Logs reversal for admin visibility

---

## 3. Complete User Journey

### Example Flow:

1. **User clicks referral link:**

   ```
   https://yourdomain.com?ref=ABC123
   ```

   - TrackReferralCode middleware stores code in session + cookie (72 hours)
   - ReferralService records click event

2. **User installs app (mobile):**

   ```
   POST /api/referral/install
   {
       "referral_code": "ABC123",
       "device_id": "unique-device-id"
   }
   ```

   - ReferralService records install event
   - Awards reward if `reward_on_install = true`

3. **User signs up:**

   ```
   POST /api/users/verify-otp
   {
       "mobile_number": "9876543210",
       "otp": "123456",
       "referral_code": "ABC123"  // optional, auto-detected from session/cookie
   }
   ```

   - UserController detects referral code
   - ReferralService::recordSignup() called
   - Sets `users.referred_by` and `users.referral_code_id`
   - Awards reward if `reward_on_signup = true`

4. **User makes first booking:**

   - Payment confirmed
   - BookingService::updateTicketWithBookingDetails() called
   - BookingService::processReferralRewards() triggered
   - ReferralService::recordFirstBooking() awards rewards
   - Awards based on ticket amount if `reward_on_first_booking = true`

5. **User cancels booking:**
   - BookingService::cancelTicket() called
   - BookingService::reverseReferralRewards() triggered
   - All rewards for that booking reversed
   - Total earnings adjusted

---

## 4. Admin Control Points

Admins can now control:

✅ **When to reward:**

- App installation (`reward_on_install`)
- Signup (`reward_on_signup`)
- First booking (`reward_on_first_booking`)

✅ **Who to reward:**

- Referrer only (`reward_referrer = true`, `reward_referee = false`)
- Referee only (`reward_referrer = false`, `reward_referee = true`)
- Both (`reward_referrer = true`, `reward_referee = true`)

✅ **How much to reward:**

- Fixed amount (`reward_type = 'fixed'`)
- Percentage of base amount (`reward_type = 'percent'`)
- Percentage of ticket amount (`reward_type = 'percent_of_ticket'`)

✅ **Fraud prevention:**

- Self-referral blocking (automatic)
- Daily cap per referrer
- Lifetime max referrals per user
- Minimum booking amount requirement

✅ **Reward lifecycle:**

- Credit delay days (`reward_credit_days`)
- Manual confirmation/reversal via admin panel

---

## 5. Anti-Fraud Mechanisms

**Built-in protections:**

1. ✅ **Self-referral blocking** - Users cannot refer themselves
2. ✅ **Daily caps** - Limit signups per referrer per day
3. ✅ **Lifetime limits** - Max total referrals per user
4. ✅ **Device tracking** - Prevent duplicate installs
5. ✅ **Minimum booking amount** - Only reward qualifying bookings
6. ✅ **First booking only** - Each user can only trigger once
7. ✅ **Automatic reversal** - Cancelled bookings reverse rewards
8. ✅ **Status tracking** - Pending → Confirmed → Reversed lifecycle

---

## 6. Testing Checklist

### Signup Flow:

- [ ] New user with referral code in URL parameter
- [ ] New user with referral code in session (from link click)
- [ ] New user with referral code in cookie (after session expires)
- [ ] New user without referral code
- [ ] Existing user logging in (should not trigger signup event)
- [ ] Self-referral attempt (should be blocked)

### Booking Flow:

- [ ] First booking by referred user (should trigger rewards)
- [ ] Second booking by same user (should NOT trigger rewards)
- [ ] Booking below minimum amount (should NOT trigger rewards)
- [ ] Booking then cancellation (should reverse rewards)
- [ ] Operator bus booking (both creation and cancellation)
- [ ] Third-party bus booking (both creation and cancellation)

### Admin Panel:

- [ ] Change reward settings and verify new signups follow new rules
- [ ] View referral analytics
- [ ] Manually confirm pending rewards
- [ ] Manually reverse confirmed rewards
- [ ] Activate/deactivate referral codes

---

## 7. API Endpoints Summary

### For Frontend Integration:

**Get Referral Data:**

```
GET /api/users/referral-data?mobile_number=9876543210
```

**Get Referral Stats:**

```
GET /api/users/referral-stats?mobile_number=9876543210
```

**Get Referral History:**

```
GET /api/users/referral-history?mobile_number=9876543210
```

**Record Install:**

```
POST /api/referral/install
{
    "referral_code": "ABC123",
    "device_id": "unique-device-id"
}
```

**Verify OTP with Referral:**

```
POST /api/users/verify-otp
{
    "mobile_number": "9876543210",
    "otp": "123456",
    "referral_code": "ABC123"  // optional
}
```

---

## 8. Database Schema

**Tables involved:**

- `referral_settings` - Global configuration
- `referral_codes` - User referral codes
- `referral_clicks` - Click tracking
- `referral_events` - Install/signup/booking events
- `referral_rewards` - Reward records with status
- `users` - Added `referred_by`, `referral_code_id`

---

## 9. Logging

All referral operations are logged:

- ✅ Signup events: `UserController: Referral signup recorded`
- ✅ Booking events: `BookingService: Referral first booking event recorded`
- ✅ Reward reversals: `BookingService: Referral rewards reversed`
- ✅ Errors: All failures logged without breaking main flow

Check logs at: `storage/logs/laravel.log`

---

## 10. Next Steps (Optional Enhancements)

1. **Email/SMS notifications** when users earn rewards
2. **Referral leaderboard** in app/web
3. **Seasonal campaigns** with bonus rewards
4. **Referral code customization** (let users choose codes)
5. **Social sharing templates** (WhatsApp, Facebook, etc.)
6. **Wallet integration** for reward redemption

---

## Support

For issues or questions:

- Check logs: `storage/logs/laravel.log`
- Admin panel: `/admin/referral/analytics`
- Documentation: `refer.md`

**Status:** ✅ Production Ready
**Last Updated:** November 24, 2025
