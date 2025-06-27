<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ApiTicketController;
use App\Http\Controllers\API\ManageTripController;
use App\Http\Controllers\Admin\VehicleTicketController;

// Auth-related
Route::post('/send-otp', [UserController::class, 'sendOTP'])->name('sendOTP');
Route::post('/verify-otp', [UserController::class, 'verifyOtp'])->name('verifyOtp');

// Autocomplete
Route::get('/autocomplete-city', [ApiTicketController::class, 'autocompleteCity']);

// Bus-related
Route::prefix('bus')->group(function () {
    Route::get('search', [ApiTicketController::class, 'ticketSearch']);
    Route::get('show-seats', [ApiTicketController::class, 'showSeat']);
    Route::get('block-ticket/{id}', [ApiTicketController::class, 'bookTicket']);
});

// Boarding/drop points
Route::get('/counters', [ApiTicketController::class, 'getCounters']);

// Seat blocking & payment
Route::post('/block-seat', [ApiTicketController::class, 'blockSeatApi']);
Route::post('/confirm-payment', [ApiTicketController::class, 'confirmPayment']);

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
