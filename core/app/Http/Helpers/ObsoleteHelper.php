<?php

namespace App\Http\Helpers;

class GenerateSeatLayout
{
    protected $seatLayout;
    protected $sitLayouts;
    protected $deckNumber;
    protected $seatNumber;

    public function __construct($seatLayout)
    {
        $this->seatLayout = $seatLayout;
        $this->sitLayouts = $this->sitLayouts();
    }

    public function sitLayouts()
    {
        // 3x2 layout configuration
        $layout['left'] = 3;
        $layout['right'] = 2;
        return (object)$layout;
    }

    public function generateLayout()
    {
        $html = '';
        // Group seats by deck
        $decks = $this->groupSeatsByDeck();

        foreach ($decks as $deckNumber => $deckSeats) {
            $html .= '<div class="seat-plan-inner">';
            $html .= '<div class="single">';
            $html .= $this->getDeckHeader($deckNumber - 1);

            // Generate vertical columns
            $verticalRows = $this->arrangeSeatsVertically($deckSeats);
            foreach ($verticalRows as $row) {
                $html .= $this->generateSeatRow($row);
            }

            $html .= '</div></div>';
        }
        return $html;
    }

    private function groupSeatsByDeck()
    {
        $decks = [];
        foreach ($this->seatLayout['SeatDetails'] as $row) {
            foreach ($row as $seat) {
                $decks[$seat['Height']][] = $seat;
            }
        }
        ksort($decks);
        return $decks;
    }

    private function arrangeSeatsVertically($deckSeats)
    {
        $verticalRows = [];
        $currentRow = [];
        $seatCount = 0;

        foreach ($deckSeats as $seat) {
            $currentRow[] = $seat;
            $seatCount++;

            if ($seatCount == ($this->sitLayouts->left + $this->sitLayouts->right)) {
                $verticalRows[] = $currentRow;
                $currentRow = [];
                $seatCount = 0;
            }
        }

        if (!empty($currentRow)) {
            $verticalRows[] = $currentRow;
        }

        return $verticalRows;
    }

    private function generateSeatRow($rowSeats)
    {
        $html = '<div class="seat-wrapper">';

        // Left section - 3 seats
        $html .= '<div class="left-side">';
        for ($i = 0; $i < $this->sitLayouts->left; $i++) {
            if (isset($rowSeats[$i])) {
                $html .= $this->generateSeat($rowSeats[$i]);
            }
        }
        $html .= '</div>';

        // Right section - 2 seats
        $html .= '<div class="right-side">';
        for ($i = $this->sitLayouts->left; $i < ($this->sitLayouts->left + $this->sitLayouts->right); $i++) {
            if (isset($rowSeats[$i])) {
                $html .= $this->generateSeat($rowSeats[$i]);
            }
        }
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    private function generateSeat($seatData)
    {
        return sprintf(
            '<div><span class="seat %s" data-seat="%s" data-price="%s">%s<span></span></span></div>',
            $this->getSeatClasses($seatData),
            $seatData['SeatName'],
            $seatData['Price']['PublishedPrice'],
            $seatData['SeatName']
        );
    }

    private function getSeatClasses($seatData)
    {
        $classes = [];
        if (!$seatData['SeatStatus']) $classes[] = 'booked';
        if ($seatData['IsLadiesSeat']) $classes[] = 'ladies';
        if ($seatData['Height'] == 2) $classes[] = 'upper';

        return implode(' ', $classes);
    }

    private function getDeckHeader($deckNumber)
    {
        $html = '<span class="front">Front</span><span class="rear">Rear</span>';

        if ($deckNumber == 0) {
            $html .= '<span class="lower">Door</span>';
            $html .= '<span class="driver"><img src="' . getImage('assets/templates/basic/images/icon/wheel.svg') . '" alt="icon"></span>';
        } else {
            $html .= '<span class="driver">Deck : ' . ($deckNumber + 1) . '</span>';
        }
        return $html;
    }
}
