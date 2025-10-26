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
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use Illuminate\Validation\ValidationException;

class ApiTicketController extends Controller
{
    protected $busService;
    protected $bookingService;

    // Use Laravel's service container to automatically inject the BusService instance.
    public function __construct(BusService $busService, BookingService $bookingService)
    {
        $this->busService = $busService;
        $this->bookingService = $bookingService;
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
    /**
     * Fetches and displays the seat layout for a specific bus route.
     *
     * This method is aggressively optimized for speed using caching. The primary
     * bottleneck, the `parseSeatHtmlToJson` function, is only called if the result
     * is not already stored in the cache. For a given trip, the first request will
     * perform the API call and the slow parsing, but all subsequent requests will
     * receive the cached data almost instantly, dramatically improving performance
     * and reducing server load.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function showSeat(Request $request)
    {
        $startTime = microtime(true);

        try {
            $validated = $request->validate([
                'SearchTokenId' => 'required|string',
                'ResultIndex' => 'required|string',
            ]);

            $searchTokenId = $validated['SearchTokenId'];
            $resultIndex = $validated['ResultIndex'];

            // Check if this is an operator bus (ResultIndex starts with 'OP_')
            if (str_starts_with($resultIndex, 'OP_')) {
                return $this->handleOperatorBusSeatLayout($resultIndex, $searchTokenId);
            }

            // Create a unique cache key for this specific seat layout request.
            $cacheKey = "seat_layout_{$searchTokenId}_{$resultIndex}";
            $cacheDurationInMinutes = 60; // Cache for 1 hour.

            // OPTIMIZATION: Use Cache::remember to fetch from cache or execute the block.
            // This is the core of the performance improvement.
            $data = Cache::remember($cacheKey, $cacheDurationInMinutes * 60, function () use ($resultIndex, $searchTokenId, $cacheKey) {

                // This block only runs if the data is NOT in the cache.
                $response = getAPIBusSeats($resultIndex, $searchTokenId);

                if (!isset($response['Error']['ErrorCode']) || $response['Error']['ErrorCode'] != 0) {
                    $errorMessage = $response['Error']['ErrorMessage'] ?? 'Failed to retrieve seat layout from the provider.';
                    // By returning null, we prevent caching a failed API response.
                    // Throwing an exception is cleaner to handle it outside the cache block.
                    throw new \RuntimeException($errorMessage);
                }

                if (!isset($response['Result']['HTMLLayout'])) {
                    Log::error('API showSeat: Third-party API missing HTMLLayout', [
                        'result_keys' => array_keys($response['Result'] ?? [])
                    ]);
                    throw new \RuntimeException('HTMLLayout not found in API response');
                }

                $htmlLayout = $response['Result']['HTMLLayout'];

                // --- THIS IS THE SLOW OPERATION ---
                $parsedLayout = parseSeatHtmlToJson($htmlLayout); // Your existing slow helper is called here.

                return [
                    'html' => $parsedLayout,
                    'availableSeats' => $response['Result']['AvailableSeats']
                ];
            });

            return response()->json($data, 200);

        } catch (ValidationException $e) {
            Log::warning('API showSeat: Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json(['error' => 'Invalid input provided.', 'details' => $e->errors()], 422);
        } catch (\RuntimeException $e) {
            // This catches API errors from inside the cache block.
            Log::error('API showSeat: Runtime error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::critical('API showSeat: Critical error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'An unexpected server error occurred.'], 500);
        } finally {
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;
            Log::info(sprintf('API showSeat: Request-response cycle completed in %.2f ms.', $executionTime));
        }
    }

    /**
     * Handles final booking for operator buses.
     */
    private function bookOperatorBusTicket(string $userIp, string $resultIndex, string $boardingPointId, string $droppingPointId, array $passengers)
    {
        try {
            Log::info('Booking operator bus ticket', [
                'result_index' => $resultIndex,
                'boarding_point_id' => $boardingPointId,
                'dropping_point_id' => $droppingPointId,
                'passenger_count' => count($passengers)
            ]);

            // Extract operator bus ID from ResultIndex (OP_1 -> 1)
            $operatorBusId = (int) str_replace('OP_', '', $resultIndex);

            // Find the operator bus
            $operatorBus = \App\Models\OperatorBus::with(['activeSeatLayout', 'currentRoute'])->find($operatorBusId);

            if (!$operatorBus) {
                return [
                    'Error' => [
                        'ErrorCode' => 404,
                        'ErrorMessage' => 'Operator bus not found'
                    ]
                ];
            }

            // For operator buses, we'll simulate a successful booking
            // In a real implementation, you might want to:
            // 1. Create a permanent booking record
            // 2. Update seat availability
            // 3. Send confirmation emails/SMS
            // 4. Generate ticket details

            // Generate a mock booking ID for operator buses
            $bookingId = 'OP_BOOK_' . time() . '_' . $operatorBusId;

            // Mock response similar to third-party API
            $mockResult = [
                'BookingId' => $bookingId,
                'BookingStatus' => 'Confirmed',
                'TotalAmount' => 0, // Will be calculated later
                'Passenger' => array_map(function ($passenger, $index) {
                    return [
                        'LeadPassenger' => $index === 0,
                        'Title' => $passenger['Title'],
                        'FirstName' => $passenger['FirstName'],
                        'LastName' => $passenger['LastName'],
                        'Email' => $passenger['Email'],
                        'Phoneno' => $passenger['Phoneno'],
                        'Gender' => $passenger['Gender'],
                        'IdType' => $passenger['IdType'],
                        'IdNumber' => $passenger['IdNumber'],
                        'Address' => $passenger['Address'],
                        'Age' => $passenger['Age'],
                        'SeatName' => $passenger['SeatName'],
                        'Seat' => [
                            'Price' => [
                                'PublishedPrice' => 1000, // Mock price for operator bus
                                'OfferedPrice' => 900,    // Mock offered price
                                'BasePrice' => 800,       // Mock base price
                                'Tax' => 100,             // Mock tax
                                'OtherCharges' => 0,      // Mock other charges
                                'Discount' => 0,          // Mock discount
                                'ServiceCharges' => 0,    // Mock service charges
                                'TDS' => 0,               // Mock TDS
                                'GST' => [                // Mock GST
                                    'CGSTAmount' => 0,
                                    'CGSTRate' => 0,
                                    'IGSTAmount' => 0,
                                    'IGSTRate' => 0,
                                    'SGSTAmount' => 0,
                                    'SGSTRate' => 0,
                                    'TaxableAmount' => 0
                                ]
                            ]
                        ]
                    ];
                }, $passengers, array_keys($passengers)),
                'BoardingPointId' => $boardingPointId,
                'DroppingPointId' => $droppingPointId,
                'OperatorBusId' => $operatorBusId,
                'ResultIndex' => $resultIndex
            ];

            Log::info('Operator bus ticket booked successfully', [
                'booking_id' => $bookingId,
                'operator_bus_id' => $operatorBusId
            ]);

            return [
                'Result' => $mockResult
            ];

        } catch (\Exception $e) {
            Log::error('Error booking operator bus ticket:', [
                'result_index' => $resultIndex,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'Error' => [
                    'ErrorCode' => 500,
                    'ErrorMessage' => 'Failed to book operator bus ticket: ' . $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Handles seat blocking for operator buses.
     */
    private function blockOperatorBusSeat(string $resultIndex, string $boardingPointId, string $droppingPointId, array $passengers, array $seats, string $userIp)
    {
        try {
            Log::info('Blocking operator bus seat', [
                'result_index' => $resultIndex,
                'boarding_point_id' => $boardingPointId,
                'dropping_point_id' => $droppingPointId,
                'seats' => $seats,
                'passenger_count' => count($passengers)
            ]);

            // Extract operator bus ID from ResultIndex (OP_1 -> 1)
            $operatorBusId = (int) str_replace('OP_', '', $resultIndex);

            // Find the operator bus
            $operatorBus = \App\Models\OperatorBus::with(['activeSeatLayout', 'currentRoute'])->find($operatorBusId);

            if (!$operatorBus) {
                return [
                    'success' => false,
                    'message' => 'Operator bus not found',
                    'error' => 'Bus not found'
                ];
            }

            // For operator buses, we'll simulate a successful block
            // In a real implementation, you might want to:
            // 1. Check seat availability
            // 2. Create a temporary booking record
            // 3. Set a timeout for the booking
            // 4. Return booking details

            // Generate a mock booking ID for operator buses
            $bookingId = 'OP_BOOK_' . time() . '_' . $operatorBusId;

            // Mock response similar to third-party API
            $mockResult = [
                'BookingId' => $bookingId,
                'BookingStatus' => 'Confirmed',
                'TotalAmount' => 0, // Will be calculated later
                'BusType' => $operatorBus->bus_type ?? 'Operator Bus',
                'TravelName' => $operatorBus->travel_name ?? 'Operator Service',
                'DepartureTime' => '2025-10-23T17:30:00', // Mock departure time
                'ArrivalTime' => '2025-10-24T11:30:00',   // Mock arrival time
                'BoardingPointdetails' => [
                    [
                        'CityPointIndex' => 1,
                        'CityPointLocation' => 'Bus Stand Patna',
                        'CityPointName' => 'Bus Stand Patna',
                        'CityPointTime' => '2025-10-23T17:30:00'
                    ]
                ],
                'DroppingPointsdetails' => [
                    [
                        'CityPointIndex' => 1,
                        'CityPointLocation' => 'ISBT Kashmiri Gate',
                        'CityPointName' => 'ISBT Kashmiri Gate',
                        'CityPointTime' => '2025-10-24T11:30:00'
                    ]
                ],
                'Passenger' => array_map(function ($passenger, $index) use ($seats) {
                    return [
                        'LeadPassenger' => $index === 0,
                        'Title' => $passenger['Title'],
                        'FirstName' => $passenger['FirstName'],
                        'LastName' => $passenger['LastName'],
                        'Email' => $passenger['Email'],
                        'Phoneno' => $passenger['Phoneno'],
                        'Gender' => $passenger['Gender'],
                        'IdType' => $passenger['IdType'],
                        'IdNumber' => $passenger['IdNumber'],
                        'Address' => $passenger['Address'],
                        'Age' => $passenger['Age'],
                        'SeatName' => $passenger['SeatName'],
                        'Seat' => [
                            'Price' => [
                                'PublishedPrice' => 1000, // Mock price for operator bus
                                'OfferedPrice' => 900,    // Mock offered price
                                'BasePrice' => 800,       // Mock base price
                                'Tax' => 100,             // Mock tax
                                'OtherCharges' => 0,      // Mock other charges
                                'Discount' => 0,          // Mock discount
                                'ServiceCharges' => 0,    // Mock service charges
                                'TDS' => 0,               // Mock TDS
                                'GST' => [                // Mock GST
                                    'CGSTAmount' => 0,
                                    'CGSTRate' => 0,
                                    'IGSTAmount' => 0,
                                    'IGSTRate' => 0,
                                    'SGSTAmount' => 0,
                                    'SGSTRate' => 0,
                                    'TaxableAmount' => 0
                                ]
                            ]
                        ]
                    ];
                }, $passengers, array_keys($passengers)),
                'BoardingPointId' => $boardingPointId,
                'DroppingPointId' => $droppingPointId,
                'OperatorBusId' => $operatorBusId,
                'ResultIndex' => $resultIndex
            ];

            Log::info('Operator bus seat blocked successfully', [
                'booking_id' => $bookingId,
                'operator_bus_id' => $operatorBusId,
                'seats' => $seats
            ]);

            return [
                'success' => true,
                'Result' => $mockResult
            ];

        } catch (\Exception $e) {
            Log::error('Error blocking operator bus seat:', [
                'result_index' => $resultIndex,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to block operator bus seats',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handles seat layout requests for operator buses.
     */
    private function handleOperatorBusSeatLayout(string $resultIndex, string $searchTokenId)
    {
        try {
            Log::info('API handleOperatorBusSeatLayout: Starting processing', [
                'result_index' => $resultIndex,
                'search_token_id' => $searchTokenId,
                'is_operator_bus_request' => true
            ]);

            // Extract operator bus ID from ResultIndex (OP_1 -> 1)
            $operatorBusId = (int) str_replace('OP_', '', $resultIndex);

            Log::info('API handleOperatorBusSeatLayout: Extracted bus ID', [
                'operator_bus_id' => $operatorBusId,
                'original_result_index' => $resultIndex,
                'extraction_successful' => $operatorBusId > 0
            ]);

            if ($operatorBusId <= 0) {
                Log::error('API handleOperatorBusSeatLayout: Invalid bus ID extracted', [
                    'result_index' => $resultIndex,
                    'extracted_id' => $operatorBusId
                ]);
                return response()->json(['error' => 'Invalid operator bus ID in ResultIndex'], 400);
            }

            // Find the operator bus with error handling
            $operatorBus = \App\Models\OperatorBus::with(['activeSeatLayout'])->find($operatorBusId);

            Log::info('API handleOperatorBusSeatLayout: Database query result', [
                'operator_bus_found' => $operatorBus ? true : false,
                'operator_bus_id' => $operatorBusId,
                'query_executed' => true,
                'bus_details' => $operatorBus ? [
                    'id' => $operatorBus->id,
                    'travel_name' => $operatorBus->travel_name,
                    'bus_type' => $operatorBus->bus_type,
                    'status' => $operatorBus->status ?? 'unknown',
                    'has_active_seat_layout' => $operatorBus->activeSeatLayout ? true : false
                ] : null
            ]);

            if (!$operatorBus) {
                Log::error('API handleOperatorBusSeatLayout: Operator bus not found in database', [
                    'operator_bus_id' => $operatorBusId,
                    'result_index' => $resultIndex,
                    'possible_causes' => [
                        'Bus ID does not exist',
                        'Bus was deleted',
                        'Bus is inactive',
                        'Database connection issue'
                    ]
                ]);
                return response()->json([
                    'error' => 'Operator bus not found',
                    'bus_id' => $operatorBusId
                ], 404);
            }

            $seatLayout = $operatorBus->activeSeatLayout;

            Log::info('API handleOperatorBusSeatLayout: Seat layout validation', [
                'seat_layout_exists' => $seatLayout ? true : false,
                'has_html_layout' => $seatLayout && $seatLayout->html_layout ? true : false,
                'seat_layout_id' => $seatLayout ? $seatLayout->id : null,
                'layout_is_active' => $seatLayout ? $seatLayout->is_active : false,
                'html_layout_length' => $seatLayout && $seatLayout->html_layout ? strlen($seatLayout->html_layout) : 0,
                'total_seats' => $seatLayout ? $seatLayout->total_seats : 0
            ]);

            if (!$seatLayout || !$seatLayout->html_layout) {
                Log::error('API handleOperatorBusSeatLayout: No valid seat layout available', [
                    'operator_bus_id' => $operatorBusId,
                    'seat_layout_exists' => $seatLayout ? true : false,
                    'html_layout_exists' => $seatLayout && $seatLayout->html_layout ? true : false,
                    'layout_is_active' => $seatLayout ? $seatLayout->is_active : false,
                    'possible_causes' => [
                        'No seat layout assigned to bus',
                        'Seat layout HTML is empty',
                        'Seat layout is inactive',
                        'Seat layout was deleted'
                    ]
                ]);
                return response()->json([
                    'error' => 'No seat layout available for this bus',
                    'bus_id' => $operatorBusId,
                    'details' => 'Seat layout not configured or inactive'
                ], 404);
            }

            Log::info('API handleOperatorBusSeatLayout: Starting HTML layout parsing', [
                'html_layout_preview' => substr($seatLayout->html_layout, 0, 200) . '...',
                'parsing_function' => 'parseSeatHtmlToJson'
            ]);

            // Parse the HTML layout using the existing helper with error handling
            try {
                $parsedLayout = parseSeatHtmlToJson($seatLayout->html_layout);
            } catch (\Exception $parseException) {
                Log::error('API handleOperatorBusSeatLayout: HTML parsing failed', [
                    'operator_bus_id' => $operatorBusId,
                    'parse_error' => $parseException->getMessage(),
                    'html_length' => strlen($seatLayout->html_layout)
                ]);
                return response()->json([
                    'error' => 'Failed to parse seat layout',
                    'details' => 'Seat layout HTML format is invalid'
                ], 500);
            }

            Log::info('API handleOperatorBusSeatLayout: HTML layout parsed successfully', [
                'operator_bus_id' => $operatorBusId,
                'result_index' => $resultIndex,
                'parsed_layout_type' => gettype($parsedLayout),
                'parsed_layout_keys' => is_array($parsedLayout) ? array_keys($parsedLayout) : [],
                'available_seats' => $operatorBus->available_seats ?? $seatLayout->total_seats,
                'total_seats' => $seatLayout->total_seats,
                'parsing_successful' => true
            ]);

            $responseData = [
                'html' => $parsedLayout,
                'availableSeats' => $operatorBus->available_seats ?? $seatLayout->total_seats,
                'busDetails' => [
                    'busId' => $operatorBusId,
                    'travelName' => $operatorBus->travel_name,
                    'busType' => $operatorBus->bus_type,
                    'totalSeats' => $seatLayout->total_seats
                ]
            ];

            Log::info('API handleOperatorBusSeatLayout: Sending successful response', [
                'response_data_keys' => array_keys($responseData),
                'html_is_array' => is_array($responseData['html']),
                'available_seats' => $responseData['availableSeats'],
                'response_size_estimate' => strlen(json_encode($responseData)) . ' characters'
            ]);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::error('API handleOperatorBusSeatLayout: Exception caught', [
                'result_index' => $resultIndex,
                'search_token_id' => $searchTokenId,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to retrieve seat layout: ' . $e->getMessage()], 500);
        }
    }

    public function getCancellationPolicy(Request $request)
    {
        try {
            $request->validate([
                'CancelPolicy' => 'required|array',
            ]);
            Log::info('Cancellation policy', $request->CancelPolicy);
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

            // Check if this is an operator bus (ResultIndex starts with 'OP_')
            if (str_starts_with($ResultIndex, 'OP_')) {
                return $this->handleOperatorBusCounters($ResultIndex, $SearchTokenID);
            }

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

    /**
     * Handles boarding/dropping points requests for operator buses.
     */
    private function handleOperatorBusCounters(string $resultIndex, string $searchTokenId)
    {
        try {
            // Extract operator bus ID from ResultIndex (OP_1 -> 1)
            $operatorBusId = (int) str_replace('OP_', '', $resultIndex);

            // Find the operator bus with its route and boarding/dropping points
            $operatorBus = \App\Models\OperatorBus::with([
                'currentRoute.boardingPoints',
                'currentRoute.droppingPoints'
            ])->find($operatorBusId);

            if (!$operatorBus || !$operatorBus->currentRoute) {
                return response()->json(['error' => 'Operator bus or route not found'], 404);
            }

            $route = $operatorBus->currentRoute;

            // Transform boarding points to match API format
            $boardingPoints = $route->boardingPoints->map(function ($point) {
                return [
                    'CityPointIndex' => $point->id,
                    'CityPointName' => $point->point_name,
                    'CityPointLocation' => $point->address ?? $point->point_name,
                    'CityPointTime' => $point->departure_time,
                ];
            })->toArray();

            // Transform dropping points to match API format
            $droppingPoints = $route->droppingPoints->map(function ($point) {
                return [
                    'CityPointIndex' => $point->id,
                    'CityPointName' => $point->point_name,
                    'CityPointLocation' => $point->address ?? $point->point_name,
                    'CityPointTime' => $point->arrival_time,
                ];
            })->toArray();

            Log::info('Operator bus counters retrieved successfully', [
                'operator_bus_id' => $operatorBusId,
                'result_index' => $resultIndex,
                'boarding_points_count' => count($boardingPoints),
                'dropping_points_count' => count($droppingPoints)
            ]);

            return response()->json([
                'boarding_points' => $boardingPoints,
                'dropping_points' => $droppingPoints
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error handling operator bus counters:', [
                'result_index' => $resultIndex,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to retrieve boarding/dropping points'], 500);
        }
    }

    public function blockSeatApi(Request $request)
    {
        try {
            Log::info('BlockSeat API request received', [
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $request->validate([
                'OriginCity' => 'nullable',
                'DestinationCity' => 'nullable',
                'SearchTokenId' => 'required',
                'ResultIndex' => 'required',
                'UserIp' => 'nullable|string',
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

            // Prepare request data for BookingService
            $requestData = [
                'OriginCity' => $request->OriginCity ?? '',
                'DestinationCity' => $request->DestinationCity ?? "",
                'SearchTokenId' => $request->SearchTokenId,
                'ResultIndex' => $request->ResultIndex,
                'UserIp' => $request->UserIp ?? $request->ip(),
                'BoardingPointId' => $request->BoardingPointId,
                'DroppingPointId' => $request->DroppingPointId,
                'Seats' => $request->Seats,
                'FirstName' => $request->FirstName,
                'LastName' => $request->LastName,
                'Gender' => $request->Gender,
                'Email' => $request->Email,
                'Phoneno' => $request->Phoneno,
                'age' => $request->age ?? 0,
                'Address' => $request->Address ?? ''
            ];

            // Use BookingService to block seats and create payment order
            $result = $this->bookingService->blockSeatsAndCreateOrder($requestData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Seats blocked successfully! Proceed to payment.',
                    'ticket_id' => $result['ticket_id'],
                    'order_details' => $result['order_details'],
                    'order_id' => $result['order_id'],
                    'amount' => $result['amount'],
                    'currency' => $result['currency'],
                    'block_details' => $result['block_details'],
                    'cancellationPolicy' => $result['cancellation_policy']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to block seats',
                'error' => $result['error'] ?? null
            ], 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('BlockSeat API validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('BlockSeat API exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
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
            Log::info('Confirming payment for API booking', $request->all());

            $request->validate([
                'razorpay_payment_id' => 'required|string',
                'razorpay_order_id' => 'required|string',
                'razorpay_signature' => 'required|string',
                'ticket_id' => 'nullable|integer|exists:booked_tickets,id',
            ]);

            // Use BookingService to verify payment and complete booking
            $result = $this->bookingService->verifyPaymentAndCompleteBooking([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_signature' => $request->razorpay_signature,
                'ticket_id' => $request->ticket_id
            ]);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful. Ticket booked successfully.',
                    'ticket_id' => $result['ticket_id'],
                    'pnr' => $result['pnr'],
                    'status' => 201
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'cancelled' => $result['cancelled'] ?? false
            ], $result['cancelled'] ?? false ? 500 : 400);

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

    // TODO:Deprecated code nothing inside
    public function getCombinedBuses(Request $request)
    {
        // Your existing getCombinedBuses logic...
    }
}
