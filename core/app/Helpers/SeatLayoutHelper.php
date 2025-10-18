<?php

namespace App\Helpers;

class SeatLayoutHelper
{
    /**
     * Convert seat class from available to booked
     * nseat -> bseat
     * hseat -> bhseat  
     * vseat -> bvseat
     */
    public static function convertSeatToBooked(string $seatClass): string
    {
        $conversions = [
            'nseat' => 'bseat',
            'hseat' => 'bhseat',
            'vseat' => 'bvseat'
        ];

        return $conversions[$seatClass] ?? $seatClass;
    }

    /**
     * Convert seat class from booked to available
     * bseat -> nseat
     * bhseat -> hseat
     * bvseat -> vseat
     */
    public static function convertSeatToAvailable(string $seatClass): string
    {
        $conversions = [
            'bseat' => 'nseat',
            'bhseat' => 'hseat',
            'bvseat' => 'vseat'
        ];

        return $conversions[$seatClass] ?? $seatClass;
    }

    /**
     * Update seat class in HTML layout for a specific seat ID
     */
    public static function updateSeatClassInHtml(string $htmlLayout, string $seatId, string $newClass): string
    {
        if (empty($htmlLayout) || empty($seatId) || empty($newClass)) {
            return $htmlLayout;
        }

        // Pattern to match seat div with specific ID
        $pattern = '/(<div[^>]*class="[^"]*)(nseat|hseat|vseat|bseat|bhseat|bvseat)([^"]*)"[^>]*id="' . preg_quote($seatId, '/') . '"[^>]*>/i';

        $replacement = '$1' . $newClass . '$3" id="' . $seatId . '"';

        return preg_replace($pattern, $replacement, $htmlLayout);
    }

    /**
     * Update multiple seats in HTML layout
     */
    public static function updateMultipleSeatsInHtml(string $htmlLayout, array $seatUpdates): string
    {
        $updatedHtml = $htmlLayout;

        foreach ($seatUpdates as $seatId => $newClass) {
            $updatedHtml = self::updateSeatClassInHtml($updatedHtml, $seatId, $newClass);
        }

        return $updatedHtml;
    }

    /**
     * Extract all seat IDs from HTML layout
     */
    public static function extractSeatIds(string $htmlLayout): array
    {
        $seatIds = [];

        // Pattern to match seat divs with IDs
        preg_match_all('/<div[^>]*id="([^"]+)"[^>]*class="[^"]*(?:nseat|hseat|vseat|bseat|bhseat|bvseat)[^"]*"[^>]*>/i', $htmlLayout, $matches);

        if (!empty($matches[1])) {
            $seatIds = array_unique($matches[1]);
        }

        return $seatIds;
    }

    /**
     * Get seat class for a specific seat ID from HTML layout
     */
    public static function getSeatClass(string $htmlLayout, string $seatId): ?string
    {
        // Pattern to match seat div with specific ID and extract class
        $pattern = '/<div[^>]*class="([^"]*)(nseat|hseat|vseat|bseat|bhseat|bvseat)([^"]*)"[^>]*id="' . preg_quote($seatId, '/') . '"[^>]*>/i';

        if (preg_match($pattern, $htmlLayout, $matches)) {
            return $matches[2]; // Return the seat class (nseat, hseat, etc.)
        }

        return null;
    }

    /**
     * Check if a seat is booked based on its class
     */
    public static function isSeatBooked(string $seatClass): bool
    {
        return in_array($seatClass, ['bseat', 'bhseat', 'bvseat']);
    }

    /**
     * Check if a seat is available based on its class
     */
    public static function isSeatAvailable(string $seatClass): bool
    {
        return in_array($seatClass, ['nseat', 'hseat', 'vseat']);
    }

    /**
     * Get all booked seats from HTML layout
     */
    public static function getBookedSeatsFromHtml(string $htmlLayout): array
    {
        $bookedSeats = [];

        // Pattern to match booked seats
        preg_match_all('/<div[^>]*class="[^"]*(?:bseat|bhseat|bvseat)[^"]*"[^>]*id="([^"]+)"[^>]*>/i', $htmlLayout, $matches);

        if (!empty($matches[1])) {
            $bookedSeats = $matches[1];
        }

        return $bookedSeats;
    }

