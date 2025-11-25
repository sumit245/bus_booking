<?php

namespace App\Http\Controllers;

use App\Lib\GoogleAuthenticator;
use App\Models\GeneralSetting;
use App\Models\BookedTicket;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;


class UserController extends Controller
{
    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function home()
    {
        $pageTitle = 'Dashboard';
        $emptyMessage = 'No booked ticket found';
        $user = auth()->user();

        $widget['total'] = BookedTicket::where('user_id', $user->id)->count();
        $widget['booked'] = BookedTicket::booked()->where('user_id', $user->id)->count();
        $widget['pending'] = BookedTicket::pending()->where('user_id', $user->id)->count();
        $widget['rejected'] = BookedTicket::rejected()->where('user_id', $user->id)->count();
        $widget['cancelled'] = BookedTicket::where('user_id', $user->id)->where('status', 3)->count();

        $bookedTickets = BookedTicket::with([
            'trip.fleetType',
            'trip.startFrom',
            'trip.endTo',
            'trip.schedule',
            'pickup',
            'drop'
        ])->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        return view($this->activeTemplate . 'user.dashboard', compact('pageTitle', 'bookedTickets', 'widget', 'emptyMessage'));
    }


    public function profile()
    {
        $pageTitle = "Profile Setting";
        $user = Auth::user();
        return view($this->activeTemplate . 'user.profile_setting', compact('pageTitle', 'user'));
    }

    public function submitProfile(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:50',
            'lastname' => 'required|string|max:50',
            'address' => 'sometimes|required|max:80',
            'state' => 'sometimes|required|max:80',
            'zip' => 'sometimes|required|max:40',
            'city' => 'sometimes|required|max:50',
            'image' => ['image', new FileTypeValidate(['jpg', 'jpeg', 'png'])]
        ], [
            'firstname.required' => 'First name field is required',
            'lastname.required' => 'Last name field is required'
        ]);

        $user = Auth::user();

        $in['firstname'] = $request->firstname;
        $in['lastname'] = $request->lastname;

        $in['address'] = [
            'address' => $request->address,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => @$user->address->country,
            'city' => $request->city,
        ];


        if ($request->hasFile('image')) {
            $location = imagePath()['profile']['user']['path'];
            $size = imagePath()['profile']['user']['size'];
            $filename = uploadImage($request->image, $location, $size, $user->image);
            $in['image'] = $filename;
        }
        $user->fill($in)->save();
        $notify[] = ['success', 'Profile updated successfully'];
        return back()->withNotify($notify);
    }

    public function changePassword()
    {
        $pageTitle = 'Change password';
        return view($this->activeTemplate . 'user.password', compact('pageTitle'));
    }

    public function submitPassword(Request $request)
    {

        $password_validation = Password::min(6);
        $general = GeneralSetting::first();
        if ($general->secure_password) {
            $password_validation = $password_validation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $this->validate($request, [
            'current_password' => 'required',
            'password' => ['required', 'confirmed', $password_validation]
        ]);


        try {
            $user = auth()->user();
            if (Hash::check($request->current_password, $user->password)) {
                $password = Hash::make($request->password);
                $user->password = $password;
                $user->save();
                $notify[] = ['success', 'Password changes successfully'];
                return back()->withNotify($notify);
            } else {
                $notify[] = ['error', 'The password doesn\'t match!'];
                return back()->withNotify($notify);
            }
        } catch (\PDOException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }
    }

    public function ticketHistory()
    {
        $pageTitle = 'Booking History';
        $emptyMessage = 'No booked ticket found';
        $bookedTickets = BookedTicket::with(['trip.fleetType', 'trip.startFrom', 'trip.endTo', 'trip.schedule', 'pickup', 'drop'])->where('user_id', auth()->user()->id)->orderBy('id', 'desc')->paginate(getPaginate());
        return view($this->activeTemplate . 'user.booking_history', compact('pageTitle', 'emptyMessage', 'bookedTickets'));
    }


    public function printTicket($id)
    {
        $ticket = BookedTicket::with([
            'trip.fleetType',
            'trip.startFrom',
            'trip.endTo',
            'trip.schedule',
            'trip.assignedVehicle.vehicle',
            'pickup',
            'drop',
            'user'
        ])->where('user_id', auth()->user()->id)->findOrFail($id);

        // Prepare ticket data for the unified print template
        $general = GeneralSetting::first();
        $companyName = $general->sitename ?? 'Bus Booking';
        $logoUrl = getImage(imagePath()['logoIcon']['path'] . '/logo.png');

        // Format seats
        $seats = is_array($ticket->seats) ? $ticket->seats : (is_string($ticket->seats) ? json_decode($ticket->seats, true) : []);
        if (!is_array($seats)) {
            $seats = explode(',', $ticket->seats ?? '');
        }
        $ticket->seats = array_filter($seats);

        // Format passenger data
        $passengers = [];
        if ($ticket->passenger_name && !empty($seats)) {
            $nameParts = explode(' ', $ticket->passenger_name, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            foreach ($seats as $seat) {
                $passengers[] = [
                    'FirstName' => $firstName,
                    'LastName' => $lastName,
                    'Age' => $ticket->passenger_age ?? 'N/A',
                    'Gender' => 1, // Default to Male
                    'Phoneno' => $ticket->passenger_phone,
                    'Seat' => [
                        'SeatName' => $seat
                    ]
                ];
            }
        }

        // Add passenger data to ticket
        $ticket->passengers = $passengers;

        // Format journey details
        $ticket->travel_name = $ticket->trip->fleetType->name ?? 'N/A';
        $ticket->bus_type = $ticket->trip->fleetType->has_ac ? 'AC' : 'Non-AC';
        $ticket->boarding_point = $ticket->pickup->name ?? $ticket->origin_city ?? 'N/A';
        $ticket->dropping_point = $ticket->drop->name ?? $ticket->destination_city ?? 'N/A';

        // Format times
        if ($ticket->trip && $ticket->trip->schedule) {
            $ticket->departure_time = \Carbon\Carbon::parse($ticket->trip->schedule->start_from)->format('h:i A');
            $ticket->arrival_time = \Carbon\Carbon::parse($ticket->trip->schedule->end_to)->format('h:i A');

            // Calculate duration
            $start = \Carbon\Carbon::parse($ticket->trip->schedule->start_from);
            $end = \Carbon\Carbon::parse($ticket->trip->schedule->end_to);
            $diff = $start->diff($end);
            $ticket->duration = $diff->format('%H:%I hrs');
        }

        // QR Code (optional - can be generated if needed)
        $qrCodeUrl = null; // You can integrate QR code generation here if needed

        return view('templates.basic.ticket.print_only', compact(
            'ticket',
            'companyName',
            'logoUrl',
            'qrCodeUrl'
        ));
    }
}
