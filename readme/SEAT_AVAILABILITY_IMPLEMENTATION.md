# Seat Availability System Implementation

## Overview
This document describes the new seat availability system for operator buses that calculates availability dynamically per schedule/date/route segment, maintaining exact compatibility with third-party API response structure.

## Implementation Date
November 2025

## Key Changes

### 1. New Service: `SeatAvailabilityService`
**Location:** `core/app/Services/SeatAvailabilityService.php`

**Purpose:** Single source of truth for seat availability calculation.

**Key Features:**
- Calculates availability per schedule/date/route segment
- Handles route segment overlap logic (e.g., Patna->Delhi vs Patna->Intermediate)
- Returns booked seats for specific context
- Caches results for performance (5-minute TTL)
- Invalidates cache on booking/cancellation

**Key Methods:**
- `getBookedSeats()` - Returns array of booked seat names
- `getAvailableSeatsCount()` - Returns count of available seats
- `invalidateCache()` - Invalidates cache for specific bus/schedule/date
- `segmentsOverlap()` - Checks if two route segments overlap

### 2. Enhanced `ApiTicketController@handleOperatorBusSeatLayout`
**Location:** `core/app/Http/Controllers/API/ApiTicketController.php`

**Changes:**
- Extracts `bus_id` and `schedule_id` from `ResultIndex` (format: `OP_{bus_id}_{schedule_id}`)
- Gets date from search token cache/request/session
- Uses `SeatAvailabilityService` to get booked seats
- Modifies HTML on-the-fly: `nseat→bseat`, `hseat→bhseat`, `vseat→bvseat`
- Builds `SeatLayout.SeatDetails` structure matching third-party API exactly
- Returns response in **EXACT** same structure as third-party API:
  ```json
  {
    "UserIp": "...",
    "SearchTokenId": "...",
    "Error": { "ErrorCode": 0, "ErrorMessage": "" },
    "Result": {
      "AvailableSeats": "41",
      "HTMLLayout": "...",
      "SeatLayout": {
        "NoOfColumns": 1,
        "NoOfRows": 7,
        "SeatDetails": [...]
      }
    }
  }
  ```

### 3. Cache Invalidation
**Location:** `core/app/Services/BookingService.php`

**Changes:**
- Added cache invalidation in `updateTicketWithBookingDetails()`
- Invalidates seat availability cache when booking is confirmed
- Ensures real-time seat availability updates

### 4. Disabled Listener
**Location:** `core/app/Listeners/UpdateSeatLayoutOnBooking.php`

**Status:** Disabled (not registered in `EventServiceProvider`)

**Reason:** The old approach of modifying HTML layout in database was incorrect because:
- Seat availability is dynamic per schedule and date
- Route segments can overlap
- A single HTML layout cannot represent all possible booking states

## Architecture

### Data Flow
1. **Request:** User requests seat layout via `/api/show-seats` with `SearchTokenId` and `ResultIndex`
2. **Extract Context:** Extract `bus_id`, `schedule_id`, and `date_of_journey`
3. **Calculate Availability:** `SeatAvailabilityService` queries `BookedTicket` for this bus/schedule/date
4. **Route Segment Overlap:** For each booking, check if its route segment overlaps with requested segment
5. **Modify HTML:** Dynamically change seat classes in HTML (nseat→bseat, etc.)
6. **Build Structure:** Build `SeatLayout.SeatDetails` array with `SeatStatus: true/false`
7. **Return Response:** Return exact structure matching third-party API

### Route Segment Overlap Logic
A seat is considered booked if ANY booking overlaps with the requested segment:

**Example:**
- Request: Patna (index 1) → Intermediate (index 3)
- Booking 1: Patna (index 1) → Delhi (index 5) → **OVERLAP** (both start at Patna)
- Booking 2: Intermediate (index 3) → Delhi (index 5) → **OVERLAP** (request ends where booking starts)
- Booking 3: Other City (index 0) → Patna (index 1) → **NO OVERLAP** (request starts where booking ends)

## Key Principles

1. **Single Source of Truth:** `SeatAvailabilityService` is the only place that calculates availability
2. **Dynamic Calculation:** Availability is calculated on-the-fly, not stored in database
3. **Exact Structure Match:** Response matches third-party API structure exactly
4. **Base Layout Immutable:** `SeatLayout.html_layout` in database is never modified (only contains nseat/hseat/vseat)
5. **Real-time Updates:** Cache is invalidated on booking/cancellation

## Files Modified

1. `core/app/Services/SeatAvailabilityService.php` - **NEW**
2. `core/app/Http/Controllers/API/ApiTicketController.php` - **MODIFIED**
3. `core/app/Services/BookingService.php` - **MODIFIED**
4. `core/app/Listeners/UpdateSeatLayoutOnBooking.php` - **DOCUMENTED AS DISABLED**

## Future Enhancements

1. **Dynamic Pricing:** Same seat can have different prices per schedule/date/route segment
2. **Scheduled Sync:** Background job to sync seat availability periodically
3. **Optimization:** Further optimize queries for large-scale operations

## Testing Checklist

- [ ] Test seat availability for operator bus with no bookings
- [ ] Test seat availability with overlapping route segments
- [ ] Test seat availability for intermediate stops (seat becomes vacant after drop)
- [ ] Test cache invalidation on booking
- [ ] Test response structure matches third-party API exactly
- [ ] Test with multiple schedules for same bus
- [ ] Test with bookings across different dates
- [ ] Test HTML modification (nseat→bseat, hseat→bhseat, vseat→bvseat)

## Rollback Instructions

If you need to rollback:

1. Revert changes in `ApiTicketController@handleOperatorBusSeatLayout`
2. Remove `SeatAvailabilityService`
3. Re-enable `UpdateSeatLayoutOnBooking` listener in `EventServiceProvider`
4. Restore old seat layout update logic

## Notes

- Third-party buses are NOT affected (they use external API)
- Only operator buses use this new system
- The system respects the exact data structure from third-party API
- Route segment overlap logic ensures no double-booking

