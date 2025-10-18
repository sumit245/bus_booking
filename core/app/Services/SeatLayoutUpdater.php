<?php

namespace App\Services;

use App\Models\BookedTicket;
use App\Models\SeatLayout;
use App\Models\OperatorBus;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SeatLayoutUpdater
{
    /**
     * Sync all seat layouts with current bookings
     */
    public function syncAllLayouts(): array
    {
        $results = [
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        try {
            // Get all active seat layouts
            $seatLayouts = SeatLayout::where('is_active', true)
                ->with('operatorBus')
                ->get();

            Log::info('SeatLayoutUpdater: Starting sync for ' . $seatLayouts->count() . ' layouts');

            foreach ($seatLayouts as $seatLayout) {
                try {
                    $result = $this->syncSingleLayout($seatLayout);
                    $results['updated'] += $result['updated'];
                    $results['skipped'] += $result['skipped'];
                    $results['errors'] += $result['errors'];
                    $results['details'][] = $result;
                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::error('SeatLayoutUpdater: Error syncing layout ' . $seatLayout->id, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $results['details'][] = [
                        'layout_id' => $seatLayout->id,
                        'bus_id' => $seatLayout->operator_bus_id,
                        'updated' => 0,
                        'skipped' => 0,
                        'errors' => 1,
                        'error_message' => $e->getMessage()
                    ];
                }
            }

            Log::info('SeatLayoutUpdater: Sync completed', $results);
            return $results;

        } catch (\Exception $e) {
            Log::error('SeatLayoutUpdater: Critical error during sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Sync a single seat layout with current bookings
     */
    public function syncSingleLayout(SeatLayout $seatLayout, bool $force = false): array
    {
        $result = [
            'layout_id' => $seatLayout->id,
            'bus_id' => $seatLayout->operator_bus_id,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'seats_updated' => []
        ];

        try {
            // Get current HTML layout
            $currentHtml = $seatLayout->html_layout;
            if (empty($currentHtml)) {
                Log::warning('SeatLayoutUpdater: Empty HTML layout for layout ' . $seatLayout->id);
                $result['skipped']++;
                return $result;
            }

            // Get all booked seats for this bus
            $bookedSeats = $this->getBookedSeatsForBus($seatLayout->operator_bus_id);

            if (empty($bookedSeats)) {
                Log::info('SeatLayoutUpdater: No booked seats found for bus ' . $seatLayout->operator_bus_id);
                $result['skipped']++;
                return $result;
            }

            // Update HTML layout
            $updatedHtml = $this->updateSeatClasses($currentHtml, $bookedSeats);

            // Check if any changes were made or if force update is requested
            if ($updatedHtml !== $currentHtml || $force) {
                $seatLayout->html_layout = $updatedHtml;
                $seatLayout->save();

                $result['updated']++;
                $result['seats_updated'] = $bookedSeats;

                Log::info('SeatLayoutUpdater: Updated layout ' . $seatLayout->id . ' for bus ' . $seatLayout->operator_bus_id, [
                    'booked_seats' => $bookedSeats,
                    'force' => $force
                ]);
            } else {
                $result['skipped']++;
                Log::info('SeatLayoutUpdater: No changes needed for layout ' . $seatLayout->id);
            }

            return $result;

        } catch (\Exception $e) {
            $result['errors']++;
            Log::error('SeatLayoutUpdater: Error updating layout ' . $seatLayout->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get all booked seats for a specific bus
     */
    private function getBookedSeatsForBus(int $operatorBusId): array
    {
        $bookedSeats = [];

        // Get bookings for this bus from the last 30 days and future dates
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $oneYearFromNow = Carbon::now()->addYear();

        $bookings = BookedTicket::where('bus_id', $operatorBusId)
            ->whereIn('status', [0, 1, 2]) // Include all booking statuses
            ->whereBetween('date_of_journey', [$thirtyDaysAgo, $oneYearFromNow])
            ->get();

        foreach ($bookings as $booking) {
            if (!empty($booking->seats)) {
                if (is_array($booking->seats)) {
                    $bookedSeats = array_merge($bookedSeats, $booking->seats);
                } elseif (is_string($booking->seats)) {
                    // Try to decode as JSON first
                    $decodedSeats = json_decode($booking->seats, true);
                    if (is_array($decodedSeats)) {
                        $bookedSeats = array_merge($bookedSeats, $decodedSeats);
                    } else {
                        // Handle comma-separated seat numbers
                        $seats = array_map('trim', explode(',', $booking->seats));
                        $bookedSeats = array_merge($bookedSeats, $seats);
                    }
                }
            }

            // Also check seat_numbers field if seats field is empty
            if (empty($booking->seats) && !empty($booking->seat_numbers)) {
                if (is_array($booking->seat_numbers)) {
                    $bookedSeats = array_merge($bookedSeats, $booking->seat_numbers);
                } elseif (is_string($booking->seat_numbers)) {
                    $seats = array_map('trim', explode(',', $booking->seat_numbers));
                    $bookedSeats = array_merge($bookedSeats, $seats);
                }
            }
        }

        // Remove duplicates and empty values
        $bookedSeats = array_unique(array_filter($bookedSeats));

        Log::info('SeatLayoutUpdater: Found booked seats for bus ' . $operatorBusId, [
            'booked_seats' => $bookedSeats,
            'total_bookings' => $bookings->count()
        ]);

        return $bookedSeats;
    }

    /**
     * Update seat classes in HTML layout
     */
    private function updateSeatClasses(string $htmlLayout, array $bookedSeats): string
    {
        if (empty($bookedSeats)) {
            return $htmlLayout;
        }

        $updatedHtml = $htmlLayout;

        foreach ($bookedSeats as $seatId) {
            // Convert seat ID to string for matching
            $seatId = (string) $seatId;

            // Update different seat types using preg_replace_callback
            $patterns = [
                // nseat (normal seat) -> bseat (booked seat) - handle both orders of attributes
                '/(<div[^>]*id="' . preg_quote($seatId, '/') . '"[^>]*class="[^"]*nseat[^"]*"[^>]*>)/i',
                '/(<div[^>]*class="[^"]*nseat[^"]*"[^>]*id="' . preg_quote($seatId, '/') . '"[^>]*>)/i',
                // hseat (horizontal seat) -> bhseat (booked horizontal seat)
                '/(<div[^>]*id="' . preg_quote($seatId, '/') . '"[^>]*class="[^"]*hseat[^"]*"[^>]*>)/i',
                '/(<div[^>]*class="[^"]*hseat[^"]*"[^>]*id="' . preg_quote($seatId, '/') . '"[^>]*>)/i',
                // vseat (vertical seat) -> bvseat (booked vertical seat)
                '/(<div[^>]*id="' . preg_quote($seatId, '/') . '"[^>]*class="[^"]*vseat[^"]*"[^>]*>)/i',
                '/(<div[^>]*class="[^"]*vseat[^"]*"[^>]*id="' . preg_quote($seatId, '/') . '"[^>]*>)/i'
            ];

            foreach ($patterns as $pattern) {
                $updatedHtml = preg_replace_callback($pattern, function ($matches) {
                    $html = $matches[1];
                    // Replace the appropriate seat class
                    if (strpos($html, 'nseat') !== false) {
                        return str_replace('nseat', 'bseat', $html);
                    } elseif (strpos($html, 'hseat') !== false) {
                        return str_replace('hseat', 'bhseat', $html);
                    } elseif (strpos($html, 'vseat') !== false) {
                        return str_replace('vseat', 'bvseat', $html);
                    }
                    return $html;
                }, $updatedHtml);
            }
        }

        return $updatedHtml;
    }

    /**
     * Sync specific seat layout by bus ID
     */
    public function syncByBusId(int $operatorBusId, bool $force = false): array
    {
        $seatLayout = SeatLayout::where('operator_bus_id', $operatorBusId)
            ->where('is_active', true)
            ->first();

        if (!$seatLayout) {
            Log::warning('SeatLayoutUpdater: No active seat layout found for bus ' . $operatorBusId);
            return [
                'layout_id' => null,
                'bus_id' => $operatorBusId,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 1,
                'error_message' => 'No active seat layout found'
            ];
        }

        return $this->syncSingleLayout($seatLayout, $force);
    }

    /**
     * Get statistics about seat layout sync
     */
    public function getSyncStats(): array
    {
        $totalLayouts = SeatLayout::where('is_active', true)->count();
        $totalBookings = BookedTicket::where('status', '!=', 0)->count();
        $recentBookings = BookedTicket::where('status', '!=', 0)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();

        return [
            'total_active_layouts' => $totalLayouts,
            'total_bookings' => $totalBookings,
            'recent_bookings_last_hour' => $recentBookings,
            'last_sync_time' => Carbon::now()->toISOString()
        ];
    }
}
