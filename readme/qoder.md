# Bus Booking System - Development Log

---

# Operator Management System - Full Analysis

## Date: November 26, 2025

---

## SYSTEM OVERVIEW

Comprehensive multi-tenant bus operator management system enabling:

- Fleet management (buses, routes)
- Booking and seat blocking
- Revenue tracking and payouts
- Staff and crew management
- Attendance tracking

---

## DATABASE MODELS

### 1. Operator Model

**File**: `core/app/Models/Operator.php`

**Key Features**:

- Multi-step registration (Basic → Company → Documents → Bank)
- Document management (PAN, Aadhaar, License, Cheque)
- Completion tracking flags
- Auto-activation when all details completed

**Relationships**:

- `routes()` - hasMany OperatorRoute ✅
- `buses()` - hasMany OperatorBus ✅
- `staff()` - hasMany Staff ✅
- `bookings()` - hasMany OperatorBooking ❌ MISSING
- `payouts()` - hasMany OperatorPayout ❌ MISSING

### 2. OperatorBus Model

**File**: `core/app/Models/OperatorBus.php`

**Features**:

- Bus details (number, type, service_name, travel_name)
- Pricing (base, published, offered, agent_commission)
- Tax calculations (CGST, SGST, IGST)
- Documents (insurance, permit, fitness)
- Amenities and features
- Seat layout integration

### 3. OperatorRoute Model

**File**: `core/app/Models/OperatorRoute.php`

**Features**:

- City-based origin/destination
- Distance and fare tracking
- Boarding/dropping points
- Bus assignments
- Schedule integration

### 4. OperatorBooking Model

**File**: `core/app/Models/OperatorBooking.php`

**Features**:

- Seat blocking for operators
- Date range support
- Integration with BookedTicket
- PNR generation

### 5. OperatorPayout Model

**File**: `core/app/Models/OperatorPayout.php`

**Features**:

- Revenue tracking
- Fee deductions (platform, gateway, TDS)
- Payment status tracking
- Period-based payouts

---

## CONTROLLERS

### Admin Side

**File**: `core/app/Http/Controllers/Admin/OperatorController.php`

**Methods**:

- `index()` - List all operators
- `create()` - Show registration form
- `store()` - Create operator + send welcome email
- `show()` - View operator details
- `edit()` - Edit operator
- `update()` - Update operator
- `destroy()` - Delete operator

### Operator Side Controllers

1. **OperatorController** (`Operator/OperatorController.php`)

   - `dashboard()` - Statistics and overview
   - `profile()` / `updateProfile()` - Profile management
   - `changePassword()` / `updatePassword()` - Password management

2. **RouteController** (`Operator/RouteController.php`)

   - Full CRUD for routes
   - `toggleStatus()` - Activate/deactivate routes

3. **BusController** (`Operator/BusController.php`)

   - Full CRUD for buses
   - `toggleStatus()` - Activate/deactivate buses
   - Cancellation policy management

4. **OperatorBookingController** (`Operator/OperatorBookingController.php`)

   - Seat blocking functionality
   - Date range blocking
   - `getSeatLayout()` - Get seat availability
   - `getAvailableSeats()` - Check seat status

5. **SeatLayoutController** (`Operator/SeatLayoutController.php`)

   - Seat layout CRUD
   - Visual editor integration

6. **StaffController** / **CrewAssignmentController** / **AttendanceController**

   - Staff management
   - Crew assignments to buses
   - Attendance tracking

7. **RevenueController** (`Operator/RevenueController.php`)
   - Revenue reports
   - Payout tracking

---

## ROUTES ANALYSIS

### Admin Routes

**Prefix**: `/admin/manage/operators`

```php
Route::resource('manage/operators', 'OperatorController')->names([
    'index' => 'admin.fleet.operators.index',
    'create' => 'admin.fleet.operators.create',
    'store' => 'admin.fleet.operators.store',
    'show' => 'admin.fleet.operators.show',
    'edit' => 'admin.fleet.operators.edit',
    'update' => 'admin.fleet.operators.update',
    'destroy' => 'admin.fleet.operators.destroy'
]);
```

### Operator Routes

**Prefix**: `/operator`
**Guard**: `operator` middleware

