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

            // Calculate base fare with markup and coupon
            $fareCalculation = $this->calculateTotalFare($blockResponse['Result']);
            $baseFare = $fareCalculation['base_fare_after_coupon']; // Use fare after markup AND coupon for fee calculation

            // Create pending ticket record (will calculate fees and total_amount internally)
            $bookedTicket = $this->createPendingTicket($requestData, $blockResponse, $fareCalculation, $user->id);

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
     * Always creates/updates user profile during booking, even without mobile verification (sv=0)
     */
    private function registerOrLoginUser(array $requestData)
    {
        if (!Auth::check()) {
            $fullPhone = $requestData['Phoneno'] ?? $requestData['passenger_phone'];

            // Normalize to 10-digit mobile number (remove country code)
            if (strpos($fullPhone, '+91') === 0) {
                $fullPhone = substr($fullPhone, 3); // Remove +91
            } elseif (strpos($fullPhone, '91') === 0 && strlen($fullPhone) > 10) {
                $fullPhone = substr($fullPhone, 2); // Remove 91 prefix
            }
            // Ensure we have exactly 10 digits
            $fullPhone = substr($fullPhone, -10); // Take last 10 digits only

            Log::info('BookingService: Normalized phone number', [
                'original' => $requestData['Phoneno'] ?? $requestData['passenger_phone'],
                'normalized' => $fullPhone
            ]);

            // Handle firstname and lastname - support both single passenger and multiple passengers (agent/admin)
            $firstName = $requestData['FirstName']
                ?? (isset($requestData['passenger_firstnames']) && is_array($requestData['passenger_firstnames'])
                    ? ($requestData['passenger_firstnames'][0] ?? '')
                    : ($requestData['passenger_firstname'] ?? ''));

            $lastName = $requestData['LastName']
                ?? (isset($requestData['passenger_lastnames']) && is_array($requestData['passenger_lastnames'])
                    ? ($requestData['passenger_lastnames'][0] ?? '')
                    : ($requestData['passenger_lastname'] ?? ''));

            $email = $requestData['Email'] ?? $requestData['passenger_email'] ?? null;
            $address = $requestData['Address'] ?? $requestData['passenger_address'] ?? '';

            // Find existing user by 10-digit mobile or create new
            $user = User::where('mobile', $fullPhone)->first();

            if ($user) {
                // User exists - UPDATE their profile with latest checkout data
                Log::info('BookingService: Updating existing user profile', [
                    'user_id' => $user->id,
                    'mobile' => $fullPhone,
                    'updating_fields' => ['firstname', 'lastname', 'email', 'address']
                ]);

                // Update profile with checkout data (keep existing values if new data is empty)
                $updateData = [];

                if (!empty($firstName)) {
                    $updateData['firstname'] = $firstName;
                }
                if (!empty($lastName)) {
                    $updateData['lastname'] = $lastName;
                }
                if (!empty($email)) {
                    $updateData['email'] = $email;
                }
                if (!empty($address)) {
                    $updateData['address'] = [
                        'address' => $address,
                        'state' => $user->address->state ?? '',
                        'zip' => $user->address->zip ?? '',
                        'country' => $user->address->country ?? 'India',
                        'city' => $user->address->city ?? ''
                    ];
                }

                // Only update if we have data to update
                if (!empty($updateData)) {
                    $user->update($updateData);
                    Log::info('BookingService: User profile updated', $updateData);
                }
            } else {
                // User doesn't exist - CREATE new user with sv=0 (not verified)
                Log::info('BookingService: Creating new user profile', [
                    'mobile' => $fullPhone,
                    'firstname' => $firstName,
                    'lastname' => $lastName
                ]);

                $user = User::create([
                    'mobile' => $fullPhone,
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'email' => $email,
                    'username' => 'user' . time() . rand(100, 999), // Ensure uniqueness
                    'password' => Hash::make(Str::random(8)),
                    'country_code' => '91',
                    'address' => [
                        'address' => $address,
                        'state' => '',
                        'zip' => '',
                        'country' => 'India',
                        'city' => ''
                    ],
                    'status' => 1,   // Active
                    'ev' => 0,       // Email not verified
                    'sv' => 0,       // Mobile not verified (will be verified through OTP)
                ]);
            }

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
            $scheduleId = !empty($parts) ? (int) end($parts) : null;

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
            // Priority: Schedule-specific points > Route-level points
            // Fix: BoardingPointId/DroppingPointId from API is point_index, not database id
            $boardingPoint = null;
            $droppingPoint = null;
            $scheduleRoute = null;

            if ($scheduleId) {
                $schedule = \App\Models\BusSchedule::with(['boardingPoints', 'droppingPoints', 'operatorRoute.boardingPoints', 'operatorRoute.droppingPoints'])->find($scheduleId);
                if ($schedule) {
                    // Try schedule-specific points first
                    $boardingPoint = $schedule->boardingPoints->firstWhere('point_index', $boardingPointId);
                    $droppingPoint = $schedule->droppingPoints->firstWhere('point_index', $droppingPointId);

                    // Store schedule's route for fallback
                    $scheduleRoute = $schedule->operatorRoute;
                }
            }

            // Fallback to route-level points if no schedule-specific points found
            // Use schedule's route if available, otherwise use bus's current route
            $routeToSearch = $scheduleRoute ?? $operatorBus->currentRoute;

            if (!$boardingPoint && $routeToSearch) {
                $boardingPoint = $routeToSearch->boardingPoints->firstWhere('point_index', $boardingPointId)
                    ?? $routeToSearch->boardingPoints->find($boardingPointId);
            }
            if (!$droppingPoint && $routeToSearch) {
                $droppingPoint = $routeToSearch->droppingPoints->firstWhere('point_index', $droppingPointId)
                    ?? $routeToSearch->droppingPoints->find($droppingPointId);
            }

            Log::info('BookingService: Found boarding/dropping points', [
                'boarding_point_id' => $boardingPointId,
                'dropping_point_id' => $droppingPointId,
                'schedule_id' => $scheduleId,
                'schedule_route_id' => $scheduleRoute ? $scheduleRoute->id : null,
                'bus_current_route_id' => $operatorBus->currentRoute ? $operatorBus->currentRoute->id : null,
                'route_searched' => $routeToSearch ? $routeToSearch->id : null,
                'boarding_point_found' => $boardingPoint ? $boardingPoint->point_name : 'NOT FOUND',
                'dropping_point_found' => $droppingPoint ? $droppingPoint->point_name : 'NOT FOUND',
                'boarding_point_details' => $boardingPoint ? [
                    'id' => $boardingPoint->id,
                    'point_index' => $boardingPoint->point_index,
                    'point_name' => $boardingPoint->point_name,
                    'point_address' => $boardingPoint->point_address,
                    'point_location' => $boardingPoint->point_location,
                    'contact_number' => $boardingPoint->contact_number,
                    'point_landmark' => $boardingPoint->point_landmark,
                    'bus_schedule_id' => $boardingPoint->bus_schedule_id,
                    'operator_route_id' => $boardingPoint->operator_route_id
                ] : null
            ]);

            // Fix: Create boarding point details with all required fields (matching third-party API format)
            // Ensure we have valid data before creating the details
            if (!$boardingPoint) {
                Log::error('BookingService: Boarding point not found', [
                    'boarding_point_id' => $boardingPointId,
                    'operator_bus_id' => $operatorBusId,
                    'schedule_id' => $scheduleId,
                    'schedule_route_id' => $scheduleRoute ? $scheduleRoute->id : null,
                    'bus_current_route_id' => $operatorBus->currentRoute ? $operatorBus->currentRoute->id : null,
                    'route_searched' => $routeToSearch ? $routeToSearch->id : null
                ]);
            }

            $boardingPointDetails = $boardingPoint ? [
                [
                    'CityPointIndex' => $boardingPoint->point_index ?? $boardingPoint->id,
                    'CityPointName' => trim($boardingPoint->point_name ?? ''),
                    'CityPointLocation' => trim($boardingPoint->point_location ?? $boardingPoint->point_address ?? $boardingPoint->point_name ?? ''),
                    'CityPointAddress' => trim($boardingPoint->point_address ?? $boardingPoint->point_location ?? $boardingPoint->point_name ?? ''),
                    'CityPointLandmark' => trim($boardingPoint->point_landmark ?? ''),
                    'CityPointContactNumber' => trim($boardingPoint->contact_number ?? ''),
                    'CityPointTime' => Carbon::parse($departureTime)->format('Y-m-d\TH:i:s'),
                ]
            ] : [];

            // Fix: Create dropping point details with all required fields (matching third-party API format)
            // Ensure we have valid data before creating the details
            if (!$droppingPoint) {
                Log::error('BookingService: Dropping point not found', [
                    'dropping_point_id' => $droppingPointId,
                    'operator_bus_id' => $operatorBusId,
                    'schedule_id' => $scheduleId,
                    'schedule_route_id' => $scheduleRoute ? $scheduleRoute->id : null,
                    'bus_current_route_id' => $operatorBus->currentRoute ? $operatorBus->currentRoute->id : null,
                    'route_searched' => $routeToSearch ? $routeToSearch->id : null
                ]);
            }

            $droppingPointDetails = $droppingPoint ? [
                [
                    'CityPointIndex' => $droppingPoint->point_index ?? $droppingPoint->id,
                    'CityPointName' => trim($droppingPoint->point_name ?? ''),
                    'CityPointLocation' => trim($droppingPoint->point_location ?? $droppingPoint->point_address ?? $droppingPoint->point_name ?? ''),
                    'CityPointAddress' => trim($droppingPoint->point_address ?? $droppingPoint->point_location ?? $droppingPoint->point_name ?? ''),
                    'CityPointLandmark' => trim($droppingPoint->point_landmark ?? ''),
                    'CityPointContactNumber' => trim($droppingPoint->contact_number ?? ''),
                    'CityPointTime' => Carbon::parse($arrivalTime)->format('Y-m-d\TH:i:s'),
                ]
            ] : [];

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
                            'CGSTAmount' => 0,
                            'CGSTRate' => 0,
                            'IGSTAmount' => 0,
                            'IGSTRate' => 0,
                            'SGSTAmount' => 0,
                            'SGSTRate' => 0,
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
                // Fix: $boardingPointDetails and $droppingPointDetails are already arrays, don't wrap again
                'BoardingPointdetails' => $boardingPointDetails,
                'DroppingPointsdetails' => $droppingPointDetails,
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
     * Calculate total fare from block response with markup and coupon applied
     * Returns: ['base_fare_before_markup' => X, 'markup_amount' => Y, 'base_fare_after_markup' => Z, 'coupon_discount' => W, 'base_fare_after_coupon' => V]
     */
    private function calculateTotalFare(array $blockResult)
    {
        // Get markup settings
        $markupData = \App\Models\MarkupTable::orderBy('id', 'desc')->first();
        $flatMarkup = isset($markupData->flat_markup) ? (float) $markupData->flat_markup : 0;
        $percentageMarkup = isset($markupData->percentage_markup) ? (float) $markupData->percentage_markup : 0;
        $threshold = isset($markupData->threshold) ? (float) $markupData->threshold : 0;

        // Get active coupon settings
        $currentCoupon = \App\Models\CouponTable::where('status', 1)
            ->where('expiry_date', '>=', \Carbon\Carbon::today())
            ->first();

        $baseFareBeforeMarkup = 0;
        $totalMarkupAmount = 0;
        $totalCouponDiscount = 0;

        foreach ($blockResult['Passenger'] as $passenger) {
            $seatPrice = $passenger['Seat']['Price']['PublishedPrice'] ?? 0;
            $baseFareBeforeMarkup += $seatPrice;

            // Apply markup per seat (matching frontend logic)
            $markupAmount = $seatPrice < $threshold ? $flatMarkup : ($seatPrice * $percentageMarkup / 100);
            $totalMarkupAmount += $markupAmount;

            // Apply coupon discount per seat (matching frontend logic)
            $priceWithMarkup = $seatPrice + $markupAmount;
            if ($currentCoupon && $currentCoupon->status == 1) {
                $couponThreshold = (float) $currentCoupon->coupon_threshold;
                $couponValue = (float) $currentCoupon->coupon_value;

                // Apply discount ONLY if price is ABOVE the threshold
                if ($priceWithMarkup > $couponThreshold) {
                    $discountAmount = 0;
                    if ($currentCoupon->discount_type === 'fixed') {
                        $discountAmount = $couponValue;
                    } elseif ($currentCoupon->discount_type === 'percentage') {
                        $discountAmount = ($priceWithMarkup * $couponValue / 100);
                    }
                    // Ensure discount doesn't exceed the price
                    $discountAmount = min($discountAmount, $priceWithMarkup);
                    $totalCouponDiscount += $discountAmount;
                }
            }
        }

        $baseFareAfterMarkup = $baseFareBeforeMarkup + $totalMarkupAmount;
        $baseFareAfterCoupon = $baseFareAfterMarkup - $totalCouponDiscount;

        return [
            'base_fare_before_markup' => round($baseFareBeforeMarkup, 2),
            'markup_amount' => round($totalMarkupAmount, 2),
            'base_fare_after_markup' => round($baseFareAfterMarkup, 2),
            'coupon_discount' => round($totalCouponDiscount, 2),
            'base_fare_after_coupon' => round($baseFareAfterCoupon, 2)
        ];
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

        // PRIORITY 1: Get from request/session data (actual search direction)
        $originId = $requestData['origin_id'] ?? $requestData['OriginId'] ?? session('origin_id') ?? null;
        $destinationId = $requestData['destination_id'] ?? $requestData['DestinationId'] ?? session('destination_id') ?? null;

        // If it's a string (city name), try to find the ID
        if (!$originId && isset($requestData['origin_city']) && is_numeric($requestData['origin_city'])) {
            $originId = $requestData['origin_city'];
        }
        if (!$destinationId && isset($requestData['destination_city']) && is_numeric($requestData['destination_city'])) {
            $destinationId = $requestData['destination_city'];
        }

        // PRIORITY 2: Fallback to operator bus route (only if session data not available)
        // Note: This fallback may not respect the actual search direction for bidirectional routes
        if ((!$originId || !$destinationId) && str_starts_with($resultIndex, 'OP_')) {
            $operatorBusId = (int) str_replace('OP_', '', $resultIndex);
            $operatorBus = OperatorBus::with('currentRoute.originCity', 'currentRoute.destinationCity')->find($operatorBusId);

            if ($operatorBus && $operatorBus->currentRoute) {
                $originId = $originId ?? ($operatorBus->currentRoute->origin_city_id ?? null);
                $destinationId = $destinationId ?? ($operatorBus->currentRoute->destination_city_id ?? null);
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
        // Fix: Use correct cache keys (origin_id, destination_id) not origin_city_id
        $searchTokenId = $requestData['SearchTokenId'] ?? $requestData['search_token_id'] ?? null;
        if ((!$originId || !$destinationId) && $searchTokenId) {
            $cachedBuses = Cache::get('bus_search_results_' . $searchTokenId);
            if ($cachedBuses) {
                // Cache stores as origin_id and destination_id (from ApiTicketController)
                $originId = $originId ?? ($cachedBuses['origin_id'] ?? null);
                $destinationId = $destinationId ?? ($cachedBuses['destination_id'] ?? null);

                Log::info('BookingService: Retrieved city IDs from cache', [
                    'search_token_id' => $searchTokenId,
                    'origin_id' => $originId,
                    'destination_id' => $destinationId,
                    'cache_keys' => array_keys($cachedBuses)
                ]);
            } else {
                Log::warning('BookingService: Cache not found for search token', [
                    'search_token_id' => $searchTokenId
                ]);
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
    private function createPendingTicket(array $requestData, array $blockResponse, array $fareCalculation, int $userId)
    {
        $seats = is_array($requestData['Seats'] ?? $requestData['seats'])
            ? $requestData['Seats'] ?? $requestData['seats']
            : explode(',', $requestData['Seats'] ?? $requestData['seats']);

        $resultIndex = $requestData['ResultIndex'] ?? $requestData['result_index'] ?? '';
        $isOperatorBus = str_starts_with($resultIndex, 'OP_');

        // Get city IDs and names
        $cityData = $this->getCityIdsAndNames($requestData, $resultIndex, $blockResponse);
        $originId = $cityData['origin_id'] ?? null;
        $destinationId = $cityData['destination_id'] ?? null;
        $originName = $cityData['origin_name'];
        $destinationName = $cityData['destination_name'];

        // Validate that we have valid city IDs (not 0 or null)
        if (!$originId || $originId == 0 || !$destinationId || $destinationId == 0) {
            Log::error('BookingService: Invalid city IDs', [
                'origin_id' => $originId,
                'destination_id' => $destinationId,
                'result_index' => $resultIndex,
                'request_data_keys' => array_keys($requestData),
                'city_data' => $cityData
            ]);

            // Try one more time to get from request directly
            $originId = $originId ?? $requestData['OriginId'] ?? $requestData['origin_id'] ?? null;
            $destinationId = $destinationId ?? $requestData['DestinationId'] ?? $requestData['destination_id'] ?? null;

            if (!$originId || $originId == 0 || !$destinationId || $destinationId == 0) {
                throw new \Exception('Invalid origin or destination city IDs. Cannot create ticket without valid city IDs.');
            }
        }

        // Calculate unit price per seat (API price, before markup)
        $totalUnitPrice = collect($blockResponse['Result']['Passenger'])->sum(function ($passenger) {
            return $passenger['Seat']['Price']['OfferedPrice'] ?? 0;
        });
        $unitPrice = count($seats) > 0 ? round($totalUnitPrice / count($seats), 2) : round($totalUnitPrice, 2);

        // Extract fare breakdown
        $baseFare = $fareCalculation['base_fare_after_coupon']; // Base fare after markup AND coupon
        $markupAmount = $fareCalculation['markup_amount'];
        $couponDiscount = $fareCalculation['coupon_discount'];

        // Calculate fees and total amount (on base fare after markup)
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
                $scheduleId = !empty($parts) ? (int) end($parts) : null;

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
        $bookedTicket->source_destination = json_encode([(string) $originId, (string) $destinationId]);

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
            $scheduleId = !empty($parts) ? (int) end($parts) : null;

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
        $bookedTicket->unit_price = $unitPrice; // API price per seat (before markup)
        // sub_total = unit_price + markup (base fare after markup, before fees)
        $bookedTicket->sub_total = round($baseFare, 2); // This is base_fare_after_markup

        // Save fee breakdown with amounts and percentages
        $bookedTicket->service_charge = $feeCalculation['service_charge'];
        $bookedTicket->service_charge_percentage = $feeCalculation['service_charge_percentage'];
        $bookedTicket->platform_fee = $feeCalculation['platform_fee'];
        $bookedTicket->platform_fee_percentage = $feeCalculation['platform_fee_percentage'];
        $bookedTicket->platform_fee_fixed = $feeCalculation['platform_fee_fixed'];
        $bookedTicket->gst = $feeCalculation['gst'];
        $bookedTicket->gst_percentage = $feeCalculation['gst_percentage'];
        // total_amount = sub_total + service_charge + platform_fee + gst
        $bookedTicket->total_amount = $feeCalculation['total_amount'];
        // paid_amount is set when payment is confirmed
        $bookedTicket->paid_amount = $feeCalculation['total_amount'];

        $bookedTicket->pnr_number = getTrx(10);

        // Fix: Use boarding_point_id for dropping_point (pickup_point and boarding_point are redundant and will be dropped)
        $boardingPointId = $requestData['BoardingPointId'] ?? $requestData['boarding_point_index'] ?? null;
        $droppingPointId = $requestData['DroppingPointId'] ?? $requestData['dropping_point_index'] ?? null;

        // Note: pickup_point and boarding_point are redundant - migration will drop them
        // For now, set dropping_point only
        $bookedTicket->dropping_point = $droppingPointId;

        // Fix: Save SearchTokenId for getBookingDetails
        // Note: UserIp is stored in api_response (no user_ip column exists)
        $searchTokenId = $requestData['SearchTokenId'] ?? $requestData['search_token_id'] ?? null;
        $userIp = $requestData['UserIp'] ?? $requestData['user_ip'] ?? request()->ip();

        $bookedTicket->search_token_id = $searchTokenId;

        Log::info('BookingService: Saved search token and user IP', [
            'search_token_id' => $searchTokenId,
            'user_ip' => $userIp,
            'ticket_id' => $bookedTicket->id ?? 'pending',
            'note' => 'UserIp stored in api_response'
        ]);
        // Get date of journey from multiple sources, ensuring it's in Y-m-d format
        $dateOfJourney = $requestData['DateOfJourney'] ?? $requestData['date_of_journey'] ?? null;

        // For API requests: Try to get from cache using SearchTokenId (same as getCounters)
        if (!$dateOfJourney && $searchTokenId) {
            $cachedData = Cache::get('bus_search_results_' . $searchTokenId);
            if ($cachedData && isset($cachedData['date_of_journey'])) {
                $dateOfJourney = $cachedData['date_of_journey'];
                Log::info('BookingService: Retrieved date_of_journey from cache', [
                    'search_token_id' => $searchTokenId,
                    'date_of_journey' => $dateOfJourney
                ]);
            }
        }

        // Try to get from session if not in request (session stores it from ticketSearch - for web requests)
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

        // Fix: Standardize api_response with correct origin/destination, UserIp, and SearchTokenId
        $standardizedBlockResponse = $blockResponse;
        if (isset($standardizedBlockResponse['Result'])) {
            $standardizedBlockResponse['Result']['Origin'] = $originName;
            $standardizedBlockResponse['Result']['Destination'] = $destinationName;
            $standardizedBlockResponse['Result']['OriginId'] = $originId;
            $standardizedBlockResponse['Result']['DestinationId'] = $destinationId;
        }
        // Add UserIp and SearchTokenId to api_response for consistency (used by getBookingDetails)
        $standardizedBlockResponse['UserIp'] = $userIp;
        $standardizedBlockResponse['SearchTokenId'] = $searchTokenId;
        $standardizedBlockResponse['success'] = true;
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

        // Invalidate seat availability cache immediately after blocking seats
        if ($isOperatorBus && $operatorBusId && $scheduleId && $dateOfJourney) {
            $availabilityService = new \App\Services\SeatAvailabilityService();
            $availabilityService->invalidateCache(
                $operatorBusId,
                $scheduleId,
                $dateOfJourney
            );
            Log::info('BookingService: Invalidated seat availability cache after seat block', [
                'bus_id' => $operatorBusId,
                'schedule_id' => $scheduleId,
                'date_of_journey' => $dateOfJourney,
                'ticket_id' => $bookedTicket->id,
                'seats' => implode(',', $seats),
                'note' => 'Cache cleared immediately after blocking seats so other users see updated availability'
            ]);
        }

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

        // Build response matching third-party API format with UserIp and SearchTokenId
        return [
            'success' => true,
            'Result' => [
                'BookingId' => $bookingId,
                'TravelOperatorPNR' => $bookingId,
                'BookingStatus' => 'Confirmed',
                'InvoiceNumber' => 'OP_INV_' . time(),
                'InvoiceAmount' => (string) ($bookedTicket->total_amount ?? 1000), // Use actual total amount
                'InvoiceCreatedOn' => now()->toISOString(),
                'TicketNo' => 'OP_TKT_' . time(),
                'Origin' => $originName ?? 'Origin City',
                'Destination' => $destinationName ?? 'Destination City',
                'Price' => [
                    'AgentCommission' => number_format($bookedTicket->agent_commission_amount ?? 0, 2, '.', ''),
                    'TDS' => 0
                ]
            ],
            'UserIp' => $bookingData['user_ip'] ?? request()->ip(),
            'SearchTokenId' => $bookedTicket->search_token_id ?? $bookingData['search_token_id'] ?? '',
            'Error' => [
                'ErrorCode' => 0,
                'ErrorMessage' => ''
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

        // Ensure UserIp and SearchTokenId are included in api_response (for getBookingDetails)
        if (!isset($completeApiResponse['UserIp']) && $bookedTicket->user_ip) {
            $completeApiResponse['UserIp'] = $bookedTicket->user_ip;
        }
        if (!isset($completeApiResponse['SearchTokenId']) && $bookedTicket->search_token_id) {
            $completeApiResponse['SearchTokenId'] = $bookedTicket->search_token_id;
        }

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

        // Process referral rewards for first booking
        $this->processReferralRewards($bookedTicket);

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

        // Fix: Set booking_id for third-party buses (always update from API response)
        if (isset($result['BookingId'])) {
            $updateData['api_booking_id'] = $result['BookingId'];
            // For third-party buses, booking_id should match api_booking_id
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

                // Fix: Set booking_id from api_booking_id for third-party buses
                // For third-party buses, booking_id should be the API's BookingId
                if (isset($result['BookingId'])) {
                    $updateData['api_booking_id'] = $result['BookingId'];
                    // Set booking_id to BookingId for third-party buses
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

            // Send ticket details to passenger (use passenger_phone from booking, fallback to user mobile)
            $passengerMobile = $bookedTicket->passenger_phone ?? ($bookedTicket->user->mobile ?? null);
            $passengerWhatsAppSuccess = sendTicketDetailsWhatsApp($ticketDetails, $passengerMobile);

            Log::info('Passenger WhatsApp notification attempted', [
                'ticket_id' => $bookedTicket->id,
                'passenger_phone' => $passengerMobile,
                'success' => $passengerWhatsAppSuccess
            ]);

            // Send ticket details to admin (always notify admin)
            $adminWhatsAppSuccess = sendTicketDetailsWhatsApp($ticketDetails, "8269566034");

            Log::info('Admin WhatsApp notification attempted', [
                'ticket_id' => $bookedTicket->id,
                'admin_phone' => '8269566034',
                'success' => $adminWhatsAppSuccess
            ]);

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

                // Format seats with passenger info for crew
                $seatNumbers = is_array($bookedTicket->seats) ? implode(', ', $bookedTicket->seats) : $bookedTicket->seats;
                $passengerName = is_array($bookedTicket->passenger_names) && !empty($bookedTicket->passenger_names)
                    ? $bookedTicket->passenger_names[0]
                    : ($bookedTicket->passenger_name ?? 'Passenger');
                $passengerPhone = $bookedTicket->passenger_phone ?? 'N/A';

                $seatInfo = "{$seatNumbers} booked by {$passengerName}, call on {$passengerPhone}";

                $whatsappBookingDetails = [
                    'source_name' => $ticketDetails['source_name'],
                    'destination_name' => $ticketDetails['destination_name'],
                    'date_of_journey' => $bookedTicket->date_of_journey,
                    'pnr' => $bookedTicket->pnr_number,
                    'seats' => $seatInfo,
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
        // Generate PDF and get media URL
        $pdfUrl = $this->generateTicketPDF($bookedTicket);

        // Get origin and destination cities
        $originCity = $bookedTicket->origin_city ?? 'Origin City';
        $destinationCity = $bookedTicket->destination_city ?? 'Destination City';

        // Safely decode boarding and dropping point details
        $boardingDetails = json_decode($bookedTicket->boarding_point_details, true);
        $droppingDetails = json_decode($bookedTicket->dropping_point_details, true);

        // Construct readable details for WhatsApp
        // Handle both array format (third-party) and object format (operator buses)
        $boardingDetailsString = 'Not Available';
        if ($boardingDetails) {
            // Handle array format: [['CityPointName' => ...], ...]
            $boardingPoint = is_array($boardingDetails) && isset($boardingDetails[0])
                ? $boardingDetails[0]
                : (is_array($boardingDetails) && isset($boardingDetails['CityPointName'])
                    ? $boardingDetails
                    : null);

            if ($boardingPoint && is_array($boardingPoint)) {
                $cityPointName = trim($boardingPoint['CityPointName'] ?? '');
                $cityPointLocation = trim($boardingPoint['CityPointLocation'] ?? $boardingPoint['CityPointAddress'] ?? '');
                $cityPointTime = $boardingPoint['CityPointTime'] ?? null;
                $cityPointContact = trim($boardingPoint['CityPointContactNumber'] ?? '');

                // Build boarding details string - only include non-empty parts
                $parts = array_filter([$cityPointName, $cityPointLocation], function ($part) {
                    return !empty(trim($part));
                });

                if (!empty($parts)) {
                    $boardingDetailsString = implode(', ', $parts);

                    // Add time if available
                    if ($cityPointTime) {
                        try {
                            $timeFormatted = Carbon::parse($cityPointTime)->format('h:i A');
                            $boardingDetailsString .= '. Time: ' . $timeFormatted;
                        } catch (\Exception $e) {
                            Log::warning('Failed to parse boarding point time', ['time' => $cityPointTime]);
                        }
                    }

                    // Add contact number if available
                    if (!empty($cityPointContact)) {
                        $boardingDetailsString .= ' Contact Number: ' . $cityPointContact;
                    }
                } else {
                    // If no name or location, show "Not Available"
                    $boardingDetailsString = 'Not Available';
                    if ($cityPointTime) {
                        try {
                            $timeFormatted = Carbon::parse($cityPointTime)->format('h:i A');
                            $boardingDetailsString .= '. Time: ' . $timeFormatted;
                        } catch (\Exception $e) {
                            // Ignore time parsing errors if we don't have location
                        }
                    }
                }
            }
        }

        $droppingDetailsString = 'Not Available';
        if ($droppingDetails) {
            // Handle array format: [['CityPointName' => ...], ...]
            $droppingPoint = is_array($droppingDetails) && isset($droppingDetails[0])
                ? $droppingDetails[0]
                : (is_array($droppingDetails) && isset($droppingDetails['CityPointName'])
                    ? $droppingDetails
                    : null);

            if ($droppingPoint && is_array($droppingPoint)) {
                $cityPointName = trim($droppingPoint['CityPointName'] ?? '');
                $cityPointLocation = trim($droppingPoint['CityPointLocation'] ?? $droppingPoint['CityPointAddress'] ?? '');

                // Build dropping details string - only include non-empty parts
                $parts = array_filter([$cityPointName, $cityPointLocation], function ($part) {
                    return !empty(trim($part));
                });

                $droppingDetailsString = !empty($parts) ? implode(', ', $parts) : 'Not Available';
            }
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
            'pdf_url' => $pdfUrl,
        ];
    }

    /**
     * Generate ticket PDF and save to public uploads directory
     */
    private function generateTicketPDF(BookedTicket $bookedTicket)
    {
        try {
            // Call the print ticket controller to get HTML
            $ticketController = new \App\Http\Controllers\TicketController();
            $formattedTicket = $ticketController->formatTicketForPrint($bookedTicket);

            // Get company details
            $general = \App\Models\GeneralSetting::first();
            $companyName = $general->sitename ?? 'Ghumantoo';

            // Get logo URL with proper error handling
            $logoPath = imagePath()['logoIcon']['path'] ?? 'assets/images/logoIcon';
            $logoFile = $logoPath . '/logo.png';

            // Use absolute path for file existence check
            $absoluteLogoPath = public_path($logoFile);

            // Only set logoUrl if file exists, otherwise use null
            $logoUrl = (file_exists($absoluteLogoPath) && is_file($absoluteLogoPath))
                ? asset($logoFile)
                : null;

            Log::info('PDF logo check', [
                'logo_file' => $logoFile,
                'absolute_path' => $absoluteLogoPath,
                'exists' => file_exists($absoluteLogoPath),
                'logo_url' => $logoUrl
            ]);

            // Render the PDF-optimized view to HTML
            $html = view('templates.basic.ticket.print_pdf', [
                'ticket' => (object) $formattedTicket,
                'companyName' => $companyName,
                'logoUrl' => $logoUrl,
            ])->render();

            // Generate PDF using Dompdf with options
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'isFontSubsettingEnabled' => false
            ]);

            // Ensure uploads/tickets directory exists (use core/public which is Laravel's public directory)
            $uploadPath = public_path('uploads/tickets');
            if (!\File::exists($uploadPath)) {
                \File::makeDirectory($uploadPath, 0755, true);
            }

            // Generate filename
            $filename = 'Ghumantoo_' . $bookedTicket->pnr_number . '.pdf';
            $filePath = $uploadPath . '/' . $filename;

            // Save PDF
            $pdf->save($filePath);

            // Return public URL - use url() instead of asset() for subdirectory support
            $publicUrl = url('uploads/tickets/' . $filename);

            Log::info('PDF generated successfully', [
                'ticket_id' => $bookedTicket->id,
                'file_path' => $filePath,
                'public_url' => $publicUrl,
                'file_exists' => file_exists($filePath)
            ]);

            return $publicUrl;

        } catch (\Exception $e) {
            Log::error('Failed to generate ticket PDF', [
                'ticket_id' => $bookedTicket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return null if PDF generation fails (WhatsApp will still send without PDF)
            return null;
        }
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

    /**
     * Cancel ticket (handles both operator and third-party buses)
     */
    public function cancelTicket(array $requestData)
    {
        try {
            Log::info('BookingService: Cancelling ticket', $requestData);

            $bookingId = $requestData['BookingId'];
            $userIp = $requestData['UserIp'] ?? request()->ip();
            $searchTokenId = $requestData['SearchTokenId'];
            $seatId = $requestData['SeatId'];
            $remarks = $requestData['Remarks'] ?? 'Cancelled by customer';

            // Find the booked ticket by BookingId
            // Try multiple fields: booking_id, api_booking_id, operator_pnr
            $bookedTicket = BookedTicket::where('booking_id', $bookingId)
                ->orWhere('api_booking_id', $bookingId)
                ->orWhere('operator_pnr', $bookingId)
                ->first();

            if (!$bookedTicket) {
                Log::error('BookingService: Ticket not found for cancellation', [
                    'booking_id' => $bookingId
                ]);
                return [
                    'success' => false,
                    'message' => 'Ticket not found',
                    'status_code' => 404
                ];
            }

            // Validate ticket can be cancelled
            if ($bookedTicket->status == 3) {
                return [
                    'success' => false,
                    'message' => 'Ticket is already cancelled',
                    'status_code' => 400
                ];
            }

            if ($bookedTicket->status == 0) {
                return [
                    'success' => false,
                    'message' => 'Cannot cancel a pending ticket. Please wait for confirmation or contact support.',
                    'status_code' => 400
                ];
            }

            // Check if ticket is for past journey
            if (Carbon::parse($bookedTicket->date_of_journey)->lt(Carbon::today())) {
                return [
                    'success' => false,
                    'message' => 'Cannot cancel a ticket for a past journey',
                    'status_code' => 400
                ];
            }

            // Determine if this is an operator bus or third-party bus
            $isOperatorBus = !empty($bookedTicket->bus_id) && !empty($bookedTicket->schedule_id);

            Log::info('BookingService: Cancelling ticket', [
                'ticket_id' => $bookedTicket->id,
                'booking_id' => $bookingId,
                'is_operator_bus' => $isOperatorBus,
                'bus_id' => $bookedTicket->bus_id,
                'schedule_id' => $bookedTicket->schedule_id
            ]);

            if ($isOperatorBus) {
                // Operator bus cancellation
                return $this->cancelOperatorBusTicket($bookedTicket, $seatId, $remarks);
            } else {
                // Third-party bus cancellation
                return $this->cancelThirdPartyBusTicket($bookedTicket, $userIp, $searchTokenId, $bookingId, $seatId, $remarks);
            }

        } catch (\Exception $e) {
            Log::error('BookingService: Error cancelling ticket', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $requestData
            ]);

            return [
                'success' => false,
                'message' => 'Failed to cancel ticket: ' . $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Cancel operator bus ticket
     */
    private function cancelOperatorBusTicket(BookedTicket $bookedTicket, string $seatId, string $remarks)
    {
        try {
            Log::info('BookingService: Cancelling operator bus ticket', [
                'ticket_id' => $bookedTicket->id,
                'seat_id' => $seatId,
                'bus_id' => $bookedTicket->bus_id,
                'schedule_id' => $bookedTicket->schedule_id,
                'date_of_journey' => $bookedTicket->date_of_journey
            ]);

            // Validate seat belongs to this ticket
            $seats = is_array($bookedTicket->seats) ? $bookedTicket->seats : explode(',', $bookedTicket->seats);
            if (!in_array($seatId, $seats)) {
                return [
                    'success' => false,
                    'message' => 'Seat not found in this booking',
                    'status_code' => 400
                ];
            }

            // Update ticket status to cancelled
            $bookedTicket->status = 3; // Cancelled
            $bookedTicket->cancellation_remarks = $remarks;
            $bookedTicket->cancelled_at = now();
            $bookedTicket->save();

            // Reverse referral rewards for cancelled booking
            $this->reverseReferralRewards($bookedTicket, $remarks);

            // Invalidate seat availability cache so seats become available again
            if ($bookedTicket->bus_id && $bookedTicket->schedule_id && $bookedTicket->date_of_journey) {
                $availabilityService = new \App\Services\SeatAvailabilityService();

                // Ensure date is in Y-m-d format
                $dateOfJourney = $bookedTicket->date_of_journey;
                if ($dateOfJourney instanceof \Carbon\Carbon) {
                    $dateOfJourney = $dateOfJourney->format('Y-m-d');
                } elseif (is_string($dateOfJourney)) {
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

                Log::info('BookingService: Invalidated seat availability cache after cancellation', [
                    'bus_id' => $bookedTicket->bus_id,
                    'schedule_id' => $bookedTicket->schedule_id,
                    'date_of_journey' => $dateOfJourney,
                    'ticket_id' => $bookedTicket->id,
                    'seats' => is_array($bookedTicket->seats) ? implode(',', $bookedTicket->seats) : $bookedTicket->seats
                ]);
            }

            return [
                'success' => true,
                'message' => 'Ticket cancelled successfully',
                'ticket_id' => $bookedTicket->id,
                'cancellation_details' => [
                    'cancelled_at' => $bookedTicket->cancelled_at->toDateTimeString(),
                    'remarks' => $bookedTicket->cancellation_remarks,
                    'status' => 'Cancelled'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('BookingService: Error cancelling operator bus ticket', [
                'ticket_id' => $bookedTicket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to cancel operator bus ticket: ' . $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Cancel third-party bus ticket
     */
    private function cancelThirdPartyBusTicket(BookedTicket $bookedTicket, string $userIp, string $searchTokenId, string $bookingId, string $seatId, string $remarks)
    {
        try {
            Log::info('BookingService: Cancelling third-party bus ticket', [
                'ticket_id' => $bookedTicket->id,
                'booking_id' => $bookingId,
                'seat_id' => $seatId,
                'user_ip' => $userIp,
                'search_token_id' => $searchTokenId
            ]);

            // Validate seat belongs to this ticket
            $seats = is_array($bookedTicket->seats) ? $bookedTicket->seats : explode(',', $bookedTicket->seats);
            if (!in_array($seatId, $seats)) {
                return [
                    'success' => false,
                    'message' => 'Seat not found in this booking',
                    'status_code' => 400
                ];
            }

            // Call third-party API to cancel the ticket
            $cancelResponse = cancelAPITicket($userIp, $searchTokenId, $bookingId, $seatId, $remarks);

            Log::info('BookingService: Third-party cancellation API response', [
                'ticket_id' => $bookedTicket->id,
                'cancel_response' => $cancelResponse
            ]);

            // Parse the response structure: SendChangeRequestResult
            $sendChangeRequestResult = $cancelResponse['SendChangeRequestResult'] ?? null;

            if (!$sendChangeRequestResult) {
                // Fallback: Check for Error directly in response (old format)
                if (isset($cancelResponse['Error']) && $cancelResponse['Error']['ErrorCode'] != 0) {
                    $errorCode = $cancelResponse['Error']['ErrorCode'];
                    $errorMessage = $cancelResponse['Error']['ErrorMessage'] ?? 'Failed to cancel ticket with provider';

                    Log::error('BookingService: Third-party cancellation failed (old format)', [
                        'ticket_id' => $bookedTicket->id,
                        'error_code' => $errorCode,
                        'error_message' => $errorMessage
                    ]);

                    // Store error in cancellation_details
                    $bookedTicket->cancellation_details = [
                        'ErrorCode' => $errorCode,
                        'ErrorMessage' => $errorMessage
                    ];
                    $bookedTicket->save();

                    return [
                        'success' => false,
                        'message' => $errorMessage,
                        'error' => [
                            'ErrorCode' => $errorCode,
                            'ErrorMessage' => $errorMessage
                        ],
                        'status_code' => 400
                    ];
                }

                // No SendChangeRequestResult and no Error - invalid response
                Log::error('BookingService: Invalid cancellation response format', [
                    'ticket_id' => $bookedTicket->id,
                    'response' => $cancelResponse
                ]);

                return [
                    'success' => false,
                    'message' => 'Invalid cancellation response format',
                    'error' => ['ErrorCode' => -1, 'ErrorMessage' => 'Invalid response structure'],
                    'status_code' => 500
                ];
            }

            // Check ErrorCode from SendChangeRequestResult.Error
            $errorCode = $sendChangeRequestResult['Error']['ErrorCode'] ?? -1;
            $errorMessage = $sendChangeRequestResult['Error']['ErrorMessage'] ?? '';

            // If ErrorCode is not 0, return error and save ErrorCode/ErrorMessage
            if ($errorCode != 0) {
                Log::error('BookingService: Third-party cancellation failed', [
                    'ticket_id' => $bookedTicket->id,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'trace_id' => $sendChangeRequestResult['TraceId'] ?? null
                ]);

                // Store error in cancellation_details
                $bookedTicket->cancellation_details = [
                    'ErrorCode' => $errorCode,
                    'ErrorMessage' => $errorMessage
                ];
                $bookedTicket->save();

                return [
                    'success' => false,
                    'message' => $errorMessage ?: 'Failed to cancel ticket with provider',
                    'error' => [
                        'ErrorCode' => $errorCode,
                        'ErrorMessage' => $errorMessage
                    ],
                    'status_code' => 400
                ];
            }

            // ErrorCode is 0 - cancellation successful
            // Extract cancellation details from BusCRInfo[0]
            $busCRInfo = $sendChangeRequestResult['BusCRInfo'] ?? [];

            Log::info('BookingService: Parsing BusCRInfo', [
                'ticket_id' => $bookedTicket->id,
                'bus_cr_info' => $busCRInfo,
                'bus_cr_info_count' => is_array($busCRInfo) ? count($busCRInfo) : 0,
                'send_change_request_result' => $sendChangeRequestResult
            ]);

            $cancellationDetails = !empty($busCRInfo) && isset($busCRInfo[0]) && is_array($busCRInfo[0]) ? $busCRInfo[0] : null;

            if (!$cancellationDetails) {
                Log::warning('BookingService: Cancellation successful but no BusCRInfo found', [
                    'ticket_id' => $bookedTicket->id,
                    'response_status' => $sendChangeRequestResult['ResponseStatus'] ?? null,
                    'trace_id' => $sendChangeRequestResult['TraceId'] ?? null,
                    'bus_cr_info' => $busCRInfo
                ]);
            } else {
                Log::info('BookingService: Extracted cancellation details from BusCRInfo', [
                    'ticket_id' => $bookedTicket->id,
                    'cancellation_details' => $cancellationDetails
                ]);
            }

            // Update ticket status to cancelled
            $bookedTicket->status = 3; // Cancelled
            $bookedTicket->cancellation_remarks = $remarks;
            $bookedTicket->cancelled_at = now();

            // Store cancellation details in cancellation_details column
            // Always save TraceId and ResponseStatus, and add BusCRInfo details if available
            $cancellationDetailsToSave = [
                'TraceId' => $sendChangeRequestResult['TraceId'] ?? null,
                'ResponseStatus' => $sendChangeRequestResult['ResponseStatus'] ?? null
            ];

            // Add BusCRInfo details if available
            if ($cancellationDetails && is_array($cancellationDetails)) {
                $cancellationDetailsToSave = array_merge($cancellationDetailsToSave, [
                    'ChangeRequestId' => $cancellationDetails['ChangeRequestId'] ?? null,
                    'ChangeRequestStatus' => $cancellationDetails['ChangeRequestStatus'] ?? null,
                    'CreditNoteNo' => $cancellationDetails['CreditNoteNo'] ?? null,
                    'CreditNoteGSTIN' => $cancellationDetails['CreditNoteGSTIN'] ?? null,
                    'CreditNoteCreatedOn' => $cancellationDetails['CreditNoteCreatedOn'] ?? null,
                    'RefundedAmount' => isset($cancellationDetails['RefundedAmount']) ? (float) $cancellationDetails['RefundedAmount'] : 0,
                    'CancellationCharge' => isset($cancellationDetails['CancellationCharge']) ? (float) $cancellationDetails['CancellationCharge'] : 0,
                    'TotalPrice' => isset($cancellationDetails['TotalPrice']) ? (float) $cancellationDetails['TotalPrice'] : 0,
                    'TotalServiceCharge' => isset($cancellationDetails['TotalServiceCharge']) ? (float) $cancellationDetails['TotalServiceCharge'] : 0,
                    'TotalGSTAmount' => isset($cancellationDetails['TotalGSTAmount']) ? (float) $cancellationDetails['TotalGSTAmount'] : 0,
                    'CancellationChargeBreakUp' => $cancellationDetails['CancellationChargeBreakUp'] ?? [],
                    'GST' => $cancellationDetails['GST'] ?? []
                ]);
            }

            $bookedTicket->cancellation_details = $cancellationDetailsToSave;

            Log::info('BookingService: Saving cancellation_details', [
                'ticket_id' => $bookedTicket->id,
                'cancellation_details' => $cancellationDetailsToSave
            ]);

            $bookedTicket->save();

            // Reverse referral rewards for cancelled booking
            $this->reverseReferralRewards($bookedTicket, $remarks);

            $logData = [
                'ticket_id' => $bookedTicket->id,
                'booking_id' => $bookingId
            ];

            if ($cancellationDetails) {
                $logData['change_request_id'] = $cancellationDetails['ChangeRequestId'] ?? null;
                $logData['refunded_amount'] = isset($cancellationDetails['RefundedAmount']) ? (float) $cancellationDetails['RefundedAmount'] : 0;
                $logData['credit_note_no'] = $cancellationDetails['CreditNoteNo'] ?? null;
                $logData['cancellation_charge'] = isset($cancellationDetails['CancellationCharge']) ? (float) $cancellationDetails['CancellationCharge'] : 0;
            }

            Log::info('BookingService: Third-party ticket cancelled successfully', $logData);

            // Build cancellation details for response
            $responseCancellationDetails = [
                'cancelled_at' => $bookedTicket->cancelled_at->toDateTimeString(),
                'remarks' => $bookedTicket->cancellation_remarks,
                'status' => 'Cancelled'
            ];

            // Add cancellation details if available
            if ($cancellationDetails) {
                $responseCancellationDetails['change_request_id'] = $cancellationDetails['ChangeRequestId'] ?? null;
                $responseCancellationDetails['change_request_status'] = $cancellationDetails['ChangeRequestStatus'] ?? null;
                $responseCancellationDetails['credit_note_no'] = $cancellationDetails['CreditNoteNo'] ?? null;
                $responseCancellationDetails['credit_note_gstin'] = $cancellationDetails['CreditNoteGSTIN'] ?? null;
                $responseCancellationDetails['credit_note_created_on'] = $cancellationDetails['CreditNoteCreatedOn'] ?? null;
                $responseCancellationDetails['refunded_amount'] = isset($cancellationDetails['RefundedAmount']) ? (float) $cancellationDetails['RefundedAmount'] : 0;
                $responseCancellationDetails['cancellation_charge'] = isset($cancellationDetails['CancellationCharge']) ? (float) $cancellationDetails['CancellationCharge'] : 0;
                $responseCancellationDetails['total_price'] = isset($cancellationDetails['TotalPrice']) ? (float) $cancellationDetails['TotalPrice'] : 0;
                $responseCancellationDetails['total_service_charge'] = isset($cancellationDetails['TotalServiceCharge']) ? (float) $cancellationDetails['TotalServiceCharge'] : 0;
                $responseCancellationDetails['total_gst_amount'] = isset($cancellationDetails['TotalGSTAmount']) ? (float) $cancellationDetails['TotalGSTAmount'] : 0;
                $responseCancellationDetails['cancellation_charge_breakup'] = $cancellationDetails['CancellationChargeBreakUp'] ?? [];
                $responseCancellationDetails['gst'] = $cancellationDetails['GST'] ?? [];
            }

            return [
                'success' => true,
                'message' => 'Ticket cancelled successfully',
                'ticket_id' => $bookedTicket->id,
                'cancellation_details' => $responseCancellationDetails
            ];

        } catch (\Exception $e) {
            Log::error('BookingService: Error cancelling third-party bus ticket', [
                'ticket_id' => $bookedTicket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to cancel third-party bus ticket: ' . $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Process referral rewards for first booking
     */
    private function processReferralRewards(BookedTicket $bookedTicket)
    {
        try {
            $referralService = app(ReferralService::class);

            // Record first booking event
            $event = $referralService->recordFirstBooking(
                $bookedTicket->user_id,
                $bookedTicket->id,
                (float) $bookedTicket->total_amount
            );

            if ($event) {
                Log::info('BookingService: Referral first booking event recorded', [
                    'ticket_id' => $bookedTicket->id,
                    'user_id' => $bookedTicket->user_id,
                    'amount' => $bookedTicket->total_amount,
                    'event_id' => $event->id
                ]);
            }
        } catch (\Exception $e) {
            // Don't fail the booking if referral processing fails
            Log::error('BookingService: Error processing referral rewards', [
                'ticket_id' => $bookedTicket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reverse referral rewards for cancelled booking
     */
    private function reverseReferralRewards(BookedTicket $bookedTicket, string $reason)
    {
        try {
            $referralService = app(ReferralService::class);

            // Reverse rewards associated with this booking
            $reversedCount = $referralService->reverseRewardsForBooking(
                $bookedTicket->id,
                $reason
            );

            if ($reversedCount > 0) {
                Log::info('BookingService: Referral rewards reversed for cancelled booking', [
                    'ticket_id' => $bookedTicket->id,
                    'user_id' => $bookedTicket->user_id,
                    'reversed_count' => $reversedCount,
                    'reason' => $reason
                ]);
            }
        } catch (\Exception $e) {
            // Don't fail the cancellation if referral reversal fails
            Log::error('BookingService: Error reversing referral rewards', [
                'ticket_id' => $bookedTicket->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
