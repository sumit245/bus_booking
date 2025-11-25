<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\MarkupTable;
use App\Models\CouponTable;
use App\Models\OperatorRoute;
use App\Models\OperatorBus;
use App\Models\BusSchedule;
use App\Models\OperatorBooking;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BusService
{
    const API_CACHE_DURATION_MINUTES = 10;

    /**
     * Main entry point for searching buses.
     */
    public function searchBuses(array $validatedData): array
    {
        $apiResponse = $this->fetchTripsFromApi(
            $validatedData['OriginId'],
            $validatedData['DestinationId'],
            $validatedData['DateOfJourney']
        );

        // Start with third-party API results
        $trips = $apiResponse['Result'] ?? [];

        // Add operator buses for this route
        $operatorBuses = $this->fetchOperatorBuses(
            $validatedData['OriginId'],
            $validatedData['DestinationId'],
            $validatedData['DateOfJourney']
        );

        // Merge operator buses with third-party results
        $trips = array_merge($trips, $operatorBuses);

        Log::info("BusService::searchBuses - After merging", [
            'third_party_count' => count($apiResponse['Result'] ?? []),
            'operator_count' => count($operatorBuses),
            'total_count' => count($trips),
            'operator_buses' => array_map(function ($bus) {
                return [
                    'ResultIndex' => $bus['ResultIndex'] ?? 'N/A',
                    'TravelName' => $bus['TravelName'] ?? 'N/A'
                ];
            }, $operatorBuses)
        ]);

        // If no trips found, check if we have operator buses or third-party API error
        if (empty($trips)) {
            if (!empty($operatorBuses)) {
                // We have operator buses, so use them
                $trips = $operatorBuses;
            } else {
                // No buses at all
                throw new \Exception('No buses found for this route and date', 404);
            }
        }

        $trips = $this->applyMarkup($trips);
        $trips = $this->applyCoupon($trips);
        $trips = $this->applyFilters($trips, $validatedData);
        $trips = $this->applySorting($trips, $validatedData); // Sorting now works on a proper array

        // Get page number from validated data or request
        $page = (int) ($validatedData['page'] ?? request()->input('page', 1));
        $perPage = 50; // Increased from 20 to 50 for better UX
        $totalTrips = count($trips);
        $offset = ($page - 1) * $perPage;
        $paginatedTrips = array_slice($trips, $offset, $perPage);

        // Debug logging
        Log::info('BusService pagination', [
            'requested_page' => $page,
            'total_trips' => $totalTrips,
            'per_page' => $perPage,
            'offset' => $offset,
            'paginated_count' => count($paginatedTrips),
            'has_more' => ($page * $perPage) < $totalTrips,
            'operator_buses_in_page' => array_values(array_filter($paginatedTrips, function ($trip) {
                return isset($trip['IsOperatorBus']) && $trip['IsOperatorBus'];
            })),
            'first_5_result_indexes' => array_slice(array_column($paginatedTrips, 'ResultIndex'), 0, 5)
        ]);

        return [
            'SearchTokenId' => $apiResponse['SearchTokenId'],
            'trips' => $paginatedTrips, // This is now guaranteed to be a sequential array
            'pagination' => [
                'total_results' => $totalTrips,
                'per_page' => $perPage,
                'current_page' => $page,
                'has_more_pages' => ($page * $perPage) < $totalTrips,
            ]
        ];
    }

    /**
     * Fetches trips from the third-party API, with caching.
     */
    private function fetchTripsFromApi(int $originId, int $destinationId, string $dateOfJourney): array
    {
        $cacheKey = "bus_search:{$originId}_{$destinationId}_{$dateOfJourney}";
        return Cache::remember($cacheKey, now()->addMinutes(self::API_CACHE_DURATION_MINUTES), function () use ($originId, $destinationId, $dateOfJourney) {
            // Log::info("CACHE MISS: Fetching fresh data from API for {$originId}-{$destinationId} on {$dateOfJourney}");
            $resp = searchAPIBuses($originId, $destinationId, $dateOfJourney, request()->ip());

            // Handle case where API returns an error
            if (isset($resp['Error']['ErrorCode']) && $resp['Error']['ErrorCode'] !== 0) {
                // Log::warning("Third-party API returned error", [
                //     'error_code' => $resp['Error']['ErrorCode'],
                //     'error_message' => $resp['Error']['ErrorMessage'] ?? 'Unknown error'
                // ]);
                return ['Result' => [], 'SearchTokenId' => null, 'Error' => $resp['Error']];
            }

            // Handle case where response is not an array (shouldn't happen with our fix, but just in case)
            if (!is_array($resp)) {
                // Log::error("Third-party API returned non-array response", [
                //     'response_type' => gettype($resp),
                //     'response_value' => $resp
                // ]);
                return ['Result' => [], 'SearchTokenId' => null, 'Error' => ['ErrorCode' => -1, 'ErrorMessage' => 'Invalid API response']];
            }

            return $resp;
        });
    }

    /**
     * Fetches operator buses for a specific route, with caching.
     */
    private function fetchOperatorBuses(int $originId, int $destinationId, string $dateOfJourney): array
    {
        $cacheKey = "operator_bus_search_v3:{$originId}_{$destinationId}_{$dateOfJourney}";
        // Temporarily bypass cache for testing
        // TODO: Re-enable caching after debugging
        // return Cache::remember($cacheKey, now()->addMinutes(self::API_CACHE_DURATION_MINUTES), function () use ($originId, $destinationId, $dateOfJourney) {
        Log::info("Fetching operator schedules for {$originId}-{$destinationId} on {$dateOfJourney}");

        try {
            // Find schedules that match the origin, destination, and date
            Log::info("Querying operator schedules for origin: {$originId}, destination: {$destinationId}, date: {$dateOfJourney}");

            $schedules = BusSchedule::active()
                ->whereHas('operatorRoute.originCity', function ($query) use ($originId) {
                    $query->where('city_id', $originId);
                })
                ->whereHas('operatorRoute.destinationCity', function ($query) use ($destinationId) {
                    $query->where('city_id', $destinationId);
                })
                ->forDate($dateOfJourney)
                ->with([
                    'operatorRoute.originCity',
                    'operatorRoute.destinationCity',
                    'operatorBus.activeSeatLayout'
                ])
                ->ordered()
                ->get();

            Log::info("Found " . $schedules->count() . " operator schedules");

            if ($schedules->isEmpty()) {
                Log::info("No operator schedules found for {$originId}-{$destinationId} on {$dateOfJourney}");
                return [];
            }

            Log::info("Processing " . $schedules->count() . " operator schedules", [
                'schedule_ids' => $schedules->pluck('id')->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error("Error querying operator schedules", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'origin_id' => $originId,
                'destination_id' => $destinationId,
                'date' => $dateOfJourney
            ]);
            return [];
        }

        $operatorBuses = [];
        $resultIndex = 1;

        try {
            foreach ($schedules as $schedule) {
                Log::info("Processing schedule ID: {$schedule->id}");

                try {
                    Log::info("Transforming schedule ID: {$schedule->id} with result index: {$resultIndex}");
                    $operatorBuses[] = $this->transformScheduleToApiFormat($schedule, $dateOfJourney, $resultIndex++);
                } catch (\Exception $e) {
                    Log::error("Error transforming schedule {$schedule->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'schedule_id' => $schedule->id
                    ]);
                    // Continue with other schedules
                }
            }
        } catch (\Exception $e) {
            Log::error("Error processing operator schedules", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'origin_id' => $originId,
                'destination_id' => $destinationId,
                'date' => $dateOfJourney
            ]);
            return [];
        }

        Log::info("Found " . count($operatorBuses) . " operator schedules for route {$originId}-{$destinationId} on {$dateOfJourney}");
        return $operatorBuses;
        // });
    }

    /**
     * Transforms schedule data to match third-party API format.
     */
    private function transformScheduleToApiFormat(BusSchedule $schedule, string $dateOfJourney, int $resultIndex): array
    {
        $bus = $schedule->operatorBus;
        $route = $schedule->operatorRoute;

        // Use schedule's departure and arrival times
        $departureTime = Carbon::parse($dateOfJourney . ' ' . $schedule->departure_time->format('H:i:s'))->format('Y-m-d\TH:i:s');
        $arrivalTime = Carbon::parse($dateOfJourney . ' ' . $schedule->arrival_time->format('H:i:s'));

        // Handle next day arrival
        if ($arrivalTime->lt(Carbon::parse($departureTime))) {
            $arrivalTime->addDay();
        }
        $arrivalTime = $arrivalTime->format('Y-m-d\TH:i:s');

        // Calculate duration
        $duration = $schedule->estimated_duration_minutes ?
            floor($schedule->estimated_duration_minutes / 60) . 'h ' . ($schedule->estimated_duration_minutes % 60) . 'm' :
            '24h';

        // Generate unique result index for this schedule
        $resultIndexStr = "OP_{$bus->id}_{$schedule->id}";

        return [
            'ResultIndex' => $resultIndexStr,
            'BusType' => $bus->bus_type,
            'TravelName' => $bus->travel_name,
            'ServiceName' => 'Seat Seller',
            'DepartureTime' => $departureTime,
            'ArrivalTime' => $arrivalTime,
            'Duration' => $duration,
            'Origin' => $route->originCity->city_name,
            'Destination' => $route->destinationCity->city_name,
            'TotalSeats' => $bus->total_seats,
            'AvailableSeats' => $this->calculateAvailableSeats($bus, $dateOfJourney),
            'LiveTrackingAvailable' => $bus->live_tracking_available ?? true,
            'MTicketEnabled' => $bus->m_ticket_enabled ?? true,
            'PartialCancellationAllowed' => $bus->partial_cancellation_allowed ?? true,
            'Description' => $bus->bus_type,
            'BusPrice' => [
                'BasePrice' => (float) ($bus->base_price ?? $bus->published_price ?? 0),
                'Tax' => (float) ($bus->tax ?? 0),
                'OtherCharges' => (float) ($bus->other_charges ?? 0),
                'Discount' => (float) ($bus->discount ?? 0),
                'PublishedPrice' => (float) ($bus->published_price ?? $bus->base_price ?? 0),
                'OfferedPrice' => (float) ($bus->offered_price ?? $bus->base_price ?? 0),
                'AgentCommission' => (float) ($bus->agent_commission ?? 0),
                'ServiceCharges' => (float) ($bus->service_charges ?? 0),
                'TDS' => (float) ($bus->tds ?? 0),
                'GST' => [
                    'CGSTAmount' => (float) ($bus->cgst_amount ?? 0),
                    'CGSTRate' => (float) ($bus->cgst_rate ?? 0),
                    'IGSTAmount' => (float) ($bus->igst_amount ?? 0),
                    'IGSTRate' => (float) ($bus->igst_rate ?? 0),
                    'SGSTAmount' => (float) ($bus->sgst_amount ?? 0),
                    'SGSTRate' => (float) ($bus->sgst_rate ?? 0),
                    'TaxableAmount' => (float) ($bus->taxable_amount ?? 0),
                ]
            ],
            'BoardingPointsDetails' => $route->boardingPoints->map(function ($point) use ($dateOfJourney) {
                $journeyDate = Carbon::parse($dateOfJourney)->format('Y-m-d');
                $departureTime = $point->point_time ?: '00:00:00';
                if (strpos($departureTime, ' ') !== false) {
                    $departureTime = Carbon::parse($departureTime)->format('H:i:s');
                }
                return [
                    'CityPointIndex' => $point->id,
                    'CityPointLocation' => $point->point_address ?: $point->point_location ?: $point->point_name,
                    'CityPointName' => $point->point_name,
                    'CityPointTime' => Carbon::parse($journeyDate . ' ' . $departureTime)->format('Y-m-d\TH:i:s'),
                    'CityPointLandmark' => $point->point_landmark,
                    'CityPointContactNumber' => $point->contact_number,
                ];
            })->toArray(),
            'DroppingPointsDetails' => $route->droppingPoints->map(function ($point) use ($dateOfJourney, $route) {
                $journeyDate = Carbon::parse($dateOfJourney)->format('Y-m-d');
                $pointArrivalTime = $point->point_time;
                if (!$pointArrivalTime) {
                    $arrivalTime = Carbon::parse($dateOfJourney)->setTime(0, 0, 0);
                    if ($route->estimated_duration) {
                        $arrivalTime->addHours((int) $route->estimated_duration);
                    } else {
                        $arrivalTime->addHours(8);
                    }
                    $pointArrivalTime = $arrivalTime->format('H:i:s');
                } else {
                    if (strpos($pointArrivalTime, ' ') !== false) {
                        $pointArrivalTime = Carbon::parse($pointArrivalTime)->format('H:i:s');
                    }
                }
                return [
                    'CityPointIndex' => $point->id,
                    'CityPointLocation' => $point->point_address ?: $point->point_location ?: $point->point_name,
                    'CityPointName' => $point->point_name,
                    'CityPointTime' => Carbon::parse($journeyDate . ' ' . $pointArrivalTime)->format('Y-m-d\TH:i:s'),
                    'CityPointLandmark' => $point->point_landmark,
                    'CityPointContactNumber' => $point->contact_number,
                ];
            })->toArray(),
            'CancellationPolicies' => $this->getOperatorCancellationPoliciesWithDates($bus, $dateOfJourney),
            // Removed SeatLayout from search results - not needed, use show-seats API instead
            'OperatorBusId' => $bus->id,
            'OperatorRouteId' => $route->id,
            'IsOperatorBus' => true,
            'ScheduleId' => $schedule->id,
            'ScheduleName' => $schedule->schedule_name
        ];
    }

    /**
     * Transforms operator bus data to match third-party API format (legacy method).
     */
    private function transformOperatorBusToApiFormat(OperatorBus $bus, OperatorRoute $route, string $dateOfJourney, int $resultIndex): array
    {
        // Set departure time to 00:00 (midnight) as requested
        $departureTime = Carbon::parse($dateOfJourney)->format('Y-m-d') . 'T00:00:00';

        // Calculate arrival time based on estimated duration
        $arrivalTime = Carbon::parse($departureTime);
        if ($route->estimated_duration) {
            $arrivalTime->addHours((int) $route->estimated_duration);
        } else {
            $arrivalTime->addHours(8); // Default 8 hours if no duration specified
        }

        // Get seat layout information
        $seatLayout = $bus->activeSeatLayout;
        $totalSeats = $seatLayout ? $seatLayout->total_seats : $bus->total_seats;
        $availableSeats = $bus->available_seats ?? $totalSeats;

        // Generate unique RouteId for operator buses (OP_ prefix + route ID)
        $routeId = 'OP_' . $route->id . '_' . $bus->id;

        return [
            'ResultIndex' => 'OP_' . $resultIndex,
            'ArrivalTime' => $arrivalTime->format('Y-m-d\TH:i:s'),
            'AvailableSeats' => $availableSeats,
            'DepartureTime' => $departureTime,
            'RouteId' => $routeId,
            'BusType' => $bus->bus_type ?? 'AC Seater',
            'ServiceName' => $bus->service_name ?? 'Seat Seller',
            'TravelName' => $bus->travel_name ?? $bus->operator->company_name ?? 'Operator Bus',
            'IdProofRequired' => false,
            'IsDropPointMandatory' => $bus->is_drop_point_mandatory ?? false,
            'LiveTrackingAvailable' => $bus->live_tracking_available ?? false,
            'MTicketEnabled' => $bus->m_ticket_enabled ?? true,
            'MaxSeatsPerTicket' => 6,
            'OperatorId' => $bus->operator_id ?? 0,
            'PartialCancellationAllowed' => $bus->partial_cancellation_allowed ?? true,
            'BoardingPointsDetails' => $route->boardingPoints->map(function ($point) use ($dateOfJourney) {
                // Parse the date of journey to get the correct date
                $journeyDate = Carbon::parse($dateOfJourney)->format('Y-m-d');

                // Use point_time from database, or default to 00:00:00
                $departureTime = $point->point_time ?: '00:00:00';

                // If point_time is already a full datetime, extract just the time part
                if (strpos($departureTime, ' ') !== false) {
                    $departureTime = Carbon::parse($departureTime)->format('H:i:s');
                }

                return [
                    'CityPointIndex' => $point->id,
                    'CityPointLocation' => $point->point_address ?: $point->point_location ?: $point->point_name,
                    'CityPointName' => $point->point_name,
                    'CityPointTime' => Carbon::parse($journeyDate . ' ' . $departureTime)->format('Y-m-d\TH:i:s'),
                    'CityPointLandmark' => $point->point_landmark,
                    'CityPointContactNumber' => $point->contact_number,
                ];
            })->toArray(),
            'DroppingPointsDetails' => $route->droppingPoints->map(function ($point) use ($dateOfJourney, $route) {
                // Parse the date of journey to get the correct date
                $journeyDate = Carbon::parse($dateOfJourney)->format('Y-m-d');

                // Use point_time from database, or calculate based on route duration
                $pointArrivalTime = $point->point_time;
                if (!$pointArrivalTime) {
                    // Calculate arrival time based on route duration
                    $arrivalTime = Carbon::parse($dateOfJourney)->setTime(0, 0, 0);
                    if ($route->estimated_duration) {
                        $arrivalTime->addHours((int) $route->estimated_duration);
                    } else {
                        $arrivalTime->addHours(8); // Default 8 hours
                    }
                    $pointArrivalTime = $arrivalTime->format('H:i:s');
                } else {
                    // If point_time is already a full datetime, extract just the time part
                    if (strpos($pointArrivalTime, ' ') !== false) {
                        $pointArrivalTime = Carbon::parse($pointArrivalTime)->format('H:i:s');
                    }
                }

                return [
                    'CityPointIndex' => $point->id,
                    'CityPointLocation' => $point->point_address ?: $point->point_location ?: $point->point_name,
                    'CityPointName' => $point->point_name,
                    'CityPointTime' => Carbon::parse($journeyDate . ' ' . $pointArrivalTime)->format('Y-m-d\TH:i:s'),
                    'CityPointLandmark' => $point->point_landmark,
                    'CityPointContactNumber' => $point->contact_number,
                ];
            })->toArray(),
            'BusPrice' => [
                'BasePrice' => (float) ($bus->base_price ?? $bus->published_price ?? 0),
                'Tax' => (float) ($bus->tax ?? 0),
                'OtherCharges' => (float) ($bus->other_charges ?? 0),
                'Discount' => (float) ($bus->discount ?? 0),
                'PublishedPrice' => (float) ($bus->published_price ?? $bus->base_price ?? 0),
                'OfferedPrice' => (float) ($bus->offered_price ?? $bus->base_price ?? 0),
                'AgentCommission' => (float) ($bus->agent_commission ?? 0),
                'ServiceCharges' => (float) ($bus->service_charges ?? 0),
                'TDS' => (float) ($bus->tds ?? 0),
                'GST' => [
                    'CGSTAmount' => (float) ($bus->cgst_amount ?? 0),
                    'CGSTRate' => (float) ($bus->cgst_rate ?? 0),
                    'IGSTAmount' => (float) ($bus->igst_amount ?? 0),
                    'IGSTRate' => (float) ($bus->igst_rate ?? 18),
                    'SGSTAmount' => (float) ($bus->sgst_amount ?? 0),
                    'SGSTRate' => (float) ($bus->sgst_rate ?? 0),
                    'TaxableAmount' => (float) ($bus->taxable_amount ?? 0),
                ],
            ],
            'CancellationPolicies' => [
                [
                    'CancellationCharge' => 10,
                    'CancellationChargeType' => 2,
                    'PolicyString' => 'Till 2 hours before departure',
                    'TimeBeforeDept' => '2$-1',
                    'FromDate' => Carbon::now()->format('Y-m-d\TH:i:s'),
                    'ToDate' => Carbon::parse($departureTime)->subHours(2)->format('Y-m-d\TH:i:s'),
                ],
                [
                    'CancellationCharge' => 50,
                    'CancellationChargeType' => 2,
                    'PolicyString' => 'Between 2 hours before departure - departure time',
                    'TimeBeforeDept' => '0$2',
                    'FromDate' => Carbon::parse($departureTime)->subHours(2)->format('Y-m-d\TH:i:s'),
                    'ToDate' => $departureTime,
                ],
            ],
        ];
    }

    /**
     * Applies markup pricing using cached rules.
     */
    private function applyMarkup(array $trips): array
    {
        // ... This method remains the same ...
        $markup = Cache::rememberForever('active_markup_rules', fn() => MarkupTable::orderBy('id', 'desc')->first());
        if (!$markup)
            return $trips;

        foreach ($trips as &$trip) {
            if (isset($trip['BusPrice']['PublishedPrice']) && is_numeric($trip['BusPrice']['PublishedPrice'])) {
                $price = (float) $trip['BusPrice']['PublishedPrice'];
                $newPrice = ($price <= (float) $markup->threshold) ? ($price + (float) $markup->flat_markup) : ($price + ($price * (float) $markup->percentage_markup / 100));
                $trip['BusPrice']['PublishedPrice'] = round($newPrice, 2);
            }
        }
        return $trips;
    }

    /**
     * Applies coupon discount using cached rules.
     */
    private function applyCoupon(array $trips): array
    {
        // ... This method remains the same ...
        $coupon = Cache::remember('active_coupon', now()->addHour(), fn() => CouponTable::where('status', 1)->where('expiry_date', '>=', Carbon::today())->first());
        if (!$coupon)
            return $trips;

        foreach ($trips as &$trip) {
            if (isset($trip['BusPrice']['PublishedPrice']) && is_numeric($trip['BusPrice']['PublishedPrice'])) {
                $priceAfterMarkup = (float) $trip['BusPrice']['PublishedPrice'];
                $trip['BusPrice']['PriceBeforeCoupon'] = $priceAfterMarkup;
                $discountAmount = 0;
                if ($priceAfterMarkup > (float) $coupon->coupon_threshold) {
                    $discountAmount = ($coupon->discount_type === 'fixed') ? (float) $coupon->coupon_value : ($priceAfterMarkup * (float) $coupon->coupon_value / 100);
                }
                $finalPrice = max(0, $priceAfterMarkup - $discountAmount);
                $trip['BusPrice']['PublishedPrice'] = round($finalPrice, 2);
            }
        }
        return $trips;
    }

    /**
     * Applies sorting to the list of trips.
     */
    private function applySorting(array $trips, array $filters): array
    {
        $sortBy = $filters['sortBy'] ?? 'departure'; // Default sort

        // Determine sort order from the sortBy value for web requests
        $sortOrder = 'asc';
        if ($sortBy === 'price-high') {
            $sortBy = 'price';
            $sortOrder = 'desc';
        } elseif ($sortBy === 'price-low') {
            $sortBy = 'price';
        }

        // THE FIX: Refined sorting logic using the spaceship operator for clarity and reliability.
        usort($trips, function ($a, $b) use ($sortBy, $sortOrder) {
            if ($sortBy === 'price') {
                $valueA = $a['BusPrice']['PublishedPrice'] ?? 0;
                $valueB = $b['BusPrice']['PublishedPrice'] ?? 0;
            } elseif ($sortBy === 'duration') {
                $valueA = isset($a['ArrivalTime'], $a['DepartureTime']) ? Carbon::parse($a['ArrivalTime'])->diffInMinutes(Carbon::parse($a['DepartureTime'])) : 0;
                $valueB = isset($b['ArrivalTime'], $b['DepartureTime']) ? Carbon::parse($b['ArrivalTime'])->diffInMinutes(Carbon::parse($b['DepartureTime'])) : 0;
            } else { // Default to departure time
                $valueA = strtotime($a['DepartureTime'] ?? 0);
                $valueB = strtotime($b['DepartureTime'] ?? 0);
            }

            if ($sortOrder === 'asc') {
                return $valueA <=> $valueB; // <=> returns -1, 0, or 1
            } else {
                return $valueB <=> $valueA; // Reverse the comparison for descending
            }
        });

        return $trips;
    }

    private function applyFilters(array $trips, array $filters): array
    {
        Log::info('Applying filters', [
            'total_trips_before_filter' => count($trips),
            'filters' => $filters
        ]);

        $filteredTrips = array_filter($trips, function ($trip) use ($filters) {
            // IMPORTANT: Filter out buses with passed departure times ONLY for TODAY's searches
            if (isset($trip['DepartureTime']) && isset($filters['DateOfJourney'])) {
                $departureDateTime = Carbon::parse($trip['DepartureTime']);
                $searchDate = Carbon::parse($filters['DateOfJourney'])->startOfDay();
                $today = Carbon::today();
                $now = Carbon::now();

                // ONLY filter out past departure times if the search is for TODAY
                // For future dates, show all buses regardless of departure time
                if ($searchDate->equalTo($today)) {
                    // For TODAY: Filter out if departure time has already passed
                    if ($departureDateTime->lessThan($now)) {
                        Log::info('Bus filtered out - departure time passed TODAY', [
                            'bus' => $trip['TravelName'] ?? 'Unknown',
                            'departure_time' => $departureDateTime->toDateTimeString(),
                            'current_time' => $now->toDateTimeString(),
                            'result_index' => $trip['ResultIndex'] ?? 'N/A'
                        ]);
                        return false;
                    }
                }
                // For future dates, do NOT filter by time - show all buses
            }


            // Live tracking filter
            if (!empty($filters['live_tracking']) && $filters['live_tracking']) {
                if (!($trip['LiveTrackingAvailable'] ?? false))
                    return false;
            }

            // Departure time filter
            if (!empty($filters['departure_time'])) {
                $departureHour = (int) Carbon::parse($trip['DepartureTime'])->format('H');
                $timeMatch = false;
                foreach ($filters['departure_time'] as $timeRange) {
                    if (
                        ($timeRange === 'morning' && $departureHour >= 6 && $departureHour < 12) ||
                        ($timeRange === 'afternoon' && $departureHour >= 12 && $departureHour < 18) ||
                        ($timeRange === 'evening' && $departureHour >= 18 && $departureHour < 24) ||
                        ($timeRange === 'night' && $departureHour >= 0 && $departureHour < 6)
                    ) {
                        $timeMatch = true;
                        break;
                    }
                }
                if (!$timeMatch)
                    return false;
            }

            // Amenities filter
            if (!empty($filters['amenities'])) {
                foreach ($filters['amenities'] as $amenity) {
                    $found = false;
                    $serviceName = $trip['ServiceName'] ?? '';
                    $description = $trip['Description'] ?? '';
                    if (stripos($serviceName, $amenity) !== false || stripos($description, $amenity) !== false) {
                        $found = true;
                    }
                    if (!$found)
                        return false;
                }
            }

            // Price range filter
            if (isset($filters['min_price']) || isset($filters['max_price'])) {
                $price = $trip['BusPrice']['PublishedPrice'] ?? null;
                if ($price === null)
                    return false;
                $minPrice = $filters['min_price'] ?? 0;
                $maxPrice = $filters['max_price'] ?? PHP_INT_MAX;
                if ($price < $minPrice || $price > $maxPrice)
                    return false;
            }

            if (!empty($filters['fleetType'])) {
                $busType = $trip['BusType'] ?? '';
                $fleetTypes = $filters['fleetType'];

                $acSelected = in_array('A/c', $fleetTypes);
                $nonAcSelected = in_array('Non-A/c', $fleetTypes);
                $seaterSelected = in_array('Seater', $fleetTypes);
                $sleeperSelected = in_array('Sleeper', $fleetTypes);

                if ($acSelected && $nonAcSelected)
                    return false;

                $acMatch = true;
                if ($acSelected || $nonAcSelected) {
                    // Step 1: Explicitly check if the bus is Non-AC using a simple, reliable regex.
                    $isNonAC = preg_match('/Non[- \s]?A\/?C/i', $busType) === 1;

                    // Step 2: A bus is AC if it contains "AC" AND is NOT a "Non-AC" bus.
                    $isAC = !$isNonAC && (preg_match('/A\/?C/i', $busType) === 1);

                    // Apply the logic based on user's selection
                    $acMatch = ($acSelected && $isAC) || ($nonAcSelected && $isNonAC);
                }

                $typeMatch = true;
                if ($seaterSelected || $sleeperSelected) {
                    $isSeater = stripos($busType, 'Seater') !== false;
                    $isSleeper = stripos($busType, 'Sleeper') !== false;
                    $typeMatch = (!$seaterSelected && !$sleeperSelected) || ($seaterSelected && $isSeater) || ($sleeperSelected && $isSleeper);
                }

                if (!($acMatch && $typeMatch))
                    return false;
            }
            return true;
        });

        Log::info('Filter results', [
            'total_trips_after_filter' => count($filteredTrips),
            'filtered_out_count' => count($trips) - count($filteredTrips)
        ]);

        return array_values($filteredTrips);
    }

    /**
     * Get current active coupon details
     */
    public static function getCurrentCoupon()
    {
        // Return the currently active and unexpired coupon
        return CouponTable::where('status', 1)
            ->where('expiry_date', '>=', Carbon::today())
            ->first();
    }

    /**
     * Calculate available seats for a bus on a specific date, excluding operator-blocked seats.
     */
    private function calculateAvailableSeats(OperatorBus $bus, string $dateOfJourney): int
    {
        $totalSeats = $bus->total_seats;

        // Get operator bookings that block seats on this date
        $blockedSeats = OperatorBooking::active()
            ->where('operator_bus_id', $bus->id)
            ->where(function ($query) use ($dateOfJourney) {
                $query->where('journey_date', $dateOfJourney)
                    ->orWhere(function ($q) use ($dateOfJourney) {
                        $q->where('is_date_range', true)
                            ->where('journey_date', '<=', $dateOfJourney)
                            ->where('journey_date_end', '>=', $dateOfJourney);
                    });
            })
            ->get();

        $totalBlockedSeats = 0;
        foreach ($blockedSeats as $booking) {
            $totalBlockedSeats += $booking->total_seats_blocked;
        }

        $availableSeats = $totalSeats - $totalBlockedSeats;

        // Ensure we don't return negative seats
        return max(0, $availableSeats);
    }

    /**
     * Get cancellation policies for operator buses with proper date formatting.
     * Uses custom policies if available, otherwise default policies.
     */
    private function getOperatorCancellationPoliciesWithDates(\App\Models\OperatorBus $bus, string $dateOfJourney): array
    {
        $journeyDate = Carbon::parse($dateOfJourney);

        // Get policies from bus model (handles both custom and default)
        $policies = $bus->cancellation_policies;

        // Add proper date formatting to match third-party API format
        return array_map(function ($policy) use ($journeyDate) {
            $timeRange = explode('$', $policy['TimeBeforeDept']);
            $timeFrom = (int) $timeRange[0];
            $timeTo = isset($timeRange[1]) ? (int) $timeRange[1] : 999;

            return [
                'CancellationCharge' => $policy['CancellationCharge'],
                'CancellationChargeType' => $policy['CancellationChargeType'],
                'PolicyString' => $policy['PolicyString'],
                'TimeBeforeDept' => $policy['TimeBeforeDept'],
                'FromDate' => $journeyDate->copy()->subHours($timeTo)->format('Y-m-d\TH:i:s'),
                'ToDate' => $journeyDate->copy()->subHours($timeFrom)->format('Y-m-d\TH:i:s')
            ];
        }, $policies);
    }
}