    /**
     * Get all available seats from HTML layout
     */
    public static function getAvailableSeatsFromHtml(string $htmlLayout): array
    {
        $availableSeats = [];

        // Pattern to match available seats
        preg_match_all('/<div[^>]*class="[^"]*(?:nseat|hseat|vseat)[^"]*"[^>]*id="([^"]+)"[^>]*>/i', $htmlLayout, $matches);

        if (!empty($matches[1])) {
            $availableSeats = $matches[1];
        }

        return $availableSeats;
    }

    /**
     * Validate seat layout HTML structure
     */
    public static function validateSeatLayoutHtml(string $htmlLayout): array
    {
        $errors = [];
        $warnings = [];

        // Check if HTML is empty
        if (empty(trim($htmlLayout))) {
            $errors[] = 'HTML layout is empty';
            return compact('errors', 'warnings');
        }

        // Check for basic HTML structure
        if (!str_contains($htmlLayout, '<div class="bus-layout">')) {
            $errors[] = 'Missing bus-layout container';
        }

        // Check for seat containers
        if (!str_contains($htmlLayout, '<div class="deck')) {
            $errors[] = 'Missing deck containers';
        }

        // Count seats by type
        $seatCounts = [
            'nseat' => preg_match_all('/nseat/i', $htmlLayout),
            'hseat' => preg_match_all('/hseat/i', $htmlLayout),
            'vseat' => preg_match_all('/vseat/i', $htmlLayout),
            'bseat' => preg_match_all('/bseat/i', $htmlLayout),
            'bhseat' => preg_match_all('/bhseat/i', $htmlLayout),
            'bvseat' => preg_match_all('/bvseat/i', $htmlLayout)
        ];

        $totalSeats = array_sum($seatCounts);

        if ($totalSeats === 0) {
            $errors[] = 'No seats found in layout';
        }

        // Check for duplicate seat IDs
        $seatIds = self::extractSeatIds($htmlLayout);
        $duplicateIds = array_diff_assoc($seatIds, array_unique($seatIds));

        if (!empty($duplicateIds)) {
            $errors[] = 'Duplicate seat IDs found: ' . implode(', ', $duplicateIds);
        }

        return compact('errors', 'warnings', 'seatCounts', 'totalSeats');
    }

    /**
     * Generate seat layout summary
     */
    public static function getSeatLayoutSummary(string $htmlLayout): array
    {
        $summary = [
            'total_seats' => 0,
            'available_seats' => 0,
            'booked_seats' => 0,
            'seat_types' => [
                'normal' => 0,
                'horizontal' => 0,
                'vertical' => 0,
                'booked_normal' => 0,
                'booked_horizontal' => 0,
                'booked_vertical' => 0
            ]
        ];

        // Count seats by type
        $summary['seat_types']['normal'] = preg_match_all('/nseat/i', $htmlLayout);
        $summary['seat_types']['horizontal'] = preg_match_all('/hseat/i', $htmlLayout);
        $summary['seat_types']['vertical'] = preg_match_all('/vseat/i', $htmlLayout);
        $summary['seat_types']['booked_normal'] = preg_match_all('/bseat/i', $htmlLayout);
        $summary['seat_types']['booked_horizontal'] = preg_match_all('/bhseat/i', $htmlLayout);
        $summary['seat_types']['booked_vertical'] = preg_match_all('/bvseat/i', $htmlLayout);

        $summary['total_seats'] = array_sum($summary['seat_types']);
        $summary['available_seats'] = $summary['seat_types']['normal'] +
            $summary['seat_types']['horizontal'] +
            $summary['seat_types']['vertical'];
        $summary['booked_seats'] = $summary['seat_types']['booked_normal'] +
            $summary['seat_types']['booked_horizontal'] +
            $summary['seat_types']['booked_vertical'];

        return $summary;
    }
}
