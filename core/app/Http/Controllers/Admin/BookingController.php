<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    protected $busService;

    public function __construct(BusService $busService)
    {
        $this->busService = $busService;
    }

    /**
     * Display the bus search form
     */
    public function search()
    {
        $pageTitle = 'Book Ticket';
        $cities = DB::table('cities')->orderBy('city_name')->get();
        
        return view('admin.booking.search.index', compact('pageTitle', 'cities'));
    }

    /**
     * Display search results
     */
    public function results(Request $request)
    {
        $pageTitle = 'Search Results';

        // Get search parameters from request
        $searchData = $request->all();
        
        // Validate required fields
        if (empty($searchData['OriginId']) || empty($searchData['DestinationId'])) {
            return redirect()
                ->route('admin.booking.search')
                ->with('error', 'Please complete the search form.');
        }

        // Validate search data
        $validatedData = $request->validate([
            'OriginId' => 'required|integer',
            'DestinationId' => 'required|integer|different:OriginId',
            'DateOfJourney' => 'required|after_or_equal:today',
            'passengers' => 'sometimes|integer|min:1|max:10',
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

        try {
            // Use BusService to get results
            $result = $this->busService->searchBuses($validatedData);

            // Store session data required for seat selection
            session()->put('search_token_id', $result['SearchTokenId'] ?? null);
            session()->put('user_ip', $request->ip());
            session()->put('origin_id', $validatedData['OriginId']);
            session()->put('destination_id', $validatedData['DestinationId']);
            session()->put('date_of_journey', $validatedData['DateOfJourney']);
            session()->put('passengers', $validatedData['passengers'] ?? 1);

            // Debug logging
            Log::info('Admin search session stored', [
                'search_token_id' => $result['SearchTokenId'] ?? null,
                'origin_id' => $validatedData['OriginId'],
                'destination_id' => $validatedData['DestinationId'],
                'date_of_journey' => $validatedData['DateOfJourney'],
                'user_ip' => $request->ip(),
            ]);

            $fromCityData = DB::table('cities')->where('city_id', $validatedData['OriginId'])->first();
            $toCityData = DB::table('cities')->where('city_id', $validatedData['DestinationId'])->first();
            $dateOfJourney = $validatedData['DateOfJourney'];
            $passengers = $validatedData['passengers'] ?? 1;

            // Get trips from BusService results
            $availableBuses = $result['trips'] ?? [];
            $pagination = $result['pagination'] ?? null;

            return view('admin.booking.search.results', compact(
                'pageTitle',
                'fromCityData',
                'toCityData',
                'dateOfJourney',
                'passengers',
                'availableBuses',
                'pagination'
            ));
        } catch (\Exception $e) {
            Log::error('Admin booking search error: ' . $e->getMessage(), [
                'search_data' => $validatedData,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->route('admin.booking.search')
                ->with('error', 'Error searching buses: ' . $e->getMessage());
        }
    }
}

