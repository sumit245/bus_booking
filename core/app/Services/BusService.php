<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\MarkupTable;
use App\Models\CouponTable; // Make sure this is imported
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusService
{
    /**
     * Fetch and process bus API response.
     */
    public static function fetchAndProcessAPIResponse($originId, $destinationId, $dateOfJourney, $ip)
    {
        $resp = searchAPIBuses($originId, $destinationId, $dateOfJourney, $ip);
        if (isset($resp['Error']['ErrorCode']) && $resp['Error']['ErrorCode'] !== 0) {
            throw new \Exception($resp['Error']['ErrorMessage']);
        }
        return $resp;
    }

    /**
     * Sort trips by departure time.
     */
    public static function sortTripsByDepartureTime($trips)
    {
        usort($trips, function ($a, $b) {
            return strtotime($a['DepartureTime']) - strtotime($b['DepartureTime']);
        });
        return $trips;
    }

    /**
     * Apply markup logic.
     */
    public static function applyMarkup($trips)
    {
        $markup = MarkupTable::orderBy('id', 'desc')->first();
        $flatMarkup = isset($markup->flat_markup) ? (float) $markup->flat_markup : 0;
        $percentageMarkup = isset($markup->percentage_markup) ? (float) $markup->percentage_markup : 0;
        $threshold = isset($markup->threshold) ? (float) $markup->threshold : 0;

        foreach ($trips as &$trip) {
            if (isset($trip['BusPrice']['PublishedPrice']) && is_numeric($trip['BusPrice']['PublishedPrice'])) {
                $originalPrice = (float) $trip['BusPrice']['PublishedPrice'];
                $newPrice = $originalPrice;

                if ($originalPrice <= $threshold) {
                    $newPrice += $flatMarkup;
                } else {
                    $newPrice += ($originalPrice * $percentageMarkup / 100);
                }

                $trip['BusPrice']['PublishedPrice'] = round($newPrice, 2);
            }
        }

        return $trips;
    }

    /**
     * Apply coupon discount logic.
     * Stores the price before coupon application in 'PriceBeforeCoupon'.
     */
    public static function applyCoupon($trips)
    {
        $coupon = CouponTable::orderBy('id', 'desc')->first();
        
        // Get coupon parameters, defaulting to 0 if not set
        $couponThreshold = isset($coupon->coupon_threshold) ? (float) $coupon->coupon_threshold : 0;
        $flatCouponAmount = isset($coupon->flat_coupon_amount) ? (float) $coupon->flat_coupon_amount : 0;
        $percentageCouponAmount = isset($coupon->percentage_coupon_amount) ? (float) $coupon->percentage_coupon_amount : 0;

        foreach ($trips as &$trip) {
            if (isset($trip['BusPrice']['PublishedPrice']) && is_numeric($trip['BusPrice']['PublishedPrice'])) {
                $priceAfterMarkup = (float) $trip['BusPrice']['PublishedPrice'];
                
                // Store the price before applying the coupon for UI display
                $trip['BusPrice']['PriceBeforeCoupon'] = round($priceAfterMarkup, 2);

                $discountAmount = 0;
                if ($priceAfterMarkup > 0) { // Only apply discount if price is positive
                    if ($priceAfterMarkup <= $couponThreshold) {
                        $discountAmount = $flatCouponAmount;
                    } else {
                        $discountAmount = ($priceAfterMarkup * $percentageCouponAmount / 100);
                    }
                }
                
                // Apply coupon discount
                $finalPrice = $priceAfterMarkup - $discountAmount;
                
                // Ensure price doesn't go below 0
                $finalPrice = max($finalPrice, 0);
                
                $trip['BusPrice']['PublishedPrice'] = round($finalPrice, 2);
            }
        }
        return $trips;
    }

    /**
     * Get current active coupon details
     */
    public static function getCurrentCoupon()
    {
        return CouponTable::orderBy('id', 'desc')->first();
    }

    /**
     * Apply filters to trips.
     */
    public static function applyFilters($trips, Request $request)
    {
        $filteredTrips = $trips;

        // Live tracking filter
        if ($request->has('live_tracking') && $request->live_tracking == 1) {
            $filteredTrips = array_filter($filteredTrips, function ($trip) {
                return isset($trip['LiveTrackingAvailable']) && $trip['LiveTrackingAvailable'] === true;
            });
        }

        // Departure time filter
        if ($request->has('departure_time') && !empty($request->departure_time)) {
            $filteredTrips = array_filter($filteredTrips, function ($trip) use ($request) {
                $departureTime = Carbon::parse($trip['DepartureTime']);
                $hour = (int)$departureTime->format('H');

                foreach ($request->departure_time as $timeRange) {
                    switch ($timeRange) {
                        case 'morning':
                            if ($hour >= 6 && $hour < 12) return true;
                            break;
                        case 'afternoon':
                            if ($hour >= 12 && $hour < 18) return true;
                            break;
                        case 'evening':
                            if ($hour >= 18 && $hour < 24) return true;
                            break;
                        case 'night':
                            if ($hour >= 0 && $hour < 6) return true;
                            break;
                    }
                }
                return false;
            });
        }

        // Amenities filter
        if ($request->has('amenities') && !empty($request->amenities)) {
            $filteredTrips = array_filter($filteredTrips, function ($trip) use ($request) {
                foreach ($request->amenities as $amenity) {
                    $found = false;
                    switch ($amenity) {
                        case 'wifi':
                            $found = stripos($trip['ServiceName'] ?? '', 'wifi') !== false || stripos($trip['Description'] ?? '', 'wifi') !== false;
                            break;
                        case 'charging':
                            $found = stripos($trip['ServiceName'] ?? '', 'charging') !== false || stripos($trip['Description'] ?? '', 'charging') !== false;
                            break;
                        case 'water':
                            $found = stripos($trip['ServiceName'] ?? '', 'water') !== false || stripos($trip['Description'] ?? '', 'water') !== false;
                            break;
                        case 'blanket':
                            $found = stripos($trip['ServiceName'] ?? '', 'blanket') !== false || stripos($trip['Description'] ?? '', 'blanket') !== false;
                            break;
                    }
                    if (!$found) return false;
                }
                return true;
            });
        }

        // Price range filter
        if (($request->has('min_price') && $request->min_price !== null) || ($request->has('max_price') && $request->max_price !== null)) {
            $minPrice = $request->min_price ?? 0;
            $maxPrice = $request->max_price ?? PHP_INT_MAX;

            $filteredTrips = array_filter($filteredTrips, function ($trip) use ($minPrice, $maxPrice) {
                $price = $trip['BusPrice']['PublishedPrice'];
                return $price >= $minPrice && $price <= $maxPrice;
            });
        }

        // Apply fleet type filter
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

                $acSelected = in_array('A/c', $request->fleetType);
                $nonAcSelected = in_array('Non-A/c', $request->fleetType);
                $seaterSelected = in_array('Seater', $request->fleetType);
                $sleeperSelected = in_array('Sleeper', $request->fleetType);

                if ($acSelected && $nonAcSelected) {
                    return false;
                }

                $matchesAcCriteria = true;
                $matchesTypeCriteria = true;

                if ($acSelected || $nonAcSelected) {
                    $matchesAcCriteria = false;
                    if ($acSelected && ((stripos($busType, 'A/c') !== false || stripos($busType, 'AC') !== false) && stripos($busType, 'Non') === false)) {
                        $matchesAcCriteria = true;
                    }
                    if ($nonAcSelected && (stripos($busType, 'Non A/c') !== false || stripos($busType, 'Non Ac') !== false)) {
                        $matchesAcCriteria = true;
                    }
                }

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

        return array_values($filteredTrips);
    }

    /**
     * Validate incoming request
     */
    public static function validateSearchRequest(Request $request)
    {
        if ($request->OriginId && $request->DestinationId && $request->OriginId == $request->DestinationId) {
            throw new \Exception('Please select pickup point and destination point properly');
        }

        if ($request->DateOfJourney && Carbon::parse($request->DateOfJourney)->format('Y-m-d') < Carbon::now()->format('Y-m-d')) {
            throw new \Exception('Date of journey can\'t be less than today.');
        }
    }

    /**
     * Store in Session
     */
    public static function storeSearchSession(Request $request, $searchTokenId)
    {
        session()->put([
            'search_token_id' => $searchTokenId,
            'user_ip' => $request->ip(),
            'date_of_journey' => $request->DateOfJourney,
            'origin_id' => $request->OriginId,
            'destination_id' => $request->DestinationId
        ]);
    }
}
