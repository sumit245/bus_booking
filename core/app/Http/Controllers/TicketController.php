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

    // Add this new method for printing bus tickets
    public function printTicket($id)
    {
        $pageTitle = 'Print Ticket';
        $ticket = \App\Models\BookedTicket::where('id', $id)
            ->orWhere('pnr_number', $id)
            ->with(['trip', 'user'])
            ->firstOrFail();

        // Get pickup and drop counters
        $ticket->pickup = \App\Models\Counter::find($ticket->pickup_point);
        $ticket->drop = \App\Models\Counter::find($ticket->dropping_point);

        // Format journey date
        if ($ticket->date_of_journey) {
            $journeyDate = \Carbon\Carbon::parse($ticket->date_of_journey);
            $ticket->formatted_date = $journeyDate->format('F d, Y');
            $ticket->journey_day = $journeyDate->format('l');
        }

        // Extract and update bus details if not already set
        if ((!$ticket->travel_name || !$ticket->bus_type || $ticket->departure_time == '00:00:00' || $ticket->arrival_time == '00:00:00') && !empty($ticket->bus_details)) {
            $busDetails = json_decode($ticket->bus_details, true);
            if ($busDetails) {
                // Update the ticket with bus details
                $this->updateTicketWithBusDetails($ticket, $busDetails);
            }
        }

        // Extract boarding and dropping point details from API response if not already set
        if ((!$ticket->boarding_point_details || !$ticket->dropping_point_details) && !empty($ticket->api_response)) {
            $apiResponse = json_decode($ticket->api_response, true);
            if ($apiResponse) {
                $this->extractAndUpdatePointDetails($ticket, $apiResponse);
            }
        }

        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }

        return view($this->activeTemplate . 'user.print_ticket', compact('pageTitle', 'ticket', 'layout'));
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
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
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
