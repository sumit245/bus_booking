# Automated Testing for Booking Ownership and Notifications

## Summary

Automated tests have been created to verify booking ownership and notification fixes. The tests are ready but require database migrations to be run.

## Test Files Created

### 1. Unit Tests: `core/tests/Unit/BookingServiceTest.php`

Tests the `BookingService::registerOrLoginUser()` method with 6 test cases:

- ✅ Test Priority 1: Authenticated user ID provided → returns authenticated user
- ✅ Test Priority 2: Web session auth → returns session user  
- ✅ Test Priority 3: No auth → creates/finds user by passenger phone
- ✅ Test authenticated user takes precedence over passenger phone
- ✅ Test invalid authenticated user ID falls back gracefully
- ✅ Test phone number normalization removes country codes

### 2. Feature Tests: `core/tests/Feature/BlockSeatApiTest.php`

Tests the `/api/bus/block-seat` API endpoint with 4 test cases:

- ✅ Test authenticated booking for someone else (User A books for User B)
- ✅ Test authenticated booking for self (same phone)
- ✅ Test guest booking (no token) → falls back to passenger phone
- ✅ Test booking ownership is correctly stored in database

### 3. Notification Tests: `core/tests/Feature/NotificationTest.php`

Tests notification logic with 5 test cases:

- ✅ Test notification sent to passenger phone
- ✅ Test notification sent to booking owner when different phone
- ✅ Test no duplicate notification when owner = passenger
- ✅ Test phone number normalization for notification comparison
- ✅ Test booking owner relationship is accessible

## Running Tests

To run the tests, ensure:

1. **Database is configured** - Set `DB_DATABASE` in `.env` for testing
2. **Migrations are run** - Run `php artisan migrate` or configure test database
3. **Test environment** - Configure test database in `phpunit.xml` if needed

Run all tests:
```bash
php artisan test
```

Run specific test suite:
```bash
php artisan test --filter BookingServiceTest
php artisan test --filter BlockSeatApiTest
php artisan test --filter NotificationTest
```

## What the Tests Verify

1. **Booking Ownership**: 
   - Authenticated user (User A) is correctly set as `booked_tickets.user_id`
   - Passenger phone (User B) is correctly stored as `booked_tickets.passenger_phone`
   - Guest bookings fall back to passenger phone logic

2. **Notification Logic**:
   - Notifications sent to passenger phone
   - Notifications sent to booking owner (if different phone)
   - No duplicate notifications when owner = passenger
   - Phone normalization works correctly

3. **Priority Order**:
   - Authenticated user ID (API token) takes highest priority
   - Web session auth takes second priority
   - Passenger phone (guest booking) is fallback

## Notes

- Tests use `RefreshDatabase` trait to ensure clean state
- Tests use reflection to test private methods (registerOrLoginUser)
- Feature tests require proper API endpoint setup and database migrations
- Notification tests verify logic; actual WhatsApp sending would be mocked in production

