<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ApiTicketController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\API\ManageTripController;
use App\Http\Controllers\Admin\VehicleTicketController;

// Auth-related
Route::post('/send-otp', [UserController::class, 'sendOTP'])->name('user.send-otp');
Route::post('/verify-otp', [UserController::class, 'verifyOtp'])->name('user.verify-otp');
Route::post('/users/get-my-tickets', [UserController::class, 'userHistoryByPhone']);
Route::post('/users/cancel-ticket', action: [UserController::class, 'cancelTicketApi']);

// Autocomplete
Route::get('/autocomplete-city', [ApiTicketController::class, 'autocompleteCity']);

// Bus-related
Route::prefix('bus')->group(function () {
    Route::get('search', [ApiTicketController::class, 'ticketSearch']);
    Route::get('show-seats', action: [ApiTicketController::class, 'showSeat']);
    Route::get('/get-counters', [ApiTicketController::class, 'getCounters']);
    // Route::get('block-ticket/{id}', [ApiTicketController::class, 'bookTicket']); Obsolete Code
    Route::post('/block-seat', [ApiTicketController::class, 'blockSeatApi']);
    // Seat blocking & payment
    Route::post('/confirm-payment', [ApiTicketController::class, 'confirmPayment']);
    Route::post('/cancellation-policy', [ApiTicketController::class,'getCancellationPolicy']);
});

// Boarding/drop points



// Coupon routes
Route::get('/coupons', [CouponController::class, 'getActiveCouponsApi']);
Route::post('/apply-coupon', [CouponController::class, 'applyCouponApi']);

// Trip management
Route::name('trip.')->prefix('manage')->group(function () {
    Route::get('route', [ManageTripController::class, 'routeList'])->name('route');
    Route::get('schedule', [ManageTripController::class, 'schedules'])->name('schedule');
    Route::get('ticket-price', [ManageTripController::class, 'ticketPriceList'])->name('ticket_price');
    Route::get('route-data', [VehicleTicketController::class, 'getRouteData'])->name('ticket.get_route_data');
    Route::get('ticket-price/check_price', [VehicleTicketController::class, 'checkTicketPrice'])->name('ticket.check_price');
    Route::get('trip', [ManageTripController::class, 'trips'])->name('list');
    Route::get('assigned-vehicle', [ManageTripController::class, 'assignedVehicleLists'])->name('vehicle.assign');
});

Route::get('/buses/combined-search', [ApiTicketController::class, 'getCombinedBuses']);
