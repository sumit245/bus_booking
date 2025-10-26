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

class VehicleTicketController extends Controller
{
    public function booked()
    {
        $pageTitle = 'Booked Ticket';
        $emptyMessage = 'There is no booked ticket';
        $tickets = BookedTicket::booked()->with(['trip.fleetType', 'trip.startFrom', 'trip.endTo', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        return view('admin.ticket.log', compact('pageTitle', 'emptyMessage', 'tickets'));
    }

    public function pending()
    {
        $pageTitle = 'Pending Ticket';
        $emptyMessage = 'There is no pending ticket';
        $tickets = BookedTicket::pending()->with(['trip.fleetType', 'trip.startFrom', 'trip.endTo', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        return view('admin.ticket.log', compact('pageTitle', 'emptyMessage', 'tickets'));
    }

    public function rejected()
    {
        $pageTitle = 'Rejected Ticket';
        $emptyMessage = 'There is no rejected ticket';
        $tickets = BookedTicket::rejected()->with(['trip.fleetType', 'trip.startFrom', 'trip.endTo', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        return view('admin.ticket.log', compact('pageTitle', 'emptyMessage', 'tickets'));
    }

    public function list()
    {
        $pageTitle = 'All Ticket';
        $emptyMessage = 'There is no ticket found';
        $tickets = BookedTicket::with(['trip.fleetType', 'trip.startFrom', 'trip.endTo', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        return view('admin.ticket.log', compact('pageTitle', 'emptyMessage', 'tickets'));
    }

    public function search(Request $request, $scope)
    {
        $search = $request->search;
        $pageTitle = '';
        $emptyMessage = 'No search result was found.';

        $ticket = BookedTicket::where('pnr_number', $search);
        switch ($scope) {
            case 'pending':
                $pageTitle .= 'Pending Ticket Search';
                break;
            case 'booked':
                $pageTitle .= 'Booked Ticket Search';
                break;
            case 'rejected':
                $pageTitle .= 'Rejected Ticket Search';
                break;
            case 'list':
                $pageTitle .= 'Ticket Booking History Search';
                break;
        }
        $tickets = $ticket->with(['trip', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        $pageTitle .= ' - ' . $search;

        return view('admin.ticket.log', compact('pageTitle', 'search', 'scope', 'emptyMessage', 'tickets'));
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
    // {
    //     $validator = Validator::make($request->all(), [
    //         'id' => 'required|integer',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
    //     }

    //     $ticket = BookedTicket::with(['user', 'trip.fleetType', 'trip.startFrom', 'trip.endTo'])
    //         ->where('id', $request->id)
    //         ->first();

    //     if (!$ticket) {
    //         return response()->json(['success' => false, 'message' => 'Ticket not found']);
    //     }

    //     // Process seat information
    //     $seatNumbers = is_array(json_decode($ticket->seats, true))
    //         ? implode(', ', json_decode($ticket->seats, true))
    //         : $ticket->seats;

    //     // Process boarding point
    //     $boardingPoint = json_decode($ticket->boarding_point_details, true);
    //     $pickupPoint = '';
    //     if (isset($boardingPoint['CityPointAddress'])) {
    //         $pickupPoint = $boardingPoint['CityPointAddress'];
    //     } elseif (isset($boardingPoint['name'])) {
    //         $pickupPoint = $boardingPoint['name'];
    //     } elseif ($ticket->pickup_point) {
    //         $pickupPoint = $ticket->pickup_point;
    //     }

    //     // Process dropping point
    //     $droppingPoint = json_decode($ticket->dropping_point_details, true);
    //     $dropPoint = '';
    //     if (isset($droppingPoint['CityPointLocation'])) {
    //         $dropPoint = $droppingPoint['CityPointLocation'];
    //     } elseif (isset($droppingPoint['name'])) {
    //         $dropPoint = $droppingPoint['name'];
    //     } elseif ($ticket->dropping_point) {
    //         $dropPoint = $ticket->dropping_point;
    //     }

    //     // Process trip details
    //     $tripDetails = json_decode($ticket->trip_details, true);
    //     $fleetType = $ticket->trip?->fleetType?->name ?? $ticket->fleet_type ?? '';
    //     $startFrom = $ticket->trip?->startFrom?->name ?? $ticket->start_from ?? '';
    //     $endTo = $ticket->trip?->endTo?->name ?? $ticket->end_to ?? '';

    //     if (empty($fleetType) && !empty($tripDetails)) {
    //         $fleetType = $tripDetails['fleet_type'] ?? $tripDetails['FleetType'] ?? '';
    //     }

    //     if (empty($startFrom) && !empty($tripDetails)) {
    //         $startFrom = $tripDetails['start_from'] ?? $tripDetails['StartFrom'] ?? '';
    //     }

    //     if (empty($endTo) && !empty($tripDetails)) {
    //         $endTo = $tripDetails['end_to'] ?? $tripDetails['EndTo'] ?? '';
    //     }

    //     // Format dates
    //     $journeyDate = date('d M, Y', strtotime($ticket->date_of_journey));
    //     $bookingDate = date('d M, Y h:i A', strtotime($ticket->created_at));

    //     // Prepare response data
    //     $ticketData = [
    //         'id' => $ticket->id,
    //         'pnr_number' => $ticket->pnr_number,
    //         'user' => $ticket->user ? [
    //             'id' => $ticket->user->id,
    //             'fullname' => $ticket->user->fullname,
    //             'username' => $ticket->user->username,
    //         ] : null,
    //         'fleet_type' => $fleetType,
    //         'start_from' => $startFrom,
    //         'end_to' => $endTo,
    //         'pickup_point' => $pickupPoint,
    //         'dropping_point' => $dropPoint,
    //         'seat_numbers' => $seatNumbers,
    //         'sub_total' => $ticket->sub_total,
    //         'formatted_fare' => showAmount($ticket->sub_total),
    //         'date_of_journey' => $ticket->date_of_journey,
    //         'formatted_journey_date' => $journeyDate,
    //         'formatted_booking_date' => $bookingDate,
    //         'status' => $ticket->status,
    //     ];

    //     return response()->json(['success' => true, 'ticket' => $ticketData]);
    // }


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
        $ticket = BookedTicket::with(['user', 'trip.fleetType', 'trip.startFrom', 'trip.endTo', 'pickup', 'drop'])
            ->where('id', $request->id)
            ->first();

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found']);
        }

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

        // Get pickup and dropping points
        $boardingPoint = json_decode($ticket->boarding_point_details, true);
        if (isset($boardingPoint['CityPointAddress'])) {
            $ticket->pickup_point = $boardingPoint['CityPointAddress'];
        } elseif (isset($boardingPoint['name'])) {
            $ticket->pickup_point = $boardingPoint['name'];
        }

        $droppingPoint = json_decode($ticket->dropping_point_details, true);
        if (isset($droppingPoint['CityPointLocation'])) {
            $ticket->dropping_point = $droppingPoint['CityPointLocation'];
        } elseif (isset($droppingPoint['name'])) {
            $ticket->dropping_point = $droppingPoint['name'];
        }

        // Format seat numbers
        $seats = is_array($ticket->seats) ? $ticket->seats : json_decode($ticket->seats, true);
        $ticket->seat_numbers = is_array($seats) ? implode(', ', $seats) : $ticket->seats;

        return response()->json(['success' => true, 'ticket' => $ticket]);
    }


}
