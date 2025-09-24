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
use App\Services\BusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ApiTicketController extends Controller
{


    public function autocompleteCity(Request $request)
    {
        $search = strtolower($request->input('query', ''));
        $cacheKey = 'cities_search' . $search;

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $cities = Cache::remember($cacheKey, 84600, function () use ($search) {
            return City::select('city_id', 'city_name')
                ->where('city_name', 'like', $search . '%')
                ->limit(10)
                ->get();
        });

        return response()->json($cities);
    }


    // 1. First of all this function will check if there is any trip available for the searched route
    public function ticketSearch(Request $request)
    {
        try {
            BusService::validateSearchRequest($request);
            $resp = BusService::fetchAndProcessAPIResponse(
                $request->OriginId,
                $request->DestinationId,
                $request->DateOfJourney,
                $request->ip()
            );

            if (!is_array($resp) || !isset($resp['Result']) || empty($resp['Result'])) {
                abort(404, 'No buses found for this route and date');
            }
            if ($resp['Error']['ErrorCode'] == 0) {
                $trips = BusService::sortTripsByDepartureTime($resp['Result']);
                $trips = BusService::applyMarkup($trips);

                if ($request->hasAny(['departure_time', 'amenities', 'min_price', 'max_price', 'fleetType'])) {
                    $trips = BusService::applyFilters($trips, $request);
                }
                return response()->json([
                    'SearchTokenId' => $resp['SearchTokenId'],
                    'trips' => $trips
                ]);
            }
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


    public function ticket()
    {
        $trips = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])
            ->where('status', 1)
            ->paginate(getPaginate(10));

        $fleetType = FleetType::active()->get();
        $routes = VehicleRoute::active()->get();
        $schedules = Schedule::all();

        return response()->json([
            'fleetType' => $fleetType,
            'trips' => $trips,
            'routes' => $routes,
            'schedules' => $schedules,
            'message' => 'Available trips',
        ]);
    }


    public function showSeat(Request $request)
    {
        try {
            $request->validate([
                'SearchTokenId' => 'required|string',
                'ResultIndex' => 'required|string',
            ]);
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
                'error' => $e->getMessage(),
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

        $route = $ticketPrice->route;
        $stoppages = $route->stoppages;
        $sourcePos = array_search($request->source_id, $stoppages);
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
            'price' => $getPrice->price,
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
                // 'receipt'  => $ticketDetails['pnr'],
                // 'amount'   => $ticketDetails['sub_total'] * 100, // Amount in paisa
                'currency' => 'INR',
                // 'notes'    => $ticketDetails, // Pass ticket details in notes
            ]);

            // Return Razorpay order ID to the client
            return response()->json([
                // 'ticket_id'      => $ticketDetails['id'],
                'order_id' => $order->id,
                // 'amount'         => $ticketDetails['sub_total'],
                'currency' => 'INR',
                'message' => 'Proceed with payment',
                // 'ticket_details' => $ticketDetails,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation error',
                'details' => $e->errors(), // Return detailed validation errors
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Resource not found',
                'details' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            // Catch any other exception and return detailed error
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(), // Optional, for detailed debugging
            ], 500);
        }
    }

    public function confirmPayment(Request $request)
    {
        try {
            $request->validate([
                'razorpay_payment_id' => 'required|string',
                'razorpay_order_id' => 'required|string',
                'razorpay_signature' => 'required|string',
                'ticket_id' => 'required|integer|exists:booked_tickets,id',
            ]);

            // Initialize Razorpay
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

            // Verify payment signature
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
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
                'pnr' => $bookedTicket->pnr_number,
                'source_name' => $bookedTicket->pickup->name,
                'destination_name' => $bookedTicket->drop->name,
                'date_of_journey' => $bookedTicket->date_of_journey,
                'seats' => implode(', ', $bookedTicket->seats),
                'passenger_name' => $request->ticket_details->passenger_names[0] ?? 'Guest',
                'boarding_details' => $bookedTicket->pickup->location . ', ' . $bookedTicket->pickup->city . ', Contact: ' . "9111888584",
                'drop_off_details' => $bookedTicket->drop->name . ', ' . $bookedTicket->drop->city,
            ];

            // Send ticket details via WhatsApp
            sendTicketDetailsWhatsApp($ticketDetails, $bookedTicket->user->mobile);
            sendTicketDetailsWhatsApp($ticketDetails, "9111888584");

            return response()->json([
                'success' => true,
                'message' => 'Payment successful. Ticket details sent via WhatsApp.',
                'details' => $ticketDetails,
                'mobile_number' => $bookedTicket->user->mobile,
                'status' => 201,
            ]);
        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            return response()->json([
                'error' => 'Payment verification failed',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
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
                'error' => $e->getMessage(),
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
                'TravelName' => 'required|string',
                'BusType' => 'required|string',
                'DepartureTime' => 'required|string',
                'ArrivalTime' => 'required|string',
                'DateOfJourney' => 'required|date',
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

            // Calculate total fare from the blocked seat response
            $totalFare = collect($response['Result']['Passenger'])->sum('Fare');

            Log::info('Total Fare Calculated: ' . $totalFare);
            // Create a pending ticket in the database
            $bookedTicket = new BookedTicket();
            $bookedTicket->user_id = Auth::id();
            $bookedTicket->pnr_number = getTrx(10);
            $bookedTicket->operator_pnr = $response['Result']['BookingId'] ?? null;
            $bookedTicket->seats = $seats;
            $bookedTicket->ticket_count = count($seats);
            $bookedTicket->sub_total = $totalFare;
            $bookedTicket->total_fare = $totalFare; // Assuming no other charges for now
            $bookedTicket->pickup_point = $request->BoardingPointId;
            $bookedTicket->dropping_point = $request->DroppingPointId;
            $bookedTicket->date_of_journey = Carbon::parse($request->DateOfJourney)->format('Y-m-d');
            $bookedTicket->status = 0; // 0 for pending
            $bookedTicket->booking_source = 'api';
            $bookedTicket->travel_name = $request->TravelName;
            $bookedTicket->bus_type = $request->BusType;
            $bookedTicket->departure_time = $request->DepartureTime;
            $bookedTicket->arrival_time = $request->ArrivalTime;
            $bookedTicket->boarding_point_details = json_encode($request->boarding_point_details);
            $bookedTicket->dropping_point_details = json_encode($request->dropping_point_details);
            $bookedTicket->search_token_id = $request->SearchTokenId;
            $bookedTicket->result_index = $request->ResultIndex;
            $bookedTicket->save();

            // Initialize Razorpay
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

            // Create Razorpay order
            $order = $api->order->create([
                'receipt' => $bookedTicket->pnr_number,
                // 'amount' => $totalFare * 100, // Amount in paisa
                'amount' => 1 * 100,
                'currency' => 'INR',
                'notes' => [
                    'ticket_id' => $bookedTicket->id,
                    'pnr_number' => $bookedTicket->pnr_number,
                ]
            ]);

            $formattedPolicy = formatCancelPolicy($response['Result']['CancelPolicy'] ?? []);
            Log::info($response);
            return response()->json([
                'success' => true,
                'message' => 'Seats blocked successfully! Proceed to payment.',
                'ticket_id' => $bookedTicket->id,
                'order_id' => $order->id,
                'amount' => $totalFare,
                'currency' => 'INR',
                'block_details' => $response['Result'],
                'cancellationPolicy' => $formattedPolicy,
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


    /**
     * Fetch buses from both third-party API and local database
     * Combines results into a single unified response
     */
    public function getCombinedBuses(Request $request)
    {
        try {
            $request->validate([
                'OriginId' => 'required',
                'DestinationId' => 'required',
                'DateOfJourney' => 'required|date'
            ]);

            $combinedResults = [
                'SearchTokenId' => null,
                'api_buses' => [],
                'local_buses' => [],
                'combined_trips' => [],
                'total_count' => 0,
                'api_count' => 0,
                'local_count' => 0
            ];

            try {
                $apiResponse = searchAPIBuses(
                    $request->OriginId,
                    $request->DestinationId,
                    $request->DateOfJourney,
                    $request->ip()
                );

                if (is_array($apiResponse) && isset($apiResponse['Result']) && !empty($apiResponse['Result'])) {
                    if (isset($apiResponse['Error']) && $apiResponse['Error']['ErrorCode'] == 0) {
                        $apiTrips = $apiResponse['Result'];

                        // Sort by departure time
                        usort($apiTrips, function ($a, $b) {
                            return strtotime($a['DepartureTime']) - strtotime($b['DepartureTime']);
                        });

                        $combinedResults['SearchTokenId'] = $apiResponse['SearchTokenId'] ?? null;
                        $combinedResults['api_buses'] = $apiTrips;
                        $combinedResults['api_count'] = count($apiTrips);

                        // Mark API buses with source identifier
                        foreach ($combinedResults['api_buses'] as &$trip) {
                            $trip['source'] = 'api';
                            $trip['booking_type'] = 'external';
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Third-party API fetch failed: ' . $e->getMessage());
                // Continue with local buses even if API fails
            }

            try {
                $localTrips = $this->fetchLocalBuses($request);
                $combinedResults['local_buses'] = $localTrips;
                $combinedResults['local_count'] = count($localTrips);

                // Mark local buses with source identifier
                foreach ($combinedResults['local_buses'] as &$trip) {
                    $trip['source'] = 'local';
                    $trip['booking_type'] = 'internal';
                }
            } catch (\Exception $e) {
                Log::warning('Local database fetch failed: ' . $e->getMessage());
            }

            $allTrips = array_merge($combinedResults['api_buses'], $combinedResults['local_buses']);

            // Sort combined trips by departure time
            usort($allTrips, function ($a, $b) {
                $timeA = isset($a['DepartureTime']) ? strtotime($a['DepartureTime']) : strtotime($a['departure_time'] ?? '00:00');
                $timeB = isset($b['DepartureTime']) ? strtotime($b['DepartureTime']) : strtotime($b['departure_time'] ?? '00:00');
                return $timeA - $timeB;
            });

            $combinedResults['combined_trips'] = $allTrips;
            $combinedResults['total_count'] = count($allTrips);

            return response()->json([
                'success' => true,
                'data' => $combinedResults,
                'message' => 'Buses fetched successfully from both sources',
                'summary' => [
                    'total_buses' => $combinedResults['total_count'],
                    'api_buses' => $combinedResults['api_count'],
                    'local_buses' => $combinedResults['local_count']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Combined bus search failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to fetch buses'
            ], 500);
        }
    }

    /**
     * Fetch buses from local database
     * Converts local trip format to match API format for consistency
     */
    private function fetchLocalBuses(Request $request)
    {
        try {
            // parse journey date with fallback
            try {
                $journeyDate = !empty($request->DateOfJourney)
                    ? Carbon::parse($request->DateOfJourney)->format('Y-m-d')
                    : Carbon::now()->format('Y-m-d');
            } catch (\Exception $e) {
                $journeyDate = Carbon::now()->format('Y-m-d');
            }

            $localTrips = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])
                ->where('status', 1)
                ->where('start_from', $request->OriginId)
                ->where('end_to', $request->DestinationId)
                ->get();

            $formattedTrips = [];

            foreach ($localTrips as $trip) {
                // vehicle_route_id may be named differently — try both
                $vehicleRouteId = $trip->vehicle_route_id ?? $trip->route_id ?? null;

                // Ticket price lookup (safe)
                $ticketPrice = null;
                if ($vehicleRouteId && $trip->fleet_type_id) {
                    $ticketPrice = TicketPrice::where('vehicle_route_id', $vehicleRouteId)
                        ->where('fleet_type_id', $trip->fleet_type_id)
                        ->first();
                }

                $price = 0;
                if ($ticketPrice && method_exists($ticketPrice, 'prices')) {
                    $sourceDestJson = json_encode([(int) $request->OriginId, (int) $request->DestinationId]);
                    $priceDetail = $ticketPrice->prices()
                        ->where('source_destination', $sourceDestJson)
                        ->first();
                    $price = $priceDetail->price ?? 0;
                }

                // Booked seats (safe)
                $bookedSeats = BookedTicket::where('trip_id', $trip->id)
                    ->when($journeyDate, function ($q) use ($journeyDate) {
                        return $q->where('date_of_journey', $journeyDate);
                    })
                    ->whereIn('status', [1, 2])
                    ->pluck('seats')
                    ->flatten()
                    ->toArray();

                // Calculate total seats from fleetType->deck_seats (robust parsing)
                $totalSeats = 40; // default fallback
                if (!empty($trip->fleetType)) {
                    $deckSeatsRaw = $trip->fleetType->deck_seats ?? null;

                    if (!empty($deckSeatsRaw)) {
                        $deckSeats = null;

                        if (is_string($deckSeatsRaw)) {
                            $decoded = json_decode($deckSeatsRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $deckSeats = $decoded;
                            } else {
                                // try to normalize strings like "40" or "[\"40\"]" or "40,30"
                                $clean = trim($deckSeatsRaw, "[] \t\n\r\0\x0B\"'");
                                if ($clean !== '') {
                                    $parts = strpos($clean, ',') !== false ? explode(',', $clean) : [$clean];
                                    $deckSeats = array_map('trim', $parts);
                                }
                            }
                        } elseif (is_array($deckSeatsRaw)) {
                            $deckSeats = $deckSeatsRaw;
                        }

                        if (!empty($deckSeats) && is_array($deckSeats)) {
                            $totalSeats = array_sum(array_map(function ($v) {
                                return (int) $v;
                            }, $deckSeats));
                        }
                    } elseif (!empty($trip->fleetType->deck_seats) && is_numeric($trip->fleetType->deck_seats)) {
                        $totalSeats = (int) $trip->fleetType->deck_seats;
                    }
                }

                $availableSeats = max(0, $totalSeats - count($bookedSeats));

                // Time & duration (guarded)
                $departureTime = $trip->start_time ?? null;
                $arrivalTime = $trip->end_time ?? null;
                $duration = null;
                if ($departureTime && $arrivalTime && method_exists($this, 'calculateDuration')) {
                    try {
                        $duration = $this->calculateDuration($departureTime, $arrivalTime);
                    } catch (\Exception $e) {
                        $duration = null;
                    }
                }

                $formattedTrips[] = [
                    'ResultIndex' => 'local_' . $trip->id,
                    // prefer trip.title (if available), else fleetType->name, else fallback
                    'TravelName' => $trip->title ?? ($trip->fleetType->name ?? 'Local Bus'),
                    'BusType' => $trip->fleetType->seat_layout ?? 'Standard',
                    'DepartureTime' => $departureTime,
                    'ArrivalTime' => $arrivalTime,
                    'Duration' => $duration,
                    'AvailableSeats' => $availableSeats,
                    'BusPrice' => [
                        'PublishedPrice' => $price,
                        'OfferedPrice' => $price,
                        'Currency' => 'INR',
                    ],
                    'Amenities' => $this->getLocalBusAmenities($trip),
                    'BusImages' => [],
                    'CancellationPolicy' => 'Standard cancellation policy applies',
                    'BoardingPoints' => $this->getLocalBoardingPoints($trip),
                    'DroppingPoints' => $this->getLocalDroppingPoints($trip),
                    'BusLayout' => null,
                    'departure_time' => $departureTime,
                    'arrival_time' => $arrivalTime,
                    'trip_id' => $trip->id,
                    'route_id' => $vehicleRouteId,
                    'fleet_type_id' => $trip->fleet_type_id,
                    'booked_seats' => $bookedSeats,
                    'total_seats' => $totalSeats,
                    'source' => 'local',
                    'booking_type' => 'internal'
                ];
            }

            return $formattedTrips;
        } catch (\Throwable $e) {
            \Log::error('fetchLocalBuses error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            // return empty array to avoid 500 — caller should handle empty result
            return [];
        }
    }


    /**
     * Calculate duration between two times
     */
    private function calculateDuration($startTime, $endTime)
    {
        try {
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);

            // Handle next day arrival
            if ($end->lt($start)) {
                $end->addDay();
            }

            $diff = $start->diff($end);
            return $diff->format('%H:%I');
        } catch (\Exception $e) {
            return '04:00'; // Default 4 hours
        }
    }

    /**
     * Get amenities for local bus
     */
    private function getLocalBusAmenities($trip)
    {
        return [
            'WiFi' => false,
            'WaterBottle' => true,
            'ChargingPoint' => false,
            'Blanket' => false,
            'Pillow' => false,
            'ReadingLight' => true,
            'Toilet' => false
        ];
    }

    /**
     * Get boarding points for local trip
     */
    private function getLocalBoardingPoints($trip)
    {
        $counters = Counter::where('city', $trip->start_from)
            ->where('status', 1)
            ->get();

        return $counters->map(function ($counter, $index) {
            return [
                'CityPointIndex' => $counter->id,
                'CityPointName' => $counter->name,
                'CityPointLocation' => $counter->address ?? $counter->location ?? '',
                'CityPointContactNumber' => $counter->contact ?? '',
                'CityPointTime' => '00:00'
            ];
        })->toArray();
    }

    /**
     * Get dropping points for local trip
     */
    private function getLocalDroppingPoints($trip)
    {
        $counters = Counter::where('city', $trip->end_to)
            ->where('status', 1)
            ->get();

        return $counters->map(function ($counter, $index) {
            return [
                'CityPointIndex' => $counter->id,
                'CityPointName' => $counter->name,
                'CityPointLocation' => $counter->address ?? $counter->location ?? '',
                'CityPointContactNumber' => $counter->contact ?? '',
                'CityPointTime' => '00:00'
            ];
        })->toArray();
    }
}
