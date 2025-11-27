<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\City;
use App\Models\FleetType;
use App\Models\Schedule;
use App\Models\TicketPrice;
use App\Models\Trip;
use App\Models\VehicleRoute;
use App\Services\BusService;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

            // Store date_of_journey with searchTokenId for later retrieval
            // Generate a search token if not provided (for operator-only searches)
            $searchTokenId = $result['SearchTokenId'] ?? null;
            if (empty($searchTokenId)) {
                // Generate a unique token for operator-only searches
                $searchTokenId = hash('sha256', $validatedData['OriginId'] . '_' . $validatedData['DestinationId'] . '_' . $validatedData['DateOfJourney'] . '_' . time());
                $result['SearchTokenId'] = $searchTokenId;
            }

            // Store search metadata with searchTokenId
            Cache::put(
                'bus_search_results_' . $searchTokenId,
                [
                    'date_of_journey' => $validatedData['DateOfJourney'],
                    'origin_id' => $validatedData['OriginId'],
                    'destination_id' => $validatedData['DestinationId']
                ],
                now()->addMinutes(60) // Cache for 1 hour
            );

            Log::info('API ticketSearch: Stored search metadata', [
                'search_token_id' => $searchTokenId,
                'date_of_journey' => $validatedData['DateOfJourney'],
                'origin_id' => $validatedData['OriginId'],
                'destination_id' => $validatedData['DestinationId']
            ]);

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
                'DateOfJourney' => 'sometimes|date_format:Y-m-d', // Accept date as parameter
            ]);

            $searchTokenId = $validated['SearchTokenId'];
            $resultIndex = $validated['ResultIndex'];

            // Store DateOfJourney in request if provided, so getDateFromSearchToken can use it
            if (isset($validated['DateOfJourney'])) {
                $request->merge(['DateOfJourney' => $validated['DateOfJourney']]);
            }

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

            // Extract operator bus ID and schedule ID from ResultIndex (OP_{bus_id}_{schedule_id})
            $parts = explode('_', str_replace('OP_', '', $resultIndex));
            $operatorBusId = !empty($parts) ? (int) $parts[0] : 0;
            $scheduleId = count($parts) > 1 ? (int) end($parts) : null;

            Log::info('API handleOperatorBusSeatLayout: Extracted IDs', [
                'operator_bus_id' => $operatorBusId,
                'schedule_id' => $scheduleId,
                'original_result_index' => $resultIndex,
                'extraction_successful' => $operatorBusId > 0
            ]);

            if ($operatorBusId <= 0) {
                Log::error('API handleOperatorBusSeatLayout: Invalid bus ID extracted', [
                    'result_index' => $resultIndex,
                    'extracted_id' => $operatorBusId
                ]);
                return response()->json([
                    'Error' => [
                        'ErrorCode' => 400,
                        'ErrorMessage' => 'Invalid operator bus ID in ResultIndex'
                    ]
                ], 400);
            }

            // Get date from search token cache
            $dateOfJourney = $this->getDateFromSearchToken($searchTokenId);

            if (!$dateOfJourney) {
                Log::error('API handleOperatorBusSeatLayout: Could not extract date from search token', [
                    'search_token_id' => $searchTokenId
                ]);
                return response()->json([
                    'Error' => [
                        'ErrorCode' => 400,
                        'ErrorMessage' => 'Invalid or expired search token'
                    ]
                ], 400);
            }

            // Find the operator bus with schedule
            $operatorBus = \App\Models\OperatorBus::with(['activeSeatLayout'])->find($operatorBusId);

            if (!$operatorBus) {
                Log::error('API handleOperatorBusSeatLayout: Operator bus not found', [
                    'operator_bus_id' => $operatorBusId,
                    'result_index' => $resultIndex
                ]);
                return response()->json([
                    'Error' => [
                        'ErrorCode' => 404,
                        'ErrorMessage' => 'Operator bus not found'
                    ]
                ], 404);
            }

            $seatLayout = $operatorBus->activeSeatLayout;

            if (!$seatLayout || !$seatLayout->html_layout) {
                Log::error('API handleOperatorBusSeatLayout: No valid seat layout available', [
                    'operator_bus_id' => $operatorBusId
                ]);
                return response()->json([
                    'Error' => [
                        'ErrorCode' => 404,
                        'ErrorMessage' => 'No seat layout available for this bus'
                    ]
                ], 404);
            }

            // Get booked seats using SeatAvailabilityService
            $availabilityService = new \App\Services\SeatAvailabilityService();
            $bookedSeats = $availabilityService->getBookedSeats(
                $operatorBusId,
                $scheduleId ?? 0,
                $dateOfJourney,
                null, // boardingPointIndex - will be calculated for all segments
                null  // droppingPointIndex - will be calculated for all segments
            );

            Log::info('API handleOperatorBusSeatLayout: Booked seats calculated', [
                'operator_bus_id' => $operatorBusId,
                'schedule_id' => $scheduleId,
                'date_of_journey' => $dateOfJourney,
                'booked_seats_count' => count($bookedSeats),
                'booked_seats' => $bookedSeats
            ]);

            // Modify HTML on-the-fly: change nseat→bseat, hseat→bhseat, vseat→bvseat
            $modifiedHtml = $this->modifyHtmlLayoutForBookedSeats($seatLayout->html_layout, $bookedSeats);

            // Parse the modified HTML layout to match third-party API response format
            $parsedLayout = parseSeatHtmlToJson($modifiedHtml);

            // Calculate available seats count
            $availableSeatsCount = $seatLayout->total_seats - count($bookedSeats);

            // Return response in the SAME format as third-party buses for consistency
            // This matches what the React Native app expects
            $responseData = [
                'html' => $parsedLayout,
                'availableSeats' => (string) max(0, $availableSeatsCount)
            ];

            Log::info('API handleOperatorBusSeatLayout: Response built successfully', [
                'available_seats' => $responseData['availableSeats'],
                'booked_seats_count' => count($bookedSeats),
                'total_seats' => $seatLayout->total_seats,
                'parsed_layout_upper_rows' => count($parsedLayout['seat']['upper_deck']['rows'] ?? []),
                'parsed_layout_lower_rows' => count($parsedLayout['seat']['lower_deck']['rows'] ?? [])
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

            return response()->json([
                'Error' => [
                    'ErrorCode' => 500,
                    'ErrorMessage' => 'Failed to retrieve seat layout: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get date from search token cache or request
     */
    private function getDateFromSearchToken(string $searchTokenId): ?string
    {
        // Priority 1: Try to get from request first (if passed as parameter)
        $request = request();
        if ($request->has('DateOfJourney')) {
            $date = $request->input('DateOfJourney');
            Log::info('API getDateFromSearchToken: Using DateOfJourney from request', [
                'search_token_id' => $searchTokenId,
                'date' => $date
            ]);
            return $this->normalizeDate($date);
        }
        if ($request->has('date_of_journey')) {
            $date = $request->input('date_of_journey');
            Log::info('API getDateFromSearchToken: Using date_of_journey from request', [
                'search_token_id' => $searchTokenId,
                'date' => $date
            ]);
            return $this->normalizeDate($date);
        }

        // Priority 2: Try to get from cache (stored when searching)
        $cachedBuses = \Illuminate\Support\Facades\Cache::get('bus_search_results_' . $searchTokenId);
        if ($cachedBuses && isset($cachedBuses['date_of_journey'])) {
            Log::info('API getDateFromSearchToken: Using date from cache', [
                'search_token_id' => $searchTokenId,
                'date' => $cachedBuses['date_of_journey']
            ]);
            return $this->normalizeDate($cachedBuses['date_of_journey']);
        }

        // Priority 3: Try session (for web requests)
        if (session()->has('date_of_journey')) {
            $date = session()->get('date_of_journey');
            Log::info('API getDateFromSearchToken: Using date from session', [
                'search_token_id' => $searchTokenId,
                'date' => $date
            ]);
            return $this->normalizeDate($date);
        }

        // Priority 4: Try to extract from cache key pattern
        // The cache key pattern is: bus_search:{origin}_{destination}_{date}
        // We'll try to find a matching cache key
        try {
            $cachePrefix = 'bus_search:';
            // Note: Laravel cache doesn't support wildcard search easily
            // For now, we'll skip this and use fallback
        } catch (\Exception $e) {
            // Ignore cache key search errors
        }

        // Last resort: log warning and use today's date
        Log::warning('API handleOperatorBusSeatLayout: Could not extract date, using today', [
            'search_token_id' => $searchTokenId,
            'cache_exists' => $cachedBuses !== null,
            'cache_keys' => $cachedBuses ? array_keys($cachedBuses) : []
        ]);

        return now()->format('Y-m-d');
    }

    /**
     * Normalize date to Y-m-d format
     */
    private function normalizeDate(?string $date): string
    {
        if (!$date) {
            return now()->format('Y-m-d');
        }

        // Already in Y-m-d format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Try m/d/Y format (from session)
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date)) {
            try {
                return \Carbon\Carbon::createFromFormat('m/d/Y', $date)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('API: Failed to parse date (m/d/Y)', ['date' => $date, 'error' => $e->getMessage()]);
            }
        }

        // Try Carbon's flexible parsing
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('API: Failed to parse date', ['date' => $date, 'error' => $e->getMessage()]);
            return now()->format('Y-m-d');
        }
    }

    /**
     * Modify HTML layout to mark booked seats
     */
    private function modifyHtmlLayoutForBookedSeats(string $htmlLayout, array $bookedSeats): string
    {
        if (empty($bookedSeats)) {
            return $htmlLayout; // No modifications needed
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $htmlLayout, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($dom);

        foreach ($bookedSeats as $seatName) {
            // CRITICAL FIX: Match by @id attribute, not text content or onclick
            // This prevents "1" from matching "U1", "11", "21", etc.
            // Seat IDs are stored in the id attribute: <div id="U1" class="nseat"> or <div id="1" class="nseat">
            $nodes = $xpath->query("//*[@id='{$seatName}' and (contains(@class, 'nseat') or contains(@class, 'hseat') or contains(@class, 'vseat'))]");

            foreach ($nodes as $node) {
                $class = $node->getAttribute('class');
                // Replace nseat with bseat, hseat with bhseat, vseat with bvseat
                $class = str_replace(['nseat', 'hseat', 'vseat'], ['bseat', 'bhseat', 'bvseat'], $class);
                $node->setAttribute('class', $class);
            }
        }

        return $dom->saveHTML();
    }

    /**
     * Build SeatLayout structure matching third-party API format
     */
    private function buildSeatLayoutStructure($seatLayout, array $bookedSeats, $operatorBus): array
    {
        // Parse the HTML layout to get seat details
        $parsedLayout = parseSeatHtmlToJson($seatLayout->html_layout);

        // Build SeatLayout structure
        $seatDetails = [];
        $maxColumns = 0;
        $maxRows = 0;

        // Process upper deck
        if (isset($parsedLayout['seat']['upper_deck']['rows']) && is_array($parsedLayout['seat']['upper_deck']['rows'])) {
            foreach ($parsedLayout['seat']['upper_deck']['rows'] as $rowNum => $rowSeats) {
                if (!is_array($rowSeats)) {
                    continue;
                }

                $rowSeatDetails = [];
                foreach ($rowSeats as $seat) {
                    // Validate seat structure
                    if (!is_array($seat) || empty($seat['seat_id'])) {
                        Log::warning('API buildSeatLayoutStructure: Invalid seat structure in upper deck', [
                            'seat' => $seat,
                            'row_num' => $rowNum
                        ]);
                        continue;
                    }

                    $seatName = $seat['seat_id'];
                    $isBooked = in_array($seatName, $bookedSeats);

                    try {
                        $seatDetail = $this->buildSeatDetail($seat, $seatName, $isBooked, true, $operatorBus);

                        // Validate seat detail structure
                        if (is_array($seatDetail) && !empty($seatDetail['SeatName'])) {
                            $rowSeatDetails[] = $seatDetail;
                        } else {
                            Log::warning('API buildSeatLayoutStructure: Invalid seat detail returned', [
                                'seat_name' => $seatName,
                                'seat_detail' => $seatDetail
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('API buildSeatLayoutStructure: Error building seat detail', [
                            'seat_name' => $seatName,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        continue;
                    }
                }

                if (!empty($rowSeatDetails)) {
                    $seatDetails[] = $rowSeatDetails;
                    $maxRows = max($maxRows, $rowNum + 1);
                    $maxColumns = max($maxColumns, count($rowSeatDetails));
                }
            }
        }

        // Process lower deck
        if (isset($parsedLayout['seat']['lower_deck']['rows']) && is_array($parsedLayout['seat']['lower_deck']['rows'])) {
            foreach ($parsedLayout['seat']['lower_deck']['rows'] as $rowNum => $rowSeats) {
                if (!is_array($rowSeats)) {
                    continue;
                }

                $rowSeatDetails = [];
                foreach ($rowSeats as $seat) {
                    // Validate seat structure
                    if (!is_array($seat) || empty($seat['seat_id'])) {
                        Log::warning('API buildSeatLayoutStructure: Invalid seat structure in lower deck', [
                            'seat' => $seat,
                            'row_num' => $rowNum
                        ]);
                        continue;
                    }

                    $seatName = $seat['seat_id'];
                    $isBooked = in_array($seatName, $bookedSeats);

                    try {
                        $seatDetail = $this->buildSeatDetail($seat, $seatName, $isBooked, false, $operatorBus);

                        // Validate seat detail structure
                        if (is_array($seatDetail) && !empty($seatDetail['SeatName'])) {
                            $rowSeatDetails[] = $seatDetail;
                        } else {
                            Log::warning('API buildSeatLayoutStructure: Invalid seat detail returned', [
                                'seat_name' => $seatName,
                                'seat_detail' => $seatDetail
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('API buildSeatLayoutStructure: Error building seat detail', [
                            'seat_name' => $seatName,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        continue;
                    }
                }

                if (!empty($rowSeatDetails)) {
                    $seatDetails[] = $rowSeatDetails;
                    $maxRows = max($maxRows, $rowNum + 1);
                    $maxColumns = max($maxColumns, count($rowSeatDetails));
                }
            }
        }

        // Ensure NoOfColumns is at least 1 if we have seats
        if ($maxColumns === 0 && !empty($seatDetails)) {
            $maxColumns = 1;
        }

        Log::info('API buildSeatLayoutStructure: Completed', [
            'total_rows' => $maxRows,
            'max_columns' => $maxColumns,
            'total_seat_details_rows' => count($seatDetails)
        ]);

        return [
            'NoOfColumns' => $maxColumns,
            'NoOfRows' => $maxRows,
            'SeatDetails' => $seatDetails
        ];
    }

    /**
     * Build individual seat detail matching third-party API format
     */
    private function buildSeatDetail(array $seat, string $seatName, bool $isBooked, bool $isUpper, $operatorBus): array
    {
        // Ensure seatName is not empty
        if (empty($seatName)) {
            $seatName = $seat['seat_id'] ?? 'UNKNOWN';
        }

        $seatType = $seat['type'] ?? 'nseat';
        $price = $seat['price'] ?? ($operatorBus->base_price ?? 0);

        // Determine SeatType: 1 = seater, 2 = sleeper
        $seatTypeCode = (strpos($seatType, 'hseat') !== false || strpos($seatType, 'vseat') !== false) ? 2 : 1;

        // Determine Height: 1 = single, 2 = double
        $height = (strpos($seatType, 'hseat') !== false || strpos($seatType, 'vseat') !== false) ? 2 : 1;

        // Calculate column and row numbers - use 0-based index if not provided
        $columnIndex = isset($seat['column']) ? (int) $seat['column'] : 0;
        $rowIndex = isset($seat['row']) ? (int) $seat['row'] : 0;

        // For SeatIndex, try to extract from seat name or use a sequential index
        $seatIndex = isset($seat['seat_index']) ? (int) $seat['seat_index'] : 0;
        if ($seatIndex === 0 && preg_match('/\d+$/', $seatName, $matches)) {
            $seatIndex = (int) $matches[0];
        }

        $columnNo = str_pad($columnIndex, 3, '0', STR_PAD_LEFT);
        $rowNo = str_pad($rowIndex, 3, '0', STR_PAD_LEFT);

        // Build price structure matching third-party API
        $basePrice = (float) $price;
        $offeredPrice = max(0, $basePrice * 0.95); // 5% discount (adjust as needed)
        $agentCommission = max(0, $basePrice * 0.05); // 5% commission (adjust as needed)
        $tds = max(0, $agentCommission * 0.05); // 5% TDS on commission
        $igstAmount = 0; // Adjust based on your tax logic
        $igstRate = 18; // Adjust based on your tax logic

        // Ensure all required fields are present and valid
        return [
            'ColumnNo' => $columnNo,
            'Height' => (int) $height,
            'IsLadiesSeat' => false,
            'IsMalesSeat' => false,
            'IsUpper' => (bool) $isUpper,
            'RowNo' => $rowNo,
            'SeatFare' => round($basePrice, 2),
            'SeatIndex' => (int) $seatIndex,
            'SeatName' => (string) $seatName,
            'SeatStatus' => !$isBooked, // true = available, false = booked
            'SeatType' => (int) $seatTypeCode,
            'Width' => 1,
            'Price' => [
                'BasePrice' => round($basePrice, 2),
                'Tax' => 0,
                'OtherCharges' => 0,
                'Discount' => 0,
                'PublishedPrice' => round($basePrice, 2),
                'OfferedPrice' => round($offeredPrice, 2),
                'AgentCommission' => round($agentCommission, 2),
                'ServiceCharges' => 0,
                'TDS' => round($tds, 2),
                'GST' => [
                    'CGSTAmount' => 0,
                    'CGSTRate' => 0,
                    'IGSTAmount' => (float) $igstAmount,
                    'IGSTRate' => (int) $igstRate,
                    'SGSTAmount' => 0,
                    'SGSTRate' => 0,
                    'TaxableAmount' => 0
                ]
            ]
        ];
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
            // Extract operator bus ID and schedule ID from ResultIndex (OP_{bus_id}_{schedule_id})
            $parts = explode('_', str_replace('OP_', '', $resultIndex));
            $operatorBusId = !empty($parts) ? (int) $parts[0] : 0;
            $scheduleId = count($parts) > 1 ? (int) end($parts) : null;

            Log::info('API handleOperatorBusCounters: Processing', [
                'result_index' => $resultIndex,
                'operator_bus_id' => $operatorBusId,
                'schedule_id' => $scheduleId
            ]);

            // Get date of journey from cache if available (same format as ticketSearch)
            $dateOfJourney = now()->format('Y-m-d');
            if ($searchTokenId) {
                $cachedData = Cache::get('bus_search_results_' . $searchTokenId);
                if ($cachedData && isset($cachedData['date_of_journey'])) {
                    $dateOfJourney = $cachedData['date_of_journey'];
                    // Normalize date format
                    try {
                        $dateOfJourney = \Carbon\Carbon::parse($dateOfJourney)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Keep original if parsing fails
                    }
                }
            }

            // If scheduleId is present, use schedule-specific points
            if ($scheduleId) {
                $schedule = \App\Models\BusSchedule::with([
                    'operatorRoute.boardingPoints',
                    'operatorRoute.droppingPoints',
                    'boardingPoints',
                    'droppingPoints'
                ])->find($scheduleId);

                if (!$schedule || !$schedule->operatorRoute) {
                    return response()->json(['error' => 'Operator schedule or route not found'], 404);
                }

                $route = $schedule->operatorRoute;

                // Get boarding/dropping points - Priority: Schedule-specific > Route-level
                $boardingPointsCollection = $schedule->boardingPoints()->where('status', 1)->orderBy('point_index')->get();
                if ($boardingPointsCollection->isEmpty()) {
                    $boardingPointsCollection = $route->boardingPoints()->where('status', 1)->orderBy('point_index')->get();
                }

                $droppingPointsCollection = $schedule->droppingPoints()->where('status', 1)->orderBy('point_index')->get();
                if ($droppingPointsCollection->isEmpty()) {
                    $droppingPointsCollection = $route->droppingPoints()->where('status', 1)->orderBy('point_index')->get();
                }
            } else {
                // Legacy path: fall back to bus currentRoute
                $operatorBus = \App\Models\OperatorBus::with([
                    'currentRoute.boardingPoints',
                    'currentRoute.droppingPoints'
                ])->find($operatorBusId);

                if (!$operatorBus || !$operatorBus->currentRoute) {
                    return response()->json(['error' => 'Operator bus or route not found'], 404);
                }

                $route = $operatorBus->currentRoute;

                // Use route-level points for legacy
                $boardingPointsCollection = $route->boardingPoints()->where('status', 1)->orderBy('point_index')->get();
                $droppingPointsCollection = $route->droppingPoints()->where('status', 1)->orderBy('point_index')->get();
            }

            // Transform boarding points to match third-party API format exactly
            $boardingPoints = $boardingPointsCollection->map(function ($point) use ($dateOfJourney) {
                // Format time: combine date with point_time (H:i format)
                $timeString = null;
                if ($point->point_time) {
                    try {
                        $time = \Carbon\Carbon::parse($point->point_time)->format('H:i:s');
                        $timeString = "{$dateOfJourney}T{$time}";
                    } catch (\Exception $e) {
                        // If point_time is null or invalid, use default time
                        $timeString = "{$dateOfJourney}T00:00:00";
                    }
                } else {
                    $timeString = "{$dateOfJourney}T00:00:00";
                }

                return [
                    'CityPointIndex' => $point->point_index ?? $point->id,
                    'CityPointName' => $point->point_name,
                    'CityPointLocation' => $point->point_location ?? $point->point_address ?? $point->point_name,
                    'CityPointAddress' => $point->point_address ?? $point->point_location ?? $point->point_name,
                    'CityPointLandmark' => $point->point_landmark ?? '',
                    'CityPointContactNumber' => $point->contact_number ?? '',
                    'CityPointTime' => $timeString,
                ];
            })->toArray();

            // Transform dropping points to match third-party API format exactly
            $droppingPoints = $droppingPointsCollection->map(function ($point) use ($dateOfJourney) {
                // Format time: combine date with point_time (H:i format)
                $timeString = null;
                if ($point->point_time) {
                    try {
                        $time = \Carbon\Carbon::parse($point->point_time)->format('H:i:s');
                        $timeString = "{$dateOfJourney}T{$time}";
                    } catch (\Exception $e) {
                        // If point_time is null or invalid, use default time
                        $timeString = "{$dateOfJourney}T00:00:00";
                    }
                } else {
                    $timeString = "{$dateOfJourney}T00:00:00";
                }

                return [
                    'CityPointIndex' => $point->point_index ?? $point->id,
                    'CityPointName' => $point->point_name,
                    'CityPointLocation' => $point->point_location ?? $point->point_address ?? $point->point_name,
                    'CityPointAddress' => $point->point_address ?? $point->point_location ?? $point->point_name,
                    'CityPointLandmark' => $point->point_landmark ?? '',
                    'CityPointContactNumber' => $point->contact_number ?? '',
                    'CityPointTime' => $timeString,
                ];
            })->toArray();

            Log::info('API handleOperatorBusCounters: Retrieved successfully', [
                'operator_bus_id' => $operatorBusId,
                'schedule_id' => $scheduleId,
                'result_index' => $resultIndex,
                'date_of_journey' => $dateOfJourney,
                'boarding_points_count' => count($boardingPoints),
                'dropping_points_count' => count($droppingPoints),
                'using_schedule_specific' => $scheduleId ? true : false
            ]);

            return response()->json([
                'boarding_points' => $boardingPoints,
                'dropping_points' => $droppingPoints
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error handling operator bus counters:', [
                'result_index' => $resultIndex,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                'Gender' => 'required|in:0,1,2',
                'Email' => 'required|email',
                'Phoneno' => 'required',
                'age' => 'nullable|integer',
            ]);

            // Get DateOfJourney from cache using SearchTokenId (same as getCounters)
            $dateOfJourney = $request->DateOfJourney ?? null;
            if (!$dateOfJourney && $request->SearchTokenId) {
                $cachedData = Cache::get('bus_search_results_' . $request->SearchTokenId);
                if ($cachedData && isset($cachedData['date_of_journey'])) {
                    $dateOfJourney = $cachedData['date_of_journey'];
                    // Normalize date format
                    try {
                        $dateOfJourney = \Carbon\Carbon::parse($dateOfJourney)->format('Y-m-d');
                    } catch (\Exception $e) {
                        Log::warning('BlockSeat API: Failed to parse date from cache', [
                            'date' => $cachedData['date_of_journey'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

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
                'Address' => $request->Address ?? '',
                'DateOfJourney' => $dateOfJourney // Add DateOfJourney to request data
            ];

            Log::info('BlockSeat API: Prepared request data with DateOfJourney', [
                'date_of_journey' => $dateOfJourney,
                'search_token_id' => $request->SearchTokenId,
                'has_cached_data' => !empty($cachedData)
            ]);

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
                // Fetch the complete ticket details to build block_details for mobile app
                $bookedTicket = BookedTicket::find($result['ticket_id']);

                if (!$bookedTicket) {
                    Log::error('API confirmPayment: Ticket not found after payment confirmation', [
                        'ticket_id' => $result['ticket_id']
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Ticket not found after payment confirmation'
                    ], 404);
                }

                // Build block_details matching mobile app expectations
                $blockDetails = $this->buildBlockDetailsForMobile($bookedTicket);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful. Ticket booked successfully.',
                    'ticket_id' => $result['ticket_id'],
                    'pnr' => $result['pnr'],
                    'block_details' => $blockDetails,
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
            Log::error('API confirmPayment: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build block_details object for mobile app compatibility
     */
    private function buildBlockDetailsForMobile(BookedTicket $bookedTicket): array
    {
        // Decode boarding and dropping point details
        $boardingDetails = json_decode($bookedTicket->boarding_point_details, true);
        $droppingDetails = json_decode($bookedTicket->dropping_point_details, true);

        // Format boarding details as string (mobile app expects string)
        $boardingDetailsString = 'Not Available';
        if ($boardingDetails) {
            // Handle both array of points and single point object
            $boardingPoint = is_array($boardingDetails) && isset($boardingDetails[0])
                ? $boardingDetails[0]
                : $boardingDetails;

            if (is_array($boardingPoint)) {
                $boardingDetailsString = ($boardingPoint['CityPointName'] ?? '') .
                    (isset($boardingPoint['CityPointLocation']) && $boardingPoint['CityPointLocation'] !== ($boardingPoint['CityPointName'] ?? '')
                        ? ', ' . $boardingPoint['CityPointLocation']
                        : '');
            }
        }

        // Format dropping details as string (mobile app expects string)
        $dropOffDetailsString = 'Not Available';
        if ($droppingDetails) {
            // Handle both array of points and single point object
            $droppingPoint = is_array($droppingDetails) && isset($droppingDetails[0])
                ? $droppingDetails[0]
                : $droppingDetails;

            if (is_array($droppingPoint)) {
                $dropOffDetailsString = ($droppingPoint['CityPointName'] ?? '') .
                    (isset($droppingPoint['CityPointLocation']) && $droppingPoint['CityPointLocation'] !== ($droppingPoint['CityPointName'] ?? '')
                        ? ', ' . $droppingPoint['CityPointLocation']
                        : '');
            }
        }

        // Get seats as array
        $seats = [];
        if (is_array($bookedTicket->seats)) {
            $seats = $bookedTicket->seats;
        } elseif (is_string($bookedTicket->seats) && !empty($bookedTicket->seats)) {
            // Try to decode if it's a JSON string
            $decoded = json_decode($bookedTicket->seats, true);
            $seats = is_array($decoded) ? $decoded : explode(',', $bookedTicket->seats);
        }

        // Get passenger name (first passenger if multiple)
        $passengerName = $bookedTicket->passenger_name ?? 'Guest';
        if (is_array($bookedTicket->passenger_names) && !empty($bookedTicket->passenger_names)) {
            $passengerName = $bookedTicket->passenger_names[0];
        }

        // Format date of journey
        $dateOfJourney = $bookedTicket->date_of_journey;
        if ($dateOfJourney) {
            try {
                $dateOfJourney = Carbon::parse($dateOfJourney)->format('Y-m-d');
            } catch (\Exception $e) {
                // Keep original format if parsing fails
            }
        }

        // Get booking_id (api_booking_id for third-party, operator_booking_id or booking_id for operator)
        $bookingId = $bookedTicket->api_booking_id
            ?? $bookedTicket->booking_id;

        return [
            'boarding_details' => $boardingDetailsString,
            'date_of_journey' => $dateOfJourney,
            'drop_off_details' => $dropOffDetailsString,
            'passenger_name' => $passengerName,
            'pnr' => $bookedTicket->pnr_number,
            'seats' => $seats,
            'booking_id' => $bookingId,
            'UserIp' => $this->getUserIpFromTicket($bookedTicket),
            'SearchTokenId' => $bookedTicket->search_token_id ?? '',
        ];
    }

    /**
     * Get UserIp from ticket (from api_response or request)
     */
    private function getUserIpFromTicket(BookedTicket $bookedTicket): string
    {
        // Try to get from api_response first
        if ($bookedTicket->api_response) {
            $apiResponse = json_decode($bookedTicket->api_response, true);
            if (is_array($apiResponse) && isset($apiResponse['UserIp'])) {
                return $apiResponse['UserIp'];
            }
        }

        // Fallback to request IP
        return request()->ip();
    }

    /**
     * Cancel ticket API endpoint
     * Handles cancellation for both third-party and operator buses
     */
    public function cancelTicketApi(Request $request)
    {
        try {
            Log::info('CancelTicket API request received', [
                'request_data' => $request->all()
            ]);

            $request->validate([
                'UserIp' => 'nullable|string',
                'SearchTokenId' => 'required|string',
                'BookingId' => 'required',
                'SeatId' => 'required|string',
                'Remarks' => 'nullable|string|max:500',
            ]);

            // Use BookingService to cancel the ticket
            $result = $this->bookingService->cancelTicket([
                'UserIp' => $request->UserIp ?? request()->ip(),
                'SearchTokenId' => $request->SearchTokenId,
                'BookingId' => $request->BookingId,
                'SeatId' => $request->SeatId,
                'Remarks' => $request->Remarks ?? 'Cancelled by customer',
            ]);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'] ?? 'Ticket cancelled successfully',
                    'ticket_id' => $result['ticket_id'] ?? null,
                    'cancellation_details' => $result['cancellation_details'] ?? null,
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to cancel ticket',
                'error' => $result['error'] ?? null,
            ], $result['status_code'] ?? 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('CancelTicket API validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('CancelTicket API exception', [
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

    /**
     * Get pricing configuration for mobile app
     * Returns markup, service charge, platform fee, and GST percentages
     */
    public function getPricingConfig()
    {
        try {
            $general = \App\Models\GeneralSetting::first();

            // Get markup percentage (active markup from settings)
            $markupPercentage = $general->markup_percentage ?? 0;

            // Get service charge percentage (default 2%)
            $serviceChargePercentage = $general->service_charge_percentage ?? 2;

            // Get platform fee (flat fee, default ₹5)
            $platformFee = $general->platform_fee ?? 5;

            // Get GST percentage (default 5%)
            $gstPercentage = $general->gst_percentage ?? 5;

            return response()->json([
                'success' => true,
                'data' => [
                    'markup_percentage' => (float) $markupPercentage,
                    'service_charge_percentage' => (float) $serviceChargePercentage,
                    'platform_fee' => (float) $platformFee,
                    'gst_percentage' => (float) $gstPercentage,
                    'currency' => '₹',
                    'currency_code' => 'INR'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get pricing config error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pricing configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
