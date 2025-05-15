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
class TicketController extends Controller
{

    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
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
            'remarks' => 'nullable|string|max:255'
        ]);

        // Find the ticket
        $ticket = BookedTicket::findOrFail($request->ticket_id);

        // Check if the ticket belongs to the authenticated user
        if (auth()->id() != $ticket->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        // Check if the ticket is already cancelled or past journey date
        if ($ticket->status != 1 || Carbon::parse($ticket->date_of_journey)->lt(Carbon::today())) {
            return response()->json([
                'success' => false,
                'message' => 'This ticket cannot be cancelled'
            ], 400);
        }

        // Get the booking ID from the PNR number
        $apiResponse = json_decode($ticket->api_response, true);
$bookingId = $apiResponse['Result']['BookingID'] ?? null;

if (!$bookingId) {
    return response()->json([
        'success' => false,
        'message' => 'Booking ID not found in API response.'
    ], 500);
}

        
        // Get the search token ID from session or use a default one
        $searchTokenId = session()->get('search_token_id') ?? '5a8e779347e468df8e212714b7bc6b6c0472a765';
        
        // Get the seats (may be multiple)
        $seats = is_array($ticket->seats) ? $ticket->seats : explode(',', $ticket->seats);
        
        $cancelSuccess = true;
        $apiResponses = [];
        
        // Cancel each seat
        foreach ($seats as $seat) {
            $response = cancelAPITicket(
                request()->ip(),
                $searchTokenId,
                $bookingId,
                $seat,
                $request->remarks ?? 'Cancelled by customer'
            );
            
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
                'ticket' => $ticket
            ]);
        }
        
        // If we got here, something went wrong with the API
        return response()->json([
            'success' => false,
            'message' => 'Failed to cancel ticket with the provider',
            'responses' => $apiResponses
        ], 500);
        
    } catch (\Exception $e) {
        Log::error('Ticket cancellation error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage()
        ], 500);
    }
}

    // Add this new method for printing bus tickets
    public function printTicket($bookingId)
{
    try {
        // Find the ticket by booking ID (PNR number)
        $ticket = BookedTicket::with(['trip', 'user', 'pickup', 'drop'])
            ->where('pnr_number', $bookingId)
            ->firstOrFail();
        
        // Log ticket data for debugging
        Log::info('Ticket data for printing', [
            'ticket_id' => $ticket->id,
            'pnr' => $ticket->pnr_number,
            'user' => $ticket->user ? $ticket->user->toArray() : 'Guest',
            'journey_date' => $ticket->date_of_journey,
            'passenger_name' => $ticket->passenger_name,
            'trip' => $ticket->trip ? $ticket->trip->toArray() : null,
            'pickup' => $ticket->pickup ? $ticket->pickup->toArray() : null,
            'drop' => $ticket->drop ? $ticket->drop->toArray() : null
        ]);
        
        $pageTitle = 'Print Ticket';
        
        // Get the general site settings - with fallback values
        $general = (object) [
            'cur_sym' => '₹', // Default currency symbol as fallback
            'sitename' => 'Bus Booking System' // Default site name as fallback
        ];
        
        // Try to get actual general settings if the function exists
        if (function_exists('getContent')) {
            $generalSettings = getContent('general_setting.content', true);
            if ($generalSettings) {
                $general = $generalSettings;
            }
        }
        
        // Determine the layout based on authentication status
        $layout = 'layouts.frontend';
        
        // Parse journey date to ensure it's in the correct format
        if ($ticket->date_of_journey && $ticket->date_of_journey != '0000-00-00') {
            $journeyDate = Carbon::parse($ticket->date_of_journey);
            $ticket->formatted_date = $journeyDate->format('F d, Y');
            $ticket->journey_day = $journeyDate->format('l');
        } else {
            // Set default values if date is invalid
            $ticket->formatted_date = 'Not specified';
            $ticket->journey_day = 'Not specified';
            
            // Try to update the date if possible
            if ($ticket->trip && $ticket->trip->created_at) {
                $ticket->date_of_journey = $ticket->trip->created_at->format('Y-m-d');
                $ticket->save();
                
                $journeyDate = Carbon::parse($ticket->date_of_journey);
                $ticket->formatted_date = $journeyDate->format('F d, Y');
                $ticket->journey_day = $journeyDate->format('l');
            }
        }
        
        // If we don't have passenger details in the ticket, try to get from session
        if (empty($ticket->passenger_name)) {
            $lastBookedTicket = session()->get('last_booked_ticket', []);
            if (!empty($lastBookedTicket) && $lastBookedTicket['pnr_number'] == $bookingId) {
                $ticket->passenger_name = $lastBookedTicket['passenger_name'] ?? null;
                $ticket->passenger_phone = $lastBookedTicket['passenger_phone'] ?? null;
                $ticket->passenger_email = $lastBookedTicket['passenger_email'] ?? null;
            }
        }
        
        // If trip is missing, try to find it
        if (!$ticket->trip) {
            // Try to find a trip with the same route
            if ($ticket->source_destination && is_array($ticket->source_destination) && count($ticket->source_destination) >= 2) {
                $trip = \App\Models\Trip::where('start_from', $ticket->source_destination[0])
                    ->where('end_to', $ticket->source_destination[1])
                    ->first();
                
                if ($trip) {
                    $ticket->trip_id = $trip->id;
                    $ticket->save();
                    
                    // Reload the ticket with the trip
                    $ticket = BookedTicket::with(['trip', 'user', 'pickup', 'drop'])
                        ->where('pnr_number', $bookingId)
                        ->firstOrFail();
                }
            }
        }
        
        // If pickup or dropping points are missing, create them
        if (!$ticket->pickup) {
            $this->createCounterIfMissing($ticket->pickup_point, 'Pickup Point', $ticket->source_destination[0] ?? 0);
            
            // Reload the ticket
            $ticket = BookedTicket::with(['trip', 'user', 'pickup', 'drop'])
                ->where('pnr_number', $bookingId)
                ->firstOrFail();
        }
        
        if (!$ticket->drop) {
            $this->createCounterIfMissing($ticket->dropping_point, 'Dropping Point', $ticket->source_destination[1] ?? 0);
            
            // Reload the ticket
            $ticket = BookedTicket::with(['trip', 'user', 'pickup', 'drop'])
                ->where('pnr_number', $bookingId)
                ->firstOrFail();
        }
        
        return view('templates.basic.user.print_ticket', compact('ticket', 'pageTitle', 'general', 'layout'));
    } catch (\Exception $e) {
        Log::error('Error in printTicket method: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        abort(500, 'Error generating ticket: ' . $e->getMessage());
    }
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
        $pageTitle = "Support Tickets";
        $supports = SupportTicket::where('user_id', Auth::id())->orderBy('priority', 'desc')->orderBy('id', 'desc')->paginate(getPaginate());
        return view($this->activeTemplate . 'user.support.index', compact('supports', 'pageTitle'));
    }


    public function openSupportTicket()
    {
        if (!Auth::user()) {
            abort(404);
        }
        $pageTitle = "Support Tickets";
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
        $allowedExts = array('jpg', 'png', 'jpeg', 'pdf', 'doc', 'docx');
        $this->validate($request, [
            'attachments' => [
                'max:4096',
                function ($attribute, $value, $fail) use ($files, $allowedExts) {
                    foreach ($files as $file) {
                        $ext = strtolower($file->getClientOriginalExtension());
                        if (($file->getSize() / 1000000) > 2) {
                            return $fail("Maximum 4MB file size allowed!");
                        }
                        if (!in_array($ext, $allowedExts)) {
                            return $fail("Only png, jpg, jpeg, pdf, doc, docx files are allowed");
                        }
                    }
                    if (count($files) > 5) {
                        return $fail("Maximum 5 files can be uploaded");
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
            foreach ($files as  $file) {
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
        $pageTitle = "Support Tickets";
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
            $allowedExts = array('jpg', 'png', 'jpeg', 'pdf', 'doc', 'docx');
            $this->validate($request, [
                'attachments' => [
                    'max:4096',
                    function ($attribute, $value, $fail) use ($attachments, $allowedExts) {
                        foreach ($attachments as $file) {
                            $ext = strtolower($file->getClientOriginalExtension());
                            if (($file->getSize() / 1000000) > 2) {
                                return $fail("Miximum 2MB file size allowed!");
                            }
                            if (!in_array($ext, $allowedExts)) {
                                return $fail("Only png, jpg, jpeg, pdf doc docx files are allowed");
                            }
                        }
                        if (count($attachments) > 5) {
                            return $fail("Maximum 5 files can be uploaded");
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
        header("Content-Type: " . $mimetype);
        return readfile($full_path);
    }
}
