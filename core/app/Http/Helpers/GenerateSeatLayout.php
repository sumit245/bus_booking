<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\Log;

class GenerateSeatLayout
{
    protected $seatLayout;
    protected $seatLayouts;

    public function __construct($seatLayout)
    {
        $this->seatLayout = $seatLayout;
        $this->seatLayouts = $this->seatLayouts();
    }

    public function seatLayouts()
    {
        $layout = ['left' => 0, 'right' => 0];
        $columnCounts = [];

        // 1. Gather all unique column numbers
        foreach ($this->seatLayout['SeatDetails'] as $row) {
            foreach ($row as $seat) {
                if (!empty($seat['ColumnNo']) && is_numeric($seat['ColumnNo'])) {
                    $columnCounts[] = (int) $seat['ColumnNo'];
                }
            }
        }

        // 2. Handle empty layout
        if (empty($columnCounts)) {
            Log::warning('No valid ColumnNo found in SeatDetails.');
            return (object) $layout;
        }

        // 3. Process and split
        $uniqueColumns = array_unique($columnCounts);
        sort($uniqueColumns); // ascending order

        $totalColumns = count($uniqueColumns);
        $middleIndex = (int) floor($totalColumns / 2);

        $leftColumns = array_slice($uniqueColumns, 0, $middleIndex);
        $rightColumns = array_slice($uniqueColumns, $middleIndex);
        $layout['left'] = count($leftColumns);
        $layout['right'] = count($rightColumns);

        return (object) $layout;
    }


    public function generateLayout()
    {
        $html = '';

        // Group seats by deck (Height)
        $decks = $this->groupSeatsByDeck();
        foreach ($decks as $deckNumber => $deckSeats) {
            $html .= '<div class="seat-plan-inner">';
            $html .= '<div class="single">';
            $html .= $this->getDeckHeader($deckNumber);

            // Generate vertical columns
            $verticalRows = $this->arrangeSeatsVertically($deckSeats);

            foreach ($verticalRows as $rowNo => $row) {
                $seatNames = array_map(function ($seat) {
                    return $seat['SeatName'] ?? '';
                }, $row);

                // Generate the row HTML
                $html .= $this->generateSeatRow($row);
            }
            $html .= '</div></div>'; // .single and .seat-plan-inner
        }

        return $html;
    }

    private function groupSeatsByDeck()
    {
        $decks = [];
        foreach ($this->seatLayout['SeatDetails'] as $row) {
            foreach ($row as $seat) {
                // Use IsUpper to determine deck - false for lower deck (0), true for upper deck (1)
                $deckIndex = $seat['IsUpper'] ? 1 : 0;
                $decks[$deckIndex][] = $seat;
            }
        }
        ksort($decks);  // Sort deck levels (0 = lower, 1 = upper)
        return $decks;
    }

    private function generateSeat($seat)
    {
        $classes = [];

        if ($seat['SeatType'] == 1) {
            $classes[] = 'seater';
        } else {
            $classes[] = 'sleeper';
        }
        if (!$seat['SeatStatus'])
            $classes[] = 'booked'; // specify style of booked seat here
        if (!empty($seat['IsLadiesSeat']) && !$seat['SeatStatus']) {
            $classes[] = 'selected-by-ladies'; // specify style of booked seat here
        }
        //if ($seat['Height'] == 2) $classes[] = 'upper'; // specify style of booked seat here

        $price = $seat['Price']['PublishedPrice'] ?? 0;

        return sprintf(
            '<span class="seat %s" data-seat="%s" data-price="%s">' .
            $seat['SeatName']
            . '<span></span>
                </span>',
            implode(' ', $classes),
            $seat['SeatName'],
            $price
        );
    }

    private function getDeckHeader($deckNumber)
    {
        $html = "";

        if ($deckNumber == 0) {
            $html .= '<div class="lower"><br/><span style="font-size:12 !important;">Lower Deck</span></div>';
            // $html .= '<span class="driver"><img src="' . asset('assets/templates/basic/images/icon/wheel.svg') . '" alt="Driver"></span>';
        } else {
            $html .= '<span class="driver">Upper Deck </span>';
        }
        return $html;
    }

    private function generateSeatRow($rowSeats)
    {
        // Log the input data for debugging
        error_log("Generating seat row... Row seats data: " . print_r($rowSeats, true));

        // Initial HTML wrapper
        $html = '<div class="seat-wrapper">';

        // Left section - 3 seats
        $html .= '<div class="left-side">';
        for ($i = 0; $i < $this->seatLayouts->left; $i++) {
            if (isset($rowSeats[$i])) {
                // Log the seat that is being added
                error_log("Adding seat at position " . $i . " from the left side: " . print_r($rowSeats[$i], true));
                $html .= $this->generateSeat($rowSeats[$i]);
            }
        }
        $html .= '</div>';

        // Right section - 2 seats
        $html .= '<div class="right-side">';
        for ($i = $this->seatLayouts->left; $i < ($this->seatLayouts->left + $this->seatLayouts->right); $i++) {
            if (isset($rowSeats[$i])) {
                // Log the seat that is being added
                error_log("Adding seat at position " . $i . " from the right side: " . print_r($rowSeats[$i], true));
                $html .= $this->generateSeat($rowSeats[$i]);
            }
        }
        $html .= '</div>';

        $html .= '</div>';

        // Log the final generated HTML
        error_log("Generated seat row HTML: " . $html);

        return $html;
    }

    private function arrangeSeatsVertically($deckSeats)
    {
        $verticalRows = [];
        $currentRow = [];
        $seatCount = 0;

        foreach ($deckSeats as $seat) {
            $currentRow[] = $seat;
            $seatCount++;

            if ($seatCount == ($this->seatLayouts->left + $this->seatLayouts->right)) {
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
}
