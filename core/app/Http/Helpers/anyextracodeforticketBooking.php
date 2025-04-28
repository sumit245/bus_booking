    public function ticketSearch(Request $request)
    {
    if ($request->OriginId && $request->DestinationId && $request->OriginId == $request->DestinationId) {
    $notify[] = ['error', 'Please select pickup point and destination point properly'];
    return redirect()->back()->withNotify($notify);
    }

    if ($request->DateOfJourney && Carbon::parse($request->DateOfJourney)->format('Y-m-d') < Carbon::now()->format('Y-m-d')) {
        $notify[] = ['error', 'Date of journey can\'t be less than today.'];
        return redirect()->back()->withNotify($notify);
        }

        // Fetch buses from the API
        $resp = searchAPIBuses($request->ip(), $request->OriginId, $request->DestinationId, Carbon::parse($request->DateOfJourney)->format('Y-m-d'));
        Log::info($resp);
        // TODO: removed this code tempoorarily
        // $trips = Trip::active();

        // if ($request->pickup && $request->destination) {
        // Session::flash('pickup', $request->pickup);
        // Session::flash('destination', $request->destination);

        // $pickup = $request->pickup;
        // $destination = $request->destination;
        // $trips = $trips->with('route')->get();
        // $tripArray = array();

        // foreach ($trips as $trip) {
        // $startPoint = array_search($trip->start_from, array_values($trip->route->stoppages));
        // $endPoint = array_search($trip->end_to, array_values($trip->route->stoppages));
        // $pickup_point = array_search($pickup, array_values($trip->route->stoppages));
        // $destination_point = array_search($destination, array_values($trip->route->stoppages));
        // if ($startPoint < $endPoint) {
            // if ($pickup_point>= $startPoint && $pickup_point < $endPoint && $destination_point> $startPoint && $destination_point <= $endPoint) {
                    // array_push($tripArray, $trip->id);
                    // }
                    // } else {
                    // $revArray = array_reverse($trip->route->stoppages);
                    // $startPoint = array_search($trip->start_from, array_values($revArray));
                    // $endPoint = array_search($trip->end_to, array_values($revArray));
                    // $pickup_point = array_search($pickup, array_values($revArray));
                    // $destination_point = array_search($destination, array_values($revArray));
                    // if ($pickup_point >= $startPoint && $pickup_point < $endPoint && $destination_point> $startPoint && $destination_point <= $endPoint) {
                            // array_push($tripArray, $trip->id);
                            // }
                            // }
                            // }

                            // $trips = Trip::active()->whereIn('id', $tripArray);
                            // } else {
                            // if ($request->pickup) {
                            // Session::flash('pickup', $request->pickup);
                            // $pickup = $request->pickup;
                            // $trips = $trips->whereHas('route', function ($route) use ($pickup) {
                            // $route->whereJsonContains('stoppages', $pickup);
                            // });
                            // }

                            // if ($request->destination) {
                            // Session::flash('destination', $request->destination);
                            // $destination = $request->destination;
                            // $trips = $trips->whereHas('route', function ($route) use ($destination) {
                            // $route->whereJsonContains('stoppages', $destination);
                            // });
                            // }
                            // }

                            // if ($request->fleetType) {
                            // $trips = $trips->whereIn('fleet_type_id', $request->fleetType);
                            // }

                            // if ($request->routes) {
                            // $trips = $trips->whereIn('vehicle_route_id', $request->routes);
                            // }

                            // if ($request->schedules) {
                            // $trips = $trips->whereIn('schedule_id', $request->schedules);
                            // }

                            // if ($request->date_of_journey) {
                            // Session::flash('date_of_journey', $request->date_of_journey);
                            // $dayOff = Carbon::parse($request->date_of_journey)->format('w');
                            // $trips = $trips->whereJsonDoesntContain('day_off', $dayOff);
                            // }

                            // $trips = $trips->with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])->where('status', 1)->paginate(getPaginate());

                            session()->put('search_token_id', $resp['SearchTokenId']);
                            session()->put('user_ip', $request->ip());
                            session()->put('date_of_journey', $request->DateOfJourney);
                            session()->put('origin_id', $request->OriginId);
                            session()->put('destination_id', $request->DestinationId);
                            // Check for errors in the API response
                            if ($resp['Error']['ErrorCode'] !== 0) {
                            $notify[] = ['error', $resp['Error']['ErrorMessage']];
                            return redirect()->back()->withNotify($notify);
                            }

                            // Extract trips from the API response
                            $trips = $resp['Result'];

                            // Sort trips by DepartureTime in ascending order
                            usort($trips, function ($a, $b) {
                            // Convert departure times to timestamps for comparison
                            $timeA = strtotime($a['DepartureTime']);
                            $timeB = strtotime($b['DepartureTime']);

                            // Sort in ascending order
                            return $timeA - $timeB;
                            });

                            $pageTitle = 'Search Result';
                            $emptyMessage = 'There is no trip available';
                            $fleetType = FleetType::active()->get();
                            $schedules = Schedule::all();
                            $routes = VehicleRoute::active()->get();
                            if (auth()->user()) {
                            $layout = 'layouts.master';
                            } else {
                            $layout = 'layouts.frontend';
                            }
                            //FIXME print_r($resp);
                            return view($this->activeTemplate . 'ticket', compact('pageTitle', 'fleetType', 'trips', 'routes', 'schedules', 'emptyMessage', 'layout'));
                            }