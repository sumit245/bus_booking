# 🔴 CRITICAL BOOKING FLOW BUGS REPORT

**Date:** November 3, 2025  
**Affected:** `ApiTicketController::blockSeatApi()` and `ApiTicketController::confirmPayment()`  
**Scope:** Both Operator Buses (OP_*) and Third-Party Buses

---

## 🐛 **BUGS IDENTIFIED**

### **1. ❌ `total_amount` Always Zero**
**Location:** `BookingService::createPendingTicket()`  
**Issue:** `total_amount` field is NEVER set, always remains `0.00000000`  
**Expected:** `total_amount` should be calculated as: `(seats * unit_price) + GST + service_charges + platform_fee + agent_commission`  
**Current Code:** Line 438 sets `sub_total` but never sets `total_amount`

**Impact:** Critical - Financial calculations are wrong, revenue tracking broken

---

### **2. ❌ Missing Fee Calculations in Backend**
**Location:** `BookingService::createPendingTicket()`  
**Issue:** Backend doesn't calculate GST, service charges, or platform fees  
**Expected:** Backend should calculate all fees using `GeneralSetting` values:
- Service Charge = `base_fare * service_charge_percentage / 100`
- Platform Fee = `(base_fare * platform_fee_percentage / 100) + platform_fee_fixed`
- GST = `(base_fare + service_charge + platform_fee) * gst_percentage / 100`
- Total = `base_fare + service_charge + platform_fee + gst`

**Impact:** Critical - Fees calculated only in frontend JavaScript, backend has no record

---

### **3. ❌ `source_destination` Using Wrong Data**
**Location:** `BookingService::createPendingTicket()` Line 425-428  
**Issue:** Using `OriginCity`/`DestinationCity` from request which might be city names, not IDs  
**Expected:** Should use actual city IDs from `origin_city`/`destination_city` session or request data  
**Current Code:**
```php
$bookedTicket->source_destination = json_encode([
    $requestData['OriginCity'] ?? $requestData['origin_city'] ?? 0,
    $requestData['DestinationCity'] ?? $requestData['destination_city'] ?? 0
]);
```

**Impact:** High - City relationships broken, reporting issues

---

### **4. ❌ `origin_city` and `destination_city` Not Saved Correctly**
**Location:** `BookingService::createPendingTicket()` and `updateAdditionalFields()`  
**Issue:** 
- In `createPendingTicket()`: Not saved at all
- In `updateAdditionalFields()`: Only saved from API response `$result['Origin']`/`$result['Destination']`, which may not exist for operator buses

**Expected:** 
- For operator buses: Get from `OperatorBus::currentRoute` (start_from/end_to cities)
- For third-party buses: Get from API response or cached search data

**Impact:** High - Missing city information, WhatsApp notifications broken

---

### **5. ❌ Redundant Fields: `pickup_point` vs `boarding_point`**
**Location:** `BookingService::createPendingTicket()` Line 440-441  
**Issue:** Both `pickup_point` and `boarding_point` storing same value as `boarding_point_id`  
**Expected:** Remove `pickup_point` (use `boarding_point_id` only) OR use `pickup_point` for Counter relationship, `boarding_point_id` for API reference

**Impact:** Medium - Data redundancy, confusion

---

### **6. ❌ Redundant Fields: `seats` vs `seat_numbers`**
**Location:** `BookingService::createPendingTicket()` Line 435  
**Issue:** `seat_numbers` field exists but never populated, `seats` already stores array of seat names  
**Expected:** Either remove `seat_numbers` OR populate it correctly

**Impact:** Low - Data redundancy only

---

### **7. ❌ Missing Operator Bus Fields**
**Location:** `BookingService::createPendingTicket()`  
**Issue:** For operator buses (OP_*), missing:
- `operator_id` (should be from `OperatorBus::operator_id`)
- `operator_booking_id` (can be same as `operator_pnr` or separate)
- `bus_id` (should be operator bus ID from ResultIndex)
- `route_id` (should be from `OperatorBus::currentRoute::id`)
- `schedule_id` (should be from cached bus data or schedule)

**Expected:** Only populate these for operator buses, leave null for third-party buses

**Impact:** High - Operator bus relationships broken, reporting incomplete

---

### **8. ❌ `api_response` Saving Incorrect Origin/Destination**
**Location:** `BookingService::createPendingTicket()` Line 491  
**Issue:** `api_response` saves entire `$blockResponse` which contains origin/destination as city names in various nested places, not standardized  
**Expected:** Should extract and standardize origin/destination before saving