```php
Route::middleware('operator')->group(function () {
    // Dashboard & Profile
    Route::get('dashboard', 'Operator\OperatorController@dashboard')->name('dashboard');
    Route::get('profile', 'Operator\OperatorController@profile')->name('profile');
    Route::get('change-password', 'Operator\OperatorController@changePassword')->name('change-password');

    // Route Management
    Route::resource('routes', 'Operator\RouteController')->names([
        'index' => 'routes.index',  // ❌ BUG: Should be 'operator.routes.index'
        'create' => 'routes.create',
        // ...
    ]);

    // Bus Management
    Route::resource('buses', 'Operator\BusController');

    // Seat Layouts
    Route::resource('buses/{bus}/seat-layouts', 'Operator\SeatLayoutController');

    // Staff Management
    Route::resource('staff', 'Operator\StaffController');

    // Crew Assignments
    Route::resource('crew', 'Operator\CrewAssignmentController');

    // Attendance
    Route::resource('attendance', 'Operator\AttendanceController');

    // Schedules
    Route::resource('schedules', 'Operator\ScheduleController');
});
```

---

## 🐛 BUGS IDENTIFIED

### BUG #1: Route Namespace Inconsistency

**Severity**: 🔴 HIGH  
**File**: `core/routes/web.php` Lines 1040-1048

**Problem**:

```php
Route::resource('routes', 'Operator\RouteController')->names([
    'index' => 'routes.index',     // ❌ Missing 'operator.' prefix
    'create' => 'routes.create',   // ❌ Missing 'operator.' prefix
    'store' => 'routes.store',     // ❌ Missing 'operator.' prefix
    'show' => 'routes.show',       // ❌ Missing 'operator.' prefix
    'edit' => 'routes.edit',       // ❌ Missing 'operator.' prefix
    'update' => 'routes.update',   // ❌ Missing 'operator.' prefix
    'destroy' => 'routes.destroy', // ❌ Missing 'operator.' prefix
]);
```

**Impact**: Views calling `route('operator.routes.index')` will fail.

**Fix**:

```php
'index' => 'operator.routes.index',
'create' => 'operator.routes.create',
// etc...
```

---

### BUG #2: Hardcoded Operator ID

**Severity**: 🔴 CRITICAL SECURITY  
**File**: `core/app/Http/Controllers/Operator/OperatorBookingController.php` Line 461

**Problem**:

```php
public function getSeatLayout(Request $request)
{
    // Skip authentication for testing - use operator ID 41 directly
    $operatorId = 41; // ❌ HARDCODED! Sutra Seva operator
```

**Impact**:

- Any operator can access another operator's data
- Security vulnerability
- Testing code left in production

**Fix**:

```php
public function getSeatLayout(Request $request)
{
    $operator = auth('operator')->user();
    $operatorId = $operator->id;
```

---

### BUG #3: Wrong Login URL in Welcome Email

**Severity**: 🟡 MEDIUM  
**File**: `core/app/Http/Controllers/Admin/OperatorController.php` Line 136

**Problem**:

```php
Mail::to($operator->email)->send(new OperatorWelcomeMail([
    'name' => $operator->name,
    'email' => $operator->email,
    'password' => $validated['password'],
    'login_url' => url('/admin/login'),  // ❌ Wrong! This is admin login
]));
```

**Impact**: New operators can't login because email has wrong URL.

**Fix**:

```php
'login_url' => route('operator.login'),  // ✅ Correct operator login
```

---

### BUG #4: Phone vs Mobile Field Inconsistency

**Severity**: 🟡 MEDIUM  
**Files**:

- `OperatorBookingController.php` Lines 188, 245
- `Operator.php` Model

**Problem**:

```php
// In OperatorBookingController:
'passenger_phones' => json_encode([$operatorBooking->operator->phone]),  // ❌ 'phone' doesn't exist

// In Operator model fillable:
'mobile',  // ✅ Correct field name
```

**Fix**: Use `$operatorBooking->operator->mobile` everywhere.

---

### BUG #5: Missing Model Relationships

**Severity**: 🟢 LOW  
**File**: `core/app/Models/Operator.php`

**Missing**:

```php
public function bookings()
{
    return $this->hasMany(OperatorBooking::class);
}

public function payouts()
{
    return $this->hasMany(OperatorPayout::class);
}
```

---

### BUG #6: Same Route Names for Buses and Seat Layouts

**Severity**: 🟡 MEDIUM  
**File**: `core/routes/web.php` Lines 1055-1092

**Problem**:

