# Agent Flow Analysis & Issues

## Overview

The agent system allows third-party agents to book bus tickets and earn commissions. The system was designed but has several incomplete implementations and critical bugs.

---

## Critical Issues Found

### 1. **Missing Auth Guard Configuration** ⚠️ **CRITICAL**

**Location:** `/config/auth.php`

**Issue:** The `agent` auth guard is configured but the provider definition is incomplete.

**Current State:**

```php
'guards' => [
    // ... other guards
    'agent' => [
        'driver' => 'session',
        'provider' => 'agents',
    ],
],

'providers' => [
    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Models\Admin::class,
    ],
    // 'agents' provider is MISSING!
]
```

**Fix Required:** Add agent provider configuration

---

### 2. **AgentCommissionCalculator Method Signature Mismatch** ⚠️ **CRITICAL**

**Location:** `/app/Services/AgentCommissionCalculator.php` & `/app/Http/Controllers/Agent/BookingController.php`

**Issue:** The `calculate()` method signature doesn't match between service and controller.

**Service Definition (Line 42):**

```php
public function calculate($bookingAmount) {
    // Only takes 1 parameter
}
```

**Controller Usage (Line 55, 95):**

```php
$commissionData = $this->commissionCalculator->calculate($totalAmount, $commissionConfig);
// Passing 2 parameters!
```

**Impact:** This will cause a runtime error when agents try to make bookings.

---

### 3. **BookingController Database Field Mismatches** ⚠️ **HIGH**

**Location:** `/app/Http/Controllers/Agent/BookingController.php`

**Multiple Issues:**

#### Issue 3a: Non-existent trip_id field (Line 107)

```php
'trip_id' => $schedule->id,  // Should be 'schedule_id'
```

#### Issue 3b: Non-existent pickup/drop fields (Lines 108-110)

```php
'source_destination' => $request->pickup_point . '-' . $request->drop_point,
'pickup_point' => $request->pickup_point,  // Field doesn't exist
'drop' => $request->drop_point,  // Should be 'dropping_point'
```

**Correct fields from migration:**

- `boarding_point_details` (JSON)
- `dropping_point_details` (JSON)
- `dropping_point` (ID)

#### Issue 3c: Missing required fields

The controller doesn't populate many required `booked_tickets` fields:

- `pnr_number` (required unique identifier)
- `bus_id`
- `route_id`
- `bus_type`
- `travel_name`
- `departure_time`, `arrival_time`
- `origin_city`, `destination_city`
- `boarding_point_details` (JSON)
- `dropping_point_details` (JSON)
- `date_of_journey` (using wrong format)
- Many others...

---

### 4. **Incomplete Admin AgentController** ⚠️ **HIGH**

**Location:** `/app/Http/Controllers/Admin/AgentController.php`

**Issue:** All methods are empty stubs with TODO comments.

**Missing Implementations:**

- `index()` - Should list all agents with filtering
- `create()` - Should show creation form
- `store()` - Should create new agent
- `show()` - Should show agent details
- `edit()` - Should show edit form
- `update()` - Should update agent details
- `verify()` - Should verify agent account
- `suspend()` - Should suspend agent account
- `bookings()` - Should show agent bookings
- `earnings()` - Should show agent earnings

**Impact:** Admins cannot manage agents at all.

---

### 5. **Incomplete ProfileController** ⚠️ **MEDIUM**

**Location:** `/app/Http/Controllers/Agent/ProfileController.php`

**Missing:**

- Profile update logic (Line 18-22)
- Document upload logic (Line 24-28)
- Password change functionality
- Profile image upload

---

### 6. **Missing Middleware for Agent Status Checks** ⚠️ **MEDIUM**

**Issue:** No middleware to check if agent is `active` vs `pending` vs `suspended`.

**Current Behavior:**

- Pending agents can access all routes (Line 113-116 in `AuthController.php` only shows warning)
- Suspended agents checked only at login, not during session

**Needed:** Middleware to restrict pending/suspended agents from booking

---

### 7. **Agent Booking Flow Incomplete** ⚠️ **HIGH**

