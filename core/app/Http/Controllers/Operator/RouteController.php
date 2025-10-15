<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\OperatorRoute;
use App\Models\BoardingPoint;
use App\Models\DroppingPoint;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RouteController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('operator');
    }

    /**
     * Display a listing of routes.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $pageTitle = "Manage Routes";
        $operator = Auth::guard('operator')->user();

        $routes = OperatorRoute::with(['originCity', 'destinationCity', 'boardingPoints', 'droppingPoints'])
            ->forOperator($operator->id)
            ->latest()
            ->paginate(getPaginate());

        return view('operator.routes.index', compact('pageTitle', 'routes', 'operator'));
    }

    /**
     * Show the form for creating a new route.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        $pageTitle = "Add New Route";
        $cities = City::orderBy('city_name')->get();

        return view('operator.routes.create', compact('pageTitle', 'cities'));
    }

    /**
     * Store a newly created route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'route_name' => 'required|string|max:255',
            'origin_city_id' => 'required|exists:cities,id',
            'destination_city_id' => 'required|exists:cities,id|different:origin_city_id',
            'description' => 'nullable|string',
            'distance' => 'nullable|numeric|min:0',
            'estimated_duration' => 'nullable|numeric|min:0.5|max:24',
            'base_fare' => 'nullable|numeric|min:0',
            'boarding_points' => 'required|array|min:1',
            'boarding_points.*.point_name' => 'required|string|max:255',
            'boarding_points.*.point_address' => 'required|string',
            'boarding_points.*.point_location' => 'required|string|max:255',
            'boarding_points.*.point_landmark' => 'nullable|string|max:255',
            'boarding_points.*.contact_number' => 'nullable|string|max:20',
            'boarding_points.*.point_time' => 'required|date_format:H:i',
            'dropping_points' => 'required|array|min:1',
            'dropping_points.*.point_name' => 'required|string|max:255',
            'dropping_points.*.point_address' => 'nullable|string',
            'dropping_points.*.point_location' => 'required|string|max:255',
            'dropping_points.*.point_landmark' => 'nullable|string|max:255',
            'dropping_points.*.contact_number' => 'nullable|string|max:20',
            'dropping_points.*.point_time' => 'required|date_format:H:i',
        ]);

        $operator = Auth::guard('operator')->user();

        // Create the route
        $route = OperatorRoute::create([
            'operator_id' => $operator->id,
            'route_name' => $request->route_name,
            'origin_city_id' => $request->origin_city_id,
            'destination_city_id' => $request->destination_city_id,
            'description' => $request->description,
            'distance' => $request->distance,
            'estimated_duration' => $request->estimated_duration,
            'base_fare' => $request->base_fare,
            'status' => 1
        ]);

        // Create boarding points
        foreach ($request->boarding_points as $index => $boardingPoint) {
            BoardingPoint::create([
                'operator_route_id' => $route->id,
                'point_name' => $boardingPoint['point_name'],
                'point_address' => $boardingPoint['point_address'],
                'point_location' => $boardingPoint['point_location'],
                'point_landmark' => $boardingPoint['point_landmark'] ?? null,
                'contact_number' => $boardingPoint['contact_number'] ?? null,
                'point_index' => $index + 1,
                'point_time' => $boardingPoint['point_time'],
                'status' => 1
            ]);
        }

        // Create dropping points
        foreach ($request->dropping_points as $index => $droppingPoint) {
            DroppingPoint::create([
                'operator_route_id' => $route->id,
                'point_name' => $droppingPoint['point_name'],
                'point_address' => $droppingPoint['point_address'] ?? null,
                'point_location' => $droppingPoint['point_location'],
                'point_landmark' => $droppingPoint['point_landmark'] ?? null,
                'contact_number' => $droppingPoint['contact_number'] ?? null,
                'point_index' => $index + 1,
                'point_time' => $droppingPoint['point_time'],
                'status' => 1
            ]);
        }

        $notify[] = ['success', 'Route created successfully!'];
        return redirect()->route('operator.routes.index')->withNotify($notify);
    }

    /**
     * Display the specified route.
     *
     * @param  \App\Models\OperatorRoute  $route
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function show(OperatorRoute $route)
    {
        $this->authorizeRoute($route);

        $pageTitle = "Route Details - " . $route->display_name;
        $route->load(['originCity', 'destinationCity', 'boardingPoints', 'droppingPoints']);

        return view('operator.routes.show', compact('pageTitle', 'route'));
    }

    /**
     * Show the form for editing the specified route.
     *
     * @param  \App\Models\OperatorRoute  $route
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(OperatorRoute $route)
    {
        $this->authorizeRoute($route);

        $pageTitle = "Edit Route - " . $route->display_name;
        $cities = City::orderBy('city_name')->get();
        $route->load(['boardingPoints', 'droppingPoints']);

        return view('operator.routes.edit', compact('pageTitle', 'route', 'cities'));
    }

    /**
     * Update the specified route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\OperatorRoute  $route
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, OperatorRoute $route)
    {
        $this->authorizeRoute($route);

        $request->validate([
            'route_name' => 'required|string|max:255',
            'origin_city_id' => 'required|exists:cities,id',
            'destination_city_id' => 'required|exists:cities,id|different:origin_city_id',
            'description' => 'nullable|string',
            'distance' => 'nullable|numeric|min:0',
            'estimated_duration' => 'nullable|numeric|min:0.5|max:24',
            'base_fare' => 'nullable|numeric|min:0',
            'status' => 'boolean',
            'boarding_points' => 'required|array|min:1',
            'boarding_points.*.point_name' => 'required|string|max:255',
            'boarding_points.*.point_address' => 'required|string',
            'boarding_points.*.point_location' => 'required|string|max:255',
            'boarding_points.*.point_landmark' => 'nullable|string|max:255',
            'boarding_points.*.contact_number' => 'nullable|string|max:20',
            'boarding_points.*.point_time' => 'required|date_format:H:i',
            'dropping_points' => 'required|array|min:1',
            'dropping_points.*.point_name' => 'required|string|max:255',
            'dropping_points.*.point_address' => 'nullable|string',
            'dropping_points.*.point_location' => 'required|string|max:255',
            'dropping_points.*.point_landmark' => 'nullable|string|max:255',
            'dropping_points.*.contact_number' => 'nullable|string|max:20',
            'dropping_points.*.point_time' => 'required|date_format:H:i',
        ]);

        // Update the route
        $route->update([
            'route_name' => $request->route_name,
            'origin_city_id' => $request->origin_city_id,
            'destination_city_id' => $request->destination_city_id,
            'description' => $request->description,
            'distance' => $request->distance,
            'estimated_duration' => $request->estimated_duration,
            'base_fare' => $request->base_fare,
            'status' => $request->has('status') ? 1 : 0
        ]);

        // Delete existing boarding and dropping points
        $route->boardingPoints()->delete();
        $route->droppingPoints()->delete();

        // Create new boarding points
        foreach ($request->boarding_points as $index => $boardingPoint) {
            BoardingPoint::create([
                'operator_route_id' => $route->id,
                'point_name' => $boardingPoint['point_name'],
                'point_address' => $boardingPoint['point_address'],
                'point_location' => $boardingPoint['point_location'],
                'point_landmark' => $boardingPoint['point_landmark'] ?? null,
                'contact_number' => $boardingPoint['contact_number'] ?? null,
                'point_index' => $index + 1,
                'point_time' => $boardingPoint['point_time'],
                'status' => 1
            ]);
        }

        // Create new dropping points
        foreach ($request->dropping_points as $index => $droppingPoint) {
            DroppingPoint::create([
                'operator_route_id' => $route->id,
                'point_name' => $droppingPoint['point_name'],
                'point_address' => $droppingPoint['point_address'] ?? null,
                'point_location' => $droppingPoint['point_location'],
                'point_landmark' => $droppingPoint['point_landmark'] ?? null,
                'contact_number' => $droppingPoint['contact_number'] ?? null,
                'point_index' => $index + 1,
                'point_time' => $droppingPoint['point_time'],
                'status' => 1
            ]);
        }

        $notify[] = ['success', 'Route updated successfully!'];
        return redirect()->route('operator.routes.index')->withNotify($notify);
    }

    /**
     * Remove the specified route.
     *
     * @param  \App\Models\OperatorRoute  $route
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(OperatorRoute $route)
    {
        $this->authorizeRoute($route);

        $route->delete();

        $notify[] = ['success', 'Route deleted successfully!'];
        return redirect()->route('operator.routes.index')->withNotify($notify);
    }

    /**
     * Toggle route status.
     *
     * @param  \App\Models\OperatorRoute  $route
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleStatus(OperatorRoute $route)
    {
        $this->authorizeRoute($route);

        $route->update(['status' => !$route->status]);

        $status = $route->status ? 'activated' : 'deactivated';
        $notify[] = ['success', "Route {$status} successfully!"];
        return back()->withNotify($notify);
    }

    /**
     * Authorize that the route belongs to the current operator.
     *
     * @param  \App\Models\OperatorRoute  $route
     * @return void
     */
    private function authorizeRoute(OperatorRoute $route)
    {
        $operator = Auth::guard('operator')->user();
        if ($route->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this route.');
        }
    }
}
