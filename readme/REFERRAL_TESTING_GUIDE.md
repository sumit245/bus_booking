# Referral System Testing Guide

## Quick Test Scenarios

### 1. Test Signup with Referral Code

**Step 1: Get a referral code**

```bash
# Create a test user first (User A - Referrer)
POST /api/users/send-otp
{
    "mobile_number": "9876543210"
}

POST /api/users/verify-otp
{
    "mobile_number": "9876543210",
    "otp": "123456"
}

# Get User A's referral code
GET /api/users/referral-data?mobile_number=9876543210
# Response will contain: "referralCode": "ABC123"
```

**Step 2: Signup new user with referral (User B - Referee)**

```bash
POST /api/users/send-otp
{
    "mobile_number": "9999888877"
}

POST /api/users/verify-otp
{
    "mobile_number": "9999888877",
    "otp": "123456",
    "referral_code": "ABC123"  # Use User A's code
}
```

**Step 3: Verify signup was tracked**

```bash
# Check User A's stats
GET /api/users/referral-stats?mobile_number=9876543210
# Should show: totalReferrals = 1

# Check database
SELECT * FROM referral_events WHERE type = 'signup' ORDER BY id DESC LIMIT 1;
# Should show User B's signup event

# Check if reward was created (if reward_on_signup is enabled)
SELECT * FROM referral_rewards ORDER BY id DESC LIMIT 1;
```

---

### 2. Test First Booking Reward

**Prerequisites:** User B (referee) already signed up with User A's referral code

**Step 1: Make first booking for User B**

```bash
# Complete full booking flow for User B (9999888877)
# ... seat selection, payment, etc ...

# After payment is confirmed, check:
GET /api/users/referral-stats?mobile_number=9876543210
# User A's totalEarnings should increase if reward_on_first_booking is enabled
```

**Step 2: Verify database**

```sql
-- Check event was recorded
SELECT * FROM referral_events
WHERE type = 'first_booking'
AND referee_user_id = (SELECT id FROM users WHERE mobile = '9999888877')
ORDER BY id DESC LIMIT 1;

-- Check reward was created
SELECT * FROM referral_rewards
WHERE event_type = 'first_booking'
ORDER BY id DESC LIMIT 1;

-- Verify user's referred_by is set
SELECT id, mobile, referred_by, referral_code_id
FROM users
WHERE mobile = '9999888877';
```

---

### 3. Test Booking Cancellation (Reward Reversal)

**Step 1: Cancel the booking**

```bash
POST /api/booking/cancel
{
    "BookingId": "BOOKING123",
    "SearchTokenId": "TOKEN123",
    "SeatId": "A1",
    "Remarks": "Test cancellation"
}
```

**Step 2: Verify reversal**

```sql
-- Check reward status changed to 'reversed'
SELECT * FROM referral_rewards
WHERE ticket_id = TICKET_ID_HERE
ORDER BY id DESC;

-- Check User A's total earnings decreased
SELECT mobile, total_earnings
FROM users
WHERE mobile = '9876543210';
```

**Step 3: Check logs**

```bash
tail -f storage/logs/laravel.log | grep "Referral"
# Should show: "BookingService: Referral rewards reversed for cancelled booking"
```

---

### 4. Test Self-Referral Prevention

**Try to refer yourself (should fail):**

```bash
# User A tries to use their own code
POST /api/users/verify-otp
{
    "mobile_number": "9876543210",
    "otp": "123456",
    "referral_code": "ABC123"  # User A's own code
}

# Check logs - should show warning:
# "Self-referral attempt blocked"
```

---

### 5. Test Admin Panel

**Access admin panel:**

```
http://yourdomain.com/admin/referral/settings
http://yourdomain.com/admin/referral/analytics
http://yourdomain.com/admin/referral/codes
http://yourdomain.com/admin/referral/rewards
```

**Test settings changes:**

1. Change `reward_type` to 'fixed' and set amount to 50
2. Perform a new signup
3. Verify reward amount is ₹50 (not percentage)

**Test manual confirmation:**

1. Set `reward_credit_days` to 7
2. Perform a signup/booking
3. Reward should be 'pending'
4. Go to `/admin/referral/rewards`
5. Click "Confirm" button
6. Verify status changes to 'confirmed'

**Test manual reversal:**

1. Find a confirmed reward
2. Click "Reverse" button
3. Enter reason
4. Verify status changes to 'reversed'

---

### 6. Test Different Reward Types

**Test Fixed Amount:**

```sql
UPDATE referral_settings SET
    reward_type = 'fixed',
    fixed_amount = 100.00,
    reward_on_signup = 1;
```

New signup should award exactly ₹100

**Test Percentage Share:**

```sql
UPDATE referral_settings SET
    reward_type = 'percent',
    percent_share = 10.00,
    reward_on_first_booking = 1;
```

Booking of ₹1000 should award ₹100 (10%)

**Test Percentage of Ticket:**

```sql
UPDATE referral_settings SET
    reward_type = 'percent_of_ticket',
    percent_of_ticket = 5.00,
    reward_on_first_booking = 1;
```

Booking of ₹1000 should award ₹50 (5% of ticket)

