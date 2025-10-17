<?php

namespace App\Services;

use App\Models\BookedTicket;
use App\Models\User;
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

            // Calculate total fare
            $totalFare = $this->calculateTotalFare($blockResponse['Result']);

            // Create pending ticket record
            $bookedTicket = $this->createPendingTicket($requestData, $blockResponse, $totalFare, $user->id);

            // Create Razorpay order
            $razorpayOrder = $this->createRazorpayOrder($bookedTicket, $totalFare);

            // Cache booking data for payment verification
            $this->cacheBookingData($bookedTicket->id, $requestData, $blockResponse);

            return [
                'success' => true,
                'ticket_id' => $bookedTicket->id,
                'order_details' => $razorpayOrder,
                'order_id' => $razorpayOrder->id,
                'amount' => $totalFare,
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
            if (!$bookingData) {
                return [
                    'success' => false,
                    'message' => 'Booking session expired. Please try again.'
                ];
            }

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

            $user = User::firstOrCreate(
                ['mobile' => $fullPhone],
                [
                    'firstname' => $requestData['FirstName'] ?? $requestData['passenger_firstname'],
                    'lastname' => $requestData['LastName'] ?? $requestData['passenger_lastname'],
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

    /**
     * Block seats using the appropriate method
     */
    private function blockSeats(array $requestData, array $passengers)
    {
        $seats = is_array($requestData['Seats'] ?? $requestData['seats'])
            ? $requestData['Seats'] ?? $requestData['seats']
            : explode(',', $requestData['Seats'] ?? $requestData['seats']);

        $resultIndex = $requestData['ResultIndex'] ?? $requestData['result_index'];
        $searchTokenId = $requestData['SearchTokenId'] ?? $requestData['search_token_id'];
        $boardingPointId = $requestData['BoardingPointId'] ?? $requestData['boarding_point_index'];
        $droppingPointId = $requestData['DroppingPointId'] ?? $requestData['dropping_point_index'];
        $userIp = $requestData['UserIp'] ?? $requestData['user_ip'] ?? request()->ip();

        // Check if this is an operator bus
        if (str_starts_with($resultIndex, 'OP_')) {
            return $this->blockOperatorBusSeat($resultIndex, $boardingPointId, $droppingPointId, $passengers, $seats, $userIp);
        } else {
            return blockSeatHelper($searchTokenId, $resultIndex, $boardingPointId, $droppingPointId, $passengers, $seats, $userIp);
        }
    }

    /**
     * Block operator bus seat
     */
    private function blockOperatorBusSeat(string $resultIndex, string $boardingPointId, string $droppingPointId, array $passengers, array $seats, string $userIp)
    {
        try {
            $operatorBusId = (int) str_replace('OP_', '', $resultIndex);
            $operatorBus = \App\Models\OperatorBus::with(['activeSeatLayout', 'currentRoute'])->find($operatorBusId);

            if (!$operatorBus) {
                return [
                    'success' => false,
                    'message' => 'Operator bus not found'
                ];
            }

            $bookingId = 'OP_BOOK_' . time() . '_' . $operatorBusId;

            $mockResult = [
                'BookingId' => $bookingId,
                'BookingStatus' => 'Confirmed',
                'TotalAmount' => 0,
                'BusType' => $operatorBus->bus_type ?? 'Operator Bus',
                'TravelName' => $operatorBus->travel_name ?? 'Operator Service',
                'DepartureTime' => '2025-10-23T17:30:00',
                'ArrivalTime' => '2025-10-24T11:30:00',
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
                                'PublishedPrice' => 1000,
                                'OfferedPrice' => 900,
                                'BasePrice' => 800,
                                'Tax' => 100,
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
                        ]
                    ];
                }, $passengers, array_keys($passengers)),
                'BoardingPointId' => $boardingPointId,
                'DroppingPointId' => $droppingPointId,
                'OperatorBusId' => $operatorBusId,
                'ResultIndex' => $resultIndex
            ];

            return [
                'success' => true,
                'Result' => $mockResult
            ];

        } catch (\Exception $e) {
            Log::error('BookingService: Error blocking operator bus seat', [
                'result_index' => $resultIndex,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to block operator bus seats: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate total fare from block response
     */
    private function calculateTotalFare(array $blockResult)
    {
        return collect($blockResult['Passenger'])->sum(function ($passenger) {
            return $passenger['Seat']['Price']['PublishedPrice'] ?? 0;
        });
    }

    /**
     * Create pending ticket record
     */
    private function createPendingTicket(array $requestData, array $blockResponse, float $totalFare, int $userId)
    {
        $seats = is_array($requestData['Seats'] ?? $requestData['seats'])
            ? $requestData['Seats'] ?? $requestData['seats']
            : explode(',', $requestData['Seats'] ?? $requestData['seats']);

        $unitPrice = collect($blockResponse['Result']['Passenger'])->sum(function ($passenger) {
            return $passenger['Seat']['Price']['OfferedPrice'] ?? 0;
        });

        $bookedTicket = new BookedTicket();
        $bookedTicket->user_id = $userId;
        $bookedTicket->bus_type = $blockResponse['Result']['BusType'];
        $bookedTicket->travel_name = $blockResponse['Result']['TravelName'];
        $bookedTicket->source_destination = json_encode([
            $requestData['OriginCity'] ?? $requestData['origin_city'] ?? 0,
            $requestData['DestinationCity'] ?? $requestData['destination_city'] ?? 0
        ]);
        $bookedTicket->departure_time = Carbon::parse($blockResponse['Result']['DepartureTime'])->format('H:i:s');
        $bookedTicket->arrival_time = Carbon::parse($blockResponse['Result']['ArrivalTime'])->format('H:i:s');
        $bookedTicket->operator_pnr = $blockResponse['Result']['BookingId'] ?? null;
        $bookedTicket->boarding_point_details = json_encode($blockResponse['Result']['BoardingPointdetails']);
        $bookedTicket->dropping_point_details = isset($blockResponse['Result']['DroppingPointsdetails'])
            ? json_encode($blockResponse['Result']['DroppingPointsdetails']) : null;
        $bookedTicket->seats = $seats;
        $bookedTicket->ticket_count = count($seats);
        $bookedTicket->unit_price = round($unitPrice, 2);
        $bookedTicket->sub_total = round($totalFare, 2);
        $bookedTicket->pnr_number = getTrx(10);
        $bookedTicket->pickup_point = $requestData['BoardingPointId'] ?? $requestData['boarding_point_index'];
        $bookedTicket->dropping_point = $requestData['DroppingPointId'] ?? $requestData['dropping_point_index'];
        $bookedTicket->search_token_id = $requestData['SearchTokenId'] ?? $requestData['search_token_id'];
        $bookedTicket->date_of_journey = $requestData['DateOfJourney'] ?? $requestData['date_of_journey'] ?? now()->format('Y-m-d');

        $leadPassenger = collect($blockResponse['Result']['Passenger'])->firstWhere('LeadPassenger', true)
            ?? $blockResponse['Result']['Passenger'][0] ?? null;

        $bookedTicket->passenger_phone = $leadPassenger['Phoneno'] ?? null;
        $bookedTicket->passenger_email = $leadPassenger['Email'] ?? null;
        $bookedTicket->passenger_address = $leadPassenger['Address'] ?? null;
        $bookedTicket->passenger_name = trim(($leadPassenger['FirstName'] ?? '') . ' ' . ($leadPassenger['LastName'] ?? ''));
        $bookedTicket->passenger_age = $leadPassenger['Age'] ?? null;

        // Save all passenger names
        $passengerNames = [];
        foreach ($blockResponse['Result']['Passenger'] as $passenger) {
            $passengerNames[] = trim(($passenger['FirstName'] ?? '') . ' ' . ($passenger['LastName'] ?? ''));
        }
        $bookedTicket->passenger_names = $passengerNames;

        $bookedTicket->api_response = json_encode($blockResponse);

        // Save bus details from block response
        if (isset($blockResponse['Result']['BusDetails'])) {
            $bookedTicket->bus_details = json_encode($blockResponse['Result']['BusDetails']);
        }

        if (isset($blockResponse['Result']['CancelPolicy'])) {
            $bookedTicket->cancellation_policy = json_encode(formatCancelPolicy($blockResponse['Result']['CancelPolicy']));
        }

        $bookedTicket->status = 0; // Pending
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
            'block_response' => $blockResponse
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

        return [
            'Result' => [
                'BookingId' => $bookingId,
                'TravelOperatorPNR' => $bookingId,
                'BookingStatus' => 'Confirmed',
                'InvoiceNumber' => 'OP_INV_' . time(),
                'InvoiceAmount' => 1000, // Mock amount
                'InvoiceCreatedOn' => now()->toISOString(),
                'TicketNo' => 'OP_TKT_' . time(),
                'Origin' => 'Origin City',
                'Destination' => 'Destination City',
                'Price' => [
                    'AgentCommission' => 50,
                    'TDS' => 10
                ]
            ]
        ];
    }

    /**
     * Update ticket with booking details
     */
    private function updateTicketWithBookingDetails(BookedTicket $bookedTicket, array $apiResponse, array $bookingData)
    {
        // Update ticket status to confirmed and save operator PNR
        $bookedTicket->operator_pnr = $apiResponse['Result']['TravelOperatorPNR'] ?? null;

        // Merge block response with booking response
        $blockResponse = json_decode($bookedTicket->api_response, true);
        $completeApiResponse = array_merge($blockResponse ?? [], $apiResponse);

        $bookedTicket->update([
            'status' => 1, // Confirmed
            'api_response' => json_encode($completeApiResponse)
        ]);

        $bookingApiId = $apiResponse['Result']['BookingID'] ?? null;

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
        }

        // Update pricing details if available
        if (isset($result['Price']['AgentCommission'])) {
            $updateData['agent_commission'] = $result['Price']['AgentCommission'];
        }

        if (isset($result['Price']['TDS'])) {
            $updateData['tds_from_api'] = $result['Price']['TDS'];
        }

        // Update city information if available
        if (isset($result['Origin'])) {
            $updateData['origin_city'] = $result['Origin'];
        }

        if (isset($result['Destination'])) {
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
                }

                // Update pricing details
                if (isset($result['Price']['AgentCommission'])) {
                    $updateData['agent_commission'] = $result['Price']['AgentCommission'];
                }

                if (isset($result['Price']['TDS'])) {
                    $updateData['tds_from_api'] = $result['Price']['TDS'];
                }

                // Update city information
                if (isset($result['Origin'])) {
                    $updateData['origin_city'] = $result['Origin'];
                }

                if (isset($result['Destination'])) {
                    $updateData['destination_city'] = $result['Destination'];
                }

                // Update dropping point details
                if (isset($result['DroppingPointdetails'])) {
                    $updateData['dropping_point_details'] = json_encode($result['DroppingPointdetails']);
                }

                // Update cancellation policy
                if (isset($result['CancelPolicy'])) {
                    $updateData['cancellation_policy'] = json_encode(formatCancelPolicy($result['CancelPolicy']));
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

            // Send ticket details to passenger
            $passengerWhatsAppSuccess = sendTicketDetailsWhatsApp($ticketDetails, $bookedTicket->user->mobile);

            // Send ticket details to admin
            $adminWhatsAppSuccess = sendTicketDetailsWhatsApp($ticketDetails, "8269566034");

            Log::info('Passenger and admin WhatsApp notification results', [
                'ticket_id' => $bookedTicket->id,
                'passenger_success' => $passengerWhatsAppSuccess,
                'admin_success' => $adminWhatsAppSuccess
            ]);

            // Check if passenger or admin WhatsApp failed
            if (!$passengerWhatsAppSuccess || !$adminWhatsAppSuccess) {
                Log::error('Passenger or admin WhatsApp notification failed', [
                    'ticket_id' => $bookedTicket->id,
                    'passenger_success' => $passengerWhatsAppSuccess,
                    'admin_success' => $adminWhatsAppSuccess
                ]);
                return false;
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
     */
    private function formatCancellationPolicy(array $cancelPolicy)
    {
        return formatCancelPolicy($cancelPolicy);
    }
}