**Missing Features:**

1. **Seat Selection Integration:**

   - The `selectSeats()` method exists but doesn't properly integrate with `SeatAvailabilityService`
   - No validation of seat availability before booking
   - Seat blocking mechanism not implemented for agents

2. **Payment Flow:**

   - No payment gateway integration
   - Commission should be instantly earned but system expects payment flow
   - Agent payment vs customer payment distinction unclear

3. **Commission Calculation Integration:**
   - Commission config not properly passed
   - No validation of commission calculations
   - Discrepancy between `base_amount_paid` and `total_amount`

---

### 8. **Dashboard Statistics Broken** ⚠️ **MEDIUM**

**Location:** `/app/Http/Controllers/Agent/DashboardController.php`

**Issues:**

#### Issue 8a: Incorrect date filtering (Lines 62-72)

```php
$agentBookings->filter(function ($booking) use ($today) {
    return $booking->bookedTicket &&
           $booking->bookedTicket->created_at->startOfDay()->equalTo($today);
})->count()
```

**Problem:** `startOfDay()` modifies the original timestamp, causing incorrect comparisons.

#### Issue 8b: Performance issues

- Loading all agent bookings into memory before filtering
- Should use database queries with `whereDate()` instead

---

### 9. **Missing Agent Views** ⚠️ **CRITICAL**

**Views that exist but may be incomplete:**

- `agent.auth.login` ✓
- `agent.auth.register` ✓
- `agent.dashboard.index` ?
- `agent.search.index` ?
- `agent.search.results` ?
- `agent.booking.index` ?
- `agent.booking.show` ?
- `agent.bookings.print` ?
- `agent.earnings.index` ?
- `agent.profile.index` ✓

**Need to verify:** Booking seat selection, payment, confirmation views

---

### 10. **API Routes vs Web Routes Confusion** ⚠️ **MEDIUM**

**Location:** `routes/web.php` (Lines 1788-1803)

**Issue:** Agent booking flow reuses `SiteController` methods but agents should have different commission logic:

```php
// Agent routes reuse user booking flow
Route::post("/booking/confirm",
    "\App\Http\Controllers\SiteController@bookTicketApi")
    ->name("booking.confirm");
```

**Problem:**

- `SiteController@bookTicketApi` doesn't handle agent commissions
- No agent-specific booking creation logic
- Agent should pay base price but charge customer base + commission

---

### 11. **Database Schema Issues** ⚠️ **MEDIUM**

#### Issue 11a: Missing enum values

**Location:** `2025_11_05_163333_add_admin_to_booking_source_enum.php`

The migration adds 'admin' to `booking_source` enum but might need to also handle 'agent' if not already present.

#### Issue 11b: Unique constraint issue

**Location:** `agent_bookings` migration (Line 38)

```php
$table->unique('booked_ticket_id');
```

**Problem:** This prevents any refunds/rebookings where same ticket needs new agent booking record.

---

### 12. **AgentBooking Model Issues** ⚠️ **LOW**

**Location:** `/app/Models/AgentBooking.php`

#### Issue 12a: Wrong payment status value (Line 148)

```php
'payment_status' => 'paid', // Commission is automatically paid via ticket pricing
```

**Should be:** `'pending'` initially, then marked as `'paid'` when admin pays out

#### Issue 12b: createFromBooking not used

The static method exists but `BookingController` doesn't use it

---

## Architecture Issues

### 1. **Dual Booking Record Pattern** ⚠️ **DESIGN ISSUE**

**Current Design:**

- Agent booking creates `BookedTicket` record (main ticket)
- Agent booking also creates `AgentBooking` record (commission tracking)

**Problems:**

- Potential data inconsistency between two tables
- No transactional integrity enforced
- Update/cancel operations need to update both tables

**Recommendation:** Use database transactions and ensure cascading updates

---

### 2. **Commission Payment Model Unclear** ⚠️ **DESIGN ISSUE**

**Questions:**

1. When does agent pay base amount?
2. When does customer pay total amount?
3. When does agent receive commission?
4. How are refunds handled?

