<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\BookedTicket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Counter;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    /**
     * Print ticket - supports both web and API routes
     * Can return HTML view or JSON response based on request type
     */
    public function printTicket($id, Request $request = null)
    {
        // Get request if not provided
        if (!$request) {
            $request = request();
        }

        // Find ticket by ID, PNR, booking_id, api_booking_id, or operator_pnr
        $ticket = \App\Models\BookedTicket::where('id', $id)
            ->orWhere('pnr_number', $id)
            ->orWhere('booking_id', $id)
            ->orWhere('api_booking_id', $id)
            ->orWhere('operator_pnr', $id)
            ->with(['trip.fleetType', 'user'])
            ->firstOrFail();

        // Format ticket data for printing
        $formattedTicket = $this->formatTicketForPrint($ticket);

        // Check if this is an API request
        $isApiRequest = $request->wantsJson() || $request->expectsJson() || $request->is('api/*');

        if ($isApiRequest) {
            return response()->json([
                'success' => true,
                'ticket' => $formattedTicket,
                'print_html' => view($this->activeTemplate . 'ticket.print_only', [
                    'ticket' => (object) $formattedTicket,
                    'companyName' => $this->getCompanyName(),
                    'logoUrl' => $this->getLogoUrl(),
                ])->render()
            ]);
        }

        // Web request - return HTML view
        $pageTitle = 'Print Ticket';

        // Use the new clean print template
        return view($this->activeTemplate . 'ticket.print_only', [
            'ticket' => (object) $formattedTicket,
            'companyName' => $this->getCompanyName(),
            'logoUrl' => $this->getLogoUrl(),
            'pageTitle' => $pageTitle
        ]);
    }

    /**
     * Public print ticket method - accessible without authentication
     * Used by both web and mobile users
     */
    public function publicPrintTicket($id, Request $request = null)
    {
        // Just call the main printTicket method
        return $this->printTicket($id, $request);
    }

    /**
     * Format ticket data for printing
     */
    private function formatTicketForPrint($ticket)
    {
        // Get seats
        $seats = is_array($ticket->seats) ? $ticket->seats : (is_string($ticket->seats) ? explode(',', $ticket->seats) : []);

        // Parse passenger names
        $nameParts = explode(' ', $ticket->passenger_name ?? '', 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        // Parse boarding and dropping point details
        $boardingPointDetails = null;
        if ($ticket->boarding_point_details) {
            $boardingDetails = is_string($ticket->boarding_point_details)
                ? json_decode($ticket->boarding_point_details, true)
                : $ticket->boarding_point_details;

            if (is_array($boardingDetails)) {
                if (isset($boardingDetails[0]) && is_array($boardingDetails[0])) {
                    $boardingPointDetails = $boardingDetails;
                } elseif (isset($boardingDetails['CityPointName']) || isset($boardingDetails['CityPointIndex'])) {
                    $boardingPointDetails = [$boardingDetails];
                }
            }
        }

        $droppingPointDetails = null;
        if ($ticket->dropping_point_details) {
            $droppingDetails = is_string($ticket->dropping_point_details)
                ? json_decode($ticket->dropping_point_details, true)
                : $ticket->dropping_point_details;

            if (is_array($droppingDetails)) {
                if (isset($droppingDetails[0]) && is_array($droppingDetails[0])) {
                    $droppingPointDetails = $droppingDetails;
                } elseif (isset($droppingDetails['CityPointName']) || isset($droppingDetails['CityPointIndex'])) {
                    $droppingPointDetails = [$droppingDetails];
                }
            }
        }

        // Format boarding and dropping points as strings
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

        // Format times
        $departureTime = $ticket->departure_time && $ticket->departure_time != '00:00:00'
            ? \Carbon\Carbon::parse($ticket->departure_time)->format('h:i A')
            : 'N/A';

        $arrivalTime = $ticket->arrival_time && $ticket->arrival_time != '00:00:00'
            ? \Carbon\Carbon::parse($ticket->arrival_time)->format('h:i A')
            : 'N/A';

        // Calculate duration
        $duration = 'N/A';
        if ($ticket->arrival_time && $ticket->departure_time && $ticket->arrival_time != '00:00:00' && $ticket->departure_time != '00:00:00') {
            $duration = \Carbon\Carbon::parse($ticket->arrival_time)
                ->diff(\Carbon\Carbon::parse($ticket->departure_time))
                ->format('%H:%I');
        }

        // Build passengers array
        $passengers = [];
        foreach ($seats as $index => $seat) {
            $isLead = ($index === 0);
            $passengers[] = [
                'LeadPassenger' => $isLead,
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'Age' => $isLead ? $ticket->passenger_age : null,
                'Gender' => $ticket->passenger_gender ?? 1,
                'Phoneno' => $isLead ? $ticket->passenger_phone : null,
                'Email' => $isLead ? $ticket->passenger_email : null,
                'Seat' => [
                    'SeatName' => $seat,
                ],
            ];
        }

        return [
            'pnr_number' => $ticket->pnr_number,
            'passenger_name' => $ticket->passenger_name ?? ($firstName . ' ' . $lastName),
            'passenger_phone' => $ticket->passenger_phone ?? null,
            'passenger_email' => $ticket->passenger_email ?? null,
            'travel_name' => $ticket->travel_name ?? ($ticket->trip && $ticket->trip->fleetType ? $ticket->trip->fleetType->name : 'N/A'),
            'bus_type' => $ticket->bus_type ?? 'N/A',
            'date_of_journey' => $ticket->date_of_journey ? \Carbon\Carbon::parse($ticket->date_of_journey)->format('Y-m-d') : null,
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime,
            'duration' => $duration,
            'boarding_point' => $boardingPointString,
            'dropping_point' => $droppingPointString,
            'seats' => $seats,
            'passengers' => $passengers,
            'sub_total' => $ticket->sub_total ?? 0,
            'service_charge' => $ticket->service_charge ?? 0,
            'service_charge_percentage' => $ticket->service_charge_percentage ?? 0,
            'platform_fee' => $ticket->platform_fee ?? 0,
            'platform_fee_percentage' => $ticket->platform_fee_percentage ?? 0,
            'platform_fee_fixed' => $ticket->platform_fee_fixed ?? 0,
            'gst' => $ticket->gst ?? 0,
            'gst_percentage' => $ticket->gst_percentage ?? 0,
            'total_amount' => $ticket->total_amount ?? ($ticket->sub_total ?? 0),
            'total_fare' => $ticket->total_amount ?? ($ticket->sub_total ?? 0),
            'status' => $ticket->status,
            'created_at' => $ticket->created_at,
        ];
    }

    /**
     * Get company name from general settings
     */
    private function getCompanyName()
    {
        try {
            $general = \App\Models\GeneralSetting::first();
            return $general && method_exists($general, 'sitename') ? $general->sitename('') : 'Bus Booking';
        } catch (\Exception $e) {
            return 'Bus Booking';
        }
    }

    /**
     * Get logo URL
     */
    private function getLogoUrl()
    {
        try {
            return getImage(imagePath()['logoIcon']['path'] . '/logo.png');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update ticket with bus details from JSON
     */
    private function updateTicketWithBusDetails($ticket, $busDetails)
    {
        $updateData = [];

        if (isset($busDetails['travel_name']) && (!$ticket->travel_name || $ticket->travel_name == '')) {
            $updateData['travel_name'] = $busDetails['travel_name'];
        }

        if (isset($busDetails['bus_type']) && (!$ticket->bus_type || $ticket->bus_type == '')) {
            $updateData['bus_type'] = $busDetails['bus_type'];
        }

        if (isset($busDetails['departure_time']) && ($ticket->departure_time == '00:00:00' || $ticket->departure_time == null)) {
            // Format the departure time correctly for database storage
            $updateData['departure_time'] = date('H:i:s', strtotime($busDetails['departure_time']));
        }

        if (isset($busDetails['arrival_time']) && ($ticket->arrival_time == '00:00:00' || $ticket->arrival_time == null)) {
            // Format the arrival time correctly for database storage
            $updateData['arrival_time'] = date('H:i:s', strtotime($busDetails['arrival_time']));
        }

        if (!empty($updateData)) {
            \App\Models\BookedTicket::where('id', $ticket->id)->update($updateData);

            // Update the ticket object with new values
            foreach ($updateData as $key => $value) {
                $ticket->$key = $value;
            }
        }
    }

    /**
     * Extract and update boarding and dropping point details from API response
     */
    private function extractAndUpdatePointDetails($ticket, $apiResponse)
    {
        $updateData = [];

        // Extract boarding point details
        if (isset($apiResponse['BoardingPointsDetails']) && is_array($apiResponse['BoardingPointsDetails'])) {
            foreach ($apiResponse['BoardingPointsDetails'] as $point) {
                if ($point['CityPointIndex'] == $ticket->pickup_point) {
                    $updateData['boarding_point_details'] = json_encode($point);

                    // Update the counter record with details
                    $this->updateCounterWithDetails($ticket->pickup_point, $point);
                    break;
                }
            }
        } elseif (isset($apiResponse['Result']['BoardingPointsDetails']) && is_array($apiResponse['Result']['BoardingPointsDetails'])) {
            foreach ($apiResponse['Result']['BoardingPointsDetails'] as $point) {
                if ($point['CityPointIndex'] == $ticket->pickup_point) {
                    $updateData['boarding_point_details'] = json_encode($point);

                    // Update the counter record with details
                    $this->updateCounterWithDetails($ticket->pickup_point, $point);
                    break;
                }
            }
        }

        // Extract dropping point details
        if (isset($apiResponse['DroppingPointsDetails']) && is_array($apiResponse['DroppingPointsDetails'])) {
            foreach ($apiResponse['DroppingPointsDetails'] as $point) {
                if ($point['CityPointIndex'] == $ticket->dropping_point) {
                    $updateData['dropping_point_details'] = json_encode($point);

                    // Update the counter record with details
                    $this->updateCounterWithDetails($ticket->dropping_point, $point);
                    break;
                }
            }
        } elseif (isset($apiResponse['Result']['DroppingPointsDetails']) && is_array($apiResponse['Result']['DroppingPointsDetails'])) {
            foreach ($apiResponse['Result']['DroppingPointsDetails'] as $point) {
                if ($point['CityPointIndex'] == $ticket->dropping_point) {
                    $updateData['dropping_point_details'] = json_encode($point);

                    // Update the counter record with details
                    $this->updateCounterWithDetails($ticket->dropping_point, $point);
                    break;
                }
            }
        }

        // Extract operator PNR if available
        if (isset($apiResponse['Result']['TravelOperatorPNR']) && !$ticket->operator_pnr) {
            $updateData['operator_pnr'] = $apiResponse['Result']['TravelOperatorPNR'];
        }

        if (!empty($updateData)) {
            \App\Models\BookedTicket::where('id', $ticket->id)->update($updateData);

            // Update the ticket object with new values
            foreach ($updateData as $key => $value) {
                $ticket->$key = $value;
            }
        }
    }

    /**
     * Update counter record with detailed information
     */
    private function updateCounterWithDetails($counterId, $details)
    {
        $counter = \App\Models\Counter::find($counterId);

        if ($counter) {
            $updateData = [];

            if (isset($details['CityPointName']) && (!$counter->name || $counter->name == 'Boarding Point ' . $counterId || $counter->name == 'Dropping Point ' . $counterId)) {
                $updateData['name'] = $details['CityPointName'];
            }

            if (isset($details['CityPointLocation']) && !$counter->address) {
                $updateData['address'] = $details['CityPointLocation'];
            }

            if (isset($details['CityPointContactNumber']) && !$counter->contact) {
                $updateData['contact'] = $details['CityPointContactNumber'];
            }

            if (!empty($updateData)) {
                \App\Models\Counter::where('id', $counterId)->update($updateData);
            }
        } else {
            // Create counter if it doesn't exist
            $counter = new \App\Models\Counter();
            $counter->id = $counterId;
            $counter->name = $details['CityPointName'] ?? 'Point ' . $counterId;
            $counter->address = $details['CityPointLocation'] ?? null;
            $counter->contact = $details['CityPointContactNumber'] ?? null;
            $counter->status = 1;
            $counter->save();
        }
    }

    /**
     * Get boarding point details from database or API
     */
    private function getBoardingPointDetails($pointId)
    {
        // First try to get from database
        $counter = \App\Models\Counter::find($pointId);

        if ($counter) {
            return [
                'CityPointIndex' => $counter->id,
                'CityPointName' => $counter->name,
                'CityPointLocation' => $counter->address ?? $counter->name,
                'CityPointTime' => null, // We don't have this in the database
                'CityPointContactNumber' => $counter->contact ?? null,
                'CityPointLandmark' => null,
            ];
        }

        // If not found in database, try to get from API
        $response = getBoardingPoints();

        if ($response && isset($response['Result']['BoardingPointsDetails'])) {
            foreach ($response['Result']['BoardingPointsDetails'] as $point) {
                if ($point['CityPointIndex'] == $pointId) {
                    return $point;
                }
            }
        }

        // If still not found, create a basic record
        return [
            'CityPointIndex' => $pointId,
            'CityPointName' => 'Boarding Point ' . $pointId,
            'CityPointLocation' => 'Location not available',
            'CityPointTime' => null,
            'CityPointContactNumber' => null,
            'CityPointLandmark' => null,
        ];
    }

    /**
     * Get dropping point details from database or API
     */
    private function getDroppingPointDetails($pointId)
    {
        // First try to get from database
        $counter = \App\Models\Counter::find($pointId);

        if ($counter) {
            return [
                'CityPointIndex' => $counter->id,
                'CityPointName' => $counter->name,
                'CityPointLocation' => $counter->address ?? $counter->name,
                'CityPointTime' => null, // We don't have this in the database
                'CityPointContactNumber' => $counter->contact ?? null,
                'CityPointLandmark' => null,
            ];
        }

        // If not found in database, try to get from API
        $response = getBoardingPoints();

        if ($response && isset($response['Result']['DroppingPointsDetails'])) {
            foreach ($response['Result']['DroppingPointsDetails'] as $point) {
                if ($point['CityPointIndex'] == $pointId) {
                    return $point;
                }
            }
        }

        // If still not found, create a basic record
        return [
            'CityPointIndex' => $pointId,
            'CityPointName' => 'Dropping Point ' . $pointId,
            'CityPointLocation' => 'Location not available',
            'CityPointTime' => null,
            'CityPointContactNumber' => null,
            'CityPointLandmark' => null,
        ];
    }

    /**
     * Create a counter record if it doesn't exist
     *
     * @param int $counterId
     * @param string $namePrefix
     * @param int $cityId
     * @return \App\Models\Counter
     */
    private function createCounterIfMissing($counterId, $namePrefix, $cityId)
    {
        $counter = \App\Models\Counter::find($counterId);

        if (!$counter) {
            $counter = new \App\Models\Counter();
            $counter->id = $counterId;
            $counter->name = $namePrefix . ' ' . $counterId;
            $counter->city = $cityId;
            $counter->status = 1;
            $counter->save();
        }

        return $counter;
    }

    // Support Ticket
    public function supportTicket()
    {
        if (!Auth::user()) {
            abort(404);
        }
        $pageTitle = 'Support Tickets';
        $supports = SupportTicket::where('user_id', Auth::id())->orderBy('priority', 'desc')->orderBy('id', 'desc')->paginate(getPaginate());
        return view($this->activeTemplate . 'user.support.index', compact('supports', 'pageTitle'));
    }

    public function openSupportTicket()
    {
        if (!Auth::user()) {
            abort(404);
        }
        $pageTitle = 'Support Tickets';
        $user = Auth::user();
        return view($this->activeTemplate . 'user.support.create', compact('pageTitle', 'user'));
    }

    public function storeSupportTicket(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }
        $ticket = new SupportTicket();
        $message = new SupportMessage();
        $files = $request->file('attachments');
        $allowedExts = ['jpg', 'png', 'jpeg', 'pdf', 'doc', 'docx'];
        $this->validate($request, [
            'attachments' => [
                'max:4096',
                function ($attribute, $value, $fail) use ($files, $allowedExts) {
                    foreach ($files as $file) {
                        $ext = strtolower($file->getClientOriginalExtension());
                        if ($file->getSize() / 1000000 > 2) {
                            return $fail('Maximum 4MB file size allowed!');
                        }
                        if (!in_array($ext, $allowedExts)) {
                            return $fail('Only png, jpg, jpeg, pdf, doc, docx files are allowed');
                        }
                    }
                    if (count($files) > 5) {
                        return $fail('Maximum 5 files can be uploaded');
                    }
                },
            ],
            'name' => 'required|max:191',
            'email' => 'required|exists:users,email|max:191',
            'subject' => 'required|max:100',
            'message' => 'required',
            'priority' => 'required|in:1,2,3',
        ]);

        $ticket->user_id = $user->id;
        $random = rand(100000, 999999);
        $ticket->ticket = $random;
        $ticket->name = $request->name;
        $ticket->email = $request->email;
        $ticket->subject = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status = 0;
        $ticket->priority = $request->priority;
        $ticket->save();
        $message->supportticket_id = $ticket->id;
        $message->message = $request->message;
        $message->save();
        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'New support ticket has opened';
        $adminNotification->click_url = urlPath('admin.ticket.view', routeParam: $ticket->id);
        $adminNotification->save();
        $path = imagePath()['ticket']['path'];
        if ($request->hasFile('attachments')) {
            foreach ($files as $file) {
                try {
                    $attachment = new SupportAttachment();
                    $attachment->support_message_id = $message->id;
                    $attachment->attachment = uploadFile($file, $path);
                    $attachment->save();
                } catch (\Exception $exp) {
                    $notify[] = ['error', 'Could not upload your file'];
                    return back()->withNotify($notify);
                }
            }
        }
        $notify[] = ['success', 'ticket created successfully!'];
        return redirect()->route('support_ticket')->withNotify($notify);
    }

    public function viewTicket($ticket)
    {
        $pageTitle = 'Support Tickets';
        $userId = 0;
        $my_ticket = SupportTicket::where('ticket', $ticket)->orderBy('id', 'desc')->firstOrFail();
        if ($my_ticket->user_id > 0) {
            if (Auth::user()) {
                $userId = Auth::id();
            } else {
                return redirect()->route('user.login');
            }
        }
        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }
        $my_ticket = SupportTicket::where('ticket', $ticket)->where('user_id', $userId)->orderBy('id', 'desc')->firstOrFail();
        $messages = SupportMessage::where('supportticket_id', $my_ticket->id)->orderBy('id', 'desc')->get();
        $user = auth()->user();
        return view($this->activeTemplate . 'user.support.view', compact('my_ticket', 'messages', 'pageTitle', 'user', 'layout'));
    }

    public function replyTicket(Request $request, $id)
    {
        $userId = 0;
        if (Auth::user()) {
            $userId = Auth::id();
        }
        $ticket = SupportTicket::where('user_id', $userId)->where('id', $id)->firstOrFail();
        $message = new SupportMessage();
        if ($request->replayTicket == 1) {
            $attachments = $request->file('attachments');
            $allowedExts = ['jpg', 'png', 'jpeg', 'pdf', 'doc', 'docx'];
            $this->validate($request, [
                'attachments' => [
                    'max:4096',
                    function ($attribute, $value, $fail) use ($attachments, $allowedExts) {
                        foreach ($attachments as $file) {
                            $ext = strtolower($file->getClientOriginalExtension());
                            if ($file->getSize() / 1000000 > 2) {
                                return $fail('Miximum 2MB file size allowed!');
                            }
                            if (!in_array($ext, $allowedExts)) {
                                return $fail('Only png, jpg, jpeg, pdf doc docx files are allowed');
                            }
                        }
                        if (count($attachments) > 5) {
                            return $fail('Maximum 5 files can be uploaded');
                        }
                    },
                ],
                'message' => 'required',
            ]);
            $ticket->status = 2;
            $ticket->last_reply = Carbon::now();
            $ticket->save();
            $message->supportticket_id = $ticket->id;
            $message->message = $request->message;
            $message->save();
            $path = imagePath()['ticket']['path'];
            if ($request->hasFile('attachments')) {
                foreach ($attachments as $file) {
                    try {
                        $attachment = new SupportAttachment();
                        $attachment->support_message_id = $message->id;
                        $attachment->attachment = uploadFile($file, $path);
                        $attachment->save();
                    } catch (\Exception $exp) {
                        $notify[] = ['error', 'Could not upload your ' . $file];
                        return back()->withNotify($notify)->withInput();
                    }
                }
            }
            $notify[] = ['success', 'Support ticket replied successfully!'];
        } elseif ($request->replayTicket == 2) {
            $ticket->status = 3;
            $ticket->last_reply = Carbon::now();
            $ticket->save();
            $notify[] = ['success', 'Support ticket closed successfully!'];
        } else {
            $notify[] = ['error', 'Invalid request'];
        }
        return back()->withNotify($notify);
    }

    public function ticketDownload($ticket_id)
    {
        $attachment = SupportAttachment::findOrFail(decrypt($ticket_id));
        $file = $attachment->attachment;
        $path = imagePath()['ticket']['path'];
        $full_path = $path . '/' . $file;
        $title = slug($attachment->supportMessage->ticket->subject);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimetype = mime_content_type($full_path);
        header('Content-Disposition: attachment; filename="' . $title . '.' . $ext . '";');
        header('Content-Type: ' . $mimetype);
        return readfile($full_path);
    }

    /**
     * Cancel a booked ticket
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelTicket(Request $request)
    {
        try {
            $request->validate([
                'ticket_id' => 'required|integer',
                'remarks' => 'nullable|string|max:255',
            ]);

            // Find the ticket
            $ticket = BookedTicket::findOrFail($request->ticket_id);

            // Check if the ticket belongs to the authenticated user
            if (auth()->id() != $ticket->user_id) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Unauthorized action',
                    ],
                    403,
                );
            }

            // Check if the ticket is already cancelled or past journey date
            if ($ticket->status != 1 || Carbon::parse($ticket->date_of_journey)->lt(Carbon::today())) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'This ticket cannot be cancelled',
                    ],
                    400,
                );
            }

            // Get the booking ID from the PNR number
            $apiResponse = json_decode($ticket->api_response, true);
            $bookingId = $apiResponse['Result']['BookingID'] ?? null;

            if (!$bookingId) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Booking ID not found in API response.',
                    ],
                    500,
                );
            }

            // Get the search token ID from session or use a default one
            $searchTokenId = session()->get('search_token_id') ?? '5a8e779347e468df8e212714b7bc6b6c0472a765';

            // Get the seats (may be multiple)
            $seats = is_array($ticket->seats) ? $ticket->seats : explode(',', $ticket->seats);

            $cancelSuccess = true;
            $apiResponses = [];

            // Cancel each seat
            foreach ($seats as $seat) {
                $response = cancelAPITicket(request()->ip(), $searchTokenId, $bookingId, $seat, $request->remarks ?? 'Cancelled by customer');

                $apiResponses[] = $response;

                // Check if any cancellation failed
                if (isset($response['Error']) && $response['Error']['ErrorCode'] != 0) {
                    $cancelSuccess = false;
                }
            }

            // If API cancellation was successful, update the ticket status
            if ($cancelSuccess) {
                $ticket->status = 3; // Cancelled status
                $ticket->cancellation_remarks = $request->remarks ?? 'Cancelled by customer';
                $ticket->cancelled_at = now();
                $ticket->save();

                // Create an admin notification
                $adminNotification = new AdminNotification();
                $adminNotification->user_id = auth()->id();
                $adminNotification->title = 'Ticket cancelled';
                $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
                $adminNotification->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Ticket cancelled successfully',
                    'ticket' => $ticket,
                ]);
            }

            // If we got here, something went wrong with the API
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to cancel ticket with the provider',
                    'responses' => $apiResponses,
                ],
                500,
            );
        } catch (\Exception $e) {
            Log::error('Ticket cancellation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'An error occurred: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
