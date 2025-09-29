<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\City;
use App\Models\Counter;
use App\Models\FleetType;
use App\Models\MarkupTable;
use App\Models\Schedule;
use App\Models\TicketPrice;
use App\Models\Trip;
use App\Models\User;
use App\Models\VehicleRoute;
use App\Services\BusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Razorpay\Api\Api;

class ApiTicketController extends Controller
{
    protected $busService;

    // Use Laravel's service container to automatically inject the BusService instance.
    public function __construct(BusService $busService)
    {
        $this->busService = $busService;
    }

    /**
     * Handles the primary bus search request.
     * Delegates all logic to the BusService for performance and clarity.
     */
    public function ticketSearch(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'OriginId' => 'required|integer',
                'DestinationId' => 'required|integer|different:OriginId',
                'DateOfJourney' => 'required|date_format:Y-m-d|after_or_equal:today',
                'page' => 'sometimes|integer|min:1',
                'sortBy' => 'sometimes|string|in:departure,price',
                'sortOrder' => 'sometimes|string|in:asc,desc',
                'fleetType' => 'sometimes|array',
                'fleetType.*' => 'string|in:AC,Non-AC,Seater,Sleeper',
                'departure_time' => 'sometimes|array',
                'departure_time.*' => 'string|in:morning,afternoon,evening,night', // Wildcard '*' validates each item
                // 'min_price' => 'sometimes|numeric|min:0',
                // 'max_price' => 'sometimes|numeric|required_with:min_price|gt:min_price',
                'live_tracking' => 'sometimes|boolean',
            ]);

            // --- THE FIX: Normalize frontend data before passing it to the service ---
            if (isset($validatedData['fleetType'])) {
                $validatedData['fleetType'] = array_map(function ($type) {
                    if ($type === 'AC')
                        return 'A/c';
                    if ($type === 'Non-AC')
                        return 'Non-A/c';
                    return $type;
                }, $validatedData['fleetType']);
            }
            // --- End of Fix ---


            $result = $this->busService->searchBuses($validatedData);

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('TicketSearch Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('TicketSearch Exception: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], $e->getCode() == 404 ? 404 : 500);
        }
    }

    // --- ALL OTHER METHODS FROM YOUR ORIGINAL CONTROLLER UNTOUCHED ---

    public function autocompleteCity(Request $request)
    {
        $search = strtolower($request->input('query', ''));
        $cacheKey = 'cities_search_' . $search;

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

    public function ticket()
    {
        $trips = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])
            ->where('status', 1)
            ->paginate(10);

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
                $availableSeats = $response['Result']['AvailableSeats'];
                return response()->json([
                    "html" => parseSeatHtmlToJson($html),
                    "availableSeats" => $availableSeats
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'status' => 404,
            ]);
        }
    }

    public function getCancellationPolicy(Request $request)
    {
        try {
            $request->validate([
                'CancelPolicy' => 'required|array',
            ]);
            if ($request->CancelPolicy) {
                return response()->json([
                    'cancellationPolicy' => formatCancelPolicy($request->CancelPolicy),
                    'status' => 200,
                ]);
            }
        } catch (\Exception $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
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
            $pnr_number = getTrx(10);
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            $order = $api->order->create(['currency' => 'INR']);

            return response()->json([
                'order_id' => $order->id,
                'currency' => 'INR',
                'message' => 'Proceed with payment',
            ]);
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
                'OriginCity' => 'required',
                'DestinationCity' => 'required',
                'SearchTokenId' => 'required',
                'ResultIndex' => 'required',
                'UserIp' => 'required|string',
                'BoardingPointId' => 'required',
                'DroppingPointId' => 'required',
                'Seats' => 'required|string',
                'FirstName' => 'required',
                'LastName' => 'required',
                'Gender' => 'required|in:0,1',
                'Email' => 'required|email',
                'Phoneno' => 'required',
                'age' => 'nullable|integer',
            ]);

            $sourceDestination = json_encode([
                $request->OriginCity,
                $request->DestinationCity
            ]);

            // Register or log in the user
            if (!Auth::check()) {
                $fullPhone = $request->Phoneno;
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
            $totalFare = collect($response['Result']['Passenger'])->sum(function ($passenger) {
                // PublishedPrice includes BasePrice, Tax, and OtherCharges.
                return $passenger['Seat']['Price']['PublishedPrice'] ?? 0;
            });

            $unitPrice = collect($response['Result']['Passenger'])->sum(function ($passenger) {
                // PublishedPrice includes BasePrice, Tax, and OtherCharges.
                return $passenger['Seat']['Price']['OfferedPrice'] ?? 0;
            });

            // Convert totalFare and unitPrice to 2 digit decimal number
            $totalFare = round($totalFare, 2);
            $unitPrice = round($unitPrice, 2);

            Log::info('Total Fare is ' . $totalFare . 'and Unit Price is ' . $unitPrice);
            // Create a pending ticket in the database
            $bookedTicket = new BookedTicket();
            $bookedTicket->user_id = Auth::id();
            $bookedTicket->bus_type = $response['Result']['BusType'];
            $bookedTicket->travel_name = $response['Result']['TravelName'];
            $bookedTicket->source_destination = $sourceDestination;
            $bookedTicket->departure_time = Carbon::parse($response['Result']['DepartureTime'])->format('H:i:s');
            $bookedTicket->arrival_time = Carbon::parse($response['Result']['ArrivalTime'])->format('H:i:s');
            $bookedTicket->operator_pnr = $response['Result']['BookingId'] ?? null; //update on time of booking
            $bookedTicket->boarding_point_details = json_encode($response['Result']['BoardingPointdetails']);
            $bookedTicket->dropping_point_details = isset($response['Result']['DroppingPointsdetails']) ? json_encode($response['Result']['DroppingPointsdetails']) : null; //update on time of booking
            $bookedTicket->seats = $seats; // This will be cast to array by the model
            $bookedTicket->ticket_count = count($seats);
            $bookedTicket->unit_price = $unitPrice;
            $bookedTicket->sub_total = $totalFare;
            $bookedTicket->pnr_number = getTrx(10);
            $bookedTicket->pickup_point = $request->BoardingPointId;
            $bookedTicket->dropping_point = $request->DroppingPointId;
            $bookedTicket->search_token_id = $request->SearchTokenId;
            $bookedTicket->date_of_journey = Carbon::parse($response['Result']['DepartureTime'])->format('Y-m-d');

            $leadPassenger = collect($response['Result']['Passenger'])->firstWhere('LeadPassenger', true)
                ?? $response['Result']['Passenger'][0] ?? null;

            // $bookedTicket->passenger_names = $passengerNames;
            $bookedTicket->passenger_phone = $leadPassenger['Phoneno'] ?? null;
            $bookedTicket->passenger_email = $leadPassenger['Email'] ?? null;
            $bookedTicket->passenger_address = $leadPassenger['Address'] ?? null;
            $bookedTicket->passenger_name = trim(($leadPassenger['FirstName'] ?? '') . ' ' . ($leadPassenger['LastName'] ?? ''));
            $bookedTicket->passenger_age = $leadPassenger['Age'] ?? null;

            $bookedTicket->status = 0; // 0 for pending
            $bookedTicket->save();

            $bookingDataToCache = [
                'user_ip' => $request->ip(),
                'search_token_id' => $request->SearchTokenId,
                'result_index' => $request->ResultIndex,
                'boarding_point_id' => $request->BoardingPointId,
                'dropping_point_id' => $request->DroppingPointId,
                'passengers' => $passengers,
            ];

            // Store necessary data for final booking in cache for 15 minutes, keyed by ticket ID
            Cache::put('booking_data_' . $bookedTicket->id, $bookingDataToCache, now()->addMinutes(15));
            Log::info('Booking data cached for ticket ID ' . $bookedTicket->id, $bookingDataToCache);

            // Initialize Razorpay
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

            // Create Razorpay order
            $order = $api->order->create([
                'receipt' => $bookedTicket->pnr_number,
                'amount' => $totalFare * 100, // Amount in paisa
                // 'amount' => 1 * 100,
                'currency' => 'INR',
                'notes' => [
                    'ticket_id' => $bookedTicket->id,
                    'pnr_number' => $bookedTicket->pnr_number,
                ]
            ]);

            $formattedPolicy = formatCancelPolicy($response['Result']['CancelPolicy'] ?? []);
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

    public function confirmPayment(Request $request)
    {
        try {
            Log::info('Now Trying to confirm payment...');
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

            // Retrieve the pending ticket
            $bookedTicket = BookedTicket::findOrFail($request->ticket_id);

            // Retrieve booking data from cache

            $bookingData = Cache::get('booking_data_' . $bookedTicket->id);

            if (!$bookingData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking session expired or is invalid. Please try blocking the seat again.'
                ], 400);
            }

            // Call the final booking API
            $apiResponse = bookAPITicket(
                $bookingData['user_ip'],
                $bookingData['search_token_id'],
                $bookingData['result_index'],
                $bookingData['boarding_point_id'],
                $bookingData['dropping_point_id'],
                $bookingData['passengers']
            );

            if (isset($apiResponse['Error']) && $apiResponse['Error']['ErrorCode'] != 0) {
                // Handle booking failure (e.g., log, notify admin)
                $bookedTicket->update(['status' => 3, 'api_response' => json_encode($apiResponse)]); // 3 for rejected
                return response()->json([
                    'success' => false,
                    'message' => $apiResponse['Error']['ErrorMessage'] ?? 'Final booking failed at operator end.'
                ], 400);
            }


            // Update ticket status to confirmed and save operator PNR
            $bookedTicket->operator_pnr = $apiResponse['Result']['TravelOperatorPNR'];
            $bookedTicket->update(['status' => 1, 'api_response' => json_encode($apiResponse)]);
            $bookingApiId = $apiResponse['Result']['BookingID'];

            Log::info('Now Getting ticket details...', ['UserIp' => $bookingData['user_ip'], 'SearchTokenId' => $bookingData['search_token_id'], 'BookingApiId' => $bookingApiId]);

            $ticketApiDetails = getAPITicketDetails($bookingData['user_ip'], $bookingData['search_token_id'], $bookingApiId);

            $bookedTicket->update(
                [
                    'api_invoice' => $ticketApiDetails['Result']['InvoiceNumber'],
                    'api_invoice_amount' => $ticketApiDetails['Result']['InvoiceAmount'],
                    'api_invoice_date' => Carbon::parse($ticketApiDetails['Result']['InvoiceCreatedOn'])->format('Y-m-d H:i:s'),
                    'api_booking_id' => $ticketApiDetails['Result']['BookingId'],
                    'api_ticket_no' => $ticketApiDetails['Result']['TicketNo'],
                    'agent_commission' => $ticketApiDetails['Result']['Price']['AgentCommission'],
                    'tds_from_api' => $ticketApiDetails['Result']['Price']['TDS'],
                    'origin_city' => $ticketApiDetails['Result']['Origin'],
                    'destination_city' => $ticketApiDetails['Result']['Destination'],
                    'dropping_point_details' => json_encode($ticketApiDetails['Result']['DroppingPointdetails'] ?? null), // Update dropping point details if available
                    'cancellation_policy' => json_encode(formatCancelPolicy($ticketApiDetails['Result']['CancelPolicy'] ?? [])), // Store formatted cancellation policy
                ]
            );

            Log::info('Get Ticekt Details API Response', $ticketApiDetails);

            // TODO: if succeed then store the search token in bookedTicket so it can be cancelled later

            // Clean up cache data
            Cache::forget('booking_data_' . $bookedTicket->id);
            Log::info('Booking session cleaned up');

            $originCity = $ticketApiDetails['Result']['Origin'];
            $destinationCity = $ticketApiDetails['Result']['Destination'];

            Log::info('Ticket from' . $originCity . ' to ' . $destinationCity);

            // Safely decode boarding and dropping point details
            $boardingDetails = json_decode($bookedTicket->boarding_point_details, true);
            $droppingDetails = $ticketApiDetails['Result']['DroppingPointdetails'];

            // Construct readable details for WhatsApp
            $boardingDetailsString = 'Not Available';
            if ($boardingDetails) {
                $boardingDetailsString = ($boardingDetails['CityPointName'] ?? '') . ', ' . ($boardingDetails['CityPointLocation'] ?? '') . '. Time: ' . Carbon::parse($boardingDetails['CityPointTime'])->format('h:i A') . 'Contact Number: ' . ($boardingDetails['CityPointContactNumber'] ?? '');
            }

            $droppingDetailsString = 'Not Available';
            if ($droppingDetails) {
                $droppingDetailsString = ($droppingDetails['CityPointName'] ?? '') . ', ' . ($droppingDetails['CityPointLocation'] ?? '');
            }

            // Fetch ticket details
            $ticketDetails = [
                'pnr' => $bookedTicket->pnr_number,
                'source_name' => $originCity,
                'destination_name' => $destinationCity,
                'date_of_journey' => $bookedTicket->date_of_journey,
                'seats' => is_array($bookedTicket->seats) ? implode(', ', $bookedTicket->seats) : $bookedTicket->seats,
                'passenger_name' => $bookedTicket->passenger_name ?? 'Guest',
                'boarding_details' => $boardingDetailsString,
                'drop_off_details' => $droppingDetailsString,
            ];
            Log::info('Now Sending ticket details:');

            // Send ticket details via WhatsApp
            sendTicketDetailsWhatsApp($ticketDetails, $bookedTicket->user->mobile);
            sendTicketDetailsWhatsApp($ticketDetails, "8269566034");

            return response()->json([
                'success' => true,
                'message' => 'Payment successful. Ticket details sent via WhatsApp.',
                'details' => $ticketApiDetails['Result'],
                'mobile_number' => $bookedTicket->passenger_phone,
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

    public function getCombinedBuses(Request $request)
    {
        // Your existing getCombinedBuses logic...
    }
}
