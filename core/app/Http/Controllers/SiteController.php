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
use App\Services\BookingService;
use App\Models\User;
use Illuminate\Support\Str;


use App\Models\MarkupTable;
use Exception;

class SiteController extends Controller
{
    protected $busService;
    protected $bookingService;

    public function __construct(BusService $busService, BookingService $bookingService)
    {
        $this->activeTemplate = activeTemplate();
        $this->busService = $busService;
        $this->bookingService = $bookingService;
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

    /**
     * Display the ticket booking/search page
     * This is the initial page where users can search for buses
     */
    public function ticket()
    {
        $pageTitle = 'Book Ticket';

        // Get cities for the search form
        $cities = DB::table("cities")->orderBy("city_name")->get();

        // Determine layout based on authentication
        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }

        // Get default cities if session data exists
        $originCity = null;
        $destinationCity = null;

        if (session()->has('origin_id')) {
            $originCity = DB::table("cities")->where("city_id", session('origin_id'))->first();
        }
        if (session()->has('destination_id')) {
            $destinationCity = DB::table("cities")->where("city_id", session('destination_id'))->first();
        }

        // Provide default cities if session data is not available
        if (!$originCity) {
            $originCity = DB::table("cities")->where("city_name", "Patna")->first();
        }
        if (!$destinationCity) {
            $destinationCity = DB::table("cities")->where("city_name", "Delhi")->first();
        }

        // Initialize variables needed by the view (for seat selection, but empty for initial page)
        $parsedLayout = [];
        $seatHtml = '';
        $isOperatorBus = false;

        return view($this->activeTemplate . 'book_ticket', compact(
            'pageTitle',
            'layout',
            'cities',
            'originCity',
            'destinationCity',
            'parsedLayout',
            'seatHtml',
            'isOperatorBus'
        ));
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
                'page' => 'sometimes|integer|min:1',
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

            $viewData = $this->prepareAndReturnView($result['trips'], $result['pagination'] ?? null);
            $viewData['currentCoupon'] = BusService::getCurrentCoupon();

            // Debug log what's being passed to the view
            $operatorBusesInView = array_filter($result['trips'], function ($trip) {
                return isset($trip['IsOperatorBus']) && $trip['IsOperatorBus'];
            });

            Log::info('SiteController@ticketSearch - Passing to view', [
                'total_trips' => count($result['trips']),
                'operator_buses' => count($operatorBusesInView),
                'operator_bus_details' => array_map(function ($bus) {
                    return [
                        'TravelName' => $bus['TravelName'] ?? 'N/A',
                        'ResultIndex' => $bus['ResultIndex'] ?? 'N/A',
                        'DepartureTime' => $bus['DepartureTime'] ?? 'N/A'
                    ];
                }, array_values($operatorBusesInView)),
                'pagination' => $result['pagination'] ?? null,
                'first_5_buses' => array_slice(array_map(function ($bus) {
                    return [
                        'TravelName' => $bus['TravelName'] ?? 'N/A',
                        'IsOperator' => isset($bus['IsOperatorBus']) && $bus['IsOperatorBus'],
                        'DepartureTime' => $bus['DepartureTime'] ?? 'N/A'
                    ];
                }, $result['trips']), 0, 5)
            ]);

            return view($this->activeTemplate . 'ticket', $viewData);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $notify[] = ['error', 'Validation failed. Please check your inputs.'];
            return redirect()->back()->withNotify($notify)->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            $notify[] = ['error', $e->getMessage()];
            return redirect()->back()->withNotify($notify);
        }
    }

    private function prepareAndReturnView($trips, $pagination = null)
    {
        try {
            $viewData = [
                'pageTitle' => 'Search Result',
                'emptyMessage' => 'There is no trip available',
                'fleetType' => FleetType::active()->get(),
                'schedules' => Schedule::all(),
                'routes' => VehicleRoute::active()->get(),
                'trips' => $trips,
                'pagination' => $pagination,
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

        // Debug logging
        Log::info('SelectSeat called', [
            'result_index' => $resultIndex,
            'token' => $token,
            'user_ip' => $userIp,
            'is_agent' => auth('agent')->check(),
            'session_data' => [
                'origin_id' => session()->get('origin_id'),
                'destination_id' => session()->get('destination_id'),
                'date_of_journey' => session()->get('date_of_journey')
            ]
        ]);

        // Initialize variables
        $parsedLayout = [];
        $seatHtml = '';
        $isOperatorBus = false;

        // Check if this is an operator bus (ResultIndex starts with 'OP_')
        if (str_starts_with($resultIndex, 'OP_')) {
            // Handle operator bus seat layout
            // ResultIndex format: OP_{bus_id}_{schedule_id}
            $parts = explode('_', $resultIndex);
            if (count($parts) >= 3) {
                $operatorBusId = (int) $parts[1];
                $scheduleId = (int) $parts[2];
            } else {
                // Fallback for old format: OP_{bus_id}
                $operatorBusId = (int) str_replace('OP_', '', $resultIndex);
                $scheduleId = null;
            }
            $operatorBus = \App\Models\OperatorBus::with(['activeSeatLayout'])->find($operatorBusId);

            if (!$operatorBus || !$operatorBus->activeSeatLayout) {
                abort(404, 'Seat layout not found for this bus');
            }

            $seatLayout = $operatorBus->activeSeatLayout;

            // Get date from session and normalize to Y-m-d format
            $dateOfJourney = session()->get('date_of_journey') ?? request()->get('date') ?? date('Y-m-d');

            // Normalize date format (handle m/d/Y from session)
            if ($dateOfJourney && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfJourney)) {
                try {
                    if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateOfJourney)) {
                        $dateOfJourney = \Carbon\Carbon::createFromFormat('m/d/Y', $dateOfJourney)->format('Y-m-d');
                    } else {
                        $dateOfJourney = \Carbon\Carbon::parse($dateOfJourney)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    Log::warning('SiteController@selectSeat: Failed to parse date', [
                        'original_date' => $dateOfJourney,
                        'error' => $e->getMessage()
                    ]);
                    $dateOfJourney = date('Y-m-d');
                }
            }

            Log::info('SiteController@selectSeat: Getting booked seats', [
                'operator_bus_id' => $operatorBusId,
                'schedule_id' => $scheduleId,
                'date_of_journey' => $dateOfJourney,
                'session_date' => session()->get('date_of_journey')
            ]);

            // Use SeatAvailabilityService to get real-time booked seats
            $availabilityService = new \App\Services\SeatAvailabilityService();
            $bookedSeats = $availabilityService->getBookedSeats(
                $operatorBusId,
                $scheduleId ?? 0,
                $dateOfJourney,
                null, // boardingPointIndex - will be calculated for all segments
                null  // droppingPointIndex - will be calculated for all segments
            );

            Log::info('SiteController@selectSeat: Booked seats found', [
                'booked_seats' => $bookedSeats,
                'count' => count($bookedSeats)
            ]);

            // Modify HTML on-the-fly: change nseat→bseat, hseat→bhseat, vseat→bvseat
            $seatHtml = $this->modifyHtmlLayoutForBookedSeats($seatLayout->html_layout, $bookedSeats);
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
                // Redirect based on user type
                if (auth('agent')->check()) {
                    $redirectUrl = route('agent.search');
                } elseif (auth('admin')->check()) {
                    $redirectUrl = route('admin.booking.search');
                } else {
                    $redirectUrl = '/';
                }
                return redirect($redirectUrl)->with('error', 'Search session expired. Please search again.');
            }

            // Check if HTMLLayout exists in response
            if (!isset($response['Result']['HTMLLayout'])) {
                // Redirect based on user type
                if (auth('agent')->check()) {
                    $redirectUrl = route('agent.search');
                } elseif (auth('admin')->check()) {
                    $redirectUrl = route('admin.booking.search');
                } else {
                    $redirectUrl = '/';
                }
                return redirect($redirectUrl)->with('error', 'Search session expired. Please search again.');
            }

            $seatHtml = $response['Result']['HTMLLayout'];
            $parsedLayout = $response['Result']['SeatLayout'] ?? [];
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

        // Get cities for both agent and regular users
        $originCity = DB::table("cities")->where("city_id", $request->session()->get("origin_id"))->first();
        $destinationCity = DB::table("cities")->where("city_id", $request->session()->get("destination_id"))->first();

        // Provide default cities if session data is not available
        if (!$originCity) {
            $originCity = DB::table("cities")->where("city_name", "Patna")->first();
        }
        if (!$destinationCity) {
            $destinationCity = DB::table("cities")->where("city_name", "Delhi")->first();
        }

        // Determine which view to show based on the route accessed, not just auth status
        // Check route name to determine if this is admin/agent/operator booking or frontend booking
        $routeName = $request->route()->getName();
        $requestPath = $request->path();

        // Log ALL seat selection requests for debugging
        Log::info('=== selectSeat Method Called ===', [
            'route_name' => $routeName,
            'request_path' => $requestPath,
            'auth_guard' => auth()->guard()->getName() ?? 'guest',
            'user_id' => auth()->id(),
            'session_origin' => session()->get('origin_id'),
            'session_destination' => session()->get('destination_id')
        ]);

        // Check if accessed via admin booking route
        if (str_contains($routeName, 'admin.booking') || str_contains($request->path(), 'admin/booking')) {
            Log::info('Admin seat selection - Variables:', [
                'seatHtml' => $seatHtml ? 'Present' : 'Empty',
                'parsedLayout' => $parsedLayout ? 'Present' : 'Empty',
                'isOperatorBus' => $isOperatorBus,
                'result_index' => $resultIndex,
                'route_name' => $routeName
            ]);
            return response()
                ->view('admin.booking.seats', compact('pageTitle', 'parsedLayout', 'originCity', 'destinationCity', 'seatHtml', 'isOperatorBus'))
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        // Check if accessed via agent booking route
        if (str_contains($routeName, 'agent.booking') || str_contains($request->path(), 'agent/booking')) {
            Log::info('Agent seat selection - Variables:', [
                'seatHtml' => $seatHtml ? 'Present' : 'Empty',
                'parsedLayout' => $parsedLayout ? 'Present' : 'Empty',
                'isOperatorBus' => $isOperatorBus,
                'result_index' => $resultIndex,
                'route_name' => $routeName,
                'request_path' => $request->path()
            ]);
            return response()
                ->view('agent.booking.seats', compact('pageTitle', 'parsedLayout', 'originCity', 'destinationCity', 'seatHtml', 'isOperatorBus'))
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        // Check if accessed via operator booking route
        // Note: Operator booking might use a different flow, so we'll default to frontend view
        // If operator has their own booking view, add it here
        if (str_contains($routeName, 'operator.booking') || str_contains($request->path(), 'operator/booking')) {
            // For now, operator uses the same flow as frontend
            // If you have operator.booking.seats view, uncomment below:
            // return view('operator.booking.seats', compact('pageTitle', 'parsedLayout', 'originCity', 'destinationCity', 'seatHtml', 'isOperatorBus'));
            Log::info('Operator seat selection - Using frontend view', [
                'route_name' => $routeName,
                'path' => $request->path()
            ]);
        }

        // Frontend booking route (ticket.seats) - always show book_ticket.blade.php
        // This is the default for public users accessing /ticket/{id}/{slug}
        Log::info('Frontend seat selection - Variables:', [
            'seatHtml' => $seatHtml ? 'Present' : 'Empty',
            'parsedLayout' => $parsedLayout ? 'Present' : 'Empty',
            'isOperatorBus' => $isOperatorBus,
            'result_index' => $resultIndex,
            'route_name' => $routeName
        ]);

        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }

        $cities = DB::table("cities")->get();
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
            // ResultIndex format: OP_{bus_id}_{schedule_id}
            $parts = explode('_', $ResultIndex);
            if (count($parts) >= 3) {
                $operatorBusId = (int) $parts[1];
                $scheduleId = (int) $parts[2];
            } else {
                // Fallback for old format: OP_{bus_id}
                $operatorBusId = (int) str_replace('OP_', '', $ResultIndex);
                $scheduleId = null;
            }
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

    /**
     * Modify HTML layout to mark booked seats
     */
    private function modifyHtmlLayoutForBookedSeats(string $htmlLayout, array $bookedSeats): string
    {
        if (empty($bookedSeats)) {
            return $htmlLayout;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $htmlLayout, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($dom);

        foreach ($bookedSeats as $seatName) {
            // CRITICAL FIX: Match by @id attribute, not text content
            // This prevents "1" from matching "U1", "11", "21", etc.
            // Seat IDs are stored in the id attribute: <div id="U1" class="nseat"> or <div id="1" class="nseat">
            $nodes = $xpath->query("//*[@id='{$seatName}' and (contains(@class, 'nseat') or contains(@class, 'hseat') or contains(@class, 'vseat'))]");

            foreach ($nodes as $node) {
                $class = $node->getAttribute('class');
                // Replace nseat with bseat, hseat with bhseat, vseat with bvseat
                $class = str_replace(['nseat', 'hseat', 'vseat'], ['bseat', 'bhseat', 'bvseat'], $class);
                $node->setAttribute('class', $class);
            }
        }

        return $dom->saveHTML();
    }

    // 4. Apply api for seat block and create payment order
    public function blockSeat(Request $request)
    {
        Log::info('Block Seat Request:', ['request' => $request->all()]);

        // Determine booking type based on route, not just auth status
        // Frontend booking (ticket.seats route) always uses single passenger format
        // Agent/Admin booking pages use multiple passenger format
        $routeName = $request->route()->getName();
        $isAgentOrAdminBooking = str_contains($routeName, 'agent.booking')
            || str_contains($routeName, 'admin.booking')
            || str_contains($request->path(), 'agent/booking')
            || str_contains($request->path(), 'admin/booking');

        // Different validation for agent/admin booking pages vs regular frontend booking
        try {
            if ($isAgentOrAdminBooking) {
                // Agent/Admin booking page - expects multiple passengers (arrays)
                $request->validate([
                    'boarding_point_index' => 'required',
                    'dropping_point_index' => 'required',
                    'seats' => 'required',
                    'passenger_phone' => 'required',
                    'passenger_email' => 'nullable|email',
                    'passenger_names' => 'required|array|min:1',
                    'passenger_names.*' => 'required|string|max:255',
                    'passenger_ages' => 'required|array|min:1',
                    'passenger_ages.*' => 'required|integer|min:1|max:120',
                    'passenger_genders' => 'required|array|min:1',
                    'passenger_genders.*' => 'required|in:1,2,3',
                ]);
            } else {
                // Frontend booking (ticket.seats route) - expects single passenger format
                $request->validate([
                    'boarding_point_index' => 'required',
                    'dropping_point_index' => 'required',
                    'gender' => 'required',
                    'seats' => 'required',
                    'passenger_phone' => 'required',
                    'passenger_firstname' => 'required',
                    'passenger_lastname' => 'required',
                    'passenger_email' => 'nullable|email',
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Block Seat Validation Failed:', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'is_agent_or_admin_booking' => $isAgentOrAdminBooking,
                'route_name' => $routeName,
                'path' => $request->path()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', array_map(function ($errors) {
                    return implode(', ', $errors);
                }, $e->errors())),
                'errors' => $e->errors()
            ], 422);
        }

        // Prepare request data for BookingService
        if ($isAgentOrAdminBooking) {
            // Agent/Admin booking - handle multiple passengers
            $passengerNames = $request->passenger_names;
            $passengerAges = $request->passenger_ages;
            $passengerGenders = $request->passenger_genders;

            // Split names into first and last names with proper handling
            $passengerFirstNames = [];
            $passengerLastNames = [];

            foreach ($passengerNames as $index => $fullName) {
                $fullName = trim($fullName);
                $gender = $passengerGenders[$index] ?? 1; // Default to 1 (Male) if not set

                // Determine title based on gender
                $title = 'Mr';
                if ($gender == 2) {
                    $title = 'Mrs';
                } elseif ($gender == 3) {
                    $title = 'Ms';
                }

                // Split name by spaces
                $nameParts = explode(' ', $fullName, 2);

                if (count($nameParts) == 1) {
                    // Only one name provided - use title as firstname, provided name as lastname
                    $passengerFirstNames[] = $title;
                    $passengerLastNames[] = $nameParts[0];
                } else {
                    // Two or more parts - first part as firstname, rest as lastname
                    $passengerFirstNames[] = $nameParts[0];
                    $passengerLastNames[] = $nameParts[1];
                }
            }

            $requestData = [
                'boarding_point_index' => $request->boarding_point_index,
                'dropping_point_index' => $request->dropping_point_index,
                'seats' => $request->seats,
                'passenger_phone' => $request->passenger_phone,
                'passenger_email' => $request->passenger_email != null ? $request->passenger_email : 'guest@vindhyashrisolutions.com',
                'passenger_firstnames' => $passengerFirstNames,
                'passenger_lastnames' => $passengerLastNames,
                'passenger_ages' => $passengerAges,
                'passenger_genders' => $passengerGenders,
                'passenger_address' => $request->passenger_address ?? '',
                'result_index' => session()->get('result_index'),
                'search_token_id' => session()->get('search_token_id'),
                'origin_city' => session()->get('origin_id'),
                'destination_city' => session()->get('destination_id'),
                'user_ip' => $request->ip()
            ];
        } else {
            // Regular booking - single passenger
            $requestData = [
                'boarding_point_index' => $request->boarding_point_index,
                'dropping_point_index' => $request->dropping_point_index,
                'gender' => $request->gender,
                'seats' => $request->seats,
                'passenger_phone' => $request->passenger_phone,
                'passenger_firstname' => $request->passenger_firstname,
                'passenger_lastname' => $request->passenger_lastname,
                'passenger_email' => $request->passenger_email != null ? $request->passenger_email : 'guest@vindhyashrisolutions.com',
                'passenger_address' => $request->passenger_address ?? '',
                'passenger_age' => $request->passenger_age ?? 0,
                'result_index' => session()->get('result_index'),
                'search_token_id' => session()->get('search_token_id'),
                'origin_city' => session()->get('origin_id'),
                'destination_city' => session()->get('destination_id'),
                'user_ip' => $request->ip()
            ];
        }

        // Add agent-specific data if accessed by agent (only for agent booking pages, not frontend)
        if ($isAgentOrAdminBooking && auth('agent')->check()) {
            $requestData['agent_id'] = auth('agent')->id();
            $requestData['booking_source'] = 'agent';

            // Calculate commission (5% of ticket price - this should come from agent settings)
            $commissionRate = 0.05; // 5% commission rate
            $requestData['commission_rate'] = $commissionRate;

            Log::info('Agent booking initiated', [
                'agent_id' => $requestData['agent_id'],
                'commission_rate' => $commissionRate
            ]);
        }

        // Add admin-specific data if accessed by admin (only for admin booking pages, not frontend)
        if ($isAgentOrAdminBooking && auth('admin')->check()) {
            $requestData['admin_id'] = auth('admin')->id();
            $requestData['booking_source'] = 'admin';

            Log::info('Admin booking initiated', [
                'admin_id' => $requestData['admin_id']
            ]);
        }

        // Use BookingService to block seats and create payment order
        $result = $this->bookingService->blockSeatsAndCreateOrder($requestData);

        if ($result['success']) {
            // For agent/admin bookings, return a payment view instead of JSON
            if ($isAgentOrAdminBooking) {
                $ticket = \App\Models\BookedTicket::find($result['ticket_id']);

                if (!$ticket) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ticket not found after blocking seats.'
                    ], 404);
                }

                // Get Razorpay key from env
                $razorpayKey = env('RAZORPAY_KEY');

                // Determine which view to return based on auth guard
                if (auth('agent')->check()) {
                    return view('agent.booking.payment', [
                        'ticket' => $ticket,
                        'order_id' => $result['order_id'],
                        'amount' => $result['amount'],
                        'currency' => $result['currency'],
                        'razorpay_key' => $razorpayKey,
                        'cancellation_policy' => $result['cancellation_policy'] ?? []
                    ]);
                } elseif (auth('admin')->check()) {
                    // For admin, you might want a different view or same view
                    return view('admin.booking.payment', [
                        'ticket' => $ticket,
                        'order_id' => $result['order_id'],
                        'amount' => $result['amount'],
                        'currency' => $result['currency'],
                        'razorpay_key' => $razorpayKey,
                        'cancellation_policy' => $result['cancellation_policy'] ?? []
                    ]);
                }
            }

            // For frontend bookings, return JSON as before
            return response()->json([
                'success' => true,
                'message' => 'Seats blocked successfully! Proceed to payment.',
                'order_id' => $result['order_id'],
                'amount' => $result['amount'],
                'currency' => $result['currency'],
                'ticket_id' => $result['ticket_id'],
                'cancellation_policy' => $result['cancellation_policy']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to block seats. Please try again.'
        ], 400);
    }

    /**
     * Verify payment and complete booking
     */
    public function bookTicketApi(Request $request)
    {
        try {
            Log::info('Verifying payment and completing booking', $request->all());

            $request->validate([
                'razorpay_payment_id' => 'required|string',
                'razorpay_order_id' => 'required|string',
                'razorpay_signature' => 'required|string',
                'ticket_id' => 'required|integer|exists:booked_tickets,id',
            ]);

            // Use BookingService to verify payment and complete booking
            $result = $this->bookingService->verifyPaymentAndCompleteBooking([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_signature' => $request->razorpay_signature,
                'ticket_id' => $request->ticket_id
            ]);

            if ($result['success']) {
                // Determine redirect based on user authentication
                if (auth()->check()) {
                    $redirectUrl = route('user.dashboard');
                } else {
                    // For guest users, redirect to public print page using ticket ID
                    $redirectUrl = route('public.ticket.print', $result['ticket_id']);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful! Ticket booked successfully.',
                    'ticket_id' => $result['ticket_id'],
                    'pnr' => $result['pnr'],
                    'redirect' => $redirectUrl
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'cancelled' => $result['cancelled'] ?? false
            ], $result['cancelled'] ?? false ? 500 : 400);

        } catch (\Exception $e) {
            Log::error('Failed to verify payment and complete booking: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete booking: ' . $e->getMessage()
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

