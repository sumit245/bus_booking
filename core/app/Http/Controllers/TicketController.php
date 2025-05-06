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
                'trip' => $ticket->trip ? $ticket->trip->toArray() : null
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
            if ($ticket->date_of_journey) {
                $journeyDate = Carbon::parse($ticket->date_of_journey);
                $ticket->formatted_date = $journeyDate->format('F d, Y');
                $ticket->journey_day = $journeyDate->format('l');
            }
            
            // Get passenger details from session if available
            $sessionBookingInfo = session()->get('booking_info', []);
            $passengerName = null;
            
            // Check if we have passenger details in the session
            if (isset($sessionBookingInfo['passenger_firstname'])) {
                $passengerName = $sessionBookingInfo['passenger_firstname'] . ' ' . 
                                ($sessionBookingInfo['passenger_lastname'] ?? '');
                $ticket->passenger_name = $passengerName;
                $ticket->passenger_phone = $sessionBookingInfo['passenger_phone'] ?? null;
                $ticket->passenger_email = $sessionBookingInfo['passenger_email'] ?? null;
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
