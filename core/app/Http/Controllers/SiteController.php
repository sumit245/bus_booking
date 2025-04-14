<?php

namespace App\Http\Controllers;

use App\Lib\BusLayout;
use App\Models\AdminNotification;
use App\Models\BookedTicket;
use App\Models\Counter;
use App\Models\FleetType;
use App\Models\Frontend;
use App\Models\Language;
use App\Models\Page;
use App\Models\Schedule;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\TicketPrice;
use App\Models\Trip;
use App\Models\VehicleRoute;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SiteController extends Controller
{
    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function index()
    {
        $count = Page::where('tempname', $this->activeTemplate)->where('slug', 'home')->count();
        if ($count == 0) {
            $page = new Page();
            $page->tempname = $this->activeTemplate;
            $page->name = 'HOME';
            $page->slug = 'home';
            $page->save();
        }

        $pageTitle = 'Home';
        $sections = Page::where('tempname', $this->activeTemplate)->where('slug', 'home')->first();

        return view($this->activeTemplate . 'home', compact('pageTitle', 'sections'));
    }

    public function pages($slug)
    {
        $page = Page::where('tempname', $this->activeTemplate)->where('slug', $slug)->firstOrFail();
        $pageTitle = $page->name;
        $sections = $page->secs;
        return view($this->activeTemplate . 'pages', compact('pageTitle', 'sections'));
    }

    public function contact()
    {
        $pageTitle = "Contact Us";
        $sections = Page::where('tempname', $this->activeTemplate)->where('slug', 'contact')->first();
        $content = Frontend::where('data_keys', 'contact.content')->first();

        return view($this->activeTemplate . 'contact', compact('pageTitle', 'sections', 'content'));
    }

    public function contactSubmit(Request $request)
    {
        $attachments = $request->file('attachments');
        $allowedExts = array('jpg', 'png', 'jpeg', 'pdf');

        $this->validate($request, [
            'name' => 'required|max:191',
            'email' => 'required|max:191',
            'subject' => 'required|max:100',
            'message' => 'required',
        ]);

        $random = getNumber();

        $ticket = new SupportTicket();
        $ticket->user_id = auth()->id() ?? 0;
        $ticket->name = $request->name;
        $ticket->email = $request->email;
        $ticket->priority = 2;

        $ticket->ticket = $random;
        $ticket->subject = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status = 0;
        $ticket->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = auth()->user() ? auth()->user()->id : 0;
        $adminNotification->title = 'A new support ticket has opened ';
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();

        $message = new SupportMessage();
        $message->supportticket_id = $ticket->id;
        $message->message = $request->message;
        $message->save();

        $notify[] = ['success', 'ticket created successfully!'];

        return redirect()->route('ticket.view', [$ticket->ticket])->withNotify($notify);
    }

    public function changeLanguage($lang = null)
    {
        $language = Language::where('code', $lang)->first();
        if (!$language) {
            $lang = 'en';
        }

        session()->put('lang', $lang);
        return redirect()->back();
    }

    public function blog()
    {
        $pageTitle = 'Blog Page';
        $blogs = Frontend::where('data_keys', 'blog.element')->orderBy('id', 'desc')->paginate(getPaginate(16));
        $latestPost = Frontend::where('data_keys', 'blog.element')->orderBy('id', 'desc')->take(10)->get();
        $sections = Page::where('tempname', $this->activeTemplate)->where('slug', 'blog')->first();
        return view($this->activeTemplate . 'blog', compact('blogs', 'pageTitle', 'latestPost', 'sections'));
    }

    public function blogDetails($id, $slug)
    {
        $blog = Frontend::where('id', $id)->where('data_keys', 'blog.element')->firstOrFail();
        $pageTitle = "Blog Details";
        $latestPost = Frontend::where('data_keys', 'blog.element')->where('id', '!=', $id)->orderBy('id', 'desc')->take(10)->get();
        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }
        return view($this->activeTemplate . 'blog_details', compact('blog', 'pageTitle', 'layout', 'latestPost'));
    }

    public function policyDetails($id, $slug)
    {
        $pageTitle = 'Policy Details';
        $policy = Frontend::where('id', $id)->where('data_keys', 'policies.element')->firstOrFail();
        return view($this->activeTemplate . 'policy_details', compact('pageTitle', 'policy'));
    }

    public function cookieDetails()
    {
        $pageTitle = 'Cookie Details';
        $cookie = Frontend::where('data_keys', 'cookie_policy.content')->first();
        return view($this->activeTemplate . 'cookie_policy', compact('pageTitle', 'cookie'));
    }

    public function cookieAccept()
    {
        session()->put('cookie_accepted', true);
        return response()->json(['success' => 'Cookie accepted successfully']);
    }

    public function ticket()
    {
        $pageTitle = 'Book Ticket';
        $emptyMessage = 'There is no trip available';
        $fleetType = FleetType::active()->get();

        $trips = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])->where('status', 1)->paginate(getPaginate(10));

        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }

        $schedules = Schedule::all();
        $routes = VehicleRoute::active()->get();
        return view($this->activeTemplate . 'ticket', compact('pageTitle', 'fleetType', 'trips', 'routes', 'schedules', 'emptyMessage', 'layout'));
    }

    public function showSeat($id)
    {
        $trip = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo', 'assignedVehicle.vehicle', 'bookedTickets'])->where('status', 1)->where('id', $id)->firstOrFail();
        $pageTitle = $trip->title;
        $route = $trip->route;
        $stoppageArr = $trip->route->stoppages;
        $stoppages = Counter::routeStoppages($stoppageArr);
        $busLayout = new BusLayout($trip);
        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }
        return view($this->activeTemplate . 'book_ticket', compact('pageTitle', 'trip', 'stoppages', 'busLayout', 'layout'));
    }

    public function getTicketPrice(Request $request)
    {
        $ticketPrice = TicketPrice::where('vehicle_route_id', $request->vehicle_route_id)->where('fleet_type_id', $request->fleet_type_id)->with('route')->first();
        $route = $ticketPrice->route;
        $stoppages = $ticketPrice->route->stoppages;
        $trip = Trip::find($request->trip_id);
        $sourcePos = array_search($request->source_id, $stoppages);
        $destinationPos = array_search($request->destination_id, $stoppages);

        $bookedTicket = BookedTicket::where('trip_id', $request->trip_id)->where('date_of_journey', Carbon::parse($request->date)->format('Y-m-d'))->whereIn('status', [1, 2])->get()->toArray();

        $startPoint = array_search($trip->start_from, array_values($trip->route->stoppages));
        $endPoint = array_search($trip->end_to, array_values($trip->route->stoppages));
        if ($startPoint < $endPoint) {
            $reverse = false;
        } else {
            $reverse = true;
        }

        if (!$reverse) {
            $can_go = ($sourcePos < $destinationPos) ? true : false;
        } else {
            $can_go = ($sourcePos > $destinationPos) ? true : false;
        }

        if (!$can_go) {
            $data = [
                'error' => 'Select Pickup Point & Dropping Point Properly',
            ];
            return response()->json($data);
        }
        $sdArray = [$request->source_id, $request->destination_id];
        $getPrice = $ticketPrice->prices()->where('source_destination', json_encode($sdArray))->orWhere('source_destination', json_encode(array_reverse($sdArray)))->first();

        if ($getPrice) {
            $price = $getPrice->price;
        } else {
            $price = [
                'error' => 'Admin may not set prices for this route. So, you can\'t buy ticket for this trip.',
            ];
        }
        $data['bookedSeats'] = $bookedTicket;
        $data['reqSource'] = $request->source_id;
        $data['reqDestination'] = $request->destination_id;
        $data['reverse'] = $reverse;
        $data['stoppages'] = $stoppages;
        $data['price'] = $price;
        return response()->json($data);
    }

    public function bookTicket(Request $request, $id)
    {
        $request->validate([
            "pickup_point" => "required|integer|gt:0",
            "dropping_point" => "required|integer|gt:0",
            "date_of_journey" => "required|date",
            "seats" => "required|string",
            "gender" => "required|integer",
        ], [
            "seats.required" => "Please Select at Least One Seat",
        ]);

        if (!auth()->user()) {
            $notify[] = ['error', 'Without login you can\'t book any tickets'];
            return redirect()->route('user.login')->withNotify($notify);
        }

        $date_of_journey = Carbon::parse($request->date_of_journey);
        $today = Carbon::today()->format('Y-m-d');
        if ($date_of_journey->format('Y-m-d') < $today) {
            $notify[] = ['error', 'Date of journey cant\'t be less than today.'];
            return redirect()->back()->withNotify($notify);
        }

        $dayOff = $date_of_journey->format('w');
        $trip = Trip::findOrFail($id);
        $route = $trip->route;
        $stoppages = $trip->route->stoppages;
        $source_pos = array_search($request->pickup_point, $stoppages);
        $destination_pos = array_search($request->dropping_point, $stoppages);

        if (!empty($trip->day_off)) {
            if (in_array($dayOff, $trip->day_off)) {
                $notify[] = ['error', 'The trip is not available for ' . $date_of_journey->format('l')];
                return redirect()->back()->withNotify($notify);
            }
        }

        $booked_ticket = BookedTicket::where('trip_id', $id)->where('date_of_journey', Carbon::parse($request->date)->format('Y-m-d'))->whereIn('status', [1, 2])->where('pickup_point', $request->pickup_point)->where('dropping_point', $request->dropping_point)->whereJsonContains('seats', rtrim($request->seats, ","))->get();
        if ($booked_ticket->count() > 0) {
            $notify[] = ['error', 'Why you are choosing those seats which are already booked?'];
            return redirect()->back()->withNotify($notify);
        }

        $startPoint = array_search($trip->start_from, array_values($trip->route->stoppages));
        $endPoint = array_search($trip->end_to, array_values($trip->route->stoppages));
        if ($startPoint < $endPoint) {
            $reverse = false;
        } else {
            $reverse = true;
        }

        if (!$reverse) {
            $can_go = ($source_pos < $destination_pos) ? true : false;
        } else {
            $can_go = ($source_pos > $destination_pos) ? true : false;
        }

        if (!$can_go) {
            $notify[] = ['error', 'Select Pickup Point & Dropping Point Properly'];
            return redirect()->back()->withNotify($notify);
        }

        $route = $trip->route;
        $ticketPrice = TicketPrice::where('fleet_type_id', $trip->fleetType->id)->where('vehicle_route_id', $route->id)->first();
        $sdArray = [$request->pickup_point, $request->dropping_point];

        $getPrice = $ticketPrice->prices()
            ->where('source_destination', json_encode($sdArray))
            ->orWhere('source_destination', json_encode(array_reverse($sdArray)))
            ->first();
        if (!$getPrice) {
            $notify[] = ['error', 'Invalid selection'];
            return back()->withNotify($notify);
        }
        $seats = array_filter((explode(',', $request->seats)));
        $unitPrice = getAmount($getPrice->price);
        $pnr_number = getTrx(10);
        $bookedTicket = new BookedTicket();
        $bookedTicket->user_id = auth()->user()->id;
        $bookedTicket->gender = $request->gender;
        $bookedTicket->trip_id = $trip->id;
        $bookedTicket->source_destination = [$request->pickup_point, $request->dropping_point];
        $bookedTicket->pickup_point = $request->pickup_point;
        $bookedTicket->dropping_point = $request->dropping_point;
        $bookedTicket->seats = $seats;
        $bookedTicket->ticket_count = sizeof($seats);
        $bookedTicket->unit_price = $unitPrice;
        $bookedTicket->sub_total = sizeof($seats) * $unitPrice;
        $bookedTicket->date_of_journey = Carbon::parse($request->date_of_journey)->format('Y-m-d');
        $bookedTicket->pnr_number = $pnr_number;
        $bookedTicket->status = 0;
        $bookedTicket->save();
        session()->put('pnr_number', $pnr_number);
        return redirect()->route('user.deposit');
    }

    // 1. First of all this function will check if there is any trip available for the searched route
    public function ticketSearch(Request $request)
    {
        if ($request->OriginId && $request->DestinationId && $request->OriginId == $request->DestinationId) {
            $notify[] = ['error', 'Please select pickup point and destination point properly'];
            return redirect()->back()->withNotify($notify);
        }

        if ($request->DateOfJourney && Carbon::parse($request->DateOfJourney)->format('Y-m-d') < Carbon::now()->format('Y-m-d')) {
            $notify[] = ['error', 'Date of journey can\'t be less than today.'];
            return redirect()->back()->withNotify($notify);
        }

        // Fetch buses from the API
        $resp = searchAPIBuses($request->ip(), $request->OriginId, $request->DestinationId, Carbon::parse($request->DateOfJourney)->format('Y-m-d'));

        // TODO: removed this code tempoorarily
        // $trips = Trip::active();

        // if ($request->pickup && $request->destination) {
        //     Session::flash('pickup', $request->pickup);
        //     Session::flash('destination', $request->destination);

        //     $pickup = $request->pickup;
        //     $destination = $request->destination;
        //     $trips = $trips->with('route')->get();
        //     $tripArray = array();

        //     foreach ($trips as $trip) {
        //         $startPoint = array_search($trip->start_from, array_values($trip->route->stoppages));
        //         $endPoint = array_search($trip->end_to, array_values($trip->route->stoppages));
        //         $pickup_point = array_search($pickup, array_values($trip->route->stoppages));
        //         $destination_point = array_search($destination, array_values($trip->route->stoppages));
        //         if ($startPoint < $endPoint) {
        //             if ($pickup_point >= $startPoint && $pickup_point < $endPoint && $destination_point > $startPoint && $destination_point <= $endPoint) {
        //                 array_push($tripArray, $trip->id);
        //             }
        //         } else {
        //             $revArray = array_reverse($trip->route->stoppages);
        //             $startPoint = array_search($trip->start_from, array_values($revArray));
        //             $endPoint = array_search($trip->end_to, array_values($revArray));
        //             $pickup_point = array_search($pickup, array_values($revArray));
        //             $destination_point = array_search($destination, array_values($revArray));
        //             if ($pickup_point >= $startPoint && $pickup_point < $endPoint && $destination_point > $startPoint && $destination_point <= $endPoint) {
        //                 array_push($tripArray, $trip->id);
        //             }
        //         }
        //     }

        //     $trips = Trip::active()->whereIn('id', $tripArray);
        // } else {
        //     if ($request->pickup) {
        //         Session::flash('pickup', $request->pickup);
        //         $pickup = $request->pickup;
        //         $trips = $trips->whereHas('route', function ($route) use ($pickup) {
        //             $route->whereJsonContains('stoppages', $pickup);
        //         });
        //     }

        //     if ($request->destination) {
        //         Session::flash('destination', $request->destination);
        //         $destination = $request->destination;
        //         $trips = $trips->whereHas('route', function ($route) use ($destination) {
        //             $route->whereJsonContains('stoppages', $destination);
        //         });
        //     }
        // }

        // if ($request->fleetType) {
        //     $trips = $trips->whereIn('fleet_type_id', $request->fleetType);
        // }

        // if ($request->routes) {
        //     $trips = $trips->whereIn('vehicle_route_id', $request->routes);
        // }

        // if ($request->schedules) {
        //     $trips = $trips->whereIn('schedule_id', $request->schedules);
        // }

        // if ($request->date_of_journey) {
        //     Session::flash('date_of_journey', $request->date_of_journey);
        //     $dayOff = Carbon::parse($request->date_of_journey)->format('w');
        //     $trips = $trips->whereJsonDoesntContain('day_off', $dayOff);
        // }

        // $trips = $trips->with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])->where('status', 1)->paginate(getPaginate());

        session()->put('search_token_id', $resp['SearchTokenId']);
        session()->put('user_ip', $request->ip());
        session()->put('date_of_journey', $request->DateOfJourney);
        session()->put('origin_id', $request->OriginId);
        session()->put('destination_id', $request->DestinationId);
        // Check for errors in the API response
        if ($resp['Error']['ErrorCode'] !== 0) {
            $notify[] = ['error', $resp['Error']['ErrorMessage']];
            return redirect()->back()->withNotify($notify);
        }

        // Extract trips from the API response
        $trips = $resp['Result'];

        // Sort trips by DepartureTime in ascending order
        usort($trips, function ($a, $b) {
            // Convert departure times to timestamps for comparison
            $timeA = strtotime($a['DepartureTime']);
            $timeB = strtotime($b['DepartureTime']);

            // Sort in ascending order
            return $timeA - $timeB;
        });

        $pageTitle = 'Search Result';
        $emptyMessage = 'There is no trip available';
        $fleetType = FleetType::active()->get();
        $schedules = Schedule::all();
        $routes = VehicleRoute::active()->get();
        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }
        //FIXME print_r($resp);
        return view($this->activeTemplate . 'ticket', compact('pageTitle', 'fleetType', 'trips', 'routes', 'schedules', 'emptyMessage', 'layout'));
    }

    // 2. We will select seats after searching
    public function selectSeat(Request $request, $resultIndex)
    {
        // Store ResultIndex in session
        session()->put('result_index', $resultIndex);
        // Get seat layout from API
        $response = getAPIBusSeats($resultIndex);

        $pageTitle = 'Select Seats';
        $seatHtml = $response['Result']['HTMLLayout'];
        $seatLayout = $response['Result']['SeatLayout'];

        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }
        $cities = DB::table("cities")->get();
        $originCity = DB::table("cities")->where("city_id", $request->session()->get("origin_id"))->first();
        $destinationCity = DB::table("cities")->where("city_id", $request->session()->get("destination_id"))->first();
        return view($this->activeTemplate . 'book_ticket', compact('pageTitle', 'seatLayout', 'layout', 'cities', 'originCity', 'destinationCity'));
    }

    public function placeholderImage($size = null)
    {
        $imgWidth = explode('x', $size)[0];
        $imgHeight = explode('x', $size)[1];
        $text = $imgWidth . '×' . $imgHeight;
        $fontFile = realpath('assets/font') . DIRECTORY_SEPARATOR . 'RobotoMono-Regular.ttf';
        $fontSize = round(($imgWidth - 50) / 8);
        if ($fontSize <= 9) {
            $fontSize = 9;
        }
        if ($imgHeight < 100 && $fontSize > 30) {
            $fontSize = 30;
        }

        $image = imagecreatetruecolor($imgWidth, $imgHeight);
        $colorFill = imagecolorallocate($image, 100, 100, 100);
        $bgFill = imagecolorallocate($image, 175, 175, 175);
        imagefill($image, 0, 0, $bgFill);
        $textBox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX = ($imgWidth - $textWidth) / 2;
        $textY = ($imgHeight + $textHeight) / 2;
        header('Content-Type: image/jpeg');
        imagettftext($image, $fontSize, 0, $textX, $textY, $colorFill, $fontFile, $text);
        imagejpeg($image);
        imagedestroy($image);
    }

    // 3. We will offer boarding and dropping points details
    public function getBoardingPoints(Request $request)
    {


        $response = getBoardingPoints();

        if (!$response || isset($response['Error']['ErrorCode']) && $response['Error']['ErrorCode'] != 0) {
            return response()->json([
                'success' => false,
                'message' => $response['Error']['ErrorMessage'] ?? 'Failed to fetch boarding points'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $response['Result'] ?? []
        ]);
    }

    // 4. Apply api for seat block
    public function blockSeat(Request $request)
    {
        Log::info('Block Seat Request:', ['request' => $request->all()]);
        $request->validate([
            'boarding_point_index' => 'required',
            'dropping_point_index' => 'required',
            'gender' => 'required',
            'seats' => 'required',
        ]);
        // Get selected seats
        $seats = explode(',', $request->seats);

        // Create passenger data for each seat
        $passengers = [];
        foreach ($seats as $index => $seatName) {
            $passengers[] = [
                "LeadPassenger" => $index === 0, // First passenger is the lead
                "Title" => $request->passenger_title,
                "FirstName" => $request->passenger_firstname,
                "LastName" => $request->passenger_lastname,
                "Email" => $request->passenger_email,
                "Phoneno" => $request->passenger_phone,
                "Gender" => $request->gender,
                "IdType" => null,
                "IdNumber" => null,
                "Address" => $request->passenger_address,
                "Age" => $request->passenger_age,
                "SeatName" => $seatName
            ];
        }
        // Call the helper function
        $response = blockSeatHelper(
            $request->boarding_point_index,
            $request->dropping_point_index,
            $passengers,
            $seats
        );

        Log::info('Block Seat Response:', ['response' => $response]);

        if (isset($response['Error']['ErrorCode']) && $response['Error']['ErrorCode'] == 0) {
            // Store booking information in session for payment
            session()->put('booking_info', [
                'boarding_point_index' => $request->boarding_point_index,
                'dropping_point_index' => $request->dropping_point_index,
                'seats' => $request->seats,
                'price' => $request->price,
                'block_response' => $response
            ]);
            // ✅ Return JSON instead of redirecting
            return response()->json([
                'success' => true,
                'message' => 'Seats blocked successfully! Proceed to payment.',
            ]);
            // Redirect to payment page
            // return redirect()->route('user.deposit');
        }

        // If there's an error
        return back()->with('error', $response['Error']['ErrorMessage'] ?? 'Failed to block seats. Please try again.');
    }

    public function bookTicketApi(Request $request)
    {
        echo "Booked Successfuly";
    }
}
