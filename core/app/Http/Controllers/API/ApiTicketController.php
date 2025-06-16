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
use App\Models\City;
use App\Models\MarkupTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session as FacadesSession;
use Illuminate\Support\Str;

class ApiTicketController extends Controller
{

  public function autocompleteCity(Request $request)
  {
    $search = $request->input('query');
    if (!$search) {
      return response()->json([]);
    }

    $cities = $cities = City::whereRaw('LOWER(city_name) = ?', [strtolower($search)])->get();


    return response()->json($cities);
  }

  // 1. First of all this function will check if there is any trip available for the searched route
  public function ticketSearch(Request $request)
  {
    try {
      //code...
      $resp = $this->fetchAndProcessAPIResponse($request);
      Log::info($resp);
      return $this->prepareAndReturnView($resp);
    } catch (\Throwable $th) {
      //throw $th;
      return response()->json([
        'error' => $th->getMessage(),
        'message' => 404
      ]);
    }
  }


  private function prepareAndReturnView($resp)
  {
    try {
      if ($resp['Error']['ErrorCode'] == 0) {
        $trips = $this->sortTripsByDepartureTime($resp['Result']);
        $markup = MarkupTable::orderBy('id', 'desc')->first();
        $flatMarkup = (float) ($markup->flat_markup ?? 0);
        $percentageMarkup = (float) ($markup->percentage_markup ?? 0);
        $threshold = (float) ($markup->threshold ?? 0);
        foreach ($trips as &$trip) {
          if (isset($trip['BusPrice']['PublishedPrice']) && is_numeric($trip['BusPrice']['PublishedPrice'])) {
            $originalPrice = (float) $trip['BusPrice']['PublishedPrice'];
            $newPrice = $originalPrice;

            if ($originalPrice >= $threshold) {
              $newPrice += $flatMarkup;
            } else {
              $newPrice += ($originalPrice * $percentageMarkup / 100);
            }

            $trip['BusPrice']['PublishedPrice'] = round($newPrice, 2);
          }
        }
        return [
          'SearchTokenId' => $resp['SearchTokenId'] ?? null,
          'trips' => $trips,
        ];
      } else {
        return $resp;
      }
    } catch (\Exception $e) {
      Log::error($e->getMessage());
    }
  }


  private function fetchAndProcessAPIResponse(Request $request)
  {
    $resp = searchAPIBuses(
      $request->OriginId,
      $request->DestinationId,
      $request->DateOfJourney,
      "192.168.12.1",
    );
    return $resp;
  }

  private function sortTripsByDepartureTime($trips)
  {
    usort($trips, function ($a, $b) {
      return strtotime($a['DepartureTime']) - strtotime($b['DepartureTime']);
    });
    return $trips;
  }

  // public function ticketSearch(Request $request)
  // {
  //   try {
  //     //code...
  //     // Validate required fields
  //     $validatedData = $request->validate([
  //       'pickup'          => 'required|integer|exists:counters,id',
  //       'destination'     => 'required|integer|exists:counters,id|different:pickup',
  //       'date_of_journey' => 'required|date|after_or_equal:today',
  //     ]);

  //     $pickup        = $validatedData['pickup'];
  //     $destination   = $validatedData['destination'];
  //     $dateOfJourney = Carbon::parse($validatedData['date_of_journey'])->format('Y-m-d');
  //     $currentTime   = Carbon::now()->addMinutes(30)->format('H:i:s'); // 30 minutes from now


  //     // Fetch trips with necessary relationships, filtering by pickup and destination
  //     $trips = Trip::with([
  //       'route',
  //       'fleetType',
  //       'schedule',
  //       'assignedVehicle.vehicleDetails',
  //       'ticketPrice',
  //       'bookedTickets' => function ($query) use ($dateOfJourney) {
  //         $query->where('date_of_journey', $dateOfJourney);
  //       },
  //     ])
  //       ->whereHas('route', function ($query) use ($pickup, $destination) {
  //         $query->where('start_from', $pickup)
  //           ->where('end_to', $destination);
  //       })
  //       ->whereHas('schedule', function ($query) use ($dateOfJourney, $currentTime) {
  //         $query->where(function ($q) use ($dateOfJourney, $currentTime) {
  //           if ($dateOfJourney === Carbon::today()->format('Y-m-d')) {
  //             // If it's today, filter trips that have not yet departed (at least 30 minutes ahead)
  //             $q->where('start_from', '>=', $currentTime);
  //           }
  //         });
  //       })
  //       ->get();