**Impact:** Medium - Data inconsistency in stored responses

---

### **9. ❌ `date_of_journey` Source Issue**
**Location:** `BookingService::createPendingTicket()` Line 443  
**Issue:** Using `$requestData['DateOfJourney'] ?? $requestData['date_of_journey']` but API might send different format  
**Expected:** Should use date from cached search data or block response

**Impact:** Low - Usually works but inconsistent

---

## 📋 **EXECUTION PLAN**

### **Phase 1: Fix Critical Issues (Total Amount & Fees)**
1. ✅ Add fee calculation method in `BookingService`
2. ✅ Retrieve `GeneralSetting` for fee percentages
3. ✅ Calculate service charge, platform fee, GST in `createPendingTicket()`
4. ✅ Set `total_amount` correctly
5. ✅ Store fee breakdown in ticket (for transparency)

### **Phase 2: Fix City Data Issues**
1. ✅ Fix `source_destination` to use correct city IDs
2. ✅ Fix `origin_city` and `destination_city` for both bus types:
   - Operator buses: Get from `OperatorBus::currentRoute::startFrom`/`endTo`
   - Third-party buses: Get from cached search data or API response
3. ✅ Ensure city names are actual city names, not IDs

### **Phase 3: Fix Operator Bus Specific Fields**
1. ✅ Detect operator bus from ResultIndex
2. ✅ Populate `operator_id`, `bus_id`, `route_id`, `schedule_id` only for operator buses
3. ✅ Set `operator_booking_id` appropriately

### **Phase 4: Clean Up Redundant Fields**
1. ✅ Decide on `pickup_point` vs `boarding_point` - keep one or both for different purposes
2. ✅ Remove or populate `seat_numbers` correctly
3. ✅ Update any references to removed fields

### **Phase 5: Fix API Response Storage**
1. ✅ Standardize `api_response` structure
2. ✅ Ensure origin/destination are consistent

### **Phase 6: Testing**
1. ✅ Test booking for operator bus (OP_1, OP_2, etc.)
2. ✅ Test booking for third-party bus (TB-*)
3. ✅ Verify all fields are populated correctly
4. ✅ Verify `total_amount` calculation matches frontend
5. ✅ Verify city data is correct
6. ✅ Verify operator-specific fields only exist for operator buses

---

## 🔍 **TESTING SCENARIOS**

### **Test 1: Operator Bus Booking (Patna → Delhi)**
- Search for buses from Patna to Delhi
- Find operator bus with ResultIndex starting with "OP_"
- Select seats and complete booking
- **Verify:**
  - `total_amount` = calculated correctly with fees
  - `source_destination` = [Patna City ID, Delhi City ID]
  - `origin_city` = "Patna"
  - `destination_city` = "Delhi"
  - `operator_id` = populated
  - `bus_id` = populated
  - `route_id` = populated
  - `operator_id`, `operator_booking_id`, `agent_id`, `bus_id` = NOT NULL (for operator buses)

### **Test 2: Third-Party Bus Booking (Patna → Delhi)**
- Search for buses from Patna to Delhi
- Find third-party bus with ResultIndex like "TB-*"
- Select seats and complete booking
- **Verify:**
  - `total_amount` = calculated correctly with fees
  - `source_destination` = [Patna City ID, Delhi City ID]
  - `origin_city` = "Patna"
  - `destination_city` = "Delhi"
  - `operator_id`, `operator_booking_id`, `agent_id`, `bus_id` = NULL (for third-party buses)

---

## 📝 **CODE CHANGES SUMMARY**

**Files to Modify:**
1. `core/app/Services/BookingService.php`
   - Add `calculateFees()` method
   - Modify `createPendingTicket()` to calculate fees and set total_amount
   - Fix city data extraction
   - Add operator bus field population
   - Fix redundant fields

**Estimated Changes:**
- ~200 lines of code changes
- ~50 lines of new code (fee calculation)
- ~150 lines of modifications

---

## ⚠️ **RISKS**

1. **Breaking Changes:** Changing field names or removing fields might break existing reports/queries
2. **Fee Calculation:** Must match frontend calculation exactly
3. **City Data:** Need to ensure city IDs are correct from session/cache
4. **Testing:** Need to test both operator and third-party bus flows

---

**Priority:** 🔴 **CRITICAL** - Financial calculations are broken

