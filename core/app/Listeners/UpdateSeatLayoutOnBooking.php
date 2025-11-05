<?php

namespace App\Listeners;

use App\Services\SeatLayoutUpdater;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * DISABLED: This listener is no longer used.
 * 
 * Seat availability is now calculated dynamically using SeatAvailabilityService
 * which queries bookings in real-time per schedule/date/route segment.
 * 
 * The old approach of modifying HTML layout in the database was incorrect because:
 * - Seat availability is dynamic per schedule and date
 * - Route segments can overlap (e.g., Patna->Delhi vs Patna->Intermediate)
 * - A single HTML layout cannot represent all possible booking states
 * 
 * If you need to re-enable this, register it in EventServiceProvider.
 */
class UpdateSeatLayoutOnBooking implements ShouldQueue
{
    /**
     * The seat layout updater service.
     *
     * @var SeatLayoutUpdater
     */
    protected $seatLayoutUpdater;

    /**
     * Create the event listener.
     */
    public function __construct(SeatLayoutUpdater $seatLayoutUpdater)
    {
        $this->seatLayoutUpdater = $seatLayoutUpdater;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        try {
            // Extract bus ID from the event
            $busId = $this->extractBusIdFromEvent($event);

            if (!$busId) {
                Log::warning('UpdateSeatLayoutOnBooking: Could not extract bus ID from event');
                return;
            }

            Log::info('UpdateSeatLayoutOnBooking: Triggering immediate seat layout update for bus ' . $busId);

            // Update seat layout for this specific bus
            $result = $this->seatLayoutUpdater->syncByBusId($busId);

            if ($result['updated'] > 0) {
                Log::info('UpdateSeatLayoutOnBooking: Successfully updated seat layout for bus ' . $busId, [
                    'seats_updated' => $result['seats_updated'] ?? []
                ]);
            } else {
                Log::info('UpdateSeatLayoutOnBooking: No updates needed for bus ' . $busId);
            }

        } catch (\Exception $e) {
            Log::error('UpdateSeatLayoutOnBooking: Error updating seat layout', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Don't rethrow the exception to prevent breaking the booking process
        }
    }

    /**
     * Extract bus ID from various event types
     */
    private function extractBusIdFromEvent($event): ?int
    {
        // Handle different event types
        if (isset($event->bookedTicket)) {
            $booking = $event->bookedTicket;
        } elseif (isset($event->booking)) {
            $booking = $event->booking;
        } elseif (isset($event->ticket)) {
            $booking = $event->ticket;
        } elseif (method_exists($event, 'getBookedTicket')) {
            $booking = $event->getBookedTicket();
        } else {
            // Try to get booking from event properties
            $booking = $event;
        }

        // Extract bus ID from booking
        if (isset($booking->bus_id)) {
            return (int) $booking->bus_id;
        }

        if (isset($booking->operator_bus_id)) {
            return (int) $booking->operator_bus_id;
        }

        // Try to extract from bus_details JSON
        if (isset($booking->bus_details) && is_string($booking->bus_details)) {
            $busDetails = json_decode($booking->bus_details, true);
            if (isset($busDetails['bus_id'])) {
                return (int) $busDetails['bus_id'];
            }
        }

        return null;
    }
}
