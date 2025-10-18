<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\TicketPrice;
use App\Models\VehicleRoute;
use Illuminate\Http\Request;

class ManageTripController extends Controller
{
 //
 public function routeList(Request $request)
 {
  try {
   // Fetch routes based on start_from and end_to
   $routes = VehicleRoute::where('start_from', $request->start_from)
    ->where('end_to', $request->end_to)
    ->orderBy('id', 'desc')
    ->get();
   return response()->json($routes);
  } catch (\Exception $e) {
   return response()->json(['error' => $e->getMessage()]);
  }
 }

 public function schedules()
 {
  try {
   $schedules = Schedule::orderBy('id', 'desc')->get();
   return response()->json($schedules);
  } catch (\Exception $e) {
   return response()->json(['error' => $e->getMessage()]);
  }
 }

 public function ticketPriceList(Request $request)
 {
  try {
   $ticketPrices = TicketPrice::where('vehicle_route_id', $request->route_id)
    ->orderBy('id', 'desc')
    ->get();
   return response()->json($ticketPrices);
  } catch (\Exception $e) {
   return response()->json(['error' => $e->getMessage()]);
  }
 }
}
