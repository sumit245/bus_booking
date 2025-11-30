# Booking Ownership and Notification Fix - Implementation Summary

## Problem Solved

### Issue 1: Wrong Booking Ownership
- **Before**: Backend used passenger phone number to determine booking owner
- **After**: Backend uses authenticated user (from Bearer token) as booking owner

### Issue 2: Wrong Notification Recipient
- **Before**: Only one notification sent (sometimes to wrong user)
- **After**: Notifications sent to BOTH booking owner and passenger (if different phones)

---

## Changes Implemented

### 1. Extract Authenticated User in `blockSeatApi()`

**File:** `core/app/Http/Controllers/API/ApiTicketController.php`

**Changes:**
- Added authentication check at the beginning of `blockSeatApi()` method
- Extracts authenticated user from Bearer token (Sanctum)
- Passes `authenticated_user_id` to BookingService via `$requestData` array

**Code Location:** Around line 1270-1307

**Key Addition:**
```php
// Get authenticated user (if available via Sanctum token)
$authenticatedUser = null;
if ($request->bearerToken()) {
    $authenticatedUser = $request->user('sanctum');
}

// Added to requestData:
'authenticated_user_id' => $authenticatedUser ? $authenticatedUser->id : null
```

---

### 2. Update `registerOrLoginUser()` to Prioritize Authenticated User

**File:** `core/app/Services/BookingService.php`

**Changes:**
- Modified `registerOrLoginUser()` method to check for authenticated user FIRST
- Three-tier priority system:
  1. **Priority 1**: Authenticated user from API token (User A)
  2. **Priority 2**: Web session authenticated user
  3. **Priority 3**: Passenger phone (fallback for guest bookings)

**Code Location:** Around line 239-277

**Key Logic:**
- If `authenticated_user_id` is provided, use that user as booking owner
- Only falls back to passenger phone if no authentication is present
- This ensures User A (logged in) can book for User B (passenger) correctly

---

### 3. Update Notification Logic for Dual Recipients

**File:** `core/app/Services/BookingService.php`

**Method:** `sendWhatsAppNotifications()`

**Changes:**
- Send notification to passenger (from `passenger_phone`)
- Also send notification to booking owner (from `user->mobile`)
- Only send duplicate if owner and passenger have different phone numbers
- Comprehensive logging for both notifications

**Code Location:** Around line 1937-1985

**Key Logic:**
1. Send to passenger phone (primary recipient)
2. Check if booking owner phone differs from passenger phone
3. If different, send notification to booking owner as well
4. Log all notification attempts for debugging

---

## Data Flow

### Before Fix:
```
Frontend Request (Bearer token: User A, Passenger: User B)
    ↓
Backend ignores token
    ↓
Uses passenger phone (User B) to find/create user
    ↓
Booking owner = User B ❌
Notification sent to User A (wrong logic) ❌
```

### After Fix:
```
Frontend Request (Bearer token: User A, Passenger: User B)
    ↓
Backend extracts User A from Bearer token ✅
    ↓
Uses User A as booking owner ✅
    ↓
Booking owner = User A ✅
Passenger details = User B ✅
Notification sent to User B (passenger) ✅
Notification sent to User A (owner) ✅
```

---

## Use Cases Handled

### ✅ Use Case 1: Authenticated User Books for Someone Else
- User A logged in, books for User B
- **Result**: Booking `user_id` = User A, notifications to both User A and User B

### ✅ Use Case 2: Authenticated User Books for Themselves
- User A logged in, books for themselves (same phone)
- **Result**: Booking `user_id` = User A, single notification to User A (no duplicate)

### ✅ Use Case 3: Guest Booking (No Authentication)
- No token provided, books for User B
- **Result**: Falls back to passenger phone logic, notification to User B

### ✅ Use Case 4: Web Session Booking
- User logged in via web session, books for someone
- **Result**: Uses web session user as booking owner

---

## Testing Recommendations

