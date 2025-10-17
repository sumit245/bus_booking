<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\BookedTicket;

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


        // OTP is verified, create or fetch the user
        $user = User::firstOrCreate(
            ['mobile' => $request->mobile_number],
            ['username' => $request->user_name]
        );
        // Log in the user
        Auth::login($user);

        // Delete OTP record
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
            $request->validate([
                'mobile_number' => ['required', 'string', 'regex:/^[6-9]\d{9}$/']
            ]);

            $user = User::where('mobile', $request->mobile_number)->first();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            // Fetch all tickets for the user, including completed and cancelled ones.
            $tickets = BookedTicket::with([
                'trip.fleetType',
                'pickup',
                'drop'
            ])
                ->where('user_id', $user->id)
                // Explicitly fetch tickets with any status if needed, or filter for specific ones.
                // ->whereIn('status', [1, 3]) // 1 for Booked, 3 for Cancelled
                ->orderBy('id', 'desc')
                ->get();

            Log::info("Fetched tickets", ["tickets" => $tickets]);
            // Transform the data for a clean API response
            $formattedTickets = $tickets->map(function ($ticket) {
                $seats = is_array($ticket->seats) ? $ticket->seats : [];
                $nameParts = explode(' ', $ticket->passenger_name, 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
                $response = json_decode($ticket->api_response);
                $booking_id = $response && isset($response->Result->BookingID) ? $response->Result->BookingID : null;
                $search_token_id = $response && isset($response->SearchTokenId) ? $response->SearchTokenId : null;
                $userIp = $response && isset($response->UserIp) ? $response->UserIp : null;

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
                            'Price' => $ticket->unit_price, // Price per seat is not available in this context
                        ],
                    ];
                }
                return [
                    'pnr_number' => $ticket->pnr_number,
                    'travel_name' => $ticket->travel_name ?? $ticket->trip->fleetType->name ?? 'N/A',
                    'bus_type' => $ticket->bus_type ?? 'N/A',
                    'date_of_journey' => Carbon::parse($ticket->date_of_journey)->format('Y-m-d'),
                    'departure_time' => $ticket->departure_time ? Carbon::parse($ticket->departure_time)->format('h:i A') : 'N/A',
                    'arrival_time' => $ticket->arrival_time ? Carbon::parse($ticket->arrival_time)->format('h:i A') : 'N/A',
                    'duration' => Carbon::parse($ticket->arrival_time)
                        ->diff($ticket->departure_time)
                        ->format('%H:%I'),
                    'boarding_point_details' => $ticket->boarding_point_details ? json_decode($ticket->boarding_point_details) : null,
                    'boarding_point' => $ticket->origin_city,
                    'dropping_point_details' => $ticket->dropping_point_details ? json_decode($ticket->dropping_point_details) : null,
                    'dropping_point' => $ticket->destination_city,
                    'passengers' => $passengers,
                    'total_fare' => round((float) $ticket->sub_total, 2),
                    'status' => $ticket->status == 1 ? 'Booked' : ($ticket->status == 3 ? 'Cancelled' : 'Rejected'),
                    'booked_at' => $ticket->created_at->toDateTimeString(),
                    'booking_id' => $booking_id,
                    'search_token_id' => $search_token_id,
                    'user_ip' => $userIp,
                    'cancellation_details' => $ticket->status == 3 ? [
                        'cancelled_at' => $ticket->cancelled_at ? Carbon::parse($ticket->cancelled_at)->toDateTimeString() : null,
                        'remarks' => $ticket->cancellation_remarks,
                    ] : null,
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
}
