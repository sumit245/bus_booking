<?php

use App\Http\Controllers\API\ApiTicketController;
use App\Http\Controllers\API\ManageTripController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/send-otp', [UserController::class, 'sendOTP'])->name('sendOTP');
Route::post('/verify-otp', [UserController::class, 'verifyOtp'])->name('verifyOtp');
Route::get('/bus/search', [ApiTicketController::class, 'ticketSearch']);
Route::get('/bus/show-seats/{id}', [ApiTicketController::class, 'showSeat']);
Route::get('/bus/book-ticket/{id}', [ApiTicketController::class, 'bookTicket']);
Route::get('/confirm-payment', [ApiTicketController::class, 'confirmPayment']);
Route::get('/counters', [ApiTicketController::class, 'getCounters']);

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
