<?php

namespace App\Events;

use App\Models\BookedTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The booked ticket instance.
     *
     * @var BookedTicket
     */
    public $bookedTicket;

    /**
     * Create a new event instance.
     *
     * @param BookedTicket $bookedTicket
     */
    public function __construct(BookedTicket $bookedTicket)
    {
        $this->bookedTicket = $bookedTicket;
    }

    /**
     * Get the booked ticket.
     *
     * @return BookedTicket
     */
    public function getBookedTicket(): BookedTicket
    {
        return $this->bookedTicket;
    }
}
