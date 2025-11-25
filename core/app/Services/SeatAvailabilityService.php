<?php

namespace App\Services;

use App\Models\BookedTicket;
use App\Models\BusSchedule;
use App\Models\BoardingPoint;
use App\Models\DroppingPoint;
use App\Models\OperatorBus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * SeatAvailabilityService
 * 
 * Single source of truth for seat availability calculation.
 * Handles route segment overlap logic for operator buses.
 * 
 * Key Features:
 * - Calculates availability per schedule/date/route segment
 * - Handles overlapping route segments (e.g., Patna->Delhi vs Patna->Intermediate)
 * - Returns booked seats for specific context
 * - Caches results for performance
 */
class SeatAvailabilityService
{
    /**
     * Get booked seats for a specific operator bus, schedule, date, and route segment
     * 
     * @param int $operatorBusId
     * @param int $scheduleId
     * @param string $dateOfJourney (Y-m-d format)
     * @param int|null $boardingPointIndex Optional: If provided, only returns seats blocked for overlapping segments
     * @param int|null $droppingPointIndex Optional: If provided, only returns seats blocked for overlapping segments
     * @return array Array of booked seat names (e.g., ['1', '2', 'U1', 'L4'])
     */
    public function getBookedSeats(
        int $operatorBusId,
        int $scheduleId,
        string $dateOfJourney,
        ?int $boardingPointIndex = null,
        ?int $droppingPointIndex = null
    ): array {
        $cacheKey = $this->getCacheKey($operatorBusId, $scheduleId, $dateOfJourney, $boardingPointIndex, $droppingPointIndex);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($operatorBusId, $scheduleId, $dateOfJourney, $boardingPointIndex, $droppingPointIndex) {
            return $this->calculateBookedSeats(
                $operatorBusId,
                $scheduleId,
                $dateOfJourney,
                $boardingPointIndex,
                $droppingPointIndex
            );
        });
    }

    /**
     * Calculate booked seats with route segment overlap logic
     */
    private function calculateBookedSeats(
        int $operatorBusId,
        int $scheduleId,
        string $dateOfJourney,
        ?int $boardingPointIndex,
        ?int $droppingPointIndex
    ): array {
        // Normalize date format - handle both Y-m-d and m/d/Y formats
        $normalizedDate = $dateOfJourney;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfJourney)) {
            try {
                if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateOfJourney)) {
                    $normalizedDate = Carbon::createFromFormat('m/d/Y', $dateOfJourney)->format('Y-m-d');
                } else {
                    $normalizedDate = Carbon::parse($dateOfJourney)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                Log::warning('SeatAvailabilityService: Failed to parse date', [
                    'original_date' => $dateOfJourney,
                    'error' => $e->getMessage()
                ]);
                $normalizedDate = $dateOfJourney; // Use as-is if parsing fails
            }
        }

        // Get all bookings for this bus, schedule, and date
        // Status: 0 = pending, 1 = confirmed, 2 = rejected
        // We only care about pending and confirmed bookings
        // Check both Y-m-d and m/d/Y formats in database
        $bookings = BookedTicket::where('bus_id', $operatorBusId)
            ->where('schedule_id', $scheduleId)
            ->where(function ($query) use ($normalizedDate, $dateOfJourney) {
                // Try exact match first (Y-m-d format)
                $query->where('date_of_journey', $normalizedDate)
                    // Also try original format if different
                    ->orWhere('date_of_journey', $dateOfJourney)
                    // Also try date comparison if stored as date
                    ->orWhereDate('date_of_journey', $normalizedDate);
            })
            ->whereIn('status', [0, 1]) // pending or confirmed
            ->whereNotNull('seats')
            ->get();

        Log::info('SeatAvailabilityService: Found bookings', [
            'operator_bus_id' => $operatorBusId,
            'schedule_id' => $scheduleId,
            'date_of_journey' => $normalizedDate,
            'original_date' => $dateOfJourney,
            'bookings_count' => $bookings->count()
        ]);

        $bookedSeats = [];

        // Get schedule to check route
        $schedule = BusSchedule::with('operatorRoute')->find($scheduleId);
        if (!$schedule || !$schedule->operatorRoute) {
            Log::warning('SeatAvailabilityService: Schedule or route not found', [
                'schedule_id' => $scheduleId,
                'operator_bus_id' => $operatorBusId
            ]);
            return $bookedSeats;
        }

        $route = $schedule->operatorRoute;

        // Get boarding and dropping points for this route
        $boardingPoints = BoardingPoint::where('operator_route_id', $route->id)
            ->active()
            ->ordered()
            ->get();

        $droppingPoints = DroppingPoint::where('operator_route_id', $route->id)
            ->active()
            ->ordered()
            ->get();

        // If no specific boarding/dropping point requested, return all booked seats
        if ($boardingPointIndex === null && $droppingPointIndex === null) {
            foreach ($bookings as $booking) {
                $seats = $this->extractSeatsFromBooking($booking);
                $bookedSeats = array_merge($bookedSeats, $seats);
            }
            return array_unique($bookedSeats);
        }

        // Route segment overlap logic
        // A seat is booked if there's ANY overlap between:
        // 1. The requested segment (boardingPointIndex -> droppingPointIndex)
        // 2. Any existing booking's segment
        foreach ($bookings as $booking) {
            $bookingBoardingIndex = $this->getBoardingPointIndex($booking, $route->id);
            $bookingDroppingIndex = $this->getDroppingPointIndex($booking, $route->id);

            if ($bookingBoardingIndex === null || $bookingDroppingIndex === null) {
                // If we can't determine the segment, consider all seats booked (safety)
                $seats = $this->extractSeatsFromBooking($booking);
                $bookedSeats = array_merge($bookedSeats, $seats);
                continue;
            }

            // Check if segments overlap
            if (
                $this->segmentsOverlap(
                    $boardingPointIndex,
                    $droppingPointIndex,
                    $bookingBoardingIndex,
                    $bookingDroppingIndex,
                    $boardingPoints,
                    $droppingPoints
                )
            ) {
                $seats = $this->extractSeatsFromBooking($booking);
                $bookedSeats = array_merge($bookedSeats, $seats);
            }
        }

        return array_unique($bookedSeats);
    }

    /**
     * Check if two route segments overlap
     * 
     * Segments overlap if:
     * - Segment A starts before Segment B ends AND
     * - Segment A ends after Segment B starts
     * 
     * Example:
     * - Request: Patna (index 1) -> Intermediate (index 3)
     * - Booking: Patna (index 1) -> Delhi (index 5)
     * - Overlap: YES (both start at Patna, and request ends before booking ends)
     * 
     * - Request: Intermediate (index 3) -> Delhi (index 5)
     * - Booking: Patna (index 1) -> Intermediate (index 3)
     * - Overlap: NO (request starts where booking ends)
     */
    private function segmentsOverlap(
        int $requestBoardingIndex,
        int $requestDroppingIndex,
        int $bookingBoardingIndex,
        int $bookingDroppingIndex,
        $boardingPoints,
        $droppingPoints
    ): bool {
        // Get point indices sorted by position in route
        $allPoints = [];

        // Combine boarding and dropping points, ordered by point_index
        foreach ($boardingPoints as $bp) {
            $allPoints[$bp->point_index] = ['type' => 'boarding', 'point' => $bp];
        }
        foreach ($droppingPoints as $dp) {
            $allPoints[$dp->point_index] = ['type' => 'dropping', 'point' => $dp];
        }

        ksort($allPoints);
        $sortedIndices = array_keys($allPoints);

        // Find positions of request and booking segments
        $requestStartPos = array_search($requestBoardingIndex, $sortedIndices);
        $requestEndPos = array_search($requestDroppingIndex, $sortedIndices);
        $bookingStartPos = array_search($bookingBoardingIndex, $sortedIndices);
        $bookingEndPos = array_search($bookingDroppingIndex, $sortedIndices);

        // If any index not found, assume overlap (safety)
        if (
            $requestStartPos === false || $requestEndPos === false ||
            $bookingStartPos === false || $bookingEndPos === false
        ) {
            Log::warning('SeatAvailabilityService: Point index not found in sorted indices', [
                'request_boarding' => $requestBoardingIndex,
                'request_dropping' => $requestDroppingIndex,
                'booking_boarding' => $bookingBoardingIndex,
                'booking_dropping' => $bookingDroppingIndex,
                'sorted_indices' => $sortedIndices
            ]);
            return true; // Safety: assume overlap if we can't determine
        }

        // Ensure start <= end for both segments
        if ($requestStartPos > $requestEndPos) {
            [$requestStartPos, $requestEndPos] = [$requestEndPos, $requestStartPos];
        }
        if ($bookingStartPos > $bookingEndPos) {
            [$bookingStartPos, $bookingEndPos] = [$bookingEndPos, $bookingStartPos];
        }

        // Check overlap: segments overlap if request starts before booking ends AND request ends after booking starts
        return $requestStartPos <= $bookingEndPos && $requestEndPos >= $bookingStartPos;
    }

    /**
     * Extract seat names from booking
     */
    private function extractSeatsFromBooking(BookedTicket $booking): array
    {
        $seats = [];

        // Try seats array first
        if ($booking->seats && is_array($booking->seats)) {
            $seats = array_merge($seats, $booking->seats);
        }

        // Fallback to seat_numbers string
        if (empty($seats) && $booking->seat_numbers) {
            $seatNumbers = explode(',', $booking->seat_numbers);
            $seats = array_merge($seats, array_map('trim', $seatNumbers));
        }

        return array_filter($seats); // Remove empty values
    }

    /**
     * Get boarding point index from booking
     */
    private function getBoardingPointIndex(BookedTicket $booking, int $routeId): ?int
    {
        // Try from boarding_point_details JSON
        if ($booking->boarding_point_details) {
            $details = json_decode($booking->boarding_point_details, true);
            if (isset($details['CityPointIndex'])) {
                return (int) $details['CityPointIndex'];
            }
        }

        // Try from boarding_point column (if it's a point_index)
        if ($booking->boarding_point) {
            // Check if it's a valid point_index for this route
            $point = BoardingPoint::where('operator_route_id', $routeId)
                ->where('point_index', $booking->boarding_point)
                ->first();
            if ($point) {
                return $point->point_index;
            }
        }

        // Try to find by matching point name/location
        // This is a fallback - less reliable
        if ($booking->boarding_point_details) {
            $details = json_decode($booking->boarding_point_details, true);
            if (isset($details['CityPointName'])) {
                $point = BoardingPoint::where('operator_route_id', $routeId)
                    ->where('point_name', $details['CityPointName'])
                    ->first();
                if ($point) {
                    return $point->point_index;
                }
            }
        }

        return null;
    }

    /**
     * Get dropping point index from booking
     */
    private function getDroppingPointIndex(BookedTicket $booking, int $routeId): ?int
    {
        // Try from dropping_point_details JSON
        if ($booking->dropping_point_details) {
            $details = json_decode($booking->dropping_point_details, true);
            if (isset($details['CityPointIndex'])) {
                return (int) $details['CityPointIndex'];
            }
        }

        // Try from dropping_point column
        if ($booking->dropping_point) {
            $point = DroppingPoint::where('operator_route_id', $routeId)
                ->where('point_index', $booking->dropping_point)
                ->first();
            if ($point) {
                return $point->point_index;
            }
        }

        // Try to find by matching point name/location
        if ($booking->dropping_point_details) {
            $details = json_decode($booking->dropping_point_details, true);
            if (isset($details['CityPointName'])) {
                $point = DroppingPoint::where('operator_route_id', $routeId)
                    ->where('point_name', $details['CityPointName'])
                    ->first();
                if ($point) {
                    return $point->point_index;
                }
            }
        }

        return null;
    }

    /**
     * Get cache key for availability
     */
    private function getCacheKey(
        int $operatorBusId,
        int $scheduleId,
        string $dateOfJourney,
        ?int $boardingPointIndex,
        ?int $droppingPointIndex
    ): string {
        $parts = [
            'seat_availability',
            $operatorBusId,
            $scheduleId,
            $dateOfJourney,
            $boardingPointIndex ?? 'all',
            $droppingPointIndex ?? 'all'
        ];

        return implode(':', $parts);
    }

    /**
     * Invalidate cache for a specific bus/schedule/date
     * Clears ALL cache variations (with and without boarding/dropping points)
     */
    public function invalidateCache(int $operatorBusId, int $scheduleId, string $dateOfJourney): void
    {
        // Normalize date format first
        $normalizedDate = $dateOfJourney;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfJourney)) {
            try {
                if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateOfJourney)) {
                    $normalizedDate = Carbon::createFromFormat('m/d/Y', $dateOfJourney)->format('Y-m-d');
                } else {
                    $normalizedDate = Carbon::parse($dateOfJourney)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                Log::warning('SeatAvailabilityService: Failed to normalize date for cache invalidation', [
                    'original_date' => $dateOfJourney,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Clear the main cache key (no boarding/dropping points)
        $mainKey = $this->getCacheKey($operatorBusId, $scheduleId, $normalizedDate, null, null);
        Cache::forget($mainKey);

        // Also try to clear with original date format if different
        if ($normalizedDate !== $dateOfJourney) {
            $originalKey = $this->getCacheKey($operatorBusId, $scheduleId, $dateOfJourney, null, null);
            Cache::forget($originalKey);
        }

        Log::info('SeatAvailabilityService: Cache invalidated', [
            'operator_bus_id' => $operatorBusId,
            'schedule_id' => $scheduleId,
            'date_of_journey' => $normalizedDate,
            'original_date' => $dateOfJourney,
            'main_key_cleared' => $mainKey,
            'note' => 'Specific cache keys cleared. All seat availability for this bus/schedule/date will be recalculated.'
        ]);
    }

    /**
     * Get available seats count
     */
    public function getAvailableSeatsCount(
        int $operatorBusId,
        int $scheduleId,
        string $dateOfJourney,
        ?int $boardingPointIndex = null,
        ?int $droppingPointIndex = null,
        int $totalSeats = 0
    ): int {
        $bookedSeats = $this->getBookedSeats(
            $operatorBusId,
            $scheduleId,
            $dateOfJourney,
            $boardingPointIndex,
            $droppingPointIndex
        );

        $bookedCount = count($bookedSeats);
        $availableCount = max(0, $totalSeats - $bookedCount);

        return $availableCount;
    }
}

