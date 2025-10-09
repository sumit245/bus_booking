<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\MarkupTable;
use App\Models\CouponTable;
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

        if (empty($apiResponse['Result'])) {
            throw new \Exception('No buses found for this route and date', 404);
        }

        $trips = $apiResponse['Result'];

        $trips = $this->applyMarkup($trips);
        $trips = $this->applyCoupon($trips);
        $trips = $this->applyFilters($trips, $validatedData);
        $trips = $this->applySorting($trips, $validatedData); // Sorting now works on a proper array

        $page = $validatedData['page'] ?? 1;
        $perPage = 20;
        $paginatedTrips = array_slice($trips, ($page - 1) * $perPage, $perPage);

        return [
            'SearchTokenId' => $apiResponse['SearchTokenId'],
            'trips' => $paginatedTrips, // This is now guaranteed to be a sequential array
            'pagination' => [
                'total_results' => count($trips),
                'per_page' => $perPage,
                'current_page' => (int) $page,
                'has_more_pages' => ($page * $perPage) < count($trips),
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
            Log::info("CACHE MISS: Fetching fresh data from API for {$originId}-{$destinationId} on {$dateOfJourney}");
            $resp = searchAPIBuses($originId, $destinationId, $dateOfJourney, request()->ip());
            if (isset($resp['Error']['ErrorCode']) && $resp['Error']['ErrorCode'] !== 0) {
                return ['Result' => [], 'SearchTokenId' => null, 'Error' => $resp['Error']];
            }
            return $resp;
        });
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
        Log::info('Applying filters: ' . json_encode($filters));
        $filteredTrips = array_filter($trips, function ($trip) use ($filters) {
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
}
