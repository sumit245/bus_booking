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
use App\Models\User;
use Illuminate\Support\Str;


use App\Models\MarkupTable;

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

    // 1. First of all this function will check if there is any trip available for the searched route
    public function ticketSearch(Request $request)
    {
        $this->validateSearchRequest($request);
        $resp = $this->fetchAndProcessAPIResponse($request);

        if ($resp instanceof \Illuminate\Http\RedirectResponse) {
            abort(404, 'No buses found for this route and date');
        }

        if (!is_array($resp) || !isset($resp['Result']) || empty($resp['Result'])) {
            abort(404, 'No buses found for this route and date');
        }

        // Store journey date in proper format
        if ($request->DateOfJourney) {
            $journeyDate = Carbon::parse($request->DateOfJourney)->format('Y-m-d');
            session()->put('date_of_journey', $journeyDate);
            Log::info('Stored journey date in session', ['date' => $journeyDate]);
        }

        return $this->prepareAndReturnView($resp, $request);
    }


    private function prepareAndReturnView($resp, $request)
    {
        $trips = $this->sortTripsByDepartureTime($resp['Result']);

        // Fetch markup details
        $markup = MarkupTable::orderBy('id', 'desc')->first();

        $flatMarkup = $markup->flat_markup ?? 0;
        $percentageMarkup = $markup->percentage_markup ?? 0;
        $threshold = $markup->threshold ?? 0;

        // Modify PublishedPrice based on new markup rules
        foreach ($trips as &$trip) {
            if (isset($trip['BusPrice']['PublishedPrice'])) {
                $originalPrice = $trip['BusPrice']['PublishedPrice'];

                if ($originalPrice <= $threshold) {
                    // Apply flat markup
                    $trip['BusPrice']['PublishedPrice'] = $originalPrice + $flatMarkup;
                } else {
                    // Apply percentage markup
                    $trip['BusPrice']['PublishedPrice'] = $originalPrice + ($originalPrice * $percentageMarkup / 100);
                }
            }
        }

        // Apply filters
        if (
            $request->has('departure_time') || $request->has('amenities') ||
            $request->has('min_price') || $request->has('max_price') ||
            $request->has('fleetType')
        ) {
            $trips = $this->applyFilters($trips, $request);
        }

        $viewData = [
            'pageTitle' => 'Search Result',
            'emptyMessage' => 'There is no trip available',
            'fleetType' => FleetType::active()->get(),
            'schedules' => Schedule::all(),
            'routes' => VehicleRoute::active()->get(),
            'trips' => $trips,
            'layout' => auth()->user() ? 'layouts.master' : 'layouts.frontend'
        ];

        return view($this->activeTemplate . 'ticket', $viewData);
    }



    private function validateSearchRequest(Request $request)
    {
        if ($request->OriginId && $request->DestinationId && $request->OriginId == $request->DestinationId) {
            $notify[] = ['error', 'Please select pickup point and destination point properly'];
            return redirect()->back()->withNotify($notify);
        }

        if ($request->DateOfJourney && Carbon::parse($request->DateOfJourney)->format('Y-m-d') < Carbon::now()->format('Y-m-d')) {
            $notify[] = ['error', 'Date of journey can\'t be less than today.'];
            return redirect()->back()->withNotify($notify);
        }
    }

    // Add this method to the SiteController class to handle filtering
    private function applyFilters($trips, Request $request)
{
    $filteredTrips = $trips;

    // Apply live tracking filter
    if ($request->has('live_tracking') && $request->live_tracking == 1) {
        $filteredTrips = array_filter($filteredTrips, function ($trip) {
            return isset($trip['LiveTrackingAvailable']) && $trip['LiveTrackingAvailable'] === true;
        });
    }

    // Apply departure time filter
    if ($request->has('departure_time') && !empty($request->departure_time)) {
        $filteredTrips = array_filter($filteredTrips, function ($trip) use ($request) {
            $departureTime = \Carbon\Carbon::parse($trip['DepartureTime']);
            $hour = (int)$departureTime->format('H');
            
            $inTimeRange = false;
            foreach ($request->departure_time as $timeRange) {
                switch ($timeRange) {
                    case 'morning':
                        if ($hour >= 6 && $hour < 12) $inTimeRange = true;
                        break;
                    case 'afternoon':
                        if ($hour >= 12 && $hour < 18) $inTimeRange = true;
                        break;
                    case 'evening':
                        if ($hour >= 18 && $hour < 24) $inTimeRange = true; // Changed < 23 to < 24
                        break;
                    case 'night':
                        if ($hour >= 0 && $hour < 6) $inTimeRange = true; // Fixed: changed || to &&
                        break;
                }
                // If we found a match, no need to check other time ranges
                if ($inTimeRange) break;
            }
            return $inTimeRange;
        });
    }

    // Apply amenities filter - Fixed logic to check if bus has the required amenities
    if ($request->has('amenities') && !empty($request->amenities)) {
        $filteredTrips = array_filter($filteredTrips, function ($trip) use ($request) {
            $matchedAmenities = 0;
            $requiredAmenities = count($request->amenities);

            foreach ($request->amenities as $amenity) {
                $hasThisAmenity = false;
                
                switch ($amenity) {
                    case 'wifi':
                        if (
                            stripos($trip['ServiceName'], 'wifi') !== false ||
                            (isset($trip['Description']) && stripos($trip['Description'], 'wifi') !== false)
                        ) {
                            $hasThisAmenity = true;
                        }
                        break;
                    case 'charging':
                        if (
                            stripos($trip['ServiceName'], 'charging') !== false ||
                            (isset($trip['Description']) && stripos($trip['Description'], 'charging') !== false)
                        ) {
                            $hasThisAmenity = true;
                        }
                        break;
                    case 'water':
                        if (
                            stripos($trip['ServiceName'], 'water') !== false ||
                            (isset($trip['Description']) && stripos($trip['Description'], 'water') !== false)
                        ) {
                            $hasThisAmenity = true;
                        }
                        break;
                    case 'blanket':
                        if (
                            stripos($trip['ServiceName'], 'blanket') !== false ||
                            (isset($trip['Description']) && stripos($trip['Description'], 'blanket') !== false)
                        ) {
                            $hasThisAmenity = true;
                        }
                        break;
                }
                
                if ($hasThisAmenity) {
                    $matchedAmenities++;
                }
            }

            // Return true only if ALL selected amenities are found
            return $matchedAmenities === $requiredAmenities;
        });
    }

    // Apply price range filter
    if (($request->has('min_price') && $request->min_price !== null) || ($request->has('max_price') && $request->max_price !== null)) {
        $minPrice = $request->min_price ?? 0;
        $maxPrice = $request->max_price ?? PHP_INT_MAX;

        $filteredTrips = array_filter($filteredTrips, function ($trip) use ($minPrice, $maxPrice) {
            $price = $trip['BusPrice']['PublishedPrice'];
            return $price >= $minPrice && $price <= $maxPrice;
        });
    }

    // Apply fleet type filter - Fixed to work with AND logic for multiple selections
    if ($request->has('fleetType') && !empty($request->fleetType)) {
        $filteredTrips = array_filter($filteredTrips, function ($trip) use ($request) {
            $busType = $trip['BusType'];
            $matchedTypes = 0;
            $requiredTypes = count($request->fleetType);

            foreach ($request->fleetType as $fleetType) {
                $hasThisType = false;
                
                switch ($fleetType) {
                    case 'Seater':
                        if (stripos($busType, 'Seater') !== false) {
                            $hasThisType = true;
                        }
                        break;
                    case 'Sleeper':
                        if (stripos($busType, 'Sleeper') !== false) {
                            $hasThisType = true;
                        }
                        break;
                    case 'A/c':
                        if ((stripos($busType, 'A/c') !== false || stripos($busType, 'AC') !== false) &&
                            stripos($busType, 'Non') === false
                        ) {
                            $hasThisType = true;
                        }
                        break;
                    case 'Non-A/c':
                        if (stripos($busType, 'Non A/c') !== false || 
                            stripos($busType, 'Non Ac') !== false ||
                            stripos($busType, 'Non-A/c') !== false) {
                            $hasThisType = true;
                        }
                        break;
                }
                
                if ($hasThisType) {
                    $matchedTypes++;
                }
            }

            // For fleet types, we use OR logic (bus can be Seater OR Sleeper)
            // But for AC/Non-AC, we use AND logic if both are selected
            $acSelected = in_array('A/c', $request->fleetType);
            $nonAcSelected = in_array('Non-A/c', $request->fleetType);
            $seaterSelected = in_array('Seater', $request->fleetType);
            $sleeperSelected = in_array('Sleeper', $request->fleetType);

            // If both AC and Non-AC are selected, bus must match both (impossible, so return false)
            if ($acSelected && $nonAcSelected) {
                return false;
            }

            // Check if bus matches the selected criteria
            $matchesAcCriteria = true;
            $matchesTypeCriteria = true;

            // Check AC/Non-AC criteria
            if ($acSelected || $nonAcSelected) {
                $matchesAcCriteria = false;
                if ($acSelected && ((stripos($busType, 'A/c') !== false || stripos($busType, 'AC') !== false) && stripos($busType, 'Non') === false)) {
                    $matchesAcCriteria = true;
                }
                if ($nonAcSelected && (stripos($busType, 'Non A/c') !== false || stripos($busType, 'Non Ac') !== false)) {
                    $matchesAcCriteria = true;
                }
            }

            // Check Seater/Sleeper criteria
            if ($seaterSelected || $sleeperSelected) {
                $matchesTypeCriteria = false;
                if ($seaterSelected && stripos($busType, 'Seater') !== false) {
                    $matchesTypeCriteria = true;
                }
                if ($sleeperSelected && stripos($busType, 'Sleeper') !== false) {
                    $matchesTypeCriteria = true;
                }
            }

            return $matchesAcCriteria && $matchesTypeCriteria;
        });
    }

    return array_values($filteredTrips); // Reset array keys
}



    // Update the prepareAndReturnView method to apply filters
    // private function prepareAndReturnView($resp, $request)
    // {
    //     $trips = $this->sortTripsByDepartureTime($resp['Result']);

    //     // Apply filters if any are set
    //     if (
    //         $request->has('departure_time') || $request->has('amenities') ||
    //         $request->has('min_price') || $request->has('max_price') ||
    //         $request->has('fleetType')
    //     ) {
    //         $trips = $this->applyFilters($trips, $request);
    //     }

    //     $viewData = [
    //         'pageTitle' => 'Search Result',
    //         'emptyMessage' => 'There is no trip available',
    //         'fleetType' => FleetType::active()->get(),
    //         'schedules' => Schedule::all(),
    //         'routes' => VehicleRoute::active()->get(),
    //         'trips' => $trips,
    //         'layout' => auth()->user() ? 'layouts.master' : 'layouts.frontend'
    //     ];

    //     return view($this->activeTemplate . 'ticket', $viewData);
    // }

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


    private function fetchAndProcessAPIResponse(Request $request)
    {
        $resp = searchAPIBuses(
            $request->OriginId,
            $request->DestinationId,
            Carbon::parse($request->DateOfJourney)->format('Y-m-d'),
            $request->ip(),
        );
        Log::info($resp);

        $this->storeSearchSession($request, $resp['SearchTokenId']);

        if ($resp['Error']['ErrorCode'] !== 0) {
            $notify[] = ['error', $resp['Error']['ErrorMessage']];
            return redirect()->back()->withNotify($notify);
        }

        return $resp;
    }

    private function storeSearchSession(Request $request, $searchTokenId)
    {
        session()->put([
            'search_token_id' => $searchTokenId,
            'user_ip' => $request->ip(),
            'date_of_journey' => $request->DateOfJourney,
            'origin_id' => $request->OriginId,
            'destination_id' => $request->DestinationId
        ]);
    }


    private function sortTripsByDepartureTime($trips)
    {
        usort($trips, function ($a, $b) {
            return strtotime($a['DepartureTime']) - strtotime($b['DepartureTime']);
        });
        return $trips;
    }

    // 2. We will select seats after searching
    public function selectSeat(Request $request, $resultIndex)
    {
        // Store ResultIndex in session
        session()->put('result_index', $resultIndex);
        $token = session()->get('search_token_id');
        $userIp = session()->get('user_ip');
        // Get seat layout from API
        $response = getAPIBusSeats($resultIndex, $token, $userIp);

        $pageTitle = 'Select Seats';
        $seatHtml = $response['Result']['HTMLLayout'];
        $seatLayout = $response['Result']['SeatLayout'];

        // Store bus details in session if available
        if (isset($response['Result']['BusType'])) {
            session()->put('bus_details', [
                'bus_type' => $response['Result']['BusType'] ?? null,
                'travel_name' => $response['Result']['TravelName'] ?? null,
                'departure_time' => $response['Result']['DepartureTime'] ?? null,
                'arrival_time' => $response['Result']['ArrivalTime'] ?? null
            ]);
        }

        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }

        $cities = DB::table("cities")->get();
        $originCity = DB::table("cities")->where("city_id", $request->session()->get("origin_id"))->first();
        $destinationCity = DB::table("cities")->where("city_id", $request->session()->get("destination_id"))->first();
        return view($this->activeTemplate . 'book_ticket', compact('pageTitle', 'seatLayout', 'layout', 'cities', 'originCity', 'destinationCity', 'seatHtml'));
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

        $verifiedPhone = Session::get('otp_verified_phone');
        if (!$verifiedPhone || $verifiedPhone !== $phone) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not verified with OTP'
            ], 400);
        }

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
