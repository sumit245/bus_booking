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
                // Check if LiveTrackingAvailable is true in the API response
                return isset($trip['LiveTrackingAvailable']) && $trip['LiveTrackingAvailable'] === true;
            });
        }

        // Apply departure time filter
        if ($request->has('departure_time') && !empty($request->departure_time)) {
            $filteredTrips = array_filter($filteredTrips, function ($trip) use ($request) {
                $departureTime = \Carbon\Carbon::parse($trip['DepartureTime']);
                $hour = (int)$departureTime->format('H');
                Log::info($trip["ResultIndex"]);
                Log::info($hour);

                $inTimeRange = false;
                foreach ($request->departure_time as $timeRange) {
                    switch ($timeRange) {
                        case 'morning':
                            if ($hour >= 4 && $hour < 12) $inTimeRange = true;
                            break;
                        case 'afternoon':
                            if ($hour >= 12 && $hour < 16) $inTimeRange = true;
                            break;
                        case 'evening':
                            if ($hour >= 16 && $hour < 20) $inTimeRange = true;
                            break;
                        case 'night':
                            if ($hour >= 20 || $hour < 4) $inTimeRange = true;
                            break;
                    }
                }
                return $inTimeRange;
            });
        }

        // Apply amenities filter
        if ($request->has('amenities') && !empty($request->amenities)) {
            $filteredTrips = array_filter($filteredTrips, function ($trip) use ($request) {
                $hasAmenities = false;

                foreach ($request->amenities as $amenity) {
                    switch ($amenity) {
                        case 'wifi':
                            // Check if service name or description contains WiFi
                            if (
                                stripos($trip['ServiceName'], 'wifi') !== false ||
                                (isset($trip['Description']) && stripos($trip['Description'], 'wifi') !== false)
                            ) {
                                $hasAmenities = true;
                            }
                            break;
                        case 'charging':
                            if (
                                stripos($trip['ServiceName'], 'charging') !== false ||
                                (isset($trip['Description']) && stripos($trip['Description'], 'charging') !== false)
                            ) {
                                $hasAmenities = true;
                            }
                            break;
                        case 'water':
                            if (
                                stripos($trip['ServiceName'], 'water') !== false ||
                                (isset($trip['Description']) && stripos($trip['Description'], 'water') !== false)
                            ) {
                                $hasAmenities = true;
                            }
                            break;
                        case 'blanket':
                            if (
                                stripos($trip['ServiceName'], 'blanket') !== false ||
                                (isset($trip['Description']) && stripos($trip['Description'], 'blanket') !== false)
                            ) {
                                $hasAmenities = true;
                            }
                            break;
                    }
                }

                return $hasAmenities;
            });
        }

        // Apply price range filter
        if (($request->has('min_price') && $request->min_price !== null) ||
            ($request->has('max_price') && $request->max_price !== null)
        ) {

            $minPrice = $request->min_price ?? 0;
            $maxPrice = $request->max_price ?? PHP_INT_MAX;

            $filteredTrips = array_filter($filteredTrips, function ($trip) use ($minPrice, $maxPrice) {
                $price = $trip['BusPrice']['PublishedPrice'];
                return $price >= $minPrice && $price <= $maxPrice;
            });
        }

        // Apply fleet type filter
        if ($request->has('fleetType') && !empty($request->fleetType)) {
            $filteredTrips = array_filter($filteredTrips, function ($trip) use ($request) {
                $busType = $trip['BusType'];

                foreach ($request->fleetType as $fleetType) {
                    // Check if the bus type contains any of the selected fleet types
                    switch ($fleetType) {
                        case 'Seater':
                            if (stripos($busType, 'Seater') !== false) {
                                return true;
                            }
                            break;
                        case 'Sleeper':
                            if (stripos($busType, 'Sleeper') !== false) {
                                return true;
                            }
                            break;
                        case 'A/C':
                            if (stripos($busType, 'A/C') !== false || stripos($busType, 'AC') !== false) {
                                return true;
                            }
                            break;
                        case 'Non A/C':
                            if (stripos($busType, 'Non A/C') !== false || stripos($busType, 'Non-AC') !== false) {
                                return true;
                            }
                            break;
                    }
                }

                return false;
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
            $request->ip(),
            $request->OriginId,
            $request->DestinationId,
            Carbon::parse($request->DateOfJourney)->format('Y-m-d')
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

    // private function prepareAndReturnView($resp, $request)
    // {
    //     $trips = $this->sortTripsByDepartureTime($resp['Result']);

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
                'block_response' => $response,
                'result_index' => session()->get('result_index'), // Add result_index from session
                'passengers' => $passengers, // Store passenger data
                'journey_date' => session()->get('date_of_journey') // Store journey date
            ]);
            // Return JSON instead of redirecting
            return response()->json([
                'response' => $response['Result'],
                'success' => true,
                'message' => 'Seats blocked successfully! Proceed to payment.',
            ]);
        }
    
        // If there's an error
        return back()->with('error', $response['Error']['ErrorMessage'] ?? 'Failed to block seats. Please try again.');
    }
    
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
    
            $ticket = new \App\Models\BookedTicket();
            $ticket->pnr_number = $request->booking_id;
            $ticket->user_id = $userId;
            $ticket->date_of_journey = $bookingInfo['journey_date'] ?? date('Y-m-d');
            $ticket->seats = $bookingInfo['seats'];
            $ticket->pickup_point = $bookingInfo['boarding_point_index'];
            $ticket->dropping_point = $bookingInfo['dropping_point_index'];
            $ticket->unit_price = $bookingInfo['price'];
            $ticket->sub_total = $bookingInfo['price'];
            $ticket->ticket_count = count(explode(',', $bookingInfo['seats']));
            $ticket->gender = $bookingInfo['passengers'][0]['Gender'] ?? 1;
            $ticket->status = 1; // Confirmed
            $ticket->api_response = json_encode($apiResponse); // Save full API response
            $ticket->save();
    
            session()->forget('booking_info');
    
            return response()->json([
                'success' => true,
                'message' => 'Ticket booked successfully',
                'booking_id' => $request->booking_id,
                'pnr' => $apiResponse['PNRNo'] ?? null
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

}    