  //     // Transform trips to match the required format
  //     $formattedTrips = $trips->map(function ($trip) {
  //       // List all seats with booking status
  //       $allSeats    = $trip->fleetType->deck_seats ?? [];
  //       $bookedSeats = $trip->bookedTickets->flatMap(fn($ticket) => $ticket->seats)->toArray();

  //       $ticketPrice = TicketPrice::where('vehicle_route_id', $trip->vehicle_route_id)
  //         ->where('fleet_type_id', $trip->fleet_type_id)
  //         ->first();

  //       $seats = array_map(function ($seat) use ($bookedSeats) {
  //         return [
  //           'seat'     => $seat,
  //           'isBooked' => in_array($seat, $bookedSeats),
  //         ];
  //       }, $allSeats);

  //       return [
  //         'id'                  => $trip->id,
  //         'start_from'          => Carbon::parse($trip->schedule->start_from)->format('H:i'),
  //         'end_at'              => Carbon::parse($trip->schedule->end_at)->format('H:i'),
  //         'seats'               => $seats,
  //         'name'                => $trip->route->name ?? 'N/A',
  //         'busType'             => $trip->fleetType->type ?? 'Unknown',
  //         'ratings'             => $trip->fleetType->ratings ?? '4',
  //         'facilities'          => $trip->fleetType->facilities ?? [],
  //         'has_ac'              => $trip->fleetType->has_ac,
  //         'seat_layout'         => $trip->fleetType->seat_layout,
  //         'deck'                => $trip->fleetType->deck,
  //         'deck_seats'          => $trip->fleetType->deck_seats,
  //         'price'               => $ticketPrice->price ?? '0',
  //         'time'                => $trip->route->time ?? 'Unknown',
  //         'distance'            => $trip->route->distance ?? 'N/A',
  //         'origin'              => $trip->startFrom->name ?? 'Unknown',
  //         'originLocation'      => $trip->startFrom->location ?? 'Unknown',
  //         'originCity'          => $trip->startFrom->city ?? 'Unknown',
  //         'destination'         => $trip->endTo->name ?? 'Unknown',
  //         'destinationLocation' => $trip->endTo->location ?? 'Unknown',
  //         'destinationCity'     => $trip->endTo->city ?? 'Unknown',
  //         'vehicle'             => $trip->assignedVehicle->vehicleDetails->register_no ?? 'N/A',
  //       ];
  //     });

  //     // Return the filtered trips
  //     return response()->json([
  //       'trips'   => $formattedTrips,
  //       'message' => 'Search results for available trips',
  //       'status'  => 200,
  //     ]);
  //   } catch (\Exception $e) {
  //     //throw $th;
  //     return response()->json([
  //       'error'  => $e->getMessage(),
  //       'status' => 404,
  //     ]);
  //   }
  // }

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


