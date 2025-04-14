<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\Counter;
use App\Models\FleetType;
use App\Models\Schedule;
use App\Models\TicketPrice;
use App\Models\Trip;
use App\Models\User;
use App\Models\VehicleRoute;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class ApiTicketController extends Controller
{

 public function ticketSearch(Request $request)
 {
  try {
   //code...
   // Validate required fields
   $validatedData = $request->validate([
    'pickup'          => 'required|integer|exists:counters,id',
    'destination'     => 'required|integer|exists:counters,id|different:pickup',
    'date_of_journey' => 'required|date|after_or_equal:today',
   ]);

   $pickup        = $validatedData['pickup'];
   $destination   = $validatedData['destination'];
   $dateOfJourney = Carbon::parse($validatedData['date_of_journey'])->format('Y-m-d');
   $currentTime   = Carbon::now()->addMinutes(30)->format('H:i:s'); // 30 minutes from now


   // Fetch trips with necessary relationships, filtering by pickup and destination
   $trips = Trip::with([
    'route',
    'fleetType',
    'schedule',
    'assignedVehicle.vehicleDetails',
    'ticketPrice',
    'bookedTickets' => function ($query) use ($dateOfJourney) {
     $query->where('date_of_journey', $dateOfJourney);
    },
   ])
    ->whereHas('route', function ($query) use ($pickup, $destination) {
     $query->where('start_from', $pickup)
      ->where('end_to', $destination);
    })
    ->whereHas('schedule', function ($query) use ($dateOfJourney, $currentTime) {
     $query->where(function ($q) use ($dateOfJourney, $currentTime) {
      if ($dateOfJourney === Carbon::today()->format('Y-m-d')) {
       // If it's today, filter trips that have not yet departed (at least 30 minutes ahead)
       $q->where('start_from', '>=', $currentTime);
      }
     });
    })
    ->get();

   // Transform trips to match the required format
   $formattedTrips = $trips->map(function ($trip) {
    // List all seats with booking status
    $allSeats    = $trip->fleetType->deck_seats ?? [];
    $bookedSeats = $trip->bookedTickets->flatMap(fn($ticket) => $ticket->seats)->toArray();

    $ticketPrice = TicketPrice::where('vehicle_route_id', $trip->vehicle_route_id)
     ->where('fleet_type_id', $trip->fleet_type_id)
     ->first();

    $seats = array_map(function ($seat) use ($bookedSeats) {
     return [
      'seat'     => $seat,
      'isBooked' => in_array($seat, $bookedSeats),
     ];
    }, $allSeats);

    return [
     'id'                  => $trip->id,
     'start_from'          => Carbon::parse($trip->schedule->start_from)->format('H:i'),
     'end_at'              => Carbon::parse($trip->schedule->end_at)->format('H:i'),
     'seats'               => $seats,
     'name'                => $trip->route->name ?? 'N/A',
     'busType'             => $trip->fleetType->type ?? 'Unknown',
     'ratings'             => $trip->fleetType->ratings ?? '4',
     'facilities'          => $trip->fleetType->facilities ?? [],
     'has_ac'              => $trip->fleetType->has_ac,
     'seat_layout'         => $trip->fleetType->seat_layout,
     'deck'                => $trip->fleetType->deck,
     'deck_seats'          => $trip->fleetType->deck_seats,
     'price'               => $ticketPrice->price ?? '0',
     'time'                => $trip->route->time ?? 'Unknown',
     'distance'            => $trip->route->distance ?? 'N/A',
     'origin'              => $trip->startFrom->name ?? 'Unknown',
     'originLocation'      => $trip->startFrom->location ?? 'Unknown',
     'originCity'          => $trip->startFrom->city ?? 'Unknown',
     'destination'         => $trip->endTo->name ?? 'Unknown',
     'destinationLocation' => $trip->endTo->location ?? 'Unknown',
     'destinationCity'     => $trip->endTo->city ?? 'Unknown',
     'vehicle'             => $trip->assignedVehicle->vehicleDetails->register_no ?? 'N/A',
    ];
   });

   // Return the filtered trips
   return response()->json([
    'trips'   => $formattedTrips,
    'message' => 'Search results for available trips',
    'status'  => 200,
   ]);

  } catch (\Exception $e) {
   //throw $th;
   return response()->json([
    'error'  => $e->getMessage(),
    'status' => 404,
   ]);
  }
 }

 public function ticket()
 {
  $trips = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])
   ->where('status', 1)
   ->paginate(getPaginate(10));

  $fleetType = FleetType::active()->get();
  $routes    = VehicleRoute::active()->get();
  $schedules = Schedule::all();

  return response()->json([
   'fleetType' => $fleetType,
   'trips'     => $trips,
   'routes'    => $routes,
   'schedules' => $schedules,
   'message'   => 'Available trips',
  ]);
 }

 public function showSeat(Request $request, $id)
 {
  try {
   // Validate the date_of_journey parameter
   $request->validate([
    'date_of_journey' => 'required|date|after_or_equal:today',
   ]);

   $dateOfJourney = Carbon::parse($request->date_of_journey)->format('Y-m-d');

   // Fetch trip with related data
   $trip = Trip::with([
    'fleetType',
    'route',
    'schedule',
    'startFrom',
    'endTo',
    'assignedVehicle.vehicle',
    'bookedTickets' => function ($query) use ($dateOfJourney) {
     $query->where('date_of_journey', $dateOfJourney);
    },
   ])
    ->where('status', 1)
    ->where('id', $id)
    ->firstOrFail();

   // Get all seats from the fleetType's deck seats
   $allSeats = $trip->fleetType->deck_seats ?? [];

   $ticketPrice = TicketPrice::where('vehicle_route_id', $trip->vehicle_route_id)
    ->where('fleet_type_id', $trip->fleet_type_id)
    ->first();

   // Get booked seats for the selected date
   $bookedSeats = BookedTicket::where('trip_id', $id)
    ->where('date_of_journey', $request->date_of_journey)
    ->pluck('seats')
    ->flatten()
    ->map(function ($seat) {
     // Decode serialized seats if they are in string format
     if (is_string($seat)) {
      $decodedSeats = json_decode($seat, true);
      return $decodedSeats ? $decodedSeats : [$seat];
     }
     return $seat;
    })
    ->flatten()
    ->toArray();

   // Prepare seat data with booking status
   $seats = array_map(function ($seat) use ($bookedSeats) {
    return [
     'seat'     => $seat,
     'isBooked' => in_array($seat, $bookedSeats), // Check if seat is booked
    ];
   }, $allSeats);

   // Prepare response data
   $response = [
    'trip'        => [
     'id'                  => $trip->id,
     'origin'              => $trip->startFrom->name ?? 'Unknown',
     'originLocation'      => $trip->startFrom->location ?? 'Unknown',
     'originCity'          => $trip->startFrom->city ?? 'Unknown',
     'destination'         => $trip->endTo->name ?? 'Unknown',
     'destinationLocation' => $trip->endTo->location ?? 'Unknown',
     'destinationCity'     => $trip->endTo->city ?? 'Unknown',
     'start_time'          => Carbon::parse($trip->schedule->start_from)->format('H:i'),
     'end_time'            => Carbon::parse($trip->schedule->end_at)->format('H:i'),
     'busType'             => $trip->fleetType->type ?? 'Unknown',
     'facilities'          => $trip->fleetType->facilities ?? [],
     'seat_layout'         => $trip->fleetType->seat_layout,
     'price'               => $ticketPrice->price,
    ],
    'seats'       => $seats,
    'bookedSeats' => $bookedSeats,
    'allSeats'    => $allSeats,
   ];

   return response()->json($response, 200);

  } catch (\Exception $e) {
   return response()->json([
    'error'  => $e->getMessage(),
    'status' => 404,
   ]);
  }
 }

 public function getTicketPrice(Request $request)
 {
  $ticketPrice = TicketPrice::where('vehicle_route_id', $request->vehicle_route_id)
   ->where('fleet_type_id', $request->fleet_type_id)
   ->with('route')
   ->first();

  if (!$ticketPrice) {
   return response()->json(['error' => 'Ticket price not found for the selected route.'], 404);
  }

  $route          = $ticketPrice->route;
  $stoppages      = $route->stoppages;
  $sourcePos      = array_search($request->source_id, $stoppages);
  $destinationPos = array_search($request->destination_id, $stoppages);

  $can_go = ($sourcePos !== false && $destinationPos !== false) && ($sourcePos < $destinationPos);
  if (!$can_go) {
   return response()->json(['error' => 'Invalid pickup or dropping point selection.'], 400);
  }

  $getPrice = $ticketPrice->prices()
   ->where('source_destination', json_encode([$request->source_id, $request->destination_id]))
   ->orWhere('source_destination', json_encode(array_reverse([$request->source_id, $request->destination_id])))
   ->first();

  if (!$getPrice) {
   return response()->json(['error' => 'Price not set for this route.'], 404);
  }

  return response()->json([
   'price'       => $getPrice->price,
   'bookedSeats' => BookedTicket::where('trip_id', $request->trip_id)
    ->where('date_of_journey', Carbon::parse($request->date)->format('Y-m-d'))
    ->whereIn('status', [1, 2])
    ->pluck('seats'),
  ]);
 }

 public function bookTicket(Request $request, $id)
 {
  try {
   $request->validate([
    'pickup'            => 'required|integer|exists:counters,id',
    'destination'       => 'required|integer|exists:counters,id|different:pickup',
    'date_of_journey'   => 'required|date|after_or_equal:today',
    'seats'             => 'required|string',
    'gender'            => 'required|integer|in:1,2,3', // 1 for male, 2 for female
    'mobile_number'     => 'required|digits:10',
    'passenger_names'   => 'required|array|min:1',
    'passenger_names.*' => 'required|string|max:191',
   ], [
    'seats.required' => 'Please select at least one seat',
   ]);

   $date_of_journey = Carbon::parse($request->date_of_journey)->format('Y-m-d');

   $trip = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])->findOrFail($id);

   // Check if seats are already booked
   $bookedSeats = BookedTicket::where('trip_id', $id)
    ->where('date_of_journey', $date_of_journey)
    ->whereIn('status', [1, 2])
    ->whereJsonContains('seats', explode(',', $request->seats))
    ->exists();

   if ($bookedSeats) {
    return response()->json(['error' => 'Some seats are already booked. Please choose other seats.'], 409);
   }

   // Fetch ticket price
   $ticketPrice = TicketPrice::where('fleet_type_id', $trip->fleetType->id)
    ->where('vehicle_route_id', $trip->route->id)
    ->first();

   $unitPrice = getAmount($ticketPrice->price);

   $pnr_number = getTrx(10);
   $seats      = array_filter(explode(',', $request->seats));

   // Save passenger details and create user if mobile number is new
   $user = User::firstOrCreate(
    ['mobile' => $request->mobile_number],
    [
     'username' => $request->passenger_names[0],
    ]
   );

   // Fetch boarding and drop-off details
   $pickupPoint  = Counter::findOrFail($request->pickup);
   $dropOffPoint = Counter::findOrFail($request->destination);

   // Save booking details
   $bookedTicket = new BookedTicket([
    'user_id'            => $user->id,
    'gender'             => $request->gender,
    'trip_id'            => $trip->id,
    'source_destination' => [$request->pickup, $request->destination],
    'pickup_point'       => $request->pickup,
    'dropping_point'     => $request->destination,
    'seats'              => $seats,
    'ticket_count'       => count($seats),
    'unit_price'         => $unitPrice,
    'sub_total'          => count($seats) * $unitPrice,
    'date_of_journey'    => $date_of_journey,
    'pnr_number'         => $pnr_number,
    'status'             => 2, // Confirmed status
    'passenger_names'    => json_encode($request->passenger_names),
   ]);

   // Save the ticket in data base
   $bookedTicket->save();

   // Prepare ticket details for WhatsApp
   $ticketDetails = [
    'id'               => $bookedTicket->id,
    'source_name'      => $pickupPoint->city ?? 'Unknown',
    'destination_name' => $dropOffPoint->city ?? 'Unknown',
    'date_of_journey'  => $date_of_journey,
    'start_time'       => Carbon::parse($trip->schedule->start_from)->format('H:i'),
    'end_time'         => Carbon::parse($trip->schedule->end_at)->format('H:i'),
    'pnr'              => $pnr_number,
    'seats'            => implode(', ', $seats),
    'passenger_name'   => $request->passenger_names[0],
    'boarding_details' => "{$pickupPoint->name}, {$pickupPoint->city} at {$trip->schedule->start_from}, Contact No: 9111888584",
    'drop_off_details' => "{$dropOffPoint->name}, {$dropOffPoint->city}",
    'ticket_count'     => count($seats),
    'unit_price'       => $unitPrice,
    'sub_total'        => count($seats) * $unitPrice,
   ];

   // Initialize Razorpay
   $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

   // Create Razorpay order
   $order = $api->order->create([
    'receipt'  => $ticketDetails['pnr'],
    'amount'   => $ticketDetails['sub_total']*100, // Amount in paisa
    'currency' => 'INR',
    'notes'    => $ticketDetails, // Pass ticket details in notes
   ]);

   // Return Razorpay order ID to the client
   return response()->json([
    'ticket_id'      => $ticketDetails['id'],
    'order_id'       => $order->id,
    'amount'         => $ticketDetails['sub_total'],
    'currency'       => 'INR',
    'message'        => 'Proceed with payment',
    'ticket_details' => $ticketDetails,
   ]);
  } catch (\Illuminate\Validation\ValidationException $e) {
   return response()->json([
    'error'   => 'Validation error',
    'details' => $e->errors(), // Return detailed validation errors
   ], 422);
  } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
   return response()->json([
    'error'   => 'Resource not found',
    'details' => $e->getMessage(),
   ], 404);
  } catch (\Exception $e) {
   // Catch any other exception and return detailed error
   return response()->json([
    'error'   => 'An unexpected error occurred',
    'message' => $e->getMessage(),
    'line'    => $e->getLine(),
    'file'    => $e->getFile(),
    'trace'   => $e->getTraceAsString(), // Optional, for detailed debugging
   ], 500);
  }
 }

 public function confirmPayment(Request $request)
 {
  try {
   $request->validate([
    'razorpay_payment_id' => 'required|string',
    'razorpay_order_id'   => 'required|string',
    'razorpay_signature'  => 'required|string',
    'ticket_id'           => 'required|integer|exists:booked_tickets,id',
   ]);

   // Initialize Razorpay
   $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

   // Verify payment signature
   $attributes = [
    'razorpay_order_id'   => $request->razorpay_order_id,
    'razorpay_payment_id' => $request->razorpay_payment_id,
    'razorpay_signature'  => $request->razorpay_signature,
   ];

   $api->utility->verifyPaymentSignature($attributes);

   // Retrieve the booked ticket
   $bookedTicket = BookedTicket::findOrFail($request->ticket_id);

// Update ticket status to approved (1)
   $bookedTicket->update(['status' => 1]);

   // Retrieve ticket details from Razorpay order notes
   $order = $api->order->fetch($request->razorpay_order_id);

   Log::info($request->ticket_details);

   // Fetch ticket details
   $ticketDetails = [
    'pnr'              => $bookedTicket->pnr_number,
    'source_name'      => $bookedTicket->pickup->name,
    'destination_name' => $bookedTicket->drop->name,
    'date_of_journey'  => $bookedTicket->date_of_journey,
    'seats'            => implode(', ', $bookedTicket->seats),
    'passenger_name'   => $request->ticket_details->passenger_names[0] ?? 'Guest',
    'boarding_details' => $bookedTicket->pickup->location . ', ' . $bookedTicket->pickup->city . ', Contact: ' . "9111888584",
    'drop_off_details' => $bookedTicket->drop->name . ', ' . $bookedTicket->drop->city,
   ];

   // Send ticket details via WhatsApp
   sendTicketDetailsWhatsApp($ticketDetails, $bookedTicket->user->mobile);
   sendTicketDetailsWhatsApp($ticketDetails, "9111888584");

   return response()->json([
    'success'       => true,
    'message'       => 'Payment successful. Ticket details sent via WhatsApp.',
    'details'       => $ticketDetails,
    'mobile_number' => $bookedTicket->user->mobile,
    'status'        => 201,
   ]);
  } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
   return response()->json([
    'error'   => 'Payment verification failed',
    'message' => $e->getMessage(),
   ], 400);
  } catch (\Exception $e) {
   return response()->json([
    'error'   => 'An unexpected error occurred',
    'message' => $e->getMessage(),
   ], 500);
  }
 }

 public function getCounters()
 {
  $counters = Counter::select('id', 'name')->get();

  return response()->json([
   'success'  => true,
   'counters' => $counters,
  ]);
 }

}
