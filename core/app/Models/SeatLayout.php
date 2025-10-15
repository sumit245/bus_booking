<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeatLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_bus_id',
        'layout_name',
        'deck_type',
        'seat_layout',
        'columns_per_row',
        'total_seats',
        'upper_deck_seats',
        'lower_deck_seats',
        'layout_data',
        'html_layout',
        'is_active'
    ];

    protected $casts = [
        'layout_data' => 'array',
        'is_active' => 'boolean',
        'total_seats' => 'integer',
        'upper_deck_seats' => 'integer',
        'lower_deck_seats' => 'integer'
    ];

    /**
     * Get the operator bus that owns this seat layout
     */
    public function operatorBus()
    {
        return $this->belongsTo(OperatorBus::class);
    }

    /**
     * Get the active seat layout for a bus
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Generate HTML layout from layout data
     */
    public function generateHtmlLayout()
    {
        if (!$this->layout_data) {
            return '';
        }

        $html = '';

        // Process upper deck (if exists)
        if (isset($this->layout_data['upper_deck']) && !empty($this->layout_data['upper_deck']['seats'])) {
            $html .= '<div class="outerseat">';
            $html .= $this->generateDeckHtml($this->layout_data['upper_deck'], 'upper');
            $html .= '</div>';
        }

        // Process lower deck (always exists)
        if (isset($this->layout_data['lower_deck'])) {
            $html .= '<div class="outerlowerseat">';
            $html .= $this->generateDeckHtml($this->layout_data['lower_deck'], 'lower');
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Get processed layout data
     */
    public function getProcessedLayoutAttribute()
    {
        return $this->layout_data;
    }

    /**
     * Generate HTML for a specific deck
     */
    private function generateDeckHtml($deckData, $deckType)
    {
        $html = '<div class="busSeatlft"><div class="lower"></div></div>';
        $html .= '<div class="busSeatrgt">';
        $html .= '<div class="busSeat">';
        $html .= '<div class="seatcontainer clearfix">';

        // Handle both old format (rows) and new format (seats array)
        if (isset($deckData['rows'])) {
            foreach ($deckData['rows'] as $rowNumber => $seats) {
                foreach ($seats as $seat) {
                    $html .= $this->generateSeatHtml($seat, $rowNumber);
                }
            }
        } elseif (isset($deckData['seats'])) {
            foreach ($deckData['seats'] as $seat) {
                $html .= $this->generateSeatHtml($seat, $seat['row'] ?? 0);
            }
        }

        $html .= '</div></div></div>';
        return $html;
    }

    /**
     * Generate HTML for individual seat
     */
    private function generateSeatHtml($seat, $rowNumber)
    {
        $seatId = $seat['seat_id'] ?? '';
        $seatType = $seat['type'] ?? 'nseat';
        $price = $seat['price'] ?? 0;
        $position = $seat['position'] ?? 0;
        $left = $seat['left'] ?? 0;


        $style = "top:{$position}px;left:{$left}px;display:block;";
        $onclick = "javascript:AddRemoveSeat(this,'{$seatId}','{$price}')";

        return "<div id=\"{$seatId}\" style=\"{$style}\" class=\"{$seatType}\" onclick=\"{$onclick}\"></div>";
    }

}
