<?php

namespace App\Services;

use App\Models\SeatLayout;
use Illuminate\Support\Facades\Log;

class SeatLayoutConsistencyManager
{
    /**
     * Ensure seat 30 is properly marked as booked in the layout
     */
    public function fixSeat30Booking(): array
    {
        try {
            $seatLayout = SeatLayout::where('operator_bus_id', 1)->first();

            if (!$seatLayout) {
                return ['success' => false, 'message' => 'No seat layout found for bus 1'];
            }

            $htmlLayout = $seatLayout->html_layout;

            // Check if seat 30 is currently showing as booked
            if (str_contains($htmlLayout, 'id="30"') && str_contains($htmlLayout, 'class="nseat"')) {
                // Replace nseat with bseat for seat 30
                $updatedLayout = str_replace(
                    'id="30" style="top:130px;left:360px;display:block;" class="nseat"',
                    'id="30" style="top:130px;left:360px;display:block;" class="bseat"',
                    $htmlLayout
                );

                $seatLayout->html_layout = $updatedLayout;
                $seatLayout->save();

                return [
                    'success' => true,
                    'message' => 'Seat 30 updated from nseat to bseat',
                    'layout_id' => $seatLayout->id
                ];
            }

            return ['success' => true, 'message' => 'Seat 30 is already properly configured'];

        } catch (\Exception $e) {
            Log::error('Error fixing seat 30 booking: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Ensure all booked seats are properly marked in the layout
     */
    public function syncAllBookedSeats(): array
    {
        $results = [
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        try {
            $seatLayouts = SeatLayout::where('is_active', true)->get();

            foreach ($seatLayouts as $seatLayout) {
                try {
                    $syncResult = $this->syncSeatLayoutBookings($seatLayout);

                    if ($syncResult['updated']) {
                        $results['updated']++;
                    } else {
                        $results['skipped']++;
                    }

                    $results['details'][] = $syncResult;

                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'layout_id' => $seatLayout->id,
                        'bus_id' => $seatLayout->operator_bus_id,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Error syncing all seat layouts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync bookings for a specific seat layout
     */
    private function syncSeatLayoutBookings(SeatLayout $seatLayout): array
    {
        $busId = $seatLayout->operator_bus_id;
        $originalHtml = $seatLayout->html_layout;
        $updatedHtml = $originalHtml;

        // Get all booked seats for this bus
        $bookedSeats = $this->getBookedSeatsForBus($busId);

        $seatsUpdated = 0;

        // Update each booked seat in the HTML
        foreach ($bookedSeats as $seatId) {
            $seatId = (string) $seatId;

            // Convert different seat types to booked versions
            $patterns = [
                'nseat' => 'bseat',
                'hseat' => 'bhseat',
                'vseat' => 'bvseat'
            ];

            foreach ($patterns as $originalClass => $bookedClass) {
                $pattern = '/id="' . preg_quote($seatId, '/') . '" style="([^"]*)" class="' . $originalClass . '"/';
                $replacement = 'id="' . $seatId . '" style="$1" class="' . $bookedClass . '"';

                if (preg_match($pattern, $updatedHtml)) {
                    $updatedHtml = preg_replace($pattern, $replacement, $updatedHtml);
                    $seatsUpdated++;
                }
            }
        }

        // Save if changes were made
        if ($updatedHtml !== $originalHtml) {
            $seatLayout->html_layout = $updatedHtml;
            $seatLayout->save();

            return [
                'layout_id' => $seatLayout->id,
                'bus_id' => $busId,
                'status' => 'updated',
                'seats_updated' => $seatsUpdated,
                'updated_seats' => $bookedSeats
            ];
        }

        return [
            'layout_id' => $seatLayout->id,
            'bus_id' => $busId,
            'status' => 'skipped',
            'message' => 'No changes needed'
        ];
    }

    /**
     * Get all booked seats for a specific bus
     */
    private function getBookedSeatsForBus(int $busId): array
    {
        $bookedSeats = [];

        // Get seats from BookedTicket
        $tickets = \App\Models\BookedTicket::where('bus_id', $busId)
            ->whereIn('status', [1, 2])
            ->get(['seats']);

        foreach ($tickets as $ticket) {
            if (is_array($ticket->seats)) {
                $bookedSeats = array_merge($bookedSeats, $ticket->seats);
            }
        }

        // Get seats from OperatorBooking
        $operatorBookings = \App\Models\OperatorBooking::where('operator_bus_id', $busId)
            ->where('status', 'active')
            ->get(['blocked_seats']);

        foreach ($operatorBookings as $booking) {
            if (is_array($booking->blocked_seats)) {
                $bookedSeats = array_merge($bookedSeats, $booking->blocked_seats);
            }
        }

        return array_unique($bookedSeats);
    }

    /**
     * Validate layout consistency across interfaces
     */
    public function validateLayoutConsistency(): array
    {
        $issues = [];

        try {
            $seatLayouts = SeatLayout::where('is_active', true)->get();

            foreach ($seatLayouts as $seatLayout) {
                $busId = $seatLayout->operator_bus_id;
                $bookedSeats = $this->getBookedSeatsForBus($busId);

                foreach ($bookedSeats as $seatId) {
                    $seatId = (string) $seatId;

                    // Check if booked seat is still showing as available
                    if (str_contains($seatLayout->html_layout, 'id="' . $seatId . '"')) {
                        if (
                            str_contains($seatLayout->html_layout, 'id="' . $seatId . '" class="nseat"') ||
                            str_contains($seatLayout->html_layout, 'id="' . $seatId . '" class="hseat"') ||
                            str_contains($seatLayout->html_layout, 'id="' . $seatId . '" class="vseat"')
                        ) {

                            $issues[] = [
                                'layout_id' => $seatLayout->id,
                                'bus_id' => $busId,
                                'seat_id' => $seatId,
                                'issue' => 'Booked seat showing as available'
                            ];
                        }
                    }
                }
            }

            return [
                'consistent' => empty($issues),
                'issues' => $issues,
                'total_layouts' => $seatLayouts->count()
            ];

        } catch (\Exception $e) {
            Log::error('Error validating layout consistency: ' . $e->getMessage());
            return [
                'consistent' => false,
                'issues' => [['error' => $e->getMessage()]],
                'total_layouts' => 0
            ];
        }
    }
}