### Test 1: Authenticated API Booking for Someone Else
```bash
# 1. Get authentication token for User A
POST /api/verify-otp
{
  "mobile_number": "1111111111",
  "otp": "123456"
}
# Response: { "token": "..." }

# 2. Book ticket for User B using User A's token
POST /api/bus/block-seat
Headers: Authorization: Bearer {token_from_step_1}
Body: {
  "Phoneno": "2222222222",  // User B's phone
  "FirstName": "User B",
  ...
}

# Verify:
# - booked_tickets.user_id = User A's ID
# - booked_tickets.passenger_phone = "2222222222"
# - Notification sent to "2222222222" (User B)
# - Notification sent to User A's phone (if different)
```

### Test 2: Authenticated API Booking for Self
```bash
# Same as Test 1, but use same phone number
# Verify only one notification sent (no duplicate)
```

### Test 3: Guest Booking
```bash
# Book without Bearer token
# Verify falls back to passenger phone logic
```

---

## Database Changes

**No database schema changes required!**

The fix only changes how `user_id` is determined and how notifications are sent. All existing columns are used correctly:
- `booked_tickets.user_id` - Now correctly stores booking owner (authenticated user)
- `booked_tickets.passenger_phone` - Still stores passenger phone (from request)

---

## Backward Compatibility

✅ **Fully backward compatible!**

- Guest bookings (no token) still work using passenger phone
- Web session bookings still work
- Existing bookings are not affected
- API endpoints accept both authenticated and guest requests

---

## Logging Enhancements

All changes include comprehensive logging:

1. **Authentication Detection:**
   - Logs when authenticated user is detected
   - Logs which priority level is used (API token, web session, or guest)

2. **Notification Tracking:**
   - Logs both passenger and owner notification attempts
   - Logs when notifications are skipped (same phone)
   - Logs reasons for skipping

3. **Error Handling:**
   - Logs warnings when authenticated user not found
   - Logs when passenger phone is missing

---

## Files Modified

1. **`core/app/Http/Controllers/API/ApiTicketController.php`**
   - Added authenticated user extraction in `blockSeatApi()`

2. **`core/app/Services/BookingService.php`**
   - Modified `registerOrLoginUser()` to prioritize authenticated user
   - Updated `sendWhatsAppNotifications()` to send to both owner and passenger

---

## Expected Behavior After Fix

### Scenario: User A (logged in) books for User B (passenger)

**Database:**
- `booked_tickets.user_id` = User A's ID ✅
- `booked_tickets.passenger_phone` = User B's phone ✅
- `booked_tickets.passenger_name` = User B's name ✅

**Notifications:**
- WhatsApp/SMS sent to User B's phone ✅
- WhatsApp/SMS sent to User A's phone ✅

**Ticket History:**
- User A can see booking in their ticket history ✅
- User B can see booking via passenger phone search ✅
- User A can cancel (owner) ✅
- User B cannot cancel (passenger) ✅

---

## Success Criteria

✅ Authenticated users can book for others
✅ Booking ownership correctly assigned to authenticated user
✅ Passenger receives ticket notification
✅ Booking owner receives ticket notification
✅ No duplicate notifications when owner = passenger
✅ Guest bookings still work (backward compatible)
✅ Web session bookings still work

---

## Next Steps for Testing

1. Test authenticated booking for someone else
2. Test authenticated booking for self
3. Test guest booking (no token)
4. Verify notifications are sent correctly
5. Verify booking ownership in database
6. Verify ticket history shows correct ownership

---

## Summary

All three changes have been successfully implemented:

1. ✅ **Authentication Detection**: `blockSeatApi()` now extracts authenticated user from Bearer token
2. ✅ **Booking Ownership**: `registerOrLoginUser()` now prioritizes authenticated user
3. ✅ **Dual Notifications**: `sendWhatsAppNotifications()` now sends to both owner and passenger

The system now correctly handles the scenario where User A (logged in) books a ticket for User B (passenger), ensuring:
- Correct booking ownership
- Correct notification delivery
- Full backward compatibility

