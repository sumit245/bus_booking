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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\BusService;
use App\Models\User;
use Illuminate\Support\Str;


use App\Models\MarkupTable;
use Exception;

class SiteController extends Controller
{
    protected $busService;

    public function __construct(BusService $busService)
    {
        $this->activeTemplate = activeTemplate();
        $this->busService = $busService;
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

        // Check for promotional keywords to prevent creating a notification
        $isPromotional = false;
        $promoKeywords = ['offer', 'discount', 'sale', 'promo', 'win', 'free', 'marketing', 'seo', 'website design', 'Ranks',];
        $ticketContent = strtolower($request->subject . ' ' . $request->message);

        foreach ($promoKeywords as $keyword) {
            if (strpos($ticketContent, $keyword) !== false) {
                $isPromotional = true;
                break; // Found a keyword, no need to check further
            }
        }

        // Only create a notification if it's not promotional
        if (!$isPromotional) {
            $adminNotification = new AdminNotification();
            $adminNotification->user_id = auth()->user() ? auth()->user()->id : 0;
            $adminNotification->title = 'A new support ticket has opened ';
            $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
            $adminNotification->save();
        }

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


    // 1. First of all this function will check if there is any trip available for the searched route
    public function ticketSearch(Request $request)
    {
        try {
            Log::info($request->all());

            $validatedData = $request->validate([
                'OriginId' => 'required|integer',
                'DestinationId' => 'required|integer|different:OriginId',
                'DateOfJourney' => 'required|after_or_equal:today',
                'sortBy' => 'sometimes|string|in:departure,price-low,price-high,duration',
                'fleetType' => 'sometimes|array',
                'fleetType.*' => 'string|in:A/c,Non-A/c,Seater,Sleeper',
                'departure_time' => 'sometimes|array',
                'departure_time.*' => 'string|in:morning,afternoon,evening,night',
                'live_tracking' => 'sometimes|boolean',
                'min_price' => 'sometimes|numeric|min:0',
                'max_price' => 'sometimes|numeric|gt:min_price',
            ]);

            // Store key search parameters in session
            session([
                'origin_id' => $validatedData['OriginId'],
                'destination_id' => $validatedData['DestinationId'],
                'date_of_journey' => $validatedData['DateOfJourney'],
                'user_ip' => $request->ip(),
            ]);

            $result = $this->busService->searchBuses($validatedData);

            // Store the search token ID
            session(['search_token_id' => $result['SearchTokenId']]);

            $viewData = $this->prepareAndReturnView($result['trips']);
            $viewData['currentCoupon'] = BusService::getCurrentCoupon();

            return view($this->activeTemplate . 'ticket', $viewData);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $notify[] = ['error', 'Validation failed. Please check your inputs.'];
            return redirect()->back()->withNotify($notify)->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            $notify[] = ['error', $e->getMessage()];
            return redirect()->back()->withNotify($notify);
        }
    }

    private function prepareAndReturnView($trips)
    {
        try {
            $viewData = [
                'pageTitle' => 'Search Result',
                'emptyMessage' => 'There is no trip available',
                'fleetType' => FleetType::active()->get(),
                'schedules' => Schedule::all(),
                'routes' => VehicleRoute::active()->get(),
                'trips' => $trips,
                'layout' => auth()->user() ? 'layouts.master' : 'layouts.frontend'
            ];
            return $viewData;
        } catch (\Exception $e) {
            $notify[] = ['error', $e->getMessage()];
            return redirect()->back()->withNotify($notify);
        }
    }

    // Add a new method to handle AJAX filter requests
    public function filterTrips(Request $request)
    {
        // Get the trips from session
        $searchTokenId = session()->get('search_token_id');
        if (!$searchTokenId) {
            return response()->json(['error' => 'No search results found. Please search again.'], 400);
        }

        // Fetch trips from API or session cache
        $resp = searchAPIBuses($request->ip(), session('origin_id'), session('destination_id'), session('date_of_journey'));

        if (isset($resp['Error']['ErrorCode']) && $resp['Error']['ErrorCode'] != 0) {
            return response()->json(['error' => $resp['Error']['ErrorMessage']], 400);
        }

        $trips = $this->sortTripsByDepartureTime($resp['Result']);
        $filteredTrips = $this->applyFilters($trips, $request);

        return response()->json([
            'success' => true,
            'trips' => $filteredTrips,
            'count' => count($filteredTrips)
        ]);
    }


    // 2. We will select seats after searching
    public function selectSeat(Request $request, $resultIndex)
    {
        // Store ResultIndex in session
        session()->put('result_index', $resultIndex);
        $token = session()->get('search_token_id');
        $userIp = session()->get('user_ip');

        // Check if this is an operator bus (ResultIndex starts with 'OP_')
        if (str_starts_with($resultIndex, 'OP_')) {
            // Handle operator bus seat layout
            $operatorBusId = (int) str_replace('OP_', '', $resultIndex);
            $operatorBus = \App\Models\OperatorBus::with(['activeSeatLayout'])->find($operatorBusId);

            if (!$operatorBus || !$operatorBus->activeSeatLayout) {
                abort(404, 'Seat layout not found for this bus');
            }

            $seatLayout = $operatorBus->activeSeatLayout;
            $seatHtml = $seatLayout->html_layout;
            $parsedLayout = parseSeatHtmlToJson($seatHtml);
            $isOperatorBus = true;

            // Store bus details in session
            session()->put('bus_details', [
                'bus_type' => $operatorBus->bus_type ?? null,
                'travel_name' => $operatorBus->travel_name ?? null,
                'departure_time' => null, // Will be set from search results
                'arrival_time' => null,   // Will be set from search results
                'is_operator_bus' => true
            ]);

        } else {
            // Handle third-party API buses
            $response = getAPIBusSeats($resultIndex, $token, $userIp);

            if (!isset($response['Result'])) {
                abort(404, 'Seat layout not found');
            }

            $seatHtml = $response['Result']['HTMLLayout'];
            $parsedLayout = $response['Result']['SeatLayout'];
            $isOperatorBus = false;

            // Store bus details in session if available
            if (isset($response['Result']['BusType'])) {
                session()->put('bus_details', [
                    'bus_type' => $response['Result']['BusType'] ?? null,
                    'travel_name' => $response['Result']['TravelName'] ?? null,
                    'departure_time' => $response['Result']['DepartureTime'] ?? null,
                    'arrival_time' => $response['Result']['ArrivalTime'] ?? null,
                    'is_operator_bus' => false
                ]);
            }
        }

        $pageTitle = 'Select Seats';

        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }

        $cities = DB::table("cities")->get();
        $originCity = DB::table("cities")->where("city_id", $request->session()->get("origin_id"))->first();
        $destinationCity = DB::table("cities")->where("city_id", $request->session()->get("destination_id"))->first();

        // Provide default cities if session data is not available
        if (!$originCity) {
            $originCity = DB::table("cities")->where("city_name", "Patna")->first();
        }
        if (!$destinationCity) {
            $destinationCity = DB::table("cities")->where("city_name", "Delhi")->first();
        }
        return view($this->activeTemplate . 'book_ticket', compact('pageTitle', 'parsedLayout', 'layout', 'cities', 'originCity', 'destinationCity', 'seatHtml', 'isOperatorBus'));
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
        $SearchTokenID = session()->get('search_token_id');
        $ResultIndex = session()->get('result_index');
        $UserIp = $request->ip();


        // Check if this is an operator bus
        if (str_starts_with($ResultIndex, 'OP_')) {
            // Handle operator bus boarding/dropping points
            $operatorBusId = (int) str_replace('OP_', '', $ResultIndex);
            $operatorBus = \App\Models\OperatorBus::with(['currentRoute.boardingPoints', 'currentRoute.droppingPoints'])->find($operatorBusId);

            if (!$operatorBus || !$operatorBus->currentRoute) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operator bus or route not found'
                ], 400);
            }

            $route = $operatorBus->currentRoute;

            // Transform boarding points to match API format
            $boardingPoints = $route->boardingPoints->map(function ($point) {
                return [
                    'CityPointIndex' => $point->id,
                    'CityPointName' => $point->point_name,
                    'CityPointLocation' => $point->point_address ?: $point->point_location ?: $point->point_name,
                    'CityPointTime' => $point->point_time ?: '00:00:00',
                    'CityPointLandmark' => $point->point_landmark,
                    'CityPointContactNumber' => $point->contact_number,
                ];
            })->toArray();

            // Transform dropping points to match API format
            $droppingPoints = $route->droppingPoints->map(function ($point) {
                return [
                    'CityPointIndex' => $point->id,
                    'CityPointName' => $point->point_name,
                    'CityPointLocation' => $point->point_address ?: $point->point_location ?: $point->point_name,
                    'CityPointTime' => $point->point_time ?: '00:00:00',
                    'CityPointLandmark' => $point->point_landmark,
                    'CityPointContactNumber' => $point->contact_number,
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'BoardingPointsDetails' => $boardingPoints,
                    'DroppingPointsDetails' => $droppingPoints
                ]
            ]);
        }

        // Handle third-party API buses
        if (!$SearchTokenID || !$ResultIndex) {
            return response()->json([
                'success' => false,
                'message' => 'Missing search token or result index'
            ], 400);
        }

        $response = getBoardingPoints($SearchTokenID, $ResultIndex, $UserIp);

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
            'passenger_phone' => 'required',
            'passenger_firstname' => 'required',
            'passenger_lastname' => 'required',
            'passenger_email' => 'required|email',
        ]);

        // Check if OTP is verified
        $phone = $request->passenger_phone;
        if (strpos($phone, '+91') === 0) {
            $phone = substr($phone, 3);
        } else if (strpos($phone, '91') === 0 && strlen($phone) > 10) {
            $phone = substr($phone, 2);
        }

        // $verifiedPhone = Session::get('otp_verified_phone');
        // if (!$verifiedPhone || $verifiedPhone !== $phone) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Phone number not verified with OTP'
        //     ], 400);
        // }

        // Register user if not already registered
        if (!Auth::check()) {
            $fullPhone = '91' . $phone;
            $user = User::where('mobile', $fullPhone)->first();

            if (!$user) {
                // Create new user
                $user = new User();
                $user->firstname = $request->passenger_firstname;
                $user->lastname = $request->passenger_lastname;
                $user->email = $request->passenger_email;
                $user->username = 'user' . time(); // Generate a unique username
                $user->mobile = $fullPhone;
                $user->password = Hash::make(Str::random(8)); // Generate a random password
                $user->country_code = '91';
                $user->address = [
                    'address' => $request->passenger_address ?? '',
                    'state' => '',
                    'zip' => '',
                    'country' => 'India',
                    'city' => ''
                ];
                $user->status = 1;
                $user->ev = 1; // Email verified
                $user->sv = 1; // SMS verified
                $user->save();

                // Log the user in
                Auth::login($user);
            } else {
                // Log in existing user
                Auth::login($user);
            }
        }

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

        // Get boarding and dropping point details before blocking seats
        $boardingPointDetails = null;
        $droppingPointDetails = null;
        $SearchTokenID = session()->get('search_token_id');
        $ResultIndex = session()->get('result_index');
        $UserIp = $request->ip();

        // Get boarding points from API
        $boardingResponse = getBoardingPoints($SearchTokenID, $ResultIndex, $UserIp);
        if ($boardingResponse && isset($boardingResponse['Result'])) {
            // Store boarding points in session for later use
            session()->put('boarding_points', $boardingResponse['Result']['BoardingPointsDetails'] ?? []);
            session()->put('dropping_points', $boardingResponse['Result']['DroppingPointsDetails'] ?? []);

            // Find the selected boarding point
            if (isset($boardingResponse['Result']['BoardingPointsDetails'])) {
                foreach ($boardingResponse['Result']['BoardingPointsDetails'] as $point) {
                    if ($point['CityPointIndex'] == $request->boarding_point_index) {
                        $boardingPointDetails = $point;
                        break;
                    }
                }
            }

            // Find the selected dropping point
            if (isset($boardingResponse['Result']['DroppingPointsDetails'])) {
                foreach ($boardingResponse['Result']['DroppingPointsDetails'] as $point) {
                    if ($point['CityPointIndex'] == $request->dropping_point_index) {
                        $droppingPointDetails = $point;
                        break;
                    }
                }
            }
        }

        // Call the helper function to block seats
        $response = blockSeatHelper(
            $SearchTokenID,
            $ResultIndex,
            $request->boarding_point_index,
            $request->dropping_point_index,
            $passengers,
            $seats,
            $UserIp
        );

        Log::info('Block Seat Response:', ['response' => $response]);

        // TODO:Need to rectify here according to rectified helper
        if (isset($response['success'])) {
            // Get trip details from the response if available
            $tripDetails = null;
            if (isset($response['Result'])) {
                $tripDetails = [
                    'departure_time' => $response['Result']['DepartureTime'] ?? null,
                    'arrival_time' => $response['Result']['ArrivalTime'] ?? null,
                    'bus_type' => $response['Result']['BusType'] ?? null,
                    'travel_name' => $response['Result']['TravelName'] ?? null,
                ];
            }

            // Store booking information in session for payment
            session()->put('booking_info', [
                'boarding_point_index' => $request->boarding_point_index,
                'dropping_point_index' => $request->dropping_point_index,
                'boarding_point_details' => $boardingPointDetails,
                'dropping_point_details' => $droppingPointDetails,
                'seats' => $request->seats,
                'price' => $request->price,
                'block_response' => $response,
                'result_index' => session()->get('result_index'),
                'passengers' => $passengers,
                'journey_date' => session()->get('date_of_journey'),
                'trip_details' => $tripDetails
            ]);

            // Return JSON instead of redirecting
            return response()->json([
                'response' => $response['Result'],
                'success' => true,
                'message' => 'Seats blocked successfully! Proceed to payment.',
            ]);
        }

        // If there's an error
        return response()->json([
            'success' => false,
            'message' => $response['Error']['ErrorMessage'] ?? 'Failed to block seats. Please try again.'
        ], 400);
    }

    /**
     * Update the bookTicketApi method to properly store bus details and boarding/dropping points
     */
    public function bookTicketApi(Request $request)
    {
        try {
            Log::info('Booking ticket after payment', $request->all());

            $request->validate([
                'booking_id' => 'required|string',
                'payment_id' => 'required|string',
                'payment_status' => 'required|string'
            ]);

            $bookingInfo = session()->get('booking_info');

            if (!$bookingInfo) {
                Log::error('Booking info not found in session');
                return response()->json([
                    'success' => false,
                    'message' => 'Booking information not found'
                ], 400);
            }

            // Check if result_index exists in booking_info
            if (!isset($bookingInfo['result_index'])) {
                Log::error('Missing result_index in booking_info session data', ['booking_info' => $bookingInfo]);
                return response()->json([
                    'success' => false,
                    'message' => 'Missing result_index in booking information.'
                ], 400);
            }

            // Get search token ID from block response if not already set
            if (!isset($bookingInfo['search_token_id'])) {
                $searchTokenId = $bookingInfo['block_response']['SearchTokenId'] ?? session()->get('search_token_id');
                $bookingInfo['search_token_id'] = $searchTokenId;
            }

            Log::info('Booking with payment', [
                'booking_id' => $request->booking_id,
                'payment_id' => $request->payment_id,
                'actual_price' => $bookingInfo['price'],
                'result_index' => $bookingInfo['result_index'],
                'search_token_id' => $bookingInfo['search_token_id']
            ]);

            // Book the ticket via external API
            $apiResponse = bookAPITicket(
                request()->ip(),
                $bookingInfo['search_token_id'],
                $bookingInfo['result_index'],
                (int) $bookingInfo['boarding_point_index'],
                (int) $bookingInfo['dropping_point_index'],
                $bookingInfo['passengers']
            );

            Log::info('Book ticket API response', ['response' => $apiResponse]);

            if (isset($apiResponse['Error']) && $apiResponse['Error']['ErrorCode'] != 0) {
                return response()->json([
                    'success' => false,
                    'message' => $apiResponse['Error']['ErrorMessage'] ?? 'Booking failed',
                    'error' => $apiResponse['Error']
                ], 500);
            }

            // Proceed to save the booking locally
            $userId = auth()->check() ? auth()->id() : 0;

            // Extract passenger details from the first passenger
            $firstPassenger = $bookingInfo['passengers'][0] ?? [];

            // Get journey date from session or API response
            $journeyDate = null;

            // Try to get date from block response
            if (isset($bookingInfo['block_response']['Result']['DepartureTime'])) {
                $departureTime = $bookingInfo['block_response']['Result']['DepartureTime'];
                $journeyDate = date('Y-m-d', strtotime($departureTime));
            }

            // If not found, try session
            if (!$journeyDate) {
                $journeyDate = session()->get('date_of_journey');
            }

            // If still not found, use current date
            if (!$journeyDate || $journeyDate == '0000-00-00') {
                $journeyDate = date('Y-m-d');
            }

            Log::info('Journey date for booking', ['date' => $journeyDate]);

            // Find or create a trip record
            $tripId = $this->findOrCreateTrip($bookingInfo);

            // Create source_destination array
            $sourceDestination = [
                session()->get('origin_id'),
                session()->get('destination_id')
            ];

            // Ensure pickup and dropping points exist in the Counter table
            $this->ensureCounterExists($bookingInfo['boarding_point_index'], $bookingInfo['dropping_point_index']);

            // Get boarding and dropping point details
            $boardingPointDetails = $bookingInfo['boarding_point_details'] ?? null;
            $droppingPointDetails = $bookingInfo['dropping_point_details'] ?? null;

            // If boarding/dropping details weren't captured during seat blocking, try to get them from API response
            if (!$boardingPointDetails && isset($apiResponse['BoardingPointsDetails'])) {
                foreach ($apiResponse['BoardingPointsDetails'] as $point) {
                    if ($point['CityPointIndex'] == $bookingInfo['boarding_point_index']) {
                        $boardingPointDetails = $point;
                        break;
                    }
                }
            } elseif (!$boardingPointDetails && isset($apiResponse['Result']['BoardingPointsDetails'])) {
                foreach ($apiResponse['Result']['BoardingPointsDetails'] as $point) {
                    if ($point['CityPointIndex'] == $bookingInfo['boarding_point_index']) {
                        $boardingPointDetails = $point;
                        break;
                    }
                }
            }

            if (!$droppingPointDetails && isset($apiResponse['DroppingPointsDetails'])) {
                foreach ($apiResponse['DroppingPointsDetails'] as $point) {
                    if ($point['CityPointIndex'] == $bookingInfo['dropping_point_index']) {
                        $droppingPointDetails = $point;
                        break;
                    }
                }
            } elseif (!$droppingPointDetails && isset($apiResponse['Result']['DroppingPointsDetails'])) {
                foreach ($apiResponse['Result']['DroppingPointsDetails'] as $point) {
                    if ($point['CityPointIndex'] == $bookingInfo['dropping_point_index']) {
                        $droppingPointDetails = $point;
                        break;
                    }
                }
            }

            // If still not found, try to get from session
            if (!$boardingPointDetails) {
                $boardingPoints = session()->get('boarding_points', []);
                foreach ($boardingPoints as $point) {
                    if ($point['CityPointIndex'] == $bookingInfo['boarding_point_index']) {
                        $boardingPointDetails = $point;
                        break;
                    }
                }
            }

            if (!$droppingPointDetails) {
                $droppingPoints = session()->get('dropping_points', []);
                foreach ($droppingPoints as $point) {
                    if ($point['CityPointIndex'] == $bookingInfo['dropping_point_index']) {
                        $droppingPointDetails = $point;
                        break;
                    }
                }
            }

            // Get bus details from booking info or API response
            $busDetails = $bookingInfo['trip_details'] ?? null;

            if (!$busDetails && isset($apiResponse['Result'])) {
                $busDetails = [
                    'bus_type' => $apiResponse['Result']['BusType'] ?? null,
                    'travel_name' => $apiResponse['Result']['TravelName'] ?? null,
                    'departure_time' => $apiResponse['Result']['DepartureTime'] ?? null,
                    'arrival_time' => $apiResponse['Result']['ArrivalTime'] ?? null
                ];
            }

            // Create the ticket record
            $ticket = new \App\Models\BookedTicket();
            $ticket->pnr_number = $request->booking_id;
            $ticket->user_id = $userId;
            $ticket->date_of_journey = $journeyDate;
            $ticket->seats = $bookingInfo['seats']; // This will be cast to array by the model
            $ticket->pickup_point = $bookingInfo['boarding_point_index'];
            $ticket->dropping_point = $bookingInfo['dropping_point_index'];
            $ticket->unit_price = $bookingInfo['price'];
            $ticket->sub_total = $bookingInfo['price'];
            $ticket->ticket_count = count(explode(',', $bookingInfo['seats']));
            $ticket->gender = $firstPassenger['Gender'] ?? 1;
            $ticket->status = 1; // Confirmed
            $ticket->api_response = json_encode($apiResponse); // Save full API response
            $ticket->trip_id = $tripId;
            $ticket->source_destination = $sourceDestination;

            // Save passenger details
            $ticket->passenger_name = $firstPassenger['FirstName'] . ' ' . $firstPassenger['LastName'];
            $ticket->passenger_phone = $firstPassenger['Phoneno'] ?? null;
            $ticket->passenger_email = $firstPassenger['Email'] ?? null;
            $ticket->passenger_address = $firstPassenger['Address'] ?? null;
            $ticket->passenger_age = $firstPassenger['Age'] ?? null;

            // Save all passenger names if multiple
            $passengerNames = [];
            foreach ($bookingInfo['passengers'] as $passenger) {
                $passengerNames[] = $passenger['FirstName'] . ' ' . $passenger['LastName'];
            }
            $ticket->passenger_names = json_encode($passengerNames);

            // Save boarding and dropping point details
            if ($boardingPointDetails) {
                $ticket->boarding_point_details = json_encode($boardingPointDetails);

                // Update the counter record with details
                $this->updateCounterWithDetails($bookingInfo['boarding_point_index'], $boardingPointDetails);
            }

            if ($droppingPointDetails) {
                $ticket->dropping_point_details = json_encode($droppingPointDetails);

                // Update the counter record with details
                $this->updateCounterWithDetails($bookingInfo['dropping_point_index'], $droppingPointDetails);
            }

            // Save bus details
            if ($busDetails) {
                $ticket->bus_details = json_encode($busDetails);

                // Also save to individual fields for direct access
                if (isset($busDetails['bus_type'])) {
                    $ticket->bus_type = $busDetails['bus_type'];
                }

                if (isset($busDetails['travel_name'])) {
                    $ticket->travel_name = $busDetails['travel_name'];
                }

                if (isset($busDetails['departure_time'])) {
                    // Format the departure time correctly for database storage
                    $ticket->departure_time = date('H:i:s', strtotime($busDetails['departure_time']));
                }

                if (isset($busDetails['arrival_time'])) {
                    // Format the arrival time correctly for database storage
                    $ticket->arrival_time = date('H:i:s', strtotime($busDetails['arrival_time']));
                }
            }

            // Save operator PNR if available
            if (isset($apiResponse['Result']['TravelOperatorPNR'])) {
                $ticket->operator_pnr = $apiResponse['Result']['TravelOperatorPNR'];
            }

            $ticket->save();

            // Store ticket in session for immediate access
            session()->put('last_booked_ticket', [
                'id' => $ticket->id,
                'pnr_number' => $ticket->pnr_number,
                'passenger_name' => $ticket->passenger_name,
                'passenger_phone' => $ticket->passenger_phone,
                'passenger_email' => $ticket->passenger_email,
                'date_of_journey' => $ticket->date_of_journey,
                'trip_id' => $ticket->trip_id,
                'source_destination' => $ticket->source_destination,
                'boarding_point_details' => $boardingPointDetails,
                'dropping_point_details' => $droppingPointDetails,
                'bus_details' => $busDetails
            ]);

            session()->forget('booking_info');

            return response()->json([
                'success' => true,
                'message' => 'Ticket booked successfully',
                'booking_id' => $request->booking_id,
                'pnr' => $apiResponse['PNRNo'] ?? null,
                'redirect' => route('user.ticket.print', $request->booking_id)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to book ticket: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to book ticket: ' . $e->getMessage()
            ], 500);
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
     * Find or create a trip record based on booking information
     * 
     * @param array $bookingInfo
     * @return int Trip ID
     */
    private function findOrCreateTrip($bookingInfo)
    {
        // Try to find an existing trip with the same route
        $originId = session()->get('origin_id');
        $destinationId = session()->get('destination_id');

        $trip = \App\Models\Trip::where('start_from', $originId)
            ->where('end_to', $destinationId)
            ->first();

        if ($trip) {
            return $trip->id;
        }

        // Extract trip details from block response if available
        $departureTime = date('H:i:s');
        $arrivalTime = date('H:i:s', strtotime('+4 hours'));
        $busType = 'Bus Trip';

        if (isset($bookingInfo['block_response']['Result'])) {
            $result = $bookingInfo['block_response']['Result'];

            if (isset($result['DepartureTime'])) {
                $departureTime = date('H:i:s', strtotime($result['DepartureTime']));
            }

            if (isset($result['ArrivalTime'])) {
                $arrivalTime = date('H:i:s', strtotime($result['ArrivalTime']));
            }

            if (isset($result['BusType'])) {
                $busType = $result['BusType'];
            }
        }

        // If no trip exists, create a new one
        $trip = new \App\Models\Trip();
        $trip->title = $busType;
        $trip->start_from = $originId;
        $trip->end_to = $destinationId;
        $trip->schedule_id = 1; // Default schedule
        $trip->start_time = $departureTime;
        $trip->end_time = $arrivalTime;
        $trip->status = 1;
        $trip->save();

        return $trip->id;
    }

    /**
     * Ensure counter records exist for pickup and dropping points
     * 
     * @param int $pickupPointId
     * @param int $droppingPointId
     * @return void
     */
    private function ensureCounterExists($pickupPointId, $droppingPointId)
    {
        // Check if pickup point exists
        $pickupCounter = \App\Models\Counter::find($pickupPointId);
        if (!$pickupCounter) {
            // Create pickup counter
            $pickupCounter = new \App\Models\Counter();
            $pickupCounter->id = $pickupPointId;
            $pickupCounter->name = 'Pickup Point ' . $pickupPointId;
            $pickupCounter->city = session()->get('origin_id') ?? 0;
            $pickupCounter->status = 1;
            $pickupCounter->save();
        }

        // Check if dropping point exists
        $droppingCounter = \App\Models\Counter::find($droppingPointId);
        if (!$droppingCounter) {
            // Create dropping counter
            $droppingCounter = new \App\Models\Counter();
            $droppingCounter->id = $droppingPointId;
            $droppingCounter->name = 'Dropping Point ' . $droppingPointId;
            $droppingCounter->city = session()->get('destination_id') ?? 0;
            $droppingCounter->status = 1;
            $droppingCounter->save();
        }
    }
}
