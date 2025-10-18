<?php

namespace App\Services;

use App\Services\SeatLayoutUpdater;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class SeatLayoutUpdateTrigger
{
    /**
     * The seat layout updater service.
     *
     * @var SeatLayoutUpdater
     */
    protected $seatLayoutUpdater;

    /**
     * Create a new service instance.
     */
    public function __construct(SeatLayoutUpdater $seatLayoutUpdater)
    {
        $this->seatLayoutUpdater = $seatLayoutUpdater;
    }

    /**
     * Trigger immediate seat layout update for a specific bus
     * This is a safe method that can be called from anywhere without breaking existing functionality
     */
    public function triggerUpdateForBus(int $busId, bool $async = true): void
    {
        try {
            if ($async) {
                // Queue the update to avoid blocking the main request
                Queue::push(function () use ($busId) {
                    $this->seatLayoutUpdater->syncByBusId($busId);
                });

                Log::info('SeatLayoutUpdateTrigger: Queued seat layout update for bus ' . $busId);
            } else {
                // Immediate update (use with caution)
                $result = $this->seatLayoutUpdater->syncByBusId($busId);

                Log::info('SeatLayoutUpdateTrigger: Immediate seat layout update for bus ' . $busId, [
                    'updated' => $result['updated'] ?? 0,
                    'errors' => $result['errors'] ?? 0
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SeatLayoutUpdateTrigger: Error triggering seat layout update', [
                'bus_id' => $busId,
                'error' => $e->getMessage()
            ]);

            // Don't throw exception to prevent breaking the calling code
        }
    }

    /**
     * Trigger immediate seat layout update for multiple buses
     */
    public function triggerUpdateForBuses(array $busIds, bool $async = true): void
    {
        foreach ($busIds as $busId) {
            $this->triggerUpdateForBus($busId, $async);
        }
    }

    /**
     * Trigger seat layout update based on booked ticket
     */
    public function triggerUpdateForBooking($booking, bool $async = true): void
    {
        $busId = $this->extractBusIdFromBooking($booking);

        if ($busId) {
            $this->triggerUpdateForBus($busId, $async);
        } else {
            Log::warning('SeatLayoutUpdateTrigger: Could not extract bus ID from booking');
        }
    }

    /**
     * Extract bus ID from booking object
     */
    private function extractBusIdFromBooking($booking): ?int
    {
        if (isset($booking->bus_id)) {
            return (int) $booking->bus_id;
        }

        if (isset($booking->operator_bus_id)) {
            return (int) $booking->operator_bus_id;
        }

        if (isset($booking->bus_details) && is_string($booking->bus_details)) {
            $busDetails = json_decode($booking->bus_details, true);
            if (isset($busDetails['bus_id'])) {
                return (int) $busDetails['bus_id'];
            }
        }

        return null;
    }

    /**
     * Static method for easy calling from anywhere
     */
    public static function updateBusSeatLayout(int $busId, bool $async = true): void
    {
        $trigger = app(self::class);
        $trigger->triggerUpdateForBus($busId, $async);
    }

    /**
     * Static method for easy calling from anywhere with booking
     */
    public static function updateSeatLayoutForBooking($booking, bool $async = true): void
    {
        $trigger = app(self::class);
        $trigger->triggerUpdateForBooking($booking, $async);
    }
}
