<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ApiTicketController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\API\ManageTripController;
use App\Http\Controllers\Admin\VehicleTicketController;
use App\Http\Controllers\API\ReferralController;
use App\Http\Controllers\API\NotificationController;

// Auth-related
Route::post('/send-otp', [UserController::class, 'sendOTP'])->name('user.send-otp');
Route::post('/verify-otp', [UserController::class, 'verifyOtp'])->name('user.verify-otp');
Route::post('/users/get-my-tickets', [UserController::class, 'userHistoryByPhone']);
Route::post('/users/get-ticket-by-booking-id', [UserController::class, 'getTicketByBookingId']);
Route::post('/users/cancel-ticket', [ApiTicketController::class, 'cancelTicketApi']);
Route::get('/users/print-ticket/{id}', [\App\Http\Controllers\TicketController::class, 'printTicket'])->name('api.print.ticket');

// FCM Token Management
Route::post('/users/fcm-token', [UserController::class, 'storeFcmToken']);
Route::delete('/users/fcm-token', [UserController::class, 'deleteFcmToken']);

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
    Route::post('/cancellation-policy', [ApiTicketController::class, 'getCancellationPolicy']);
    // Get pricing configuration for mobile app
    Route::get('/pricing-config', [ApiTicketController::class, 'getPricingConfig']);
});

// Boarding/drop points



// Coupon routes
Route::get('/coupons', [CouponController::class, 'getActiveCouponsApi']);
Route::post('/coupons/validate', [CouponController::class, 'validateCouponApi']);
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

// Referral routes
Route::prefix('referral')->name('referral.')->group(function () {
    Route::post('/install', [ReferralController::class, 'recordInstall'])->name('install');
    Route::post('/click', [ReferralController::class, 'recordClick'])->name('click');
    Route::post('/signup', [ReferralController::class, 'recordSignup'])->name('signup');
    Route::get('/settings', [ReferralController::class, 'getSettings'])->name('settings');
});

Route::prefix('users')->name('users.')->group(function () {
    Route::get('/referral-data', [ReferralController::class, 'getReferralData'])->name('referral.data');
    Route::get('/referral-stats', [ReferralController::class, 'getReferralStats'])->name('referral.stats');
    Route::get('/referral-history', [ReferralController::class, 'getReferralHistory'])->name('referral.history');
});

// Notification Endpoints (Admin authentication handled in controller - supports Sanctum and admin guard)
Route::prefix('notifications')->group(function () {
    Route::post('/send-release', [NotificationController::class, 'sendReleaseNotification']);
    Route::post('/send-promotional', [NotificationController::class, 'sendPromotionalNotification']);
    Route::post('/send-booking', [NotificationController::class, 'sendBookingNotification']);
    Route::post('/send-general', [NotificationController::class, 'sendGeneralNotification']);
});

