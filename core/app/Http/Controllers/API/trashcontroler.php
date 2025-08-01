
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
                            if (
                                stripos($busType, 'Non A/c') !== false ||
                                stripos($busType, 'Non Ac') !== false ||
                                stripos($busType, 'Non-A/c') !== false
                            ) {
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