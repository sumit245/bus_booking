<?php

namespace App\Services;

use App\Models\BookedTicket;
use App\Models\User;
use App\Models\GeneralSetting;
use App\Models\City;
use App\Models\OperatorBus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Razorpay\Api\Api;

class BookingService
{
    /**
     * Block seats and create payment order
     */
    public function blockSeatsAndCreateOrder(array $requestData)
    {
        try {
            Log::info('BookingService: Blocking seats and creating payment order', $requestData);

            // Register or log in the user
            $user = $this->registerOrLoginUser($requestData);

            // Prepare passenger data
            $passengers = $this->preparePassengerData($requestData);

            // Block seats
            $blockResponse = $this->blockSeats($requestData, $passengers);

            if (!$blockResponse['success']) {
                return [
                    'success' => false,
                    'message' => $blockResponse['message'] ?? 'Failed to block seats',
                    'error' => $blockResponse['error'] ?? null
                ];
            }

            // Calculate base fare (before fees)
            $baseFare = $this->calculateTotalFare($blockResponse['Result']);

            // Create pending ticket record (will calculate fees and total_amount internally)
            $bookedTicket = $this->createPendingTicket($requestData, $blockResponse, $baseFare, $user->id);

            // Create Razorpay order using the calculated total_amount from ticket
            $razorpayOrder = $this->createRazorpayOrder($bookedTicket, $bookedTicket->total_amount ?? $baseFare);

            // Cache booking data for payment verification
            $this->cacheBookingData($bookedTicket->id, $requestData, $blockResponse);

            return [
                'success' => true,
                'ticket_id' => $bookedTicket->id,
                'order_details' => $razorpayOrder,
                'order_id' => $razorpayOrder->id,
                'amount' => $bookedTicket->total_amount ?? $baseFare,
                'currency' => 'INR',
                'block_details' => $blockResponse['Result'],
                'cancellation_policy' => $this->formatCancellationPolicy($blockResponse['Result']['CancelPolicy'] ?? [])
            ];

        } catch (\Exception $e) {
            Log::error('BookingService: Error in blockSeatsAndCreateOrder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process booking: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment and complete booking
     */
    public function verifyPaymentAndCompleteBooking(array $paymentData)
    {
        try {
            Log::info('BookingService: Verifying payment and completing booking', $paymentData);

            // Verify Razorpay payment signature
            $this->verifyRazorpaySignature($paymentData);

            // Get the pending ticket
            $bookedTicket = BookedTicket::findOrFail($paymentData['ticket_id']);

            // Get cached booking data
            $bookingData = Cache::get('booking_data_' . $bookedTicket->id);
            Log::info('BookingService: Retrieved cached booking data', ['booking_data' => $bookingData]);
            if (!$bookingData) {
                return [
                    'success' => false,
                    'message' => 'Booking session expired. Please try again.'
                ];
            }
            
            // Ensure ticket_id is in booking data for operator bus bookings
            $bookingData['ticket_id'] = $bookedTicket->id;

            // Complete the booking via API
            $apiResponse = $this->completeBooking($bookingData);

            if (isset($apiResponse['Error']) && $apiResponse['Error']['ErrorCode'] != 0) {
                // Booking failed - update ticket status
                $bookedTicket->update([
                    'status' => 3, // Rejected
                    'api_response' => json_encode($apiResponse)
                ]);

                return [
                    'success' => false,
                    'message' => $apiResponse['Error']['ErrorMessage'] ?? 'Booking failed at operator end'
                ];
            }

            // Update ticket with booking details
            $this->updateTicketWithBookingDetails($bookedTicket, $apiResponse, $bookingData);

            // Send WhatsApp notifications
            $whatsappSuccess = $this->sendWhatsAppNotifications($bookedTicket, $apiResponse, $bookingData);

            // If WhatsApp fails, cancel the booking
            if (!$whatsappSuccess) {
                $this->cancelBookingDueToNotificationFailure($bookedTicket, $apiResponse, $bookingData);
                return [
                    'success' => false,
                    'message' => 'Booking cancelled due to notification failure. Please try again.',
                    'cancelled' => true
                ];
            }

            // Clean up cache
            Cache::forget('booking_data_' . $bookedTicket->id);

            return [
                'success' => true,
                'message' => 'Booking completed successfully',
                'ticket_id' => $bookedTicket->id,
                'pnr' => $bookedTicket->pnr_number
            ];

        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            Log::error('BookingService: Payment signature verification failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('BookingService: Error in verifyPaymentAndCompleteBooking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to complete booking: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Register or login user
     */
    private function registerOrLoginUser(array $requestData)
    {
        if (!Auth::check()) {
            $fullPhone = $requestData['Phoneno'] ?? $requestData['passenger_phone'];

            // Normalize phone number
            if (strpos($fullPhone, '+91') === 0) {
                $fullPhone = substr($fullPhone, 3);
            } elseif (strpos($fullPhone, '91') === 0 && strlen($fullPhone) > 10) {
                $fullPhone = substr($fullPhone, 2);
            }
            $fullPhone = '91' . $fullPhone;

            // Handle firstname and lastname - support both single passenger and multiple passengers (agent/admin)
            $firstName = $requestData['FirstName'] 
                ?? (isset($requestData['passenger_firstnames']) && is_array($requestData['passenger_firstnames']) 
                    ? ($requestData['passenger_firstnames'][0] ?? '') 
                    : ($requestData['passenger_firstname'] ?? ''));
            
            $lastName = $requestData['LastName'] 
                ?? (isset($requestData['passenger_lastnames']) && is_array($requestData['passenger_lastnames']) 
                    ? ($requestData['passenger_lastnames'][0] ?? '') 
                    : ($requestData['passenger_lastname'] ?? ''));

            $user = User::firstOrCreate(
                ['mobile' => $fullPhone],
                [
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'email' => $requestData['Email'] ?? $requestData['passenger_email'],
                    'username' => 'user' . time(),
                    'password' => Hash::make(Str::random(8)),
                    'country_code' => '91',
                    'address' => [
                        'address' => $requestData['Address'] ?? $requestData['passenger_address'] ?? '',
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
            return $user;
        }

        return Auth::user();
    }

    /**
     * Prepare passenger data
     */
    private function preparePassengerData(array $requestData)
    {
        $seats = is_array($requestData['Seats'] ?? $requestData['seats'])
            ? $requestData['Seats'] ?? $requestData['seats']
            : explode(',', $requestData['Seats'] ?? $requestData['seats']);

        // Check if this is an agent booking with multiple passengers
        if (isset($requestData['passenger_firstnames']) && isset($requestData['passenger_lastnames'])) {
            // Agent booking - multiple passengers
            return collect($seats)->map(function ($seatName, $index) use ($requestData) {
                $firstName = $requestData['passenger_firstnames'][$index] ?? '';
                $lastName = $requestData['passenger_lastnames'][$index] ?? '';
                $age = $requestData['passenger_ages'][$index] ?? 0;
                $gender = $requestData['passenger_genders'][$index] ?? 1;

                return [
                    "LeadPassenger" => $index === 0,
                    "Title" => $gender == 1 ? "Mr" : ($gender == 2 ? "Mrs" : "Other"),
                    "FirstName" => $firstName,
                    "LastName" => $lastName,
                    "Email" => $requestData['passenger_email'],
                    "Phoneno" => $requestData['passenger_phone'],
                    "Gender" => $gender,
                    "IdType" => null,
                    "IdNumber" => null,
                    "Address" => $requestData['passenger_address'] ?? '',
                    "Age" => $age,
                    "SeatName" => $seatName
                ];
            })->toArray();
        } else {
            // Regular booking - single passenger
            return collect($seats)->map(function ($seatName, $index) use ($requestData) {
                return [
                    "LeadPassenger" => $index === 0,
                    "Title" => ($requestData['Gender'] ?? $requestData['gender']) == 1 ? "Mr" : "Mrs",
                    "FirstName" => $requestData['FirstName'] ?? $requestData['passenger_firstname'],
                    "LastName" => $requestData['LastName'] ?? $requestData['passenger_lastname'],
                    "Email" => $requestData['Email'] ?? $requestData['passenger_email'],
                    "Phoneno" => $requestData['Phoneno'] ?? $requestData['passenger_phone'],
                    "Gender" => $requestData['Gender'] ?? $requestData['gender'],
                    "IdType" => null,
                    "IdNumber" => null,
                    "Address" => $requestData['Address'] ?? $requestData['passenger_address'] ?? '',
                    "Age" => $requestData['age'] ?? $requestData['passenger_age'] ?? 0,
                    "SeatName" => $seatName
                ];
            })->toArray();
        }
    }

    /**
     * Block seats using the appropriate method
     */
    private function blockSeats(array $requestData, array $passengers)
    {
        $seats = is_array($requestData['Seats'] ?? $requestData['seats'])
            ? $requestData['Seats'] ?? $requestData['seats']
            : explode(',', $requestData['Seats'] ?? $requestData['seats']);

        $resultIndex = $requestData['ResultIndex'] ?? $requestData['result_index'] ?? '';
        $searchTokenId = $requestData['SearchTokenId'] ?? $requestData['search_token_id'] ?? '';
        $boardingPointId = $requestData['BoardingPointId'] ?? $requestData['boarding_point_index'] ?? '';
        $droppingPointId = $requestData['DroppingPointId'] ?? $requestData['dropping_point_index'] ?? '';
        $userIp = $requestData['UserIp'] ?? $requestData['user_ip'] ?? request()->ip();

        // Validate required fields
        if (empty($resultIndex)) {
            return ['success' => false, 'message' => 'ResultIndex is required'];
        }
        if (empty($boardingPointId)) {
            return ['success' => false, 'message' => 'Boarding point is required'];
        }
        if (empty($droppingPointId)) {
            return ['success' => false, 'message' => 'Dropping point is required'];
        }

        // Check if this is an operator bus
        if (str_starts_with($resultIndex, 'OP_')) {
            // Operator buses don't require searchTokenId
            return $this->blockOperatorBusSeat($resultIndex, $boardingPointId, $droppingPointId, $passengers, $seats, $userIp, $searchTokenId);
        } else {
            // Third-party buses require searchTokenId
            if (empty($searchTokenId)) {
                return ['success' => false, 'message' => 'SearchTokenId is required for third-party bus bookings'];
            }
            return blockSeatHelper($searchTokenId, $resultIndex, $boardingPointId, $droppingPointId, $passengers, $seats, $userIp);
        }
    }

    /**
     * Block operator bus seat
     */
    private function blockOperatorBusSeat(string $resultIndex, string $boardingPointId, string $droppingPointId, array $passengers, array $seats, string $userIp, string $searchTokenId)
    {
        try {
            $operatorBusId = (int) str_replace('OP_', '', $resultIndex);
            $operatorBus = \App\Models\OperatorBus::with(['activeSeatLayout', 'currentRoute.boardingPoints', 'currentRoute.droppingPoints'])->find($operatorBusId);

            if (!$operatorBus || !$operatorBus->activeSeatLayout || !$operatorBus->currentRoute) {
                return ['success' => false, 'message' => 'Operator bus details not found or incomplete.'];
            }

            // CRITICAL: Always get times from BusSchedule model, NOT cache (cache may have wrong times)
            // Parse ResultIndex: OP_{bus_id}_{schedule_id} - last part is schedule_id
            $parts = explode('_', str_replace('OP_', '', $resultIndex));
            $scheduleId = !empty($parts) ? (int)end($parts) : null;
            
            $departureTime = null;
            $arrivalTime = null;
            
            if ($scheduleId) {
                $schedule = \App\Models\BusSchedule::find($scheduleId);
                if ($schedule && $schedule->departure_time && $schedule->arrival_time) {
                    // Get date of journey from request or session
                    $dateOfJourney = request()->input('DateOfJourney') 
                        ?? request()->input('date_of_journey') 
                        ?? session('date_of_journey')
                        ?? now()->format('Y-m-d');
                    
                    // Build full datetime from schedule time + date of journey
                    $departureTime = Carbon::parse($dateOfJourney . ' ' . $schedule->departure_time->format('H:i:s'))->format('Y-m-d\TH:i:s');
                    $arrivalTime = Carbon::parse($dateOfJourney . ' ' . $schedule->arrival_time->format('H:i:s'));
                    
                    // Handle next day arrival
                    if ($arrivalTime->lt(Carbon::parse($departureTime))) {
                        $arrivalTime->addDay();
                    }
                    $arrivalTime = $arrivalTime->format('Y-m-d\TH:i:s');
                    
                    Log::info('Got times from BusSchedule', [
                        'schedule_id' => $scheduleId,
                        'departure_time' => $departureTime,
                        'arrival_time' => $arrivalTime,
                        'schedule_departure' => $schedule->departure_time->format('H:i:s'),
                        'schedule_arrival' => $schedule->arrival_time->format('H:i:s')
                    ]);
                }
            }
            
            // If no times found, this is an error
            if (!$departureTime || !$arrivalTime) {
                Log::error('CRITICAL: Could not get departure/arrival times for operator bus', [
                    'result_index' => $resultIndex,
                    'schedule_id' => $scheduleId,
                    'operator_bus_id' => $operatorBusId,
                    'schedule_exists' => $scheduleId ? \App\Models\BusSchedule::find($scheduleId) !== null : false
                ]);
                return ['success' => false, 'message' => 'Could not retrieve bus schedule times. Please try searching again.'];
            }

            // Get boarding and dropping points
            $boardingPoint = $operatorBus->currentRoute->boardingPoints->find($boardingPointId);
            $droppingPoint = $operatorBus->currentRoute->droppingPoints->find($droppingPointId);

            $boardingPointDetails = $boardingPoint ? [
                'CityPointIndex' => $boardingPoint->id,
                'CityPointLocation' => $boardingPoint->address ?? $boardingPoint->point_name,
                'CityPointName' => $boardingPoint->point_name,
                'CityPointTime' => Carbon::parse($departureTime)->format('Y-m-d\TH:i:s'),
            ] : null;

            $droppingPointDetails = $droppingPoint ? [
                'CityPointIndex' => $droppingPoint->id,
                'CityPointLocation' => $droppingPoint->address ?? $droppingPoint->point_name,
                'CityPointName' => $droppingPoint->point_name,
                'CityPointTime' => Carbon::parse($arrivalTime)->format('Y-m-d\TH:i:s'),
            ] : null;

            // Get seat prices
            $parsedLayout = parseSeatHtmlToJson($operatorBus->activeSeatLayout->html_layout);
            $seatPrices = [];
            foreach (['upper_deck', 'lower_deck'] as $deck) {
                foreach ($parsedLayout['seat'][$deck]['rows'] as $row) {
                    foreach ($row as $seat) {
                        $seatPrices[$seat['seat_id']] = $seat['price'];
                    }
                }
            }

            $passengersWithPrice = array_map(function ($passenger) use ($seatPrices) {
                $price = $seatPrices[$passenger['SeatName']] ?? 1000; // Default price if not found
                $passenger['Seat'] = [
                    'Price' => [
                        'PublishedPrice' => $price,
                        'OfferedPrice' => $price,
                        'BasePrice' => $price,
                        'Tax' => 0,
                        'OtherCharges' => 0,
                        'Discount' => 0,
                        'ServiceCharges' => 0,
                        'TDS' => 0,
                        'GST' => [
                            'CGSTAmount' => 0, 'CGSTRate' => 0, 'IGSTAmount' => 0,
                            'IGSTRate' => 0, 'SGSTAmount' => 0, 'SGSTRate' => 0,
                            'TaxableAmount' => 0
                        ]
                    ]
                ];
                return $passenger;
            }, $passengers);


            $bookingId = 'OP_BOOK_' . time() . '_' . $operatorBusId;

            // Get cancellation policy from operator bus
            $cancelPolicy = $operatorBus->cancellation_policies ?? [];
            
            // Format cancellation policy to match API format if needed
            if (!empty($cancelPolicy) && isset($cancelPolicy[0]['TimeBeforeDept'])) {
                // Policy is already in correct format
            } else {
                // Use default policies if none set
                $cancelPolicy = $operatorBus->getCancellationPoliciesAttribute();
            }

            $result = [
                'BookingId' => $bookingId,
                'BookingStatus' => 'Blocked',
                'TotalAmount' => collect($passengersWithPrice)->sum('Seat.Price.PublishedPrice'),
                'BusType' => $operatorBus->bus_type ?? 'Operator Bus',
                'TravelName' => $operatorBus->travel_name ?? 'Operator Service',
                'DepartureTime' => $departureTime,
                'ArrivalTime' => $arrivalTime,
                'BoardingPointdetails' => [$boardingPointDetails],
                'DroppingPointsdetails' => [$droppingPointDetails],
                'Passenger' => $passengersWithPrice,
                'BoardingPointId' => $boardingPointId,
                'DroppingPointId' => $droppingPointId,
                'OperatorBusId' => $operatorBusId,
                'ResultIndex' => $resultIndex,
                'CancelPolicy' => $cancelPolicy,
            ];

            return [
                'success' => true,
                'Result' => $result
            ];

        } catch (\Exception $e) {
            Log::error('BookingService: Error blocking operator bus seat', [
                'result_index' => $resultIndex,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to block operator bus seats: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate total fare from block response (base fare only)
     */
    private function calculateTotalFare(array $blockResult)
    {
        return collect($blockResult['Passenger'])->sum(function ($passenger) {
            return $passenger['Seat']['Price']['PublishedPrice'] ?? 0;
        });
    }

    /**
     * Calculate fees (service charge, platform fee, GST) and total amount
     * Formula: base_fare + service_charge + platform_fee + gst = total_amount
     */
    private function calculateFeesAndTotal(float $baseFare, ?float $agentCommission = null): array
    {
        $generalSettings = GeneralSetting::first();
        
        $serviceChargePercentage = $generalSettings->service_charge_percentage ?? 0;
        $platformFeePercentage = $generalSettings->platform_fee_percentage ?? 0;
        $platformFeeFixed = $generalSettings->platform_fee_fixed ?? 0;
        $gstPercentage = $generalSettings->gst_percentage ?? 0;

        // Service Charge
        $serviceCharge = round($baseFare * ($serviceChargePercentage / 100), 2);

        // Platform Fee (percentage + fixed)
        $platformFee = round(($baseFare * ($platformFeePercentage / 100)) + $platformFeeFixed, 2);

        // Amount before GST
        $amountBeforeGST = $baseFare + $serviceCharge + $platformFee;

        // GST (on base_fare + service_charge + platform_fee)
        $gst = round($amountBeforeGST * ($gstPercentage / 100), 2);

        // Total Amount (base + fees + GST + agent commission if applicable)
        $totalAmount = $amountBeforeGST + $gst;
        if ($agentCommission !== null && $agentCommission > 0) {
            // Agent commission is already included in the base fare or calculated separately
            // Don't add it to total_amount as it's a deduction, not an addition
        }

        return [
            'base_fare' => round($baseFare, 2),
            'service_charge' => $serviceCharge,
            'service_charge_percentage' => $serviceChargePercentage,
            'platform_fee' => $platformFee,
            'platform_fee_percentage' => $platformFeePercentage,
            'platform_fee_fixed' => $platformFeeFixed,
            'gst' => $gst,
            'gst_percentage' => $gstPercentage,
            'amount_before_gst' => round($amountBeforeGST, 2),
            'total_amount' => round($totalAmount, 2),
            'agent_commission' => $agentCommission ?? 0,
        ];
    }

    /**
     * Get city IDs and names from request data (handles both operator and third-party buses)
     */
    private function getCityIdsAndNames(array $requestData, string $resultIndex, ?array $blockResponse = null): array
    {
        $originId = null;
        $destinationId = null;
        $originName = null;
        $destinationName = null;

        // Check if this is an operator bus
        if (str_starts_with($resultIndex, 'OP_')) {
            $operatorBusId = (int) str_replace('OP_', '', $resultIndex);
            $operatorBus = OperatorBus::with('currentRoute.originCity', 'currentRoute.destinationCity')->find($operatorBusId);
            
            if ($operatorBus && $operatorBus->currentRoute) {
                $originId = $operatorBus->currentRoute->origin_city_id ?? null;
                $destinationId = $operatorBus->currentRoute->destination_city_id ?? null;
                $originName = $operatorBus->currentRoute->originCity->city_name ?? null;
                $destinationName = $operatorBus->currentRoute->destinationCity->city_name ?? null;
            }
        }

        // Fallback to request/session data
        if (!$originId) {
            $originId = $requestData['origin_id'] ?? $requestData['OriginId'] ?? null;
            // If it's a string (city name), try to find the ID
            if (!$originId && isset($requestData['origin_city']) && is_numeric($requestData['origin_city'])) {
                $originId = $requestData['origin_city'];
            }
        }
        if (!$destinationId) {
            $destinationId = $requestData['destination_id'] ?? $requestData['DestinationId'] ?? null;
            // If it's a string (city name), try to find the ID
            if (!$destinationId && isset($requestData['destination_city']) && is_numeric($requestData['destination_city'])) {
                $destinationId = $requestData['destination_city'];
            }
        }

        // Get city names if we have IDs
        if ($originId && !$originName) {
            $originCity = City::find($originId);
            $originName = $originCity ? $originCity->city_name : null;
        }
        if ($destinationId && !$destinationName) {
            $destinationCity = City::find($destinationId);
            $destinationName = $destinationCity ? $destinationCity->city_name : null;
        }

        // Try to extract from cached search data
        if ((!$originId || !$destinationId) && isset($requestData['search_token_id'])) {
            $cachedBuses = Cache::get('bus_search_results_' . $requestData['search_token_id']);
            if ($cachedBuses && isset($cachedBuses['origin_city_id'])) {
                $originId = $originId ?? $cachedBuses['origin_city_id'];
                $destinationId = $destinationId ?? $cachedBuses['destination_city_id'];
            }
        }

        return [
            'origin_id' => $originId,
            'destination_id' => $destinationId,
            'origin_name' => $originName,
            'destination_name' => $destinationName
        ];
    }

    /**
     * Create pending ticket record
     */
    private function createPendingTicket(array $requestData, array $blockResponse, float $baseFare, int $userId)
    {
        $seats = is_array($requestData['Seats'] ?? $requestData['seats'])
            ? $requestData['Seats'] ?? $requestData['seats']
            : explode(',', $requestData['Seats'] ?? $requestData['seats']);

        $resultIndex = $requestData['ResultIndex'] ?? $requestData['result_index'] ?? '';
        $isOperatorBus = str_starts_with($resultIndex, 'OP_');

        // Get city IDs and names
        $cityData = $this->getCityIdsAndNames($requestData, $resultIndex, $blockResponse);
        $originId = $cityData['origin_id'] ?? 0;
        $destinationId = $cityData['destination_id'] ?? 0;
        $originName = $cityData['origin_name'];
        $destinationName = $cityData['destination_name'];

        // Calculate unit price per seat
        $totalUnitPrice = collect($blockResponse['Result']['Passenger'])->sum(function ($passenger) {
            return $passenger['Seat']['Price']['OfferedPrice'] ?? 0;
        });
        $unitPrice = count($seats) > 0 ? round($totalUnitPrice / count($seats), 2) : round($totalUnitPrice, 2);

        // Calculate fees and total amount
        $agentCommission = isset($requestData['agent_id']) && isset($requestData['commission_rate'])
            ? round($baseFare * $requestData['commission_rate'], 2)
            : null;
        
        $feeCalculation = $this->calculateFeesAndTotal($baseFare, $agentCommission);

        // Get operator bus data if applicable
        $operatorBusId = null;
        $operatorId = null;
        $routeId = null;
        $scheduleId = null;
        
        if ($isOperatorBus) {
            $operatorBusId = (int) str_replace('OP_', '', $resultIndex);
            $operatorBus = OperatorBus::with('currentRoute', 'operator')->find($operatorBusId);
            
            if ($operatorBus) {
                $operatorId = $operatorBus->operator_id ?? null;
                $routeId = $operatorBus->current_route_id ?? null;
                
                // Extract schedule_id directly from ResultIndex: OP_{bus_id}_{schedule_id}
                $parts = explode('_', str_replace('OP_', '', $resultIndex));
                $scheduleId = !empty($parts) ? (int)end($parts) : null;
                
                // Verify schedule exists and belongs to this bus
                if ($scheduleId) {
                    $schedule = \App\Models\BusSchedule::find($scheduleId);
                    if (!$schedule || $schedule->operator_bus_id != $operatorBusId) {
                        Log::warning('Schedule ID mismatch', [
                            'schedule_id' => $scheduleId,
                            'operator_bus_id' => $operatorBusId,
                            'result_index' => $resultIndex
                        ]);
                        $scheduleId = null;
                    }
                }
            }
        }

        $bookedTicket = new BookedTicket();
        $bookedTicket->user_id = $userId;
        $bookedTicket->bus_type = $blockResponse['Result']['BusType'] ?? null;
        $bookedTicket->travel_name = $blockResponse['Result']['TravelName'] ?? null;
        
        // Fix: source_destination should use actual city IDs - save as JSON string in old format: "[\"9292\",\"230\"]"
        // Note: We manually json_encode here to match the old format (string with escaped quotes)
        $bookedTicket->source_destination = json_encode([(string)$originId, (string)$destinationId]);
        
        // Fix: origin_city and destination_city should be city names
        $bookedTicket->origin_city = $originName;
        $bookedTicket->destination_city = $destinationName;
        
        // Fix: Extract departure_time and arrival_time - USE blockResponse FIRST
        // blockOperatorBusSeat now ensures times come from BusSchedule (not current time)
        $departureTime = $blockResponse['Result']['DepartureTime'] ?? null;
        $arrivalTime = $blockResponse['Result']['ArrivalTime'] ?? null;
        
        // Get searchTokenId early for use throughout the method
        $searchTokenId = $requestData['SearchTokenId'] ?? $requestData['search_token_id'] ?? '';
        
        // Fallback to cache if not in blockResponse (shouldn't happen for operator buses)
        if (!$departureTime || !$arrivalTime) {
            if ($searchTokenId) {
                $cachedBuses = Cache::get('bus_search_results_' . $searchTokenId);
                if ($cachedBuses && isset($cachedBuses['CombinedBuses'])) {
                    $busData = collect($cachedBuses['CombinedBuses'])->firstWhere('ResultIndex', $resultIndex);
                    if ($busData) {
                        $departureTime = $departureTime ?? $busData['DepartureTime'] ?? null;
                        $arrivalTime = $arrivalTime ?? $busData['ArrivalTime'] ?? null;
                    }
                }
            }
        }
        
        // LAST RESORT: For operator buses, get directly from BusSchedule model
        if ((!$departureTime || !$arrivalTime) && $isOperatorBus) {
            // Parse ResultIndex: OP_{bus_id}_{schedule_id} - last part is schedule_id
            $parts = explode('_', str_replace('OP_', '', $resultIndex));
            $scheduleId = !empty($parts) ? (int)end($parts) : null;
            
            if ($scheduleId) {
                $schedule = \App\Models\BusSchedule::find($scheduleId);
                if ($schedule && $schedule->departure_time && $schedule->arrival_time) {
                    $dateOfJourney = $requestData['DateOfJourney'] ?? $requestData['date_of_journey'] ?? now()->format('Y-m-d');
                    
                    if (!$departureTime) {
                        $departureTime = Carbon::parse($dateOfJourney . ' ' . $schedule->departure_time->format('H:i:s'))->format('Y-m-d\TH:i:s');
                    }
                    if (!$arrivalTime) {
                        $arrivalTime = Carbon::parse($dateOfJourney . ' ' . $schedule->arrival_time->format('H:i:s'));
                        if ($arrivalTime->lt(Carbon::parse($departureTime))) {
                            $arrivalTime->addDay();
                        }
                        $arrivalTime = $arrivalTime->format('Y-m-d\TH:i:s');
                    }
                    
                    Log::info('Got times from BusSchedule in createPendingTicket', [
                        'schedule_id' => $scheduleId,
                        'departure_time' => $departureTime,
                        'arrival_time' => $arrivalTime
                    ]);
                }
            }
        }
        
        // Parse and set times (extract just the time portion from ISO8601 datetime strings)
        if ($departureTime) {
            try {
                // Handle both ISO8601 datetime (2025-11-03T06:56:29) and time-only (06:56:29) formats
                $parsed = Carbon::parse($departureTime);
                $bookedTicket->departure_time = $parsed->format('H:i:s');
                Log::info('Setting departure_time', ['original' => $departureTime, 'parsed' => $bookedTicket->departure_time]);
            } catch (\Exception $e) {
                Log::warning('Failed to parse departure_time', ['time' => $departureTime, 'error' => $e->getMessage()]);
                $bookedTicket->departure_time = null;
            }
        }
        
        if ($arrivalTime) {
            try {
                // Handle both ISO8601 datetime (2025-11-03T14:56:29) and time-only (14:56:29) formats
                $parsed = Carbon::parse($arrivalTime);
                $bookedTicket->arrival_time = $parsed->format('H:i:s');
                Log::info('Setting arrival_time', ['original' => $arrivalTime, 'parsed' => $bookedTicket->arrival_time]);
            } catch (\Exception $e) {
                Log::warning('Failed to parse arrival_time', ['time' => $arrivalTime, 'error' => $e->getMessage()]);
                $bookedTicket->arrival_time = null;
            }
        }
        $bookedTicket->operator_pnr = $blockResponse['Result']['BookingId'] ?? null;
        $bookedTicket->boarding_point_details = json_encode($blockResponse['Result']['BoardingPointdetails'] ?? []);
        $bookedTicket->dropping_point_details = isset($blockResponse['Result']['DroppingPointsdetails'])
            ? json_encode($blockResponse['Result']['DroppingPointsdetails']) : null;
        
        // Fix: seats - seat_numbers is redundant and will be dropped
        $bookedTicket->seats = $seats;
        
        $bookedTicket->ticket_count = count($seats);
        $bookedTicket->unit_price = $unitPrice;
        $bookedTicket->sub_total = round($baseFare, 2);
        
        // Fix: Calculate and set total_amount correctly
        $bookedTicket->total_amount = $feeCalculation['total_amount'];
        
        $bookedTicket->pnr_number = getTrx(10);
        
        // Fix: Use boarding_point_id for dropping_point (pickup_point and boarding_point are redundant and will be dropped)
        $boardingPointId = $requestData['BoardingPointId'] ?? $requestData['boarding_point_index'] ?? null;
        $droppingPointId = $requestData['DroppingPointId'] ?? $requestData['dropping_point_index'] ?? null;
        
        // Note: pickup_point and boarding_point are redundant - migration will drop them
        // For now, set dropping_point only
        $bookedTicket->dropping_point = $droppingPointId;
        
        $bookedTicket->search_token_id = $requestData['SearchTokenId'] ?? $requestData['search_token_id'] ?? null;
        // Get date of journey from multiple sources, ensuring it's in Y-m-d format
        $dateOfJourney = $requestData['DateOfJourney'] ?? $requestData['date_of_journey'] ?? null;
        
        // Try to get from session if not in request (session stores it from ticketSearch)
        if (!$dateOfJourney) {
            $dateOfJourney = session()->get('date_of_journey');
        }
        
        // Normalize date format (handle M/d/Y, d/m/Y, Y-m-d, etc.)
        if ($dateOfJourney) {
            // Session stores date in m/d/Y format (e.g., "11/27/2025")
            // Try to parse it correctly
            try {
                // First try m/d/Y format (session format from ticketSearch)
                if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateOfJourney)) {
                    $parsedDate = \Carbon\Carbon::createFromFormat('m/d/Y', $dateOfJourney);
                    $dateOfJourney = $parsedDate->format('Y-m-d');
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfJourney)) {
                    // Already in Y-m-d format
                    $parsedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $dateOfJourney);
                    $dateOfJourney = $parsedDate->format('Y-m-d');
                } else {
                    // Try Carbon's flexible parsing as fallback
                    $parsedDate = \Carbon\Carbon::parse($dateOfJourney);
                    $dateOfJourney = $parsedDate->format('Y-m-d');
                }
            } catch (\Exception $e) {
                Log::warning('BookingService: Failed to parse date_of_journey', [
                    'original_date' => $dateOfJourney,
                    'error' => $e->getMessage(),
                    'session_date' => session()->get('date_of_journey')
                ]);
                // Fallback to today if parsing fails
                $dateOfJourney = now()->format('Y-m-d');
            }
        } else {
            // Last resort: use today
            $dateOfJourney = now()->format('Y-m-d');
        }
        
        $bookedTicket->date_of_journey = $dateOfJourney;
        
        Log::info('BookingService: Set date_of_journey for ticket', [
            'ticket_id' => 'pending',
            'date_of_journey' => $dateOfJourney,
            'original_request' => $requestData['DateOfJourney'] ?? $requestData['date_of_journey'] ?? 'not provided',
            'session_date' => session()->get('date_of_journey')
        ]);

        $leadPassenger = collect($blockResponse['Result']['Passenger'])->firstWhere('LeadPassenger', true)
            ?? $blockResponse['Result']['Passenger'][0] ?? null;

        $bookedTicket->passenger_phone = $leadPassenger['Phoneno'] ?? null;
        $bookedTicket->passenger_email = $leadPassenger['Email'] ?? null;
        $bookedTicket->passenger_address = $leadPassenger['Address'] ?? null;
        $bookedTicket->passenger_name = trim(($leadPassenger['FirstName'] ?? '') . ' ' . ($leadPassenger['LastName'] ?? ''));
        $bookedTicket->passenger_age = $leadPassenger['Age'] ?? null;

        // Save all passenger names - ensure consistent JSON encoding (array format)
        $passengerNames = [];
        if (isset($requestData['passenger_firstnames']) && isset($requestData['passenger_lastnames'])) {
            // Agent booking - use provided passenger data
            for ($i = 0; $i < count($requestData['passenger_firstnames']); $i++) {
                $firstName = $requestData['passenger_firstnames'][$i] ?? '';
                $lastName = $requestData['passenger_lastnames'][$i] ?? '';
                $passengerNames[] = trim($firstName . ' ' . $lastName);
            }
        } else {
            // Regular booking - use API response data
            foreach ($blockResponse['Result']['Passenger'] as $passenger) {
                $passengerNames[] = trim(($passenger['FirstName'] ?? '') . ' ' . ($passenger['LastName'] ?? ''));
            }
        }
        // Fix: Store as JSON array, not double-encoded string
        $bookedTicket->passenger_names = $passengerNames; // Eloquent will auto-json_encode due to $casts

        // Fix: Handle agent-specific data (only set for agent bookings)
        if (isset($requestData['agent_id'])) {
            $bookedTicket->agent_id = $requestData['agent_id'];
            $bookedTicket->booking_source = $requestData['booking_source'] ?? 'agent';

            // Calculate and store commission
            if (isset($requestData['commission_rate'])) {
                $bookedTicket->agent_commission = $requestData['commission_rate'];
                $bookedTicket->agent_commission_amount = $agentCommission;

                Log::info('Agent commission calculated', [
                    'agent_id' => $requestData['agent_id'],
                    'base_fare' => $baseFare,
                    'commission_rate' => $requestData['commission_rate'],
                    'commission_amount' => $agentCommission
                ]);
            }
        }

        // Fix: Handle admin-specific data (only set for admin bookings)
        if (isset($requestData['admin_id'])) {
            $bookedTicket->booking_source = $requestData['booking_source'] ?? 'admin';

            Log::info('Admin booking created', [
                'admin_id' => $requestData['admin_id'],
                'base_fare' => $baseFare,
                'total_amount' => $feeCalculation['total_amount']
            ]);
        }

        // Fix: Only set operator-specific fields for operator buses
        if ($isOperatorBus && $operatorBusId) {
            $bookedTicket->operator_id = $operatorId;
            $bookedTicket->operator_booking_id = $blockResponse['Result']['BookingId'] ?? null;
            $bookedTicket->bus_id = $operatorBusId;
            $bookedTicket->route_id = $routeId;
            $bookedTicket->schedule_id = $scheduleId;
            // Fix: Set booking_id for operator buses (use operator_pnr or BookingId)
            $bookedTicket->booking_id = $blockResponse['Result']['BookingId'] ?? $bookedTicket->operator_pnr ?? null;
        } else {
            // For third-party buses, keep these null
            $bookedTicket->operator_id = null;
            $bookedTicket->operator_booking_id = null;
            $bookedTicket->bus_id = null;
            $bookedTicket->route_id = null;
            $bookedTicket->schedule_id = null;
            // Fix: Set booking_id for third-party buses (use api_booking_id later, or pnr for now)
            $bookedTicket->booking_id = null; // Will be set from api_booking_id after booking confirmation
        }
        
        // Fix: ticket_no - will be set after booking confirmation from api_response
        $bookedTicket->ticket_no = null; // Will be populated from api_ticket_no after booking
        
        // Fix: payment_status and paid_amount - will be set when payment is confirmed
        $bookedTicket->payment_status = null; // Will be set to 'paid' after payment confirmation
        $bookedTicket->paid_amount = 0; // Will be set to total_amount after payment confirmation

        // Fix: Standardize api_response with correct origin/destination
        $standardizedBlockResponse = $blockResponse;
        if (isset($standardizedBlockResponse['Result'])) {
            $standardizedBlockResponse['Result']['Origin'] = $originName;
            $standardizedBlockResponse['Result']['Destination'] = $destinationName;
            $standardizedBlockResponse['Result']['OriginId'] = $originId;
            $standardizedBlockResponse['Result']['DestinationId'] = $destinationId;
        }
        $bookedTicket->api_response = json_encode($standardizedBlockResponse);

        // Fix: Save bus_details - construct from available data
        $busDetailsData = [];
        
        // Try to get from blockResponse first
        if (isset($blockResponse['Result']['BusDetails'])) {
            $busDetailsData = $blockResponse['Result']['BusDetails'];
        } else {
            // Construct bus_details from blockResponse and cached data
            $dateOfJourney = $requestData['DateOfJourney'] ?? $requestData['date_of_journey'] ?? now()->format('Y-m-d');
            $busDetailsData = [
                'departure_time' => $departureTime 
                    ? Carbon::parse($departureTime)->format('m/d/Y H:i:s') 
                    : ($bookedTicket->departure_time ? Carbon::parse($dateOfJourney . ' ' . $bookedTicket->departure_time)->format('m/d/Y H:i:s') : null),
                'arrival_time' => $arrivalTime 
                    ? Carbon::parse($arrivalTime)->format('m/d/Y H:i:s') 
                    : ($bookedTicket->arrival_time ? Carbon::parse($dateOfJourney . ' ' . $bookedTicket->arrival_time)->format('m/d/Y H:i:s') : null),
                'bus_type' => $blockResponse['Result']['BusType'] ?? $bookedTicket->bus_type,
                'travel_name' => $blockResponse['Result']['TravelName'] ?? $bookedTicket->travel_name,
            ];
            
            // Add more details from cached bus data if available
            if ($searchTokenId) {
                $cachedBuses = Cache::get('bus_search_results_' . $searchTokenId);
                if ($cachedBuses && isset($cachedBuses['CombinedBuses'])) {
                    $busData = collect($cachedBuses['CombinedBuses'])->firstWhere('ResultIndex', $resultIndex);
                    if ($busData) {
                        $busDetailsData = array_merge($busDetailsData, [
                            'Duration' => $busData['Duration'] ?? null,
                            'AvailableSeats' => $busData['AvailableSeats'] ?? null,
                            'BusName' => $busData['BusName'] ?? null,
                        ]);
                    }
                }
            }
        }
        
        if (!empty($busDetailsData)) {
            $bookedTicket->bus_details = json_encode($busDetailsData);
            Log::info('Saving bus_details', ['bus_details' => $busDetailsData]);
        }

        if (isset($blockResponse['Result']['CancelPolicy'])) {
            $cancelPolicy = $blockResponse['Result']['CancelPolicy'];
            
            // Check if this is operator bus format (has TimeBeforeDept) or third-party API format (has FromDate)
            if (!empty($cancelPolicy) && isset($cancelPolicy[0]['TimeBeforeDept'])) {
                // Operator bus format - already has PolicyString, just store as-is
                $bookedTicket->cancellation_policy = json_encode($cancelPolicy);
            } else {
                // Third-party API format - use formatCancelPolicy
                $bookedTicket->cancellation_policy = json_encode(formatCancelPolicy($cancelPolicy));
            }
        }

        $bookedTicket->status = 0; // Pending
        
        // Log fee calculation for debugging
        Log::info('BookingService: Ticket created with fee calculation', [
            'ticket_id' => 'pending',
            'base_fare' => $feeCalculation['base_fare'],
            'service_charge' => $feeCalculation['service_charge'],
            'platform_fee' => $feeCalculation['platform_fee'],
            'gst' => $feeCalculation['gst'],
            'total_amount' => $feeCalculation['total_amount'],
            'is_operator_bus' => $isOperatorBus,
            'origin_id' => $originId,
            'destination_id' => $destinationId,
            'origin_name' => $originName,
            'destination_name' => $destinationName
        ]);
        
        $bookedTicket->save();

        return $bookedTicket;
    }

    /**
     * Create Razorpay order
     */
    private function createRazorpayOrder(BookedTicket $bookedTicket, float $totalFare)
    {
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        return $api->order->create([
            'receipt' => $bookedTicket->pnr_number,
            'amount' => $totalFare * 100, // Amount in paisa
            'currency' => 'INR',
            'notes' => [
                'ticket_id' => $bookedTicket->id,
                'pnr_number' => $bookedTicket->pnr_number,
            ]
        ]);
    }

    /**
     * Cache booking data for payment verification
     */
    private function cacheBookingData(int $ticketId, array $requestData, array $blockResponse)
    {
        $bookingData = [
            'user_ip' => $requestData['UserIp'] ?? $requestData['user_ip'] ?? request()->ip(),
            'search_token_id' => $requestData['SearchTokenId'] ?? $requestData['search_token_id'],
            'result_index' => $requestData['ResultIndex'] ?? $requestData['result_index'],
            'boarding_point_id' => $requestData['BoardingPointId'] ?? $requestData['boarding_point_index'],
            'dropping_point_id' => $requestData['DroppingPointId'] ?? $requestData['dropping_point_index'],
            'passengers' => $this->preparePassengerData($requestData),
            'block_response' => $blockResponse,
            'ticket_id' => $ticketId // Include ticket ID for bookOperatorBusTicket
        ];

        Cache::put('booking_data_' . $ticketId, $bookingData, now()->addMinutes(15));
    }

    /**
     * Verify Razorpay payment signature
     */
    private function verifyRazorpaySignature(array $paymentData)
    {
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        $attributes = [
            'razorpay_order_id' => $paymentData['razorpay_order_id'],
            'razorpay_payment_id' => $paymentData['razorpay_payment_id'],
            'razorpay_signature' => $paymentData['razorpay_signature'],
        ];

        $api->utility->verifyPaymentSignature($attributes);
    }

    /**
     * Complete booking via API
     */
    private function completeBooking(array $bookingData)
    {
        if (str_starts_with($bookingData['result_index'], 'OP_')) {
            return $this->bookOperatorBusTicket($bookingData);
        } else {
            return bookAPITicket(
                $bookingData['user_ip'],
                $bookingData['search_token_id'],
                $bookingData['result_index'],
                $bookingData['boarding_point_id'],
                $bookingData['dropping_point_id'],
                $bookingData['passengers']
            );
        }
    }

    /**
     * Book operator bus ticket
     */
    private function bookOperatorBusTicket(array $bookingData)
    {
        $operatorBusId = (int) str_replace('OP_', '', $bookingData['result_index']);
        $bookingId = 'OP_BOOK_' . time() . '_' . $operatorBusId;
        
        // Get ticket ID from cached booking data
        $ticketId = $bookingData['ticket_id'] ?? null;
        $bookedTicket = null;
        
        if ($ticketId) {
            $bookedTicket = BookedTicket::find($ticketId);
        }
        
        // Get origin and destination from booked ticket or operator bus
        $originName = $bookedTicket->origin_city ?? null;
        $destinationName = $bookedTicket->destination_city ?? null;
        
        if (!$originName || !$destinationName) {
            $operatorBus = OperatorBus::with('currentRoute.originCity', 'currentRoute.destinationCity')->find($operatorBusId);
            if ($operatorBus && $operatorBus->currentRoute) {
                $originName = $originName ?? $operatorBus->currentRoute->originCity->city_name ?? 'Origin City';
                $destinationName = $destinationName ?? $operatorBus->currentRoute->destinationCity->city_name ?? 'Destination City';
            }
        }

        return [
            'Result' => [
                'BookingId' => $bookingId,
                'TravelOperatorPNR' => $bookingId,
                'BookingStatus' => 'Confirmed',
                'InvoiceNumber' => 'OP_INV_' . time(),
                'InvoiceAmount' => $bookedTicket->total_amount ?? 1000, // Use actual total amount
                'InvoiceCreatedOn' => now()->toISOString(),
                'TicketNo' => 'OP_TKT_' . time(),
                'Origin' => $originName ?? 'Origin City',
                'Destination' => $destinationName ?? 'Destination City',
                'Price' => [
                    'AgentCommission' => $bookedTicket->agent_commission_amount ?? 0,
                    'TDS' => 0
                ]
            ]
        ];
    }

    /**
     * Update ticket with booking details
     */
    private function updateTicketWithBookingDetails(BookedTicket $bookedTicket, array $apiResponse, array $bookingData)
    {
        // Invalidate seat availability cache for this booking
        if ($bookedTicket->bus_id && $bookedTicket->schedule_id && $bookedTicket->date_of_journey) {
            $availabilityService = new \App\Services\SeatAvailabilityService();
            
            // Ensure date is in Y-m-d format
            $dateOfJourney = $bookedTicket->date_of_journey;
            if ($dateOfJourney instanceof \Carbon\Carbon) {
                $dateOfJourney = $dateOfJourney->format('Y-m-d');
            } elseif (is_string($dateOfJourney)) {
                // Try to parse and reformat if needed
                try {
                    $dateOfJourney = \Carbon\Carbon::parse($dateOfJourney)->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::warning('BookingService: Invalid date format for cache invalidation', [
                        'date_of_journey' => $dateOfJourney
                    ]);
                }
            }
            
            $availabilityService->invalidateCache(
                $bookedTicket->bus_id,
                $bookedTicket->schedule_id,
                $dateOfJourney
            );
            Log::info('BookingService: Invalidated seat availability cache', [
                'bus_id' => $bookedTicket->bus_id,
                'schedule_id' => $bookedTicket->schedule_id,
                'date_of_journey' => $dateOfJourney,
                'original_date' => $bookedTicket->date_of_journey,
                'ticket_id' => $bookedTicket->id,
                'seats' => is_array($bookedTicket->seats) ? implode(',', $bookedTicket->seats) : $bookedTicket->seats
            ]);
        } else {
            Log::warning('BookingService: Cannot invalidate cache - missing required fields', [
                'bus_id' => $bookedTicket->bus_id,
                'schedule_id' => $bookedTicket->schedule_id,
                'date_of_journey' => $bookedTicket->date_of_journey,
                'ticket_id' => $bookedTicket->id
            ]);
        }

        // Update ticket status to confirmed and save operator PNR
        $bookedTicket->operator_pnr = $apiResponse['Result']['TravelOperatorPNR'] ?? $apiResponse['Result']['BookingId'] ?? null;

        // Merge block response with booking response
        $blockResponse = json_decode($bookedTicket->api_response, true);
        $completeApiResponse = array_merge($blockResponse ?? [], $apiResponse);

        // Fix: Extract and set departure_time and arrival_time if missing
        $updateData = [
            'status' => 1, // Confirmed
            'api_response' => json_encode($completeApiResponse)
        ];
        
        // Fix: Set departure_time and arrival_time if missing (from api_response or bus_details)
        if (!$bookedTicket->departure_time || !$bookedTicket->arrival_time) {
            // Try to extract from api_response first
            $result = $apiResponse['Result'] ?? [];
            
            if (!$bookedTicket->departure_time && isset($result['DepartureTime'])) {
                try {
                    $updateData['departure_time'] = Carbon::parse($result['DepartureTime'])->format('H:i:s');
                } catch (\Exception $e) {
                    Log::warning('Failed to parse departure_time from api_response', ['time' => $result['DepartureTime']]);
                }
            }
            
            if (!$bookedTicket->arrival_time && isset($result['ArrivalTime'])) {
                try {
                    $updateData['arrival_time'] = Carbon::parse($result['ArrivalTime'])->format('H:i:s');
                } catch (\Exception $e) {
                    Log::warning('Failed to parse arrival_time from api_response', ['time' => $result['ArrivalTime']]);
                }
            }
            
            // If still missing, try bus_details JSON
            if ((!$bookedTicket->departure_time || !$bookedTicket->arrival_time) && $bookedTicket->bus_details) {
                $busDetails = json_decode($bookedTicket->bus_details, true);
                if ($busDetails) {
                    if (!$bookedTicket->departure_time && isset($busDetails['departure_time'])) {
                        try {
                            $updateData['departure_time'] = Carbon::parse($busDetails['departure_time'])->format('H:i:s');
                        } catch (\Exception $e) {
                            Log::warning('Failed to parse departure_time from bus_details', ['time' => $busDetails['departure_time']]);
                        }
                    }
                    if (!$bookedTicket->arrival_time && isset($busDetails['arrival_time'])) {
                        try {
                            $updateData['arrival_time'] = Carbon::parse($busDetails['arrival_time'])->format('H:i:s');
                        } catch (\Exception $e) {
                            Log::warning('Failed to parse arrival_time from bus_details', ['time' => $busDetails['arrival_time']]);
                        }
                    }
                }
            }
        }
        
        // Fix: Set payment_status and paid_amount when booking is confirmed
        $updateData['payment_status'] = 'paid';
        $updateData['paid_amount'] = $bookedTicket->total_amount ?? 0;
        
        $bookedTicket->update($updateData);

        $bookingApiId = $apiResponse['Result']['BookingID'] ?? $apiResponse['Result']['BookingId'] ?? null;

        // Update additional fields from the booking response
        $this->updateAdditionalFields($bookedTicket, $apiResponse);

        // Get detailed ticket information if this is not an operator bus
        if (!str_starts_with($bookingData['result_index'], 'OP_') && $bookingApiId) {
            $this->updateTicketWithDetailedInfo($bookedTicket, $bookingData, $bookingApiId);
        }
    }

    /**
     * Update additional fields from booking response
     */
    private function updateAdditionalFields(BookedTicket $bookedTicket, array $apiResponse)
    {
        $result = $apiResponse['Result'] ?? [];
        $updateData = [];

        // Update invoice details if available
        if (isset($result['InvoiceNumber'])) {
            $updateData['api_invoice'] = $result['InvoiceNumber'];
        }

        if (isset($result['InvoiceAmount'])) {
            $updateData['api_invoice_amount'] = $result['InvoiceAmount'];
        }

        if (isset($result['InvoiceCreatedOn'])) {
            $updateData['api_invoice_date'] = Carbon::parse($result['InvoiceCreatedOn'])->format('Y-m-d H:i:s');
        }

        if (isset($result['BookingId'])) {
            $updateData['api_booking_id'] = $result['BookingId'];
        }

        if (isset($result['TicketNo'])) {
            $updateData['api_ticket_no'] = $result['TicketNo'];
            // Fix: Also set ticket_no field (not just api_ticket_no)
            $updateData['ticket_no'] = $result['TicketNo'];
        }
        
        // Fix: Set booking_id if not already set
        if (isset($result['BookingId']) && !$bookedTicket->booking_id) {
            $updateData['booking_id'] = $result['BookingId'];
        }
        
        // Fix: Set payment_status and paid_amount when booking is confirmed
        if (!isset($updateData['payment_status'])) {
            $updateData['payment_status'] = 'paid'; // Payment was verified before reaching here
        }
        if (!isset($updateData['paid_amount']) && $bookedTicket->total_amount > 0) {
            $updateData['paid_amount'] = $bookedTicket->total_amount;
        }

        // Update pricing details if available
        if (isset($result['Price']['AgentCommission'])) {
            $updateData['agent_commission'] = $result['Price']['AgentCommission'];
        }

        if (isset($result['Price']['TDS'])) {
            $updateData['tds_from_api'] = $result['Price']['TDS'];
        }

        // Update city information if available (only if not already set correctly)
        // Don't overwrite if we already have correct city names from createPendingTicket
        if (isset($result['Origin']) && !$bookedTicket->origin_city) {
            $updateData['origin_city'] = $result['Origin'];
        }

        if (isset($result['Destination']) && !$bookedTicket->destination_city) {
            $updateData['destination_city'] = $result['Destination'];
        }

        // Update the ticket with additional information
        if (!empty($updateData)) {
            $bookedTicket->update($updateData);
        }
    }

    /**
     * Update ticket with detailed information from getAPITicketDetails
     */
    private function updateTicketWithDetailedInfo(BookedTicket $bookedTicket, array $bookingData, string $bookingApiId)
    {
        try {
            Log::info('Getting detailed ticket information', [
                'UserIp' => $bookingData['user_ip'],
                'SearchTokenId' => $bookingData['search_token_id'],
                'BookingApiId' => $bookingApiId
            ]);

            $ticketApiDetails = getAPITicketDetails(
                $bookingData['user_ip'],
                $bookingData['search_token_id'],
                $bookingApiId
            );

            Log::info('Got detailed ticket information', ['details' => $ticketApiDetails]);

            if (isset($ticketApiDetails['Result'])) {
                $result = $ticketApiDetails['Result'];

                $updateData = [];

                // Update invoice details
                if (isset($result['InvoiceNumber'])) {
                    $updateData['api_invoice'] = $result['InvoiceNumber'];
                }

                if (isset($result['InvoiceAmount'])) {
                    $updateData['api_invoice_amount'] = $result['InvoiceAmount'];
                }

                if (isset($result['InvoiceCreatedOn'])) {
                    $updateData['api_invoice_date'] = Carbon::parse($result['InvoiceCreatedOn'])->format('Y-m-d H:i:s');
                }

                if (isset($result['BookingId'])) {
                    $updateData['api_booking_id'] = $result['BookingId'];
                }

                if (isset($result['TicketNo'])) {
                    $updateData['api_ticket_no'] = $result['TicketNo'];
                    // Fix: Also set ticket_no field
                    $updateData['ticket_no'] = $result['TicketNo'];
                }
                
                // Fix: Set booking_id if not already set
                if (isset($result['BookingId']) && !$bookedTicket->booking_id) {
                    $updateData['booking_id'] = $result['BookingId'];
                }

                // Update pricing details
                if (isset($result['Price']['AgentCommission'])) {
                    $updateData['agent_commission'] = $result['Price']['AgentCommission'];
                }

                if (isset($result['Price']['TDS'])) {
                    $updateData['tds_from_api'] = $result['Price']['TDS'];
                }

                // Update city information (only if not already set correctly)
                if (isset($result['Origin']) && !$bookedTicket->origin_city) {
                    $updateData['origin_city'] = $result['Origin'];
                }

                if (isset($result['Destination']) && !$bookedTicket->destination_city) {
                    $updateData['destination_city'] = $result['Destination'];
                }

                // Update dropping point details
                if (isset($result['DroppingPointdetails'])) {
                    $updateData['dropping_point_details'] = json_encode($result['DroppingPointdetails']);
                }

                // Update cancellation policy
                if (isset($result['CancelPolicy'])) {
                    $cancelPolicy = $result['CancelPolicy'];
                    
                    // Check if this is operator bus format (has TimeBeforeDept) or third-party API format (has FromDate)
                    if (!empty($cancelPolicy) && isset($cancelPolicy[0]['TimeBeforeDept'])) {
                        // Operator bus format - already has PolicyString, just store as-is
                        $updateData['cancellation_policy'] = json_encode($cancelPolicy);
                    } else {
                        // Third-party API format - use formatCancelPolicy
                        $updateData['cancellation_policy'] = json_encode(formatCancelPolicy($cancelPolicy));
                    }
                }

                // Update the ticket with all the detailed information
                if (!empty($updateData)) {
                    $bookedTicket->update($updateData);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to get detailed ticket information', [
                'ticket_id' => $bookedTicket->id,
                'booking_api_id' => $bookingApiId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send WhatsApp notifications
     */
    private function sendWhatsAppNotifications(BookedTicket $bookedTicket, array $apiResponse, array $bookingData)
    {
        try {
            Log::info('Starting WhatsApp notification process', [
                'ticket_id' => $bookedTicket->id,
                'pnr' => $bookedTicket->pnr_number,
                'result_index' => $bookingData['result_index']
            ]);

            // Prepare ticket details for WhatsApp
            $ticketDetails = $this->prepareTicketDetailsForWhatsApp($bookedTicket, $apiResponse, $bookingData);

            // Send ticket details to passenger (user who booked)
            $passengerWhatsAppSuccess = sendTicketDetailsWhatsApp($ticketDetails, $bookedTicket->user->mobile ?? null);

            // Send ticket details to admin (always notify admin)
            $adminWhatsAppSuccess = sendTicketDetailsWhatsApp($ticketDetails, "8269566034");

            // Send ticket details to agent if booking was made by agent
            $agentWhatsAppSuccess = true;
            if ($bookedTicket->agent_id) {
                $agent = \App\Models\Agent::find($bookedTicket->agent_id);
                if ($agent && $agent->phone) {
                    $agentWhatsAppSuccess = sendTicketDetailsWhatsApp($ticketDetails, $agent->phone);
                    Log::info('Agent WhatsApp notification sent', [
                        'ticket_id' => $bookedTicket->id,
                        'agent_id' => $bookedTicket->agent_id,
                        'agent_phone' => $agent->phone,
                        'success' => $agentWhatsAppSuccess
                    ]);
                }
            }

            // Send ticket details to operator if booking is for operator bus
            $operatorWhatsAppSuccess = true;
            if ($bookedTicket->operator_id) {
                $operator = \App\Models\Operator::find($bookedTicket->operator_id);
                if ($operator && $operator->mobile) {
                    $operatorWhatsAppSuccess = sendTicketDetailsWhatsApp($ticketDetails, $operator->mobile);
                    Log::info('Operator WhatsApp notification sent', [
                        'ticket_id' => $bookedTicket->id,
                        'operator_id' => $bookedTicket->operator_id,
                        'operator_mobile' => $operator->mobile,
                        'success' => $operatorWhatsAppSuccess
                    ]);
                }
            }

            Log::info('WhatsApp notification results for all stakeholders', [
                'ticket_id' => $bookedTicket->id,
                'passenger_success' => $passengerWhatsAppSuccess,
                'admin_success' => $adminWhatsAppSuccess,
                'agent_success' => $agentWhatsAppSuccess,
                'operator_success' => $operatorWhatsAppSuccess
            ]);

            // Check if critical notifications failed (passenger and admin are mandatory)
            if (!$passengerWhatsAppSuccess || !$adminWhatsAppSuccess) {
                Log::error('Critical WhatsApp notification failed', [
                    'ticket_id' => $bookedTicket->id,
                    'passenger_success' => $passengerWhatsAppSuccess,
                    'admin_success' => $adminWhatsAppSuccess
                ]);
                return false;
            }
            
            // Log warning if agent/operator notifications failed but don't fail the booking
            if (!$agentWhatsAppSuccess || !$operatorWhatsAppSuccess) {
                Log::warning('Non-critical WhatsApp notification failed', [
                    'ticket_id' => $bookedTicket->id,
                    'agent_success' => $agentWhatsAppSuccess,
                    'operator_success' => $operatorWhatsAppSuccess
                ]);
            }

            // For operator buses, send crew notifications
            if (str_starts_with($bookingData['result_index'], 'OP_')) {
                $operatorBusId = (int) str_replace('OP_', '', $bookingData['result_index']);

                $whatsappBookingDetails = [
                    'source_name' => $ticketDetails['source_name'],
                    'destination_name' => $ticketDetails['destination_name'],
                    'date_of_journey' => $bookedTicket->date_of_journey,
                    'pnr' => $bookedTicket->pnr_number,
                    'seats' => is_array($bookedTicket->seats) ? implode(', ', $bookedTicket->seats) : $bookedTicket->seats,
                    'boarding_details' => $ticketDetails['boarding_details'],
                    'drop_off_details' => $ticketDetails['drop_off_details'],
                    'travel_date' => $bookedTicket->date_of_journey,
                    'departure_time' => $bookedTicket->departure_time ?? 'N/A',
                    'passenger_count' => $bookedTicket->ticket_count,
                    'total_amount' => $bookedTicket->sub_total,
                    'booking_id' => $bookedTicket->pnr_number
                ];

                $whatsappResults = \App\Http\Helpers\WhatsAppHelper::sendCrewBookingNotification($operatorBusId, $whatsappBookingDetails);

                Log::info('WhatsApp crew notification results', [
                    'ticket_id' => $bookedTicket->id,
                    'operator_bus_id' => $operatorBusId,
                    'results' => $whatsappResults
                ]);

                if ($whatsappResults && is_array($whatsappResults)) {
                    foreach ($whatsappResults as $result) {
                        if (!$result['success']) {
                            Log::error('WhatsApp notification failed for crew member', [
                                'staff_id' => $result['staff_id'],
                                'staff_name' => $result['staff_name'],
                                'role' => $result['role']
                            ]);
                            return false;
                        }
                    }
                } else {
                    Log::error('WhatsApp crew notification failed completely', [
                        'ticket_id' => $bookedTicket->id,
                        'operator_bus_id' => $operatorBusId
                    ]);
                    return false;
                }
            } else {
                // For third-party buses, we don't have crew assignments
                Log::info('Third-party bus - WhatsApp crew notifications not applicable', [
                    'ticket_id' => $bookedTicket->id,
                    'result_index' => $bookingData['result_index']
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('BookingService: WhatsApp notification failed', [
                'ticket_id' => $bookedTicket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Prepare ticket details for WhatsApp notification
     */
    private function prepareTicketDetailsForWhatsApp(BookedTicket $bookedTicket, array $apiResponse, array $bookingData)
    {
        // Get origin and destination cities
        $originCity = $bookedTicket->origin_city ?? 'Origin City';
        $destinationCity = $bookedTicket->destination_city ?? 'Destination City';

        // Safely decode boarding and dropping point details
        $boardingDetails = json_decode($bookedTicket->boarding_point_details, true);
        $droppingDetails = json_decode($bookedTicket->dropping_point_details, true);

        // Construct readable details for WhatsApp
        $boardingDetailsString = 'Not Available';
        if ($boardingDetails) {
            $boardingDetailsString = ($boardingDetails['CityPointName'] ?? '') . ', ' .
                ($boardingDetails['CityPointLocation'] ?? '') . '. Time: ' .
                Carbon::parse($boardingDetails['CityPointTime'] ?? now())->format('h:i A') .
                ' Contact Number: ' . ($boardingDetails['CityPointContactNumber'] ?? '');
        }

        $droppingDetailsString = 'Not Available';
        if ($droppingDetails) {
            $droppingDetailsString = ($droppingDetails['CityPointName'] ?? '') . ', ' .
                ($droppingDetails['CityPointLocation'] ?? '');
        }

        return [
            'pnr' => $bookedTicket->pnr_number,
            'source_name' => $originCity,
            'destination_name' => $destinationCity,
            'date_of_journey' => $bookedTicket->date_of_journey,
            'seats' => is_array($bookedTicket->seats) ? implode(', ', $bookedTicket->seats) : $bookedTicket->seats,
            'passenger_name' => $bookedTicket->passenger_name ?? 'Guest',
            'boarding_details' => $boardingDetailsString,
            'drop_off_details' => $droppingDetailsString,
        ];
    }

    /**
     * Cancel booking due to notification failure
     */
    private function cancelBookingDueToNotificationFailure(BookedTicket $bookedTicket, array $apiResponse, array $bookingData)
    {
        try {
            $cancelResponse = cancelAPITicket(
                $bookingData['user_ip'],
                $bookingData['search_token_id'],
                $apiResponse['Result']['BookingId'] ?? $bookedTicket->pnr_number,
                is_array($bookedTicket->seats) ? $bookedTicket->seats[0] : $bookedTicket->seats,
                'WhatsApp notification failed - automatic cancellation'
            );

            $bookedTicket->update(['status' => 0]); // Cancelled

            Log::info('BookingService: Ticket cancelled due to WhatsApp failure', [
                'ticket_id' => $bookedTicket->id,
                'cancel_response' => $cancelResponse
            ]);

        } catch (\Exception $e) {
            Log::error('BookingService: Failed to cancel ticket after WhatsApp failure', [
                'ticket_id' => $bookedTicket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Format cancellation policy
     * Handles both operator bus format (TimeBeforeDept) and third-party API format (FromDate/ToDate)
     */
    private function formatCancellationPolicy(array $cancelPolicy)
    {
        // Check if this is operator bus format (has TimeBeforeDept) or third-party API format (has FromDate)
        if (!empty($cancelPolicy) && isset($cancelPolicy[0]['TimeBeforeDept'])) {
            // Operator bus format - already has PolicyString, return as-is
            return $cancelPolicy;
        } else {
            // Third-party API format - use formatCancelPolicy helper
            return formatCancelPolicy($cancelPolicy);
        }
    }
}
