<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\OperatorBus;
use App\Models\OperatorRoute;
use App\Models\BusRouteHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $pageTitle = "Manage Buses";
        $operator = Auth::guard('operator')->user();

        $buses = $operator->buses()
            ->with(['currentRoute.originCity', 'currentRoute.destinationCity', 'activeSeatLayout'])
            ->when(request('search'), function ($query) {
                $query->where(function ($q) {
                    $q->where('bus_number', 'like', '%' . request('search') . '%')
                        ->orWhere('travel_name', 'like', '%' . request('search') . '%')
                        ->orWhere('bus_type', 'like', '%' . request('search') . '%');
                });
            })
            ->when(request('status') !== null, function ($query) {
                $query->where('status', request('status'));
            })
            ->when(request('route_id'), function ($query) {
                $query->where('current_route_id', request('route_id'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $routes = $operator->routes()->active()->get();

        return view('operator.buses.index', compact('pageTitle', 'buses', 'routes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        $pageTitle = "Add New Bus";
        $operator = Auth::guard('operator')->user();
        $routes = $operator->routes()->active()->get();

        // Prefill travel name with operator's default travel name
        $defaultTravelName = $operator->default_travel_name;

        return view('operator.buses.create', compact('pageTitle', 'routes', 'defaultTravelName'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        $validated = $request->validate([
            'bus_number' => 'required|string|max:20|unique:operator_buses,bus_number',
            'bus_type' => 'required|string|max:255',
            'service_name' => 'nullable|string|max:255',
            'travel_name' => 'required|string|max:255',
            'total_seats' => 'required|integer|min:1|max:100',
            'available_seats' => 'required|integer|min:0|lte:total_seats',
            'current_route_id' => 'nullable|exists:operator_routes,id',
            'base_price' => 'required|numeric|min:0',
            'published_price' => 'required|numeric|min:0',
            'offered_price' => 'required|numeric|min:0',
            'agent_commission' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'service_charges' => 'nullable|numeric|min:0',
            'tds' => 'nullable|numeric|min:0',
            'cgst_amount' => 'nullable|numeric|min:0',
            'cgst_rate' => 'nullable|numeric|min:0|max:100',
            'igst_amount' => 'nullable|numeric|min:0',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'sgst_amount' => 'nullable|numeric|min:0',
            'sgst_rate' => 'nullable|numeric|min:0|max:100',
            'taxable_amount' => 'nullable|numeric|min:0',
            'id_proof_required' => 'nullable|boolean',
            'is_drop_point_mandatory' => 'nullable|boolean',
            'live_tracking_available' => 'nullable|boolean',
            'm_ticket_enabled' => 'nullable|boolean',
            'max_seats_per_ticket' => 'nullable|integer|min:1|max:20',
            'partial_cancellation_allowed' => 'nullable|boolean',
            'description' => 'nullable|string',
            'amenities' => 'nullable|array',
            'fuel_type' => 'nullable|string|max:50',
            'manufacturing_year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'insurance_number' => 'nullable|string|max:100',
            'insurance_expiry' => 'nullable|date|after:today',
            'permit_number' => 'nullable|string|max:100',
            'permit_expiry' => 'nullable|date|after:today',
            'fitness_certificate' => 'nullable|string|max:100',
            'fitness_expiry' => 'nullable|date|after:today',
        ]);

        // Ensure the route belongs to the operator
        if ($validated['current_route_id']) {
            $route = $operator->routes()->find($validated['current_route_id']);
            if (!$route) {
                return back()->withErrors(['current_route_id' => 'Selected route does not belong to you.']);
            }
        }

        try {
            $validated['operator_id'] = $operator->id;

            // Convert checkbox values to proper booleans
            $validated['status'] = $request->has('status') ? 1 : 0;
            $validated['id_proof_required'] = $request->has('id_proof_required') ? 1 : 0;
            $validated['is_drop_point_mandatory'] = $request->has('is_drop_point_mandatory') ? 1 : 0;
            $validated['live_tracking_available'] = $request->has('live_tracking_available') ? 1 : 0;
            $validated['m_ticket_enabled'] = $request->has('m_ticket_enabled') ? 1 : 0;
            $validated['partial_cancellation_allowed'] = $request->has('partial_cancellation_allowed') ? 1 : 0;

            $bus = OperatorBus::create($validated);

            // Create route history entry if route is assigned
            if ($bus->current_route_id) {
                BusRouteHistory::create([
                    'bus_id' => $bus->id,
                    'route_id' => $bus->current_route_id,
                    'assigned_date' => now()->toDateString(),
                    'notes' => 'Initial assignment'
                ]);
            }

            Log::info('Bus created successfully', [
                'bus_id' => $bus->id,
                'bus_number' => $bus->bus_number,
                'operator_id' => $operator->id
            ]);

            $notify[] = ['success', 'Bus added successfully!'];
            return redirect()->route('operator.buses.index')->withNotify($notify);

        } catch (\Exception $e) {
            Log::error('Error creating bus', [
                'error' => $e->getMessage(),
                'operator_id' => $operator->id,
                'bus_number' => $validated['bus_number']
            ]);

            $notify[] = ['error', 'Failed to add bus. Please try again.'];
            return back()->withNotify($notify);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OperatorBus  $bus
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function show(OperatorBus $bus)
    {
        $pageTitle = "Bus Details - " . $bus->display_name;
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator
        if ($bus->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this bus.');
        }

        $bus->load(['currentRoute.originCity', 'currentRoute.destinationCity', 'routeHistory.route', 'activeSeatLayout', 'seatLayouts']);

        return view('operator.buses.show', compact('pageTitle', 'bus'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\OperatorBus  $bus
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(OperatorBus $bus)
    {
        $pageTitle = "Edit Bus - " . $bus->display_name;
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator
        if ($bus->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this bus.');
        }

        $routes = $operator->routes()->active()->get();

        return view('operator.buses.edit', compact('pageTitle', 'bus', 'routes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\OperatorBus  $bus
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, OperatorBus $bus)
    {
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator
        if ($bus->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this bus.');
        }

        $validated = $request->validate([
            'bus_number' => 'required|string|max:20|unique:operator_buses,bus_number,' . $bus->id,
            'bus_type' => 'required|string|max:255',
            'service_name' => 'nullable|string|max:255',
            'travel_name' => 'required|string|max:255',
            'total_seats' => 'required|integer|min:1|max:100',
            'available_seats' => 'required|integer|min:0|lte:total_seats',
            'current_route_id' => 'nullable|exists:operator_routes,id',
            'base_price' => 'required|numeric|min:0',
            'published_price' => 'required|numeric|min:0',
            'offered_price' => 'required|numeric|min:0',
            'agent_commission' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'service_charges' => 'nullable|numeric|min:0',
            'tds' => 'nullable|numeric|min:0',
            'cgst_amount' => 'nullable|numeric|min:0',
            'cgst_rate' => 'nullable|numeric|min:0|max:100',
            'igst_amount' => 'nullable|numeric|min:0',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'sgst_amount' => 'nullable|numeric|min:0',
            'sgst_rate' => 'nullable|numeric|min:0|max:100',
            'taxable_amount' => 'nullable|numeric|min:0',
            'id_proof_required' => 'nullable|boolean',
            'is_drop_point_mandatory' => 'nullable|boolean',
            'live_tracking_available' => 'nullable|boolean',
            'm_ticket_enabled' => 'nullable|boolean',
            'max_seats_per_ticket' => 'nullable|integer|min:1|max:20',
            'partial_cancellation_allowed' => 'nullable|boolean',
            'description' => 'nullable|string',
            'amenities' => 'nullable|array',
            'fuel_type' => 'nullable|string|max:50',
            'manufacturing_year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'insurance_number' => 'nullable|string|max:100',
            'insurance_expiry' => 'nullable|date|after:today',
            'permit_number' => 'nullable|string|max:100',
            'permit_expiry' => 'nullable|date|after:today',
            'fitness_certificate' => 'nullable|string|max:100',
            'fitness_expiry' => 'nullable|date|after:today',
        ]);

        // Ensure the route belongs to the operator
        if ($validated['current_route_id']) {
            $route = $operator->routes()->find($validated['current_route_id']);
            if (!$route) {
                return back()->withErrors(['current_route_id' => 'Selected route does not belong to you.']);
            }
        }

        try {
            $oldRouteId = $bus->current_route_id;

            // Convert checkbox values to proper booleans
            $validated['status'] = $request->has('status') ? 1 : 0;
            $validated['id_proof_required'] = $request->has('id_proof_required') ? 1 : 0;
            $validated['is_drop_point_mandatory'] = $request->has('is_drop_point_mandatory') ? 1 : 0;
            $validated['live_tracking_available'] = $request->has('live_tracking_available') ? 1 : 0;
            $validated['m_ticket_enabled'] = $request->has('m_ticket_enabled') ? 1 : 0;
            $validated['partial_cancellation_allowed'] = $request->has('partial_cancellation_allowed') ? 1 : 0;

            $bus->update($validated);

            // Handle route change
            if ($oldRouteId != $bus->current_route_id) {
                // Close previous route assignment
                if ($oldRouteId) {
                    BusRouteHistory::where('bus_id', $bus->id)
                        ->where('route_id', $oldRouteId)
                        ->whereNull('unassigned_date')
                        ->update(['unassigned_date' => now()->toDateString()]);
                }

                // Create new route assignment
                if ($bus->current_route_id) {
                    BusRouteHistory::create([
                        'bus_id' => $bus->id,
                        'route_id' => $bus->current_route_id,
                        'assigned_date' => now()->toDateString(),
                        'notes' => 'Route changed from admin panel'
                    ]);
                }
            }

            Log::info('Bus updated successfully', [
                'bus_id' => $bus->id,
                'bus_number' => $bus->bus_number,
                'operator_id' => $operator->id
            ]);

            $notify[] = ['success', 'Bus updated successfully!'];
            return redirect()->route('operator.buses.index')->withNotify($notify);

        } catch (\Exception $e) {
            Log::error('Error updating bus', [
                'error' => $e->getMessage(),
                'bus_id' => $bus->id,
                'operator_id' => $operator->id
            ]);

            $notify[] = ['error', 'Failed to update bus. Please try again.'];
            return back()->withNotify($notify);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OperatorBus  $bus
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(OperatorBus $bus)
    {
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator
        if ($bus->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this bus.');
        }

        try {
            $busNumber = $bus->bus_number;
            $bus->delete();

            Log::info('Bus deleted successfully', [
                'bus_number' => $busNumber,
                'operator_id' => $operator->id
            ]);

            $notify[] = ['success', 'Bus deleted successfully!'];
            return redirect()->route('operator.buses.index')->withNotify($notify);

        } catch (\Exception $e) {
            Log::error('Error deleting bus', [
                'error' => $e->getMessage(),
                'bus_id' => $bus->id,
                'operator_id' => $operator->id
            ]);

            $notify[] = ['error', 'Failed to delete bus. Please try again.'];
            return back()->withNotify($notify);
        }
    }

    /**
     * Toggle bus status (activate/deactivate).
     *
     * @param  \App\Models\OperatorBus  $bus
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleStatus(OperatorBus $bus)
    {
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator
        if ($bus->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this bus.');
        }

        try {
            $bus->update(['status' => !$bus->status]);

            $status = $bus->status ? 'activated' : 'deactivated';

            Log::info('Bus status toggled', [
                'bus_id' => $bus->id,
                'bus_number' => $bus->bus_number,
                'new_status' => $bus->status,
                'operator_id' => $operator->id
            ]);

            $notify[] = ['success', "Bus {$status} successfully!"];
            return back()->withNotify($notify);

        } catch (\Exception $e) {
            Log::error('Error toggling bus status', [
                'error' => $e->getMessage(),
                'bus_id' => $bus->id,
                'operator_id' => $operator->id
            ]);

            $notify[] = ['error', 'Failed to update bus status. Please try again.'];
            return back()->withNotify($notify);
        }
    }
}