---

### 7. Test Dual Beneficiary

**Reward both referrer and referee:**

```sql
UPDATE referral_settings SET
    reward_referrer = 1,
    reward_referee = 1,
    reward_type = 'fixed',
    fixed_amount = 50.00,
    reward_on_signup = 1;
```

After signup, check:

```sql
SELECT * FROM referral_rewards
WHERE event_id = (SELECT id FROM referral_events ORDER BY id DESC LIMIT 1);
-- Should show 2 rewards: one for referrer, one for referee
```

---

### 8. Test Daily Cap

**Set daily cap:**

```sql
UPDATE referral_settings SET daily_cap_per_referrer = 2;
```

**Test:**

1. User A's code used for 2 signups → Should work
2. User A's code used for 3rd signup → Should be blocked
3. Next day → Should work again

**Verify:**

```bash
# Check logs for:
# "Daily referral cap reached for referrer"
```

---

### 9. Test Minimum Booking Amount

**Set minimum:**

```sql
UPDATE referral_settings SET
    min_booking_amount = 500.00,
    reward_on_first_booking = 1;
```

**Test:**

- Booking of ₹300 → No reward
- Booking of ₹600 → Reward created

---

### 10. Test API Endpoints

**Test all frontend endpoints:**

```bash
# 1. Get referral data
curl -X GET "http://localhost/api/users/referral-data?mobile_number=9876543210"

# 2. Get referral stats
curl -X GET "http://localhost/api/users/referral-stats?mobile_number=9876543210"

# 3. Get referral history
curl -X GET "http://localhost/api/users/referral-history?mobile_number=9876543210&limit=20"

# 4. Record install
curl -X POST "http://localhost/api/referral/install" \
  -H "Content-Type: application/json" \
  -d '{
    "referral_code": "ABC123",
    "device_id": "test-device-123"
  }'
```

**Expected responses:**

- All should return `"success": true`
- Data should match database records

---

## Database Quick Checks

**Check all referral data for a user:**

```sql
-- Get user's referral code
SELECT code FROM referral_codes WHERE user_id = USER_ID;

-- Get user's referrals
SELECT * FROM users WHERE referred_by = USER_ID;

-- Get user's events (as referrer)
SELECT * FROM referral_events WHERE referrer_user_id = USER_ID;

-- Get user's rewards
SELECT * FROM referral_rewards WHERE beneficiary_user_id = USER_ID;

-- Get user's total stats
SELECT
    u.mobile,
    u.total_earnings,
    rc.code,
    rc.total_signups,
    rc.total_bookings,
    rc.total_earnings
FROM users u
LEFT JOIN referral_codes rc ON rc.user_id = u.id
WHERE u.id = USER_ID;
```

---

## Common Issues & Solutions

### Issue: "Referral code not found"

**Solution:** Ensure user has a referral code created

```sql
-- Check if code exists
SELECT * FROM referral_codes WHERE user_id = USER_ID;

-- If not, code will be auto-generated on first API call to /api/users/referral-data
```

### Issue: "Reward not created"

**Check:**

1. Is referral system enabled? `SELECT is_enabled FROM referral_settings;`
2. Is reward trigger enabled? `SELECT reward_on_signup, reward_on_first_booking FROM referral_settings;`
3. Check logs for errors: `tail -f storage/logs/laravel.log`

### Issue: "Reward amount is 0"

**Check:**

```sql
SELECT reward_type, fixed_amount, percent_share, percent_of_ticket
FROM referral_settings;
```

Ensure values are set correctly

### Issue: "Second booking also created reward"

**Check:**

```sql
-- Should only have ONE first_booking event per user
SELECT COUNT(*) FROM referral_events
WHERE referee_user_id = USER_ID AND type = 'first_booking';
-- Should be 0 or 1, never more than 1
```

---

## Performance Testing

**Test with volume:**

```bash
# Create 100 signups with referrals
for i in {1..100}; do
    curl -X POST "http://localhost/api/users/verify-otp" \
      -H "Content-Type: application/json" \
      -d "{
        \"mobile_number\": \"98765432$i\",
        \"otp\": \"123456\",
        \"referral_code\": \"ABC123\"
      }"
done

# Check query performance
EXPLAIN SELECT * FROM referral_events WHERE referral_code_id = 1;
# Should use index
```

---

## Monitoring

**Key metrics to watch:**

```sql
-- Total active referral codes
SELECT COUNT(*) FROM referral_codes WHERE is_active = 1;

-- Total rewards paid today
SELECT SUM(amount) FROM referral_rewards
WHERE status = 'confirmed'
AND DATE(created_at) = CURDATE();

-- Conversion funnel
SELECT
    COUNT(CASE WHEN type = 'click' THEN 1 END) as clicks,
    COUNT(CASE WHEN type = 'install' THEN 1 END) as installs,
    COUNT(CASE WHEN type = 'signup' THEN 1 END) as signups,
    COUNT(CASE WHEN type = 'first_booking' THEN 1 END) as bookings
FROM referral_events
WHERE DATE(triggered_at) = CURDATE();
```

---

**Happy Testing! 🎉**
