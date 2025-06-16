<?php

use App\Http\Controllers\API\ApiTicketController;
use App\Http\Controllers\API\ManageTripController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

// Process begins by sending otp
Route::post('/send-otp', [UserController::class, 'sendOTP'])->name('sendOTP');
// User is then verified
Route::post('/verify-otp', [UserController::class, 'verifyOtp'])->name('verifyOtp');
// During Search  Cities are auto filled
Route::get('/autocomplete-city', [ApiTicketController::class, 'autocompleteCity']); //autocomplete city in origin and destination

//1. search a bus
Route::get('/bus/search', [ApiTicketController::class, 'ticketSearch']);
//2. show seat layout
Route::get('/bus/show-seats', [ApiTicketController::class, 'showSeat']);

// 3. Get Available Boarding and dropping points
Route::get('/counters', [ApiTicketController::class, 'getCounters']);

//4. block seat
Route::get('/block-seat', [ApiTicketController::class, 'blockSeatApi']);

// Get Payment Confirmation
Route::get('/confirm-payment', [ApiTicketController::class, 'confirmPayment']);

// book seat
Route::get('/bus/block-ticket/{id}', [ApiTicketController::class, 'bookTicket']);




//manage trip
Route::name('trip.')->prefix('manage')->group(function () {
    //route
    Route::get('route', [ManageTripController::class, 'routeList'])->name('route');

    //schedule
    Route::get('schedule', [ManageTripController::class, 'schedules'])->name('schedule');

    //ticket price
    Route::get('ticket-price', [ManageTripController::class, 'ticketPriceList'])->name('ticket_price');
    Route::get('route-data', 'VehicleTicketController@getRouteData')->name('ticket.get_route_data');
    Route::get('ticket-price/check_price', 'VehicleTicketController@checkTicketPrice')->name('ticket.check_price');
    //trip
    Route::get('trip', 'ManageTripController@trips')->name('list');

    //assigned vehicle
    Route::get('assigned-vehicle', 'ManageTripController@assignedVehicleLists')->name('vehicle.assign');
});