**Current code assumes:** Agent pays base, customer pays total, commission earned immediately
**Missing:** Commission payout/settlement system

---

### 3. **Agent Status Workflow** ⚠️ **DESIGN ISSUE**

**States:** pending → active → suspended

**Missing:**

- Rejection state?
- What happens to bookings when agent suspended?
- Can suspended agent be reactivated?
- Email notifications for status changes?

---

## Missing Features

### High Priority

1. ❌ Agent admin CRUD operations
2. ❌ Agent status verification workflow
3. ❌ Complete agent booking flow with commission
4. ❌ Agent payout management
5. ❌ Admin agent management views

### Medium Priority

6. ❌ Agent profile update functionality
7. ❌ Agent document upload/verification
8. ❌ Agent booking cancellation with commission reversal
9. ❌ Agent performance analytics
10. ❌ Commission configuration UI for admin

### Low Priority

11. ❌ Agent dashboard charts/graphs
12. ❌ Agent referral system
13. ❌ Agent tier levels (bronze/silver/gold)
14. ❌ Agent training materials
15. ❌ Agent support ticket system

---

## Recommended Fixes Priority

### CRITICAL (Fix Immediately)

1. Add agent provider to `config/auth.php`
2. Fix `AgentCommissionCalculator::calculate()` signature
3. Fix `BookingController` database field mismatches
4. Implement admin agent CRUD operations

### HIGH (Fix Soon)

5. Complete `BookingController::create()` with all required fields
6. Implement agent middleware for status checking
7. Fix dashboard statistics queries
8. Complete agent booking views

### MEDIUM (Fix When Possible)

9. Implement profile update functionality
10. Add agent booking cancellation
11. Clarify payment/commission flow
12. Add proper error handling and validation

---

## Testing Checklist

### Agent Authentication

- [ ] Agent can register
- [ ] Agent receives pending status
- [ ] Agent can login with pending status
- [ ] Agent sees warning message
- [ ] Agent cannot book when pending
- [ ] Admin can verify agent
- [ ] Agent status changes to active
- [ ] Agent can logout

### Agent Booking Flow

- [ ] Agent can search buses
- [ ] Agent sees search results
- [ ] Agent sees schedules
- [ ] Agent can select seats
- [ ] Agent sees commission calculation
- [ ] Agent can book tickets
- [ ] `booked_tickets` record created correctly
- [ ] `agent_bookings` record created correctly
- [ ] Agent statistics updated
- [ ] Agent can view booking
- [ ] Agent can print ticket
- [ ] Agent can cancel booking
- [ ] Commission properly reversed

### Admin Management

- [ ] Admin can list agents
- [ ] Admin can view agent details
- [ ] Admin can verify agent
- [ ] Admin can suspend agent
- [ ] Admin can view agent bookings
- [ ] Admin can view agent earnings
- [ ] Admin can configure commission rates
- [ ] Admin can process agent payouts

---

## Files Requiring Immediate Attention

### Must Fix

1. `/config/auth.php` - Add agent provider
2. `/app/Services/AgentCommissionCalculator.php` - Fix method signature
3. `/app/Http/Controllers/Agent/BookingController.php` - Fix all booking logic
4. `/app/Http/Controllers/Admin/AgentController.php` - Implement all methods

### Should Fix

5. `/app/Http/Controllers/Agent/DashboardController.php` - Fix statistics
6. `/app/Http/Controllers/Agent/ProfileController.php` - Implement updates
7. `/app/Models/AgentBooking.php` - Fix payment status

### Create Missing

8. Admin agent management views (index, show, edit, etc.)
9. Agent middleware for status checking
10. Agent booking complete flow views

---

## Summary

The agent system is **30-40% complete**. Critical infrastructure exists (models, migrations, authentication) but core functionality is missing or broken. The system cannot be used in production without fixing the critical issues.

**Estimated effort to complete:** 3-5 days for core functionality

**Priority order:**

1. Fix authentication and configuration (1 day)
2. Implement admin agent management (1 day)
3. Fix and complete booking flow (2 days)
4. Testing and refinement (1 day)
