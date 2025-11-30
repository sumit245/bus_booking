<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\BookedTicket;
use App\Services\ReferralService;

class UserController extends Controller
{
    /**
     * Send OTP to the user's mobile number.
     */
    public function sendOTP(Request $request)
    {
        $this->validatePhone($request);
        Log::info("Sending OTP to", ["phone" => $request->all()]);
        try {
            // Generate OTP
            $otp = (string) rand(100000, 999999);
            try {
                Otp::updateOrCreate(
                    ['mobile_number' => $request->mobile_number],
                    [
                        'otp' => $otp,
                        'expires_at' => Carbon::now()->addMinutes(3),
                    ]
                );

            } catch (\Exception $e) {
                Log::error('Error while updating or creating OTP record', ['error' => $e->getMessage()]);
            }


            // Send OTP via WhatsApp API
            sendOtp($request->mobile_number, $otp, 'Guest');

            return response()->json([
                'message' => 'OTP sent successfully to ' . $request->mobile_number,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Verify the OTP and log in the user.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|regex:/^[6-9]\d{9}$/',
            'otp' => 'required|digits:6',
            'referral_code' => 'nullable|string|size:6', // Optional referral code
        ]);

        // Normalize mobile number to 10 digits (same logic as BookingService)
        $mobileNumber = $request->mobile_number;
        if (strpos($mobileNumber, '+91') === 0) {
            $mobileNumber = substr($mobileNumber, 3); // Remove +91
        } elseif (strpos($mobileNumber, '91') === 0 && strlen($mobileNumber) > 10) {
            $mobileNumber = substr($mobileNumber, 2); // Remove 91 prefix
        }
        // Ensure we have exactly 10 digits
        $mobileNumber = substr($mobileNumber, -10);

        Log::info('UserController: Verifying OTP for normalized mobile', [
            'original' => $request->mobile_number,
            'normalized' => $mobileNumber
        ]);

        $otpRecord = Otp::where('mobile_number', $request->mobile_number)->first();

        if (!$otpRecord) {
            return response()->json([
                'message' => 'OTP not found. Please request a new OTP.',
                'status' => 404,
            ], 404);
        }

        if ($otpRecord->expires_at < Carbon::now()) {
            return response()->json([
                'message' => 'OTP expired. Please request a new OTP.',
                'status' => 400,
            ], 400);
        }

        if ($otpRecord->otp !== $request->otp) {
            return response()->json([
                'message' => 'Invalid OTP. Please try again.',
                'status' => 400,
            ], 400);
        }

        // OTP is verified - find or create user with 10-digit mobile
        $user = User::where('mobile', $mobileNumber)->first();
        $isNewUser = !$user;

        if ($user) {
            // User exists - UPDATE sv=1 (mobile verified)
            Log::info('UserController: User exists, marking mobile as verified', [
                'user_id' => $user->id,
                'mobile' => $mobileNumber
            ]);

            $user->update([
                'sv' => 1, // Mark mobile as verified
            ]);
        } else {
            // User doesn't exist - CREATE new user with sv=1 (verified through OTP)
            Log::info('UserController: Creating new user via OTP verification', [
                'mobile' => $mobileNumber,
                'username' => $request->user_name,
                'referral_code' => $request->referral_code
            ]);

            $user = User::create([
                'mobile' => $mobileNumber,
                'username' => $request->user_name ?? ('user' . time() . rand(100, 999)),
                'password' => Hash::make(Str::random(8)),
                'country_code' => '91',
                'status' => 1,   // Active
                'ev' => 0,       // Email not verified yet
                'sv' => 1,       // Mobile verified (OTP success)
            ]);
        }

        // Handle referral code for new users
        $referralService = app(ReferralService::class);
        $referralCode = $request->referral_code ?? session('referral_code') ?? request()->cookie('referral_code');

        if ($isNewUser && $referralCode) {
            // Record signup event with referral code
            $event = $referralService->recordSignup($referralCode, $user->id);

            if ($event) {
                Log::info('UserController: Referral signup recorded', [
                    'user_id' => $user->id,
                    'referral_code' => $referralCode,
                    'event_id' => $event->id
                ]);
            }
        }

        // Log in the user
        Auth::login($user);

        // Delete OTP record (single-use)
        $otpRecord->delete();

        return response()->json([
            'message' => 'Logged in successfully.',
            'status' => 200,
            'data' => [
                'user' => $user,
                'token' => $user->createToken('mobile-app')->plainTextToken,
            ],
        ], 200);
    }

    /**
     * Validate the phone number format.
     */
    protected function validatePhone(Request $request)
    {
        $request->validate([
            'mobile_number' => ['required', 'regex:/^[6-9]\d{9}$/'],
        ]);
    }
    // UserController.php
    public function userHistoryByPhone(Request $request)
    {
        try {
            // Get authenticated user from token (Sanctum)
            $authenticatedUser = null;
            if ($request->bearerToken()) {
                $authenticatedUser = $request->user('sanctum');
            }

            // Fallback: If no authenticated user, try to find by mobile_number (for backward compatibility)
            $user = $authenticatedUser;
            if (!$user && $request->has('mobile_number')) {
                $request->validate([
                    'mobile_number' => ['required', 'string', 'regex:/^[6-9]\d{9}$/']
                ]);
                $user = User::where('mobile', $request->mobile_number)->first();
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please authenticate or provide valid mobile_number.'
                ], 404);
            }

            // Normalize user's mobile number for comparison
            $userMobile = $user->mobile;
            // Remove country code if present
            if (strpos($userMobile, '+91') === 0) {
                $userMobile = substr($userMobile, 3);
            } elseif (strpos($userMobile, '91') === 0 && strlen($userMobile) > 10) {
                $userMobile = substr($userMobile, 2);
            }
            // Ensure we have exactly 10 digits
            $userMobile = substr($userMobile, -10);

            // Fetch tickets where:
            // 1. user_id matches (tickets booked by this user - can cancel)
            // 2. OR passenger_phone matches user's mobile (tickets where user is a passenger - cannot cancel)
            $tickets = BookedTicket::with([
                'trip.fleetType'
            ])
                ->where(function ($query) use ($user, $userMobile) {
                    // Tickets booked by this user (owner)
                    $query->where('user_id', $user->id)
                        // OR tickets where this user is a passenger
                        ->orWhere(function ($q) use ($userMobile) {
                        // Match passenger_phone (exact match or normalized)
                        $q->where('passenger_phone', $userMobile)
                            ->orWhere('passenger_phone', '91' . $userMobile)
                            ->orWhere('passenger_phone', '+91' . $userMobile)
                            ->orWhereRaw('RIGHT(passenger_phone, 10) = ?', [$userMobile]);

                        // Also check passenger_phones array if it exists
                        $q->orWhereJsonContains('passenger_phones', $userMobile)
                            ->orWhereJsonContains('passenger_phones', '91' . $userMobile)
                            ->orWhereJsonContains('passenger_phones', '+91' . $userMobile);
                    });
                })
                ->orderBy('id', 'desc')
                ->get();

            Log::info("Fetched tickets", [
                "user_id" => $user->id,
                "user_mobile" => $userMobile,
                "ticket_count" => $tickets->count()
            ]);

            // Transform the data for a clean API response
            $formattedTickets = $tickets->map(function ($ticket) use ($user, $userMobile) {
                // Determine if user can cancel this ticket
                // User can cancel only if they are the booking owner (user_id matches)
                // Cannot cancel if they are just a passenger (passenger_phone matches)
                $canCancel = ($ticket->user_id == $user->id);
                $seats = is_array($ticket->seats) ? $ticket->seats : [];
                $nameParts = explode(' ', $ticket->passenger_name ?? '', 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';

                // Fix: Get booking_id, search_token_id, user_ip from both api_response and direct columns
                $response = null;
                if ($ticket->api_response) {
                    $response = is_string($ticket->api_response) ? json_decode($ticket->api_response) : $ticket->api_response;
                }

                // Try to get from api_response first, then fallback to direct columns
                $booking_id = null;
                if ($response) {
                    // For third-party buses: Result->BookingID
                    if (isset($response->Result->BookingID)) {
                        $booking_id = $response->Result->BookingID;
                    }
                    // For operator buses: Result->BookingId
                    elseif (isset($response->Result->BookingId)) {
                        $booking_id = $response->Result->BookingId;
                    }
                }
                // Fallback to direct column
                if (!$booking_id && $ticket->booking_id) {
                    $booking_id = $ticket->booking_id;
                }
                if (!$booking_id && $ticket->api_booking_id) {
                    $booking_id = $ticket->api_booking_id;
                }

                // Prioritize direct column over API response
                $search_token_id = $ticket->search_token_id;
                // Fallback to api_response if column is empty
                if (!$search_token_id && $response && isset($response->SearchTokenId)) {
                    $search_token_id = $response->SearchTokenId;
                }

                $userIp = null;
                if ($response && isset($response->UserIp)) {
                    $userIp = $response->UserIp;
                }
                // Try to get from api_response if stored differently
                if (!$userIp && $response && isset($response->Result->UserIp)) {
                    $userIp = $response->Result->UserIp;
                }

                // Fix: Parse boarding and dropping point details properly
                $boardingPointDetails = null;
                if ($ticket->boarding_point_details) {
                    $boardingDetails = is_string($ticket->boarding_point_details)
                        ? json_decode($ticket->boarding_point_details, true)
                        : $ticket->boarding_point_details;

                    // Ensure it's in the correct format (array of objects)
                    if (is_array($boardingDetails)) {
                        // If it's already an array of arrays, use as is
                        if (isset($boardingDetails[0]) && is_array($boardingDetails[0])) {
                            $boardingPointDetails = $boardingDetails;
                        }
                        // If it's a single object, wrap it in an array
                        elseif (isset($boardingDetails['CityPointName']) || isset($boardingDetails['CityPointIndex'])) {
                            $boardingPointDetails = [$boardingDetails];
                        }
                        // If it's an empty array, set to null
                        elseif (empty($boardingDetails)) {
                            $boardingPointDetails = null;
                        } else {
                            $boardingPointDetails = $boardingDetails;
                        }
                    }
                }

                $droppingPointDetails = null;
                if ($ticket->dropping_point_details) {
                    $droppingDetails = is_string($ticket->dropping_point_details)
                        ? json_decode($ticket->dropping_point_details, true)
                        : $ticket->dropping_point_details;

                    // Ensure it's in the correct format (array of objects)
                    if (is_array($droppingDetails)) {
                        // If it's already an array of arrays, use as is
                        if (isset($droppingDetails[0]) && is_array($droppingDetails[0])) {
                            $droppingPointDetails = $droppingDetails;
                        }
                        // If it's a single object, wrap it in an array
                        elseif (isset($droppingDetails['CityPointName']) || isset($droppingDetails['CityPointIndex'])) {
                            $droppingPointDetails = [$droppingDetails];
                        }
                        // If it's an empty array, set to null
                        elseif (empty($droppingDetails)) {
                            $droppingPointDetails = null;
                        } else {
                            $droppingPointDetails = $droppingDetails;
                        }
                    }
                }

                // Calculate price per seat
                $seatCount = count($seats);
                $unitPrice = $seatCount > 0 ? round((float) ($ticket->sub_total / $seatCount), 2) : (float) $ticket->unit_price;

                $passengers = [];
                foreach ($seats as $index => $seat) {
                    $isLead = ($index === 0);
                    $passengers[] = [
                        'LeadPassenger' => $isLead,
                        'Title' => 'Mr', // Defaulting as gender is not available per passenger
                        'Address' => $isLead ? $ticket->passenger_address : null,
                        'Age' => $isLead ? $ticket->passenger_age : null,
                        'Email' => $isLead ? $ticket->passenger_email : null,
                        'FirstName' => $firstName,
                        'Gender' => 1, // Defaulting to Male
                        'LastName' => $lastName,
                        'Phoneno' => $isLead ? $ticket->passenger_phone : null,
                        'Seat' => [
                            'SeatName' => $seat,
                            'Price' => number_format($unitPrice, 8, '.', ''), // Format to match API format
                        ],
                    ];
                }

                // Extract GST details from api_response if available
                $gstDetails = null;
                if ($response && isset($response->Result->Price->GST)) {
                    $gstDetails = $response->Result->Price->GST;
                }

                // Determine if ticket can be cancelled (only owner can cancel, not passengers)
                // Also check if ticket is already cancelled or past journey date
                $isPastJourney = Carbon::parse($ticket->date_of_journey)->lt(Carbon::today());
                $isCancelled = ($ticket->status == 3);
                $canCancelTicket = $canCancel && !$isCancelled && !$isPastJourney && ($ticket->status == 1);

                return [
                    'pnr_number' => $ticket->pnr_number,
                    'travel_name' => $ticket->travel_name ?? ($ticket->trip && $ticket->trip->fleetType ? $ticket->trip->fleetType->name : 'N/A'),
                    'bus_type' => $ticket->bus_type ?? 'N/A',
                    'date_of_journey' => Carbon::parse($ticket->date_of_journey)->format('Y-m-d'),
                    'departure_time' => $ticket->departure_time ? Carbon::parse($ticket->departure_time)->format('h:i A') : 'N/A',
                    'arrival_time' => $ticket->arrival_time ? Carbon::parse($ticket->arrival_time)->format('h:i A') : 'N/A',
                    'duration' => ($ticket->arrival_time && $ticket->departure_time)
                        ? Carbon::parse($ticket->arrival_time)
                            ->diff(Carbon::parse($ticket->departure_time))
                            ->format('%H:%I')
                        : 'N/A',
                    'boarding_point_details' => $boardingPointDetails,
                    'boarding_point' => $ticket->origin_city,
                    'dropping_point_details' => $droppingPointDetails,
                    'dropping_point' => $ticket->destination_city,
                    'passengers' => $passengers,
                    'unit_price' => round((float) $unitPrice, 2),
                    'sub_total' => round((float) $ticket->sub_total, 2),
                    'total_fare' => round((float) $ticket->sub_total, 2), // Keep for backward compatibility
                    'total_amount' => round((float) ($ticket->total_amount ?? $ticket->sub_total), 2),
                    'paid_amount' => round((float) ($ticket->paid_amount ?? $ticket->sub_total), 2),
                    'gst_details' => $gstDetails,
                    'status' => match ($ticket->status) {
                        0 => 'Pending',
                        1 => 'Booked',
                        2 => 'Rejected',
                        3 => 'Cancelled',
                        4 => 'Expired',
                        default => 'Unknown',
                    },
                    'booked_at' => $ticket->created_at->toDateTimeString(),
                    'booking_id' => $booking_id,
                    'search_token_id' => $search_token_id,
                    'user_ip' => $userIp,
                    'can_cancel' => $canCancelTicket, // Flag indicating if user can cancel this ticket
                    'is_owner' => $canCancel, // Flag indicating if user is the booking owner
                    'is_booking_owner' => $canCancel, // Alias for is_owner (for frontend compatibility)
                    'booking_owner_id' => $ticket->user_id, // ID of the user who made the booking (for analytics)
                    'cancellation_details' => $ticket->status == 3 ? array_merge(
                        $ticket->cancellation_details ?? [],
                        [
                            'cancelled_at' => $ticket->cancelled_at ? Carbon::parse($ticket->cancelled_at)->toDateTimeString() : null,
                            'remarks' => $ticket->cancellation_remarks,
                        ]
                    ) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'user' => [
                    'name' => $user->firstname . ' ' . $user->lastname,
                    'mobile' => $user->mobile,
                ],
                'tickets' => $formattedTickets
            ]);
        } catch (\Exception $e) {
            //throw $th;
            Log::error('Error in userHistoryByPhone: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }

    }

    /**
     * Get ticket details by booking_id
     * Used by React Native app to fetch complete ticket details after booking confirmation
     */
    public function getTicketByBookingId(Request $request)
    {
        Log::info('getTicketByBookingId request', ['request' => $request->all()]);
        try {
            $request->validate([
                'booking_id' => ['required', 'string']
            ]);

            $bookingId = $request->booking_id;

            // Find ticket by booking_id, api_booking_id, or operator_pnr
            $ticket = BookedTicket::with([
                'trip.fleetType'
            ])
                ->where(function ($query) use ($bookingId) {
                    $query->where('booking_id', $bookingId)
                        ->orWhere('api_booking_id', $bookingId)
                        ->orWhere('operator_pnr', $bookingId);
                })
                ->first();

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found with the provided booking_id'
                ], 404);
            }

            // Get authenticated user (if available via Sanctum token)
            $authenticatedUser = null;
            if ($request->bearerToken()) {
                $authenticatedUser = $request->user('sanctum');
            }

            // Determine if authenticated user is the booking owner
            $isOwner = false;
            $canCancel = false;
            if ($authenticatedUser) {
                $isOwner = ($ticket->user_id == $authenticatedUser->id);
                // Check if ticket can be cancelled (only owner can cancel, not cancelled, not past journey, status = 1)
                $isPastJourney = Carbon::parse($ticket->date_of_journey)->lt(Carbon::today());
                $isCancelled = ($ticket->status == 3);
                $canCancel = $isOwner && !$isCancelled && !$isPastJourney && ($ticket->status == 1);
            }

            // Reuse the same formatting logic as userHistoryByPhone
            $seats = is_array($ticket->seats) ? $ticket->seats : [];
            $nameParts = explode(' ', $ticket->passenger_name ?? '', 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            // Get booking_id, search_token_id, user_ip from both api_response and direct columns
            $response = null;
            if ($ticket->api_response) {
                $response = is_string($ticket->api_response) ? json_decode($ticket->api_response) : $ticket->api_response;
            }

            // Try to get from api_response first, then fallback to direct columns
            $booking_id = null;
            if ($response) {
                // For third-party buses: Result->BookingID
                if (isset($response->Result->BookingID)) {
                    $booking_id = $response->Result->BookingID;
                }
                // For operator buses: Result->BookingId
                elseif (isset($response->Result->BookingId)) {
                    $booking_id = $response->Result->BookingId;
                }
            }
            // Fallback to direct column
            if (!$booking_id && $ticket->booking_id) {
                $booking_id = $ticket->booking_id;
            }
            if (!$booking_id && $ticket->api_booking_id) {
                $booking_id = $ticket->api_booking_id;
            }

            // Prioritize direct column over API response
            $search_token_id = $ticket->search_token_id;
            // Fallback to api_response if column is empty
            if (!$search_token_id && $response && isset($response->SearchTokenId)) {
                $search_token_id = $response->SearchTokenId;
            }

            $userIp = null;
            if ($response && isset($response->UserIp)) {
                $userIp = $response->UserIp;
            }
            // Try to get from api_response if stored differently
            if (!$userIp && $response && isset($response->Result->UserIp)) {
                $userIp = $response->Result->UserIp;
            }

            // Parse boarding and dropping point details properly
            $boardingPointDetails = null;
            if ($ticket->boarding_point_details) {
                $boardingDetails = is_string($ticket->boarding_point_details)
                    ? json_decode($ticket->boarding_point_details, true)
                    : $ticket->boarding_point_details;

                // Ensure it's in the correct format (array of objects)
                if (is_array($boardingDetails)) {
                    // If it's already an array of arrays, use as is
                    if (isset($boardingDetails[0]) && is_array($boardingDetails[0])) {
                        $boardingPointDetails = $boardingDetails;
                    }
                    // If it's a single object, wrap it in an array
                    elseif (isset($boardingDetails['CityPointName']) || isset($boardingDetails['CityPointIndex'])) {
                        $boardingPointDetails = [$boardingDetails];
                    }
                    // If it's an empty array, set to null
                    elseif (empty($boardingDetails)) {
                        $boardingPointDetails = null;
                    } else {
                        $boardingPointDetails = $boardingDetails;
                    }
                }
            }

            $droppingPointDetails = null;
            if ($ticket->dropping_point_details) {
                $droppingDetails = is_string($ticket->dropping_point_details)
                    ? json_decode($ticket->dropping_point_details, true)
                    : $ticket->dropping_point_details;

                // Ensure it's in the correct format (array of objects)
                if (is_array($droppingDetails)) {
                    // If it's already an array of arrays, use as is
                    if (isset($droppingDetails[0]) && is_array($droppingDetails[0])) {
                        $droppingPointDetails = $droppingDetails;
                    }
                    // If it's a single object, wrap it in an array
                    elseif (isset($droppingDetails['CityPointName']) || isset($droppingDetails['CityPointIndex'])) {
                        $droppingPointDetails = [$droppingDetails];
                    }
                    // If it's an empty array, set to null
                    elseif (empty($droppingDetails)) {
                        $droppingPointDetails = null;
                    } else {
                        $droppingPointDetails = $droppingDetails;
                    }
                }
            }

            // Calculate price per seat
            $seatCount = count($seats);
            $unitPrice = $seatCount > 0 ? round((float) ($ticket->sub_total / $seatCount), 2) : (float) $ticket->unit_price;

            $passengers = [];
            foreach ($seats as $index => $seat) {
                $isLead = ($index === 0);
                $passengers[] = [
                    'LeadPassenger' => $isLead,
                    'Title' => 'Mr', // Defaulting as gender is not available per passenger
                    'Address' => $isLead ? $ticket->passenger_address : null,
                    'Age' => $isLead ? $ticket->passenger_age : null,
                    'Email' => $isLead ? $ticket->passenger_email : null,
                    'FirstName' => $firstName,
                    'Gender' => 1, // Defaulting to Male
                    'LastName' => $lastName,
                    'Phoneno' => $isLead ? $ticket->passenger_phone : null,
                    'Seat' => [
                        'SeatName' => $seat,
                        'Price' => number_format($unitPrice, 8, '.', ''), // Format to match API format
                    ],
                ];
            }

            // Extract GST details from api_response if available
            $gstDetails = null;
            if ($response && isset($response->Result->Price->GST)) {
                $gstDetails = $response->Result->Price->GST;
            }

            // Format boarding_point and dropping_point as strings for React Native compatibility
            $boardingPointString = $ticket->origin_city ?? '';
            if ($boardingPointDetails && isset($boardingPointDetails[0])) {
                $bp = $boardingPointDetails[0];
                $boardingPointString = ($bp['CityPointName'] ?? '') .
                    (isset($bp['CityPointLocation']) && $bp['CityPointLocation'] !== ($bp['CityPointName'] ?? '')
                        ? ', ' . $bp['CityPointLocation']
                        : '');
            }

            $droppingPointString = $ticket->destination_city ?? '';
            if ($droppingPointDetails && isset($droppingPointDetails[0])) {
                $dp = $droppingPointDetails[0];
                $droppingPointString = ($dp['CityPointName'] ?? '') .
                    (isset($dp['CityPointLocation']) && $dp['CityPointLocation'] !== ($dp['CityPointName'] ?? '')
                        ? ', ' . $dp['CityPointLocation']
                        : '');
            }

            // Format departure and arrival times for React Native component
            $departureTimeFormatted = $ticket->departure_time
                ? Carbon::parse($ticket->departure_time)->format('Y-m-d\TH:i:s')
                : null;
            $arrivalTimeFormatted = $ticket->arrival_time
                ? Carbon::parse($ticket->arrival_time)->format('Y-m-d\TH:i:s')
                : null;

            $formattedTicket = [
                'pnr_number' => $ticket->pnr_number,
                'pnr' => $ticket->pnr_number, // Alias for React Native compatibility
                'travel_name' => $ticket->travel_name ?? ($ticket->trip && $ticket->trip->fleetType ? $ticket->trip->fleetType->name : 'N/A'),
                'TravelName' => $ticket->travel_name ?? ($ticket->trip && $ticket->trip->fleetType ? $ticket->trip->fleetType->name : 'N/A'), // React Native expects this
                'bus_type' => $ticket->bus_type ?? 'N/A',
                'BusType' => $ticket->bus_type ?? 'N/A', // React Native expects this
                'date_of_journey' => Carbon::parse($ticket->date_of_journey)->format('Y-m-d'),
                'departure_time' => $ticket->departure_time ? Carbon::parse($ticket->departure_time)->format('h:i A') : 'N/A',
                'DepartureTime' => $departureTimeFormatted, // React Native expects ISO format
                'arrival_time' => $ticket->arrival_time ? Carbon::parse($ticket->arrival_time)->format('h:i A') : 'N/A',
                'ArrivalTime' => $arrivalTimeFormatted, // React Native expects ISO format
                'duration' => ($ticket->arrival_time && $ticket->departure_time)
                    ? Carbon::parse($ticket->arrival_time)
                        ->diff(Carbon::parse($ticket->departure_time))
                        ->format('%H:%I')
                    : 'N/A',
                'Duration' => ($ticket->arrival_time && $ticket->departure_time)
                    ? Carbon::parse($ticket->arrival_time)
                        ->diff(Carbon::parse($ticket->departure_time))
                        ->format('%H:%I')
                    : 'N/A', // React Native expects this
                'boarding_point_details' => $boardingPointDetails,
                'BoardingPointDetails' => $boardingPointDetails ? $boardingPointDetails[0] : null, // React Native expects single object
                'boarding_point' => $boardingPointString,
                'boarding_details' => $boardingPointString, // React Native expects this
                'dropping_point_details' => $droppingPointDetails,
                'DroppingPointDetails' => $droppingPointDetails ? $droppingPointDetails[0] : null, // React Native expects single object
                'dropping_point' => $droppingPointString,
                'drop_off_details' => $droppingPointString, // React Native expects this
                'passengers' => $passengers,
                'Passenger' => $passengers, // React Native expects this
                'unit_price' => round((float) $unitPrice, 2),
                'sub_total' => round((float) $ticket->sub_total, 2),
                'total_fare' => round((float) $ticket->sub_total, 2), // Keep for backward compatibility
                'TotalFare' => round((float) $ticket->sub_total, 2), // React Native expects this
                'Fare' => round((float) $ticket->sub_total, 2), // React Native expects this
                'total_amount' => round((float) ($ticket->total_amount ?? $ticket->sub_total), 2),
                'paid_amount' => round((float) ($ticket->paid_amount ?? $ticket->sub_total), 2),
                'gst_details' => $gstDetails,
                'status' => $ticket->status == 1 ? 'Booked' : ($ticket->status == 3 ? 'Cancelled' : 'Rejected'),
                'booked_at' => $ticket->created_at->toDateTimeString(),
                'booking_id' => $booking_id,
                'BookingId' => $booking_id, // React Native expects this
                'SearchTokenId' => $search_token_id,
                'UserIp' => $userIp,
                'can_cancel' => $canCancel, // Flag indicating if user can cancel this ticket
                'is_owner' => $isOwner, // Flag indicating if user is the booking owner
                'is_booking_owner' => $isOwner, // Alias for is_owner (for frontend compatibility)
                'booking_owner_id' => $ticket->user_id, // ID of the user who made the booking (for analytics)
                'cancellation_details' => $ticket->status == 3 ? array_merge(
                    $ticket->cancellation_details ?? [],
                    [
                        'cancelled_at' => $ticket->cancelled_at ? Carbon::parse($ticket->cancelled_at)->toDateTimeString() : null,
                        'remarks' => $ticket->cancellation_remarks,
                    ]
                ) : null,
            ];

            return response()->json([
                'success' => true,
                'ticket' => $formattedTicket
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('getTicketByBookingId validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in getTicketByBookingId: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

}