```php
// Buses use 'operator.buses.*'
Route::resource('buses', 'Operator\BusController')->names([
    'index' => 'buses.index',  // ❌ Should be 'operator.buses.index'
]);

// Seat layouts also missing prefix
Route::resource('buses/{bus}/seat-layouts', 'Operator\SeatLayoutController')->names([
    'index' => 'seat-layouts.index',  // ❌ Should be 'operator.buses.seat-layouts.index'
]);
```

---

## ⚠️ MISSING IMPLEMENTATIONS

### 1. Booking Routes Not Found

**Expected but Missing**:

```php
Route::resource('bookings', 'Operator\OperatorBookingController');
```

**Controller exists** but routes not registered in web.php.

### 2. Revenue Routes Not Found

**Expected but Missing**:

```php
Route::get('revenue', 'Operator\RevenueController@index');
Route::get('revenue/reports', 'Operator\RevenueController@reports');
```

**Controller exists** but routes not registered.

---

## 🛠️ RECOMMENDED FIXES

### Priority 1 (Critical)

1. ✅ Remove hardcoded operator ID (BUG #2)
2. ✅ Fix all route namespace prefixes (BUG #1, #6)
3. ✅ Fix operator login URL in email (BUG #3)

### Priority 2 (High)

4. ✅ Fix phone/mobile field inconsistency (BUG #4)
5. ✅ Add missing booking routes
6. ✅ Add missing revenue routes

### Priority 3 (Medium)

7. ✅ Add missing model relationships (BUG #5)
8. ✅ Test complete operator workflow
9. ✅ Add inline documentation

---

## 📝 VIEWS STRUCTURE

```
core/resources/views/
├── operators/              # Admin manages operators
│   ├── index.blade.php      # List all operators
│   ├── create.blade.php     # Create operator (multi-step form)
│   ├── edit.blade.php       # Edit operator
│   └── show.blade.php       # View operator details
│
├── operator/               # Operator panel
│   ├── layouts/
│   │   └── app.blade.php    # Main layout
│   ├── partials/
│   │   ├── sidenav.blade.php
│   │   ├── topnav.blade.php
│   │   └── breadcrumb.blade.php
│   ├── dashboard.blade.php
│   ├── profile.blade.php
│   ├── change-password.blade.php
│   ├── routes/
│   │   ├── index.blade.php   # List routes
│   │   ├── create.blade.php  # Create route
│   │   ├── edit.blade.php    # Edit route
│   │   └── show.blade.php    # View route
│   ├── buses/
│   ├── bookings/
│   ├── seat-layouts/
│   ├── staff/
│   ├── crew/
│   ├── attendance/
│   ├── schedules/
│   └── revenue/
```

---

## ✅ WORKING FEATURES

1. ✅ Multi-guard authentication (admin vs operator)
2. ✅ Multi-step operator registration
3. ✅ Document upload and management
4. ✅ Welcome email with credentials
5. ✅ Route CRUD operations (views exist)
6. ✅ Bus fleet management
7. ✅ Seat layout editor (drag & drop)
8. ✅ Operator seat blocking system
9. ✅ Staff and crew management
10. ✅ Attendance tracking
11. ✅ Revenue and payout tracking

---

## 📊 CODE QUALITY

### Good Practices

- ✅ Separation of concerns (Admin vs Operator)
- ✅ Guard-based authentication
- ✅ Eloquent relationships
- ✅ Scope methods for queries
- ✅ Comprehensive validation
- ✅ Email notifications

### Needs Improvement

- ❌ Hardcoded values (operator ID)
- ❌ Inconsistent naming (phone/mobile)
- ❌ Missing route prefixes
- ❌ Incomplete relationships
- ❌ Limited inline documentation

---

# Seat Layout Editor - Recent Improvements

## Date: November 26, 2025

### Issue Fixed: Drag & Drop Not Working

**Problem**: Initial drag and drop functionality was not working because event listeners were attached to grid elements before the bus layout structure was created. When `createBusLayout()` replaced the DOM elements, the event listeners were orphaned.

**Solution**: Moved `setupDragAndDrop()` to execute AFTER `createBusLayout()` in the initialization sequence and whenever layout configuration changes.

**Files Modified**:

- `/assets/admin/js/seat-layout-editor.js`
  - Updated `init()` method to call `setupDragAndDrop()` after `createBusLayout()`
  - Updated `setSeatLayout()`, `setColumnsPerRow()`, and `setDeckType()` methods to re-setup drag and drop after recreating layouts

---

### Feature Added: Seat Repositioning

**Functionality**: Users can now drag and drop existing seats to new positions within the bus layout.

**Implementation**:

- Made seat elements draggable with `draggable="true"` attribute
- Added dragstart/dragend event handlers to seat elements
- Enhanced `moveSeatToPosition()` method to:
  - Support cross-deck moves (lower to upper, upper to lower)
  - Properly clean up old position and data
  - Regenerate seat IDs when moving between decks
  - Validate space availability at target position
  - Update layout data correctly

**Files Modified**:

- `/assets/admin/js/seat-layout-editor.js`
  - Updated `createSeatElement()` - Added drag event listeners
  - Enhanced `moveSeatToPosition()` - Complete rewrite for robust repositioning

---

### Feature Added: Seat Deletion by Drag & Drop

**Functionality**: Users can delete seats by dragging them outside the bus layout area.

**Implementation**:

- Added document-level dragover/drop event listeners
- Visual feedback system:
  - Seat shows 0.3 opacity + grayscale filter when outside layout (will be deleted)
  - Seat returns to 0.5 opacity when dragged back over layout
- Created new `deleteSeat()` method for programmatic deletion without confirmation
- Refactored `deleteSelectedSeat()` to use the new `deleteSeat()` method

**Files Modified**:

- `/assets/admin/js/seat-layout-editor.js`
  - Updated `setupDragAndDrop()` - Added document-level drag handlers
  - Created `deleteSeat()` method
  - Refactored `deleteSelectedSeat()` method

---

### Feature Added: Visual Drop Indicators

**Functionality**: Green highlighting and `+` button appear when dragging a seat over valid drop positions, making it clear where seats can be placed.

**Implementation**:

- Created `highlightDropPosition()` method that:

  - Shows green background (rgba(0, 255, 0, 0.3)) for valid positions
  - Shows red background (rgba(255, 0, 0, 0.3)) for invalid positions
  - Displays large green `+` symbol on valid drop target
  - Accounts for multi-cell seats (horizontal/vertical sleepers)
  - Ignores the seat being dragged when checking validity

- Created `clearDropHighlights()` method that:

  - Removes all highlight classes and styles
  - Restores original cell appearance
  - Restores `+` symbols for empty cells

- Enhanced `canPlaceSeat()` method with `ignoreSeat` parameter:
  - Allows checking if a position is valid while ignoring the seat being moved
  - Properly handles repositioning to the same or adjacent positions

**Integration**:

- Drop highlighting triggers on `dragover` event
- Highlights clear on `dragleave`, `drop`, and `dragend` events
- Works seamlessly with both new seat placement and repositioning

**Files Modified**:

- `/assets/admin/js/seat-layout-editor.js`
  - Created `highlightDropPosition()` method
  - Created `clearDropHighlights()` method
  - Enhanced `canPlaceSeat()` with optional `ignoreSeat` parameter
  - Updated `setupDragAndDrop()` to call highlighting methods
  - Updated `createSeatElement()` dragend handler to clear highlights

---

## Technical Summary

All changes were made to a single file: `/assets/admin/js/seat-layout-editor.js`

**New Methods**:

1. `deleteSeat(seatElement)` - Delete a seat without confirmation
2. `highlightDropPosition(grid, x, y, draggingSeat)` - Show visual drop feedback
3. `clearDropHighlights(grid)` - Remove visual drop feedback

**Enhanced Methods**:

1. `init()` - Reordered initialization sequence
2. `setupDragAndDrop()` - Added document-level drag handlers and highlighting calls
3. `moveSeatToPosition()` - Complete rewrite for cross-deck support
4. `canPlaceSeat()` - Added ignoreSeat parameter
5. `createSeatElement()` - Added drag event listeners and highlight clearing
6. `deleteSelectedSeat()` - Refactored to use deleteSeat()
7. `setSeatLayout()`, `setColumnsPerRow()`, `setDeckType()` - Added drag and drop re-setup

**User Experience Improvements**:

- ✅ Drag and drop works reliably
- ✅ Seats can be repositioned anywhere on the layout
- ✅ Seats can be moved between upper and lower decks
- ✅ Seats can be deleted by dragging outside layout
- ✅ Clear visual feedback shows valid/invalid drop zones
- ✅ Green `+` button indicates safe drop areas
- ✅ Red highlighting indicates invalid positions
- ✅ Grayscale effect shows seat will be deleted
