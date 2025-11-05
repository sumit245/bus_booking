<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\FleetType;
use App\Models\VehicleRoute;
use App\Models\TicketPrice;
use App\Models\TicketPriceByStoppage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class VehicleTicketController extends Controller
{
    /**
     * Unified ticket listing with filter support
     * Supports: all, booked, pending, rejected, cancelled
     * URL: /admin/ticket?filter=booked&search=ABC123
     */
    public function index(Request $request)
    {
        // Get filter from query parameter (default: 'all')
        $filter = $request->get('filter', 'all');

        // Build base query with relationships
        // Conditionally include pickup relationship to avoid errors if column doesn't exist
        $relationships = ['trip.fleetType', 'trip.startFrom', 'trip.endTo', 'drop', 'user'];

        // Check if pickup_point column exists before eager loading pickup relationship
        $tableName = (new BookedTicket)->getTable();
        if (Schema::hasColumn($tableName, 'pickup_point')) {
            $relationships[] = 'pickup';
        }

        // Set default values
        $pageTitle = 'All Tickets';
        $emptyMessage = 'No tickets found';

        // Build query with filter - use scope methods directly on the model
        switch ($filter) {
            case 'booked':
                $query = BookedTicket::booked();
                $pageTitle = 'Booked Tickets';
                $emptyMessage = 'There are no booked tickets';
                break;
            case 'pending':
                $query = BookedTicket::pending();
                $pageTitle = 'Pending Tickets';
                $emptyMessage = 'There are no pending tickets';
                break;
            case 'rejected':
                $query = BookedTicket::rejected();
                $pageTitle = 'Rejected Tickets';
                $emptyMessage = 'There are no rejected tickets';
                break;
            case 'cancelled':
                $query = BookedTicket::where('status', 3);
                $pageTitle = 'Cancelled Tickets';
                $emptyMessage = 'There are no cancelled tickets';
                break;
            case 'all':
            default:
                // No filter applied - show all tickets
                $query = BookedTicket::query();
                break;
        }

        // Now apply eager loading AFTER the filter
        $query->with($relationships);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('pnr_number', 'like', '%' . $search . '%');
            $pageTitle .= ' - Search: ' . $search;
        }

        // Date range filters (optional)
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->where('date_of_journey', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->where('date_of_journey', '<=', $request->date_to);
        }

        // Debug: Log the query and filter
        Log::info('Ticket filter query', [
            'filter' => $filter,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'count_before_paginate' => $query->count()
        ]);

        // Order and paginate
        $tickets = $query->orderBy('id', 'desc')->paginate(getPaginate());

        // Append query parameters to pagination links
        $tickets->appends($request->query());

        return view('admin.ticket.log', compact('pageTitle', 'emptyMessage', 'tickets', 'filter'));
    }

    /**
     * Backward compatibility: Redirect old routes to new filter-based approach
     */
    public function booked()
    {
        return redirect()->route('admin.vehicle.ticket.index', ['filter' => 'booked']);
    }

    public function pending()
    {
        return redirect()->route('admin.vehicle.ticket.index', ['filter' => 'pending']);
    }

    public function rejected()
    {
        return redirect()->route('admin.vehicle.ticket.index', ['filter' => 'rejected']);
    }

    public function list()
    {
        return redirect()->route('admin.vehicle.ticket.index', ['filter' => 'all']);
    }

    /**
     * Search functionality - now integrated into index method
     * Keeping for backward compatibility
     */
    public function search(Request $request, $scope)
    {
        return redirect()->route('admin.vehicle.ticket.index', [
            'filter' => $scope === 'list' ? 'all' : $scope,
            'search' => $request->search ?? ''
        ]);
    }

    public function ticketPriceList()
    {
        $pageTitle = "All Ticket Price";
        $emptyMessage = "No ticket price found";
        $fleetTypes = FleetType::active()->get();
        $routes = VehicleRoute::active()->get();
        $prices = TicketPrice::with(['fleetType', 'route'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.trip.ticket.price_list', compact('pageTitle', 'emptyMessage', 'prices', 'fleetTypes', 'routes'));
    }

    public function ticketPriceCreate()
    {
        $pageTitle = "Add Ticket Price";
        $fleetTypes = FleetType::active()->get();
        $routes = VehicleRoute::active()->get();
        return view('admin.trip.ticket.add_price', compact('pageTitle', 'fleetTypes', 'routes'));
    }

    public function ticketPriceEdit($id)
    {
        $pageTitle = "Update Ticket Price";
        $ticketPrice = TicketPrice::with(['prices', 'route.startFrom', 'route.endTo'])->findOrfail($id);
        $stoppageArr = $ticketPrice->route->stoppages;
        $stoppages = stoppageCombination($stoppageArr, 2);
        return view('admin.trip.ticket.edit_price', compact('pageTitle', 'ticketPrice', 'stoppages'));
    }

    public function cancelTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $notify[] = ['error', $validator->errors()->first()];
            return back()->withNotify($notify);
        }

        $ticket = BookedTicket::findOrFail($request->ticket_id);

        // Update ticket status to rejected (3)
        $ticket->status = 3;
        $ticket->save();

        $notify[] = ['success', 'Ticket has been cancelled successfully'];
        return back()->withNotify($notify);
    }

    public function refundTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|integer',
            'amount' => 'required|numeric',
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            $notify[] = ['error', $validator->errors()->first()];
            return back()->withNotify($notify);
        }

        $ticket = BookedTicket::findOrFail($request->ticket_id);

        // Update ticket status to rejected (3)
        $ticket->status = 3;
        $ticket->refund_amount = $request->amount;
        $ticket->refund_reason = $request->reason;
        $ticket->refunded_at = now();
        $ticket->save();

        // TODO: Process refund to user's wallet or payment method if needed

        $notify[] = ['success', 'Ticket has been refunded successfully'];
        return back()->withNotify($notify);
    }

    public function getRouteData(Request $request)
    {
        $route = VehicleRoute::where('id', $request->vehicle_route_id)->where('status', 1)->first();
        $check = TicketPrice::where('vehicle_route_id', $request->vehicle_route_id)->where('fleet_type_id', $request->fleet_type_id)->first();
        if ($check) {
            return response()->json(['error' => trans('You have added prices for this fleet type on this route')]);
        }
        $stoppages = array_values($route->stoppages);
        $stoppages = stoppageCombination($stoppages, 2);
        return view('admin.trip.ticket.route_data', compact('stoppages', 'route'));
    }

    public function ticketPriceStore(Request $request)
    {
        $validation_rule = [
            'fleet_type' => 'required|integer|gt:0',
            'route' => 'required|integer|gt:0',
            'main_price' => 'required|numeric',
            'price' => 'sometimes|required|array|min:1',
            'price.*' => 'sometimes|required|numeric',
        ];
        $messages = [
            'main_price' => 'Price for Source to Destination',
            'price.*.required' => 'All Price Fields are Required',
            'price.*.numeric' => 'All Price Fields Should Be a Number',
        ];

        $validator = Validator::make($request->except('_token'), $validation_rule, $messages);
        $validator->validate();

        $check = TicketPrice::where('fleet_type_id', $request->fleet_type)->where('vehicle_route_id', $request->route)->first();
        if ($check) {
            $notify[] = ['error', 'Duplicate fleet type and route can\'t be allowed'];
            return back()->withNotify($notify);
        }

        $create = new TicketPrice();
        $create->fleet_type_id = $request->fleet_type;
        $create->vehicle_route_id = $request->route;
        $create->price = $request->main_price;
        $create->save();

        foreach ($request->price as $key => $val) {
            $idArray = explode('-', $key);
            $priceByStoppage = new TicketPriceByStoppage();
            $priceByStoppage->ticket_price_id = $create->id;
            $priceByStoppage->source_destination = $idArray;
            $priceByStoppage->price = $val;
            $priceByStoppage->save();
        }
        $notify[] = ['success', 'Ticket price added successfully'];
        return back()->withNotify($notify);
    }

    public function ticketPriceUpdate(Request $request, $id)
    {

        $request->validate([
            'price' => 'required|numeric',
        ]);

        if ($id == 0) {
            $source_destination[0] = $request->source;
            $source_destination[1] = $request->destination;
            $ticketPrice = TicketPriceByStoppage::whereJsonContains('source_destination', $source_destination)->first();
            if ($ticketPrice) {
                $ticketPrice->price = $request->price;
                $ticketPrice->save();
            } else {
                $ticketPrice = new TicketPriceByStoppage();
                $ticketPrice->ticket_price_id = $request->ticket_price;
                $ticketPrice->source_destination = $source_destination;
                $ticketPrice->price = $request->price;
                $ticketPrice->save();
            }
        } else {
            $prices = TicketPriceByStoppage::findOrFail($id);
            $prices->price = $request->price;
            $prices->save();
        }

        $notify = ['success' => true, 'message' => 'Price Updated Successfully'];
        return response()->json($notify);
    }

    public function ticketPriceDelete(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $data = TicketPrice::where('id', $request->id)->first();
        $data->prices()->delete();
        $data->delete();

        $notify[] = ['success', 'Price Deleted Successfully'];
        return redirect()->back()->withNotify($notify);
    }

    public function checkTicketPrice(Request $request)
    {
        $check = TicketPrice::where('vehicle_route_id', $request->vehicle_route_id)->where('fleet_type_id', $request->fleet_type_id)->first();

        if (!$check) {
            return response()->json(['error' => 'Ticket price not added for this fleet-route combination yet. Please add ticket price before creating a trip.']);
        }
    }

    public function ticketDetails(Request $request)
    {
        // Log to file directly to ensure it works even if Log facade fails
        file_put_contents(
            storage_path('logs/ticket_details_debug.log'),
            date('Y-m-d H:i:s') . " - TicketDetails METHOD CALLED\n" .
            "URL: " . $request->fullUrl() . "\n" .
            "Method: " . $request->method() . "\n" .
            "ID: " . ($request->id ?? 'NULL') . "\n" .
            "All params: " . json_encode($request->all()) . "\n" .
            "---\n",
            FILE_APPEND
        );

        // Log immediately when method is called
        Log::info('=== TicketDetails METHOD CALLED ===', [
            'timestamp' => now()->toDateTimeString(),
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'request_id' => $request->id,
            'request_pnr' => $request->pnr,
            'all_params' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        try {
            Log::info('TicketDetails processing started', [
                'id' => $request->id,
                'pnr' => $request->pnr,
                'all_params' => $request->all()
            ]);

            // Validate request
            if (!$request->has('id') || empty($request->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket ID is required'
                ], 400);
            }

            // Build relationships array - conditionally include pickup
            $relationships = ['user', 'trip.fleetType', 'trip.startFrom', 'trip.endTo', 'drop'];

            // Check if pickup_point column exists before eager loading pickup relationship
            $tableName = (new BookedTicket)->getTable();
            if (Schema::hasColumn($tableName, 'pickup_point')) {
                $relationships[] = 'pickup';
            }

            Log::info('Loading ticket with relationships', ['relationships' => $relationships]);

            $ticket = BookedTicket::with($relationships)
                ->where('id', $request->id)
                ->first();

            if (!$ticket) {
                Log::warning('Ticket not found', ['id' => $request->id]);
                return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
            }

            Log::info('Ticket found', ['ticket_id' => $ticket->id, 'pnr' => $ticket->pnr_number]);

            // Format data for display
            $ticket->formatted_journey_date = showDateTime($ticket->date_of_journey, 'd M, Y');
            $ticket->formatted_booking_date = showDateTime($ticket->created_at, 'd M, Y h:i A');
            $ticket->formatted_fare = showAmount($ticket->sub_total);

            // Get fleet type, start and end locations
            $ticket->fleet_type = $ticket->trip?->fleetType?->name ?? $ticket->fleet_type ?? '';
            $ticket->start_from = $ticket->trip?->startFrom?->name ?? $ticket->start_from ?? '';
            $ticket->end_to = $ticket->trip?->endTo?->name ?? $ticket->end_to ?? '';

            // Fallback to JSON data if available
            if (empty($ticket->fleet_type) && !empty($ticket->trip_details)) {
                $tripDetails = json_decode($ticket->trip_details, true);
                $ticket->fleet_type = $tripDetails['fleet_type'] ?? $tripDetails['FleetType'] ?? '';
            }

            if (empty($ticket->start_from) && !empty($ticket->trip_details)) {
                $tripDetails = json_decode($ticket->trip_details, true);
                $ticket->start_from = $tripDetails['start_from'] ?? $tripDetails['StartFrom'] ?? '';
            }

            if (empty($ticket->end_to) && !empty($ticket->trip_details)) {
                $tripDetails = json_decode($ticket->trip_details, true);
                $ticket->end_to = $tripDetails['end_to'] ?? $tripDetails['EndTo'] ?? '';
            }

            // Get pickup and dropping points from JSON or relationship
            $ticket->pickup_point = 'N/A';
            $ticket->dropping_point = 'N/A';

            // Try to get pickup point from JSON
            if ($ticket->boarding_point_details) {
                $boardingPoint = json_decode($ticket->boarding_point_details, true);
                if (isset($boardingPoint['CityPointAddress'])) {
                    $ticket->pickup_point = $boardingPoint['CityPointAddress'];
                } elseif (isset($boardingPoint['CityPointName'])) {
                    $ticket->pickup_point = $boardingPoint['CityPointName'];
                } elseif (isset($boardingPoint['name'])) {
                    $ticket->pickup_point = $boardingPoint['name'];
                }
            }

            // Fallback to relationship if available and JSON doesn't have it
            if ($ticket->pickup_point === 'N/A' && $ticket->pickup) {
                $ticket->pickup_point = $ticket->pickup->name ?? 'N/A';
            }

            // Fallback to origin_city if still N/A
            if ($ticket->pickup_point === 'N/A' && $ticket->origin_city) {
                $ticket->pickup_point = $ticket->origin_city;
            }

            // Try to get dropping point from JSON
            if ($ticket->dropping_point_details) {
                $droppingPoint = json_decode($ticket->dropping_point_details, true);
                if (isset($droppingPoint['CityPointLocation'])) {
                    $ticket->dropping_point = $droppingPoint['CityPointLocation'];
                } elseif (isset($droppingPoint['CityPointName'])) {
                    $ticket->dropping_point = $droppingPoint['CityPointName'];
                } elseif (isset($droppingPoint['name'])) {
                    $ticket->dropping_point = $droppingPoint['name'];
                }
            }

            // Fallback to relationship if available and JSON doesn't have it
            if ($ticket->dropping_point === 'N/A' && $ticket->drop) {
                $ticket->dropping_point = $ticket->drop->name ?? 'N/A';
            }

            // Fallback to destination_city if still N/A
            if ($ticket->dropping_point === 'N/A' && $ticket->destination_city) {
                $ticket->dropping_point = $ticket->destination_city;
            }

            // Format seat numbers
            $seats = is_array($ticket->seats) ? $ticket->seats : json_decode($ticket->seats, true);
            $ticket->seat_numbers = is_array($seats) ? implode(', ', $seats) : $ticket->seats;

            Log::info('Ticket details formatted successfully', ['ticket_id' => $ticket->id]);

            // Prepare response data - manually build array to avoid JSON encoding issues
            $responseData = [
                'success' => true,
                'ticket' => [
                    'id' => $ticket->id,
                    'pnr_number' => $ticket->pnr_number,
                    'formatted_journey_date' => $ticket->formatted_journey_date,
                    'formatted_booking_date' => $ticket->formatted_booking_date,
                    'formatted_fare' => $ticket->formatted_fare,
                    'fleet_type' => $ticket->fleet_type,
                    'start_from' => $ticket->start_from,
                    'end_to' => $ticket->end_to,
                    'pickup_point' => $ticket->pickup_point,
                    'dropping_point' => $ticket->dropping_point,
                    'seat_numbers' => $ticket->seat_numbers,
                    'status' => $ticket->status,
                    'sub_total' => $ticket->sub_total,
                    'user' => $ticket->user ? [
                        'id' => $ticket->user->id,
                        'fullname' => $ticket->user->fullname ?? ($ticket->user->firstname . ' ' . $ticket->user->lastname),
                        'username' => $ticket->user->username,
                    ] : null,
                ]
            ];

            Log::info('Sending ticket details response', ['ticket_id' => $ticket->id]);

            return response()->json($responseData);

        } catch (\Exception $e) {
            Log::error('Error fetching ticket details: ' . $e->getMessage(), [
                'ticket_id' => $request->id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error loading ticket details: ' . $e->getMessage()
            ], 500);
        }
    }

}