  public function showSeat(Request $request)
  {
    try {
      $SearchTokenID = $request->SearchTokenId;
      $ResultIndex = $request->ResultIndex;
      $response = getAPIBusSeats($ResultIndex, $SearchTokenID);
      if ($response['Error']['ErrorCode'] == 0) {
        $html = $response['Result']['HTMLLayout'];
        Log::info($html);
        $availableSeats = $response['Result']['AvailableSeats'];
        return response()->json([
          "html" => parseSeatHtmlToJson($html),
          "availableSeats" => $availableSeats
        ], 200);
      }

      // todo: Add here the code to return the html
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
      Log::info($request->all());
      $pnr_number = getTrx(10);


      // Initialize Razorpay
      $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

      // Create Razorpay order
      $order = $api->order->create([
        'receipt'  => $ticketDetails['pnr'],
        'amount'   => $ticketDetails['sub_total'] * 100, // Amount in paisa
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

  public function getCounters(Request $request)
  {
    try {
      $SearchTokenID = $request->SearchTokenId;
      $ResultIndex = $request->ResultIndex;
      $response = getBoardingPoints($SearchTokenID, $ResultIndex, "192.168.12.1");
      if ($response["Error"]["ErrorCode"] == 0) {
        $resp = $response["Result"];
        Log::info($resp);
        return response()->json([
          'boarding_points' => $resp["BoardingPointsDetails"],
          "dropping_points" => $resp["DroppingPointsDetails"]
        ]);
      }
      return response()->json([
        "error_code" => $response["Error"]["ErrorCode"],
        "error_message" => $response["Error"]["ErrorMessage"]
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'error'  => $e->getMessage(),
        'status' => 404,
      ]);
    }
  }

  public function blockSeatApi(Request $request)
  {
    try {
      $request->validate([
        'SearchTokenId' => 'required',
        'ResultIndex' => 'required',
        'BoardingPointId' => 'required',
        'DroppingPointId' => 'required',
        'Gender' => 'required|in:0,1',
        'Seats' => 'required|string',
        'Phoneno' => 'required',
        'FirstName' => 'required',
        'LastName' => 'required',
        'Email' => 'required|email',
      ]);

      Log::info('Block Seat Request:', ['request' => $request->all()]);

      // Register or log in the user
      if (!Auth::check()) {
        $fullPhone = '91' . $request->Phoneno;
        $user = User::firstOrCreate(
          ['mobile' => $fullPhone],
          [
            'firstname' => $request->FirstName,
            'lastname' => $request->LastName,
            'email' => $request->Email,
            'username' => 'user' . time(),
            'password' => Hash::make(Str::random(8)),
            'country_code' => '91',
            'address' => [
              'address' => $request->Address,
              'state' => '',
              'zip' => '',
              'country' => 'India',
              'city' => ''
            ],
            'status' => 1,
            'ev' => 1,
            'sv' => 1,
          ]
        );
        Auth::login($user);
      }

      $seats = explode(',', $request->Seats);

      $passengers = collect($seats)->map(function ($seatName, $index) use ($request) {
        return [
          "LeadPassenger" => $index === 0,
          "Title" => $request->Gender == 1 ? "Mr" : "Mrs",
          "FirstName" => $request->FirstName,
          "LastName" => $request->LastName,
          "Email" => $request->Email,
          "Phoneno" => $request->Phoneno,
          "Gender" => $request->Gender,
          "IdType" => null,
          "IdNumber" => null,
          "Address" => $request->Address,
          "Age" => $request->age ?? 0,
          "SeatName" => $seatName
        ];
      })->toArray();

      $response = blockSeatHelper(
        $request->SearchTokenId,
        $request->ResultIndex,
        $request->BoardingPointId,
        $request->DroppingPointId,
        $passengers,
        $seats,
        $request->ip()
      );

      if (!$response['success']) {
        return response()->json([
          'success' => false,
          'message' => $response['message'] ?? 'Unable to block seats',
          'error' => $response['error'] ?? null
        ], 400);
      }

      $formattedPolicy = formatCancelPolicy($response['Result']['CancelPolicy'] ?? []);
      Log::info($response);
      return response()->json([
        'success' => true,
        'response' => array_merge(
          $response['Result'],
          [
            'Passenger' => collect($response['Result']['Passenger'] ?? [])
              ->map(function ($passenger, $index) use ($passengers) {
                $passenger['SeatName'] = $passengers[$index]['SeatName'] ?? null;
                return $passenger;
              })
              ->toArray()
          ]
        ),
        'cancellationPolicy' => $formattedPolicy,
        'message' => 'Seats blocked successfully! Proceed to payment.',
      ]);
    } catch (\Exception $e) {
      Log::error('BlockSeat API exception: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Unexpected error occurred',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
