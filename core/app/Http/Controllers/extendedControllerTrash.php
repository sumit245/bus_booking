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
        $reverse=false;
        } else {
        $reverse=true;
        }

        if (!$reverse) {
        $can_go=($sourcePos < $destinationPos) ? true : false;
        } else {
        $can_go=($sourcePos> $destinationPos) ? true : false;
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
            $notify[]=['error', 'Date of journey cant\' t be less than today.'];
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
                $reverse=false;
                } else {
                $reverse=true;
                }

                if (!$reverse) {
                $can_go=($source_pos < $destination_pos) ? true : false;
                } else {
                $can_go=($source_pos> $destination_pos) ? true : false;
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