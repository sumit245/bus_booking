<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorBus extends Model
{
    use HasFactory;

    protected $table = 'operator_buses';

    protected $fillable = [
        'operator_id',
        'current_route_id',
        'bus_number',
        'bus_type',
        'service_name',
        'travel_name',
        'total_seats',
        'available_seats',
        'base_price',
        'published_price',
        'offered_price',
        'agent_commission',
        'tax',
        'other_charges',
        'discount',
        'service_charges',
        'tds',
        'cgst_amount',
        'cgst_rate',
        'igst_amount',
        'igst_rate',
        'sgst_amount',
        'sgst_rate',
        'taxable_amount',
        'id_proof_required',
        'is_drop_point_mandatory',
        'live_tracking_available',
        'm_ticket_enabled',
        'max_seats_per_ticket',
        'partial_cancellation_allowed',
        'status',
        'description',
        'amenities',
        'fuel_type',
        'manufacturing_year',
        'insurance_number',
        'insurance_expiry',
        'permit_number',
        'permit_expiry',
        'fitness_certificate',
        'fitness_expiry'
    ];

    protected $casts = [
        'status' => 'boolean',
        'id_proof_required' => 'boolean',
        'is_drop_point_mandatory' => 'boolean',
        'live_tracking_available' => 'boolean',
        'm_ticket_enabled' => 'boolean',
        'partial_cancellation_allowed' => 'boolean',
        'base_price' => 'decimal:2',
        'published_price' => 'decimal:2',
        'offered_price' => 'decimal:2',
        'agent_commission' => 'decimal:2',
        'tax' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'discount' => 'decimal:2',
        'service_charges' => 'decimal:2',
        'tds' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'cgst_rate' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'igst_rate' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'sgst_rate' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'amenities' => 'array',
        'insurance_expiry' => 'date',
        'permit_expiry' => 'date',
        'fitness_expiry' => 'date',
        'manufacturing_year' => 'integer'
    ];

    /**
     * Get the operator that owns the bus.
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Get the current route assigned to this bus.
     */
    public function currentRoute()
    {
        return $this->belongsTo(OperatorRoute::class, 'current_route_id');
    }

    /**
     * Get all routes this bus has been assigned to (for history tracking).
     */
    public function routeHistory()
    {
        return $this->hasMany(BusRouteHistory::class, 'bus_id');
    }

    /**
     * Scope to get only active buses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get buses by operator.
     */
    public function scopeByOperator($query, $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    /**
     * Scope to get buses on a specific route.
     */
    public function scopeOnRoute($query, $routeId)
    {
        return $query->where('current_route_id', $routeId);
    }

    /**
     * Get formatted base price.
     */
    public function getFormattedBasePriceAttribute()
    {
        return $this->base_price ? '₹' . number_format((float) $this->base_price, 2) : 'N/A';
    }

    /**
     * Get formatted published price.
     */
    public function getFormattedPublishedPriceAttribute()
    {
        return $this->published_price ? '₹' . number_format((float) $this->published_price, 2) : 'N/A';
    }

    /**
     * Get formatted offered price.
     */
    public function getFormattedOfferedPriceAttribute()
    {
        return $this->offered_price ? '₹' . number_format((float) $this->offered_price, 2) : 'N/A';
    }

    /**
     * Get bus display name.
     */
    public function getDisplayNameAttribute()
    {
        return $this->bus_number . ' - ' . $this->travel_name;
    }

    /**
     * Get occupancy percentage.
     */
    public function getOccupancyPercentageAttribute()
    {
        if ($this->total_seats == 0) {
            return 0;
        }

        $bookedSeats = $this->total_seats - $this->available_seats;
        return round(($bookedSeats / $this->total_seats) * 100, 1);
    }

    /**
     * Check if bus is available for booking.
     */
    public function getIsAvailableAttribute()
    {
        return $this->status && $this->available_seats > 0;
    }

    /**
     * Get bus age in years.
     */
    public function getAgeAttribute()
    {
        if (!$this->manufacturing_year) {
            return null;
        }

        return date('Y') - $this->manufacturing_year;
    }

    /**
     * Check if any document is expiring soon (within 30 days).
     */
    public function getHasExpiringDocumentsAttribute()
    {
        $today = now();
        $thirtyDaysFromNow = $today->addDays(30);

        return ($this->insurance_expiry && $this->insurance_expiry <= $thirtyDaysFromNow) ||
            ($this->permit_expiry && $this->permit_expiry <= $thirtyDaysFromNow) ||
            ($this->fitness_expiry && $this->fitness_expiry <= $thirtyDaysFromNow);
    }

    /**
     * Get next expiring document.
     */
    public function getNextExpiringDocumentAttribute()
    {
        $documents = [];

        if ($this->insurance_expiry) {
            $documents[] = ['type' => 'Insurance', 'date' => $this->insurance_expiry];
        }

        if ($this->permit_expiry) {
            $documents[] = ['type' => 'Permit', 'date' => $this->permit_expiry];
        }

        if ($this->fitness_expiry) {
            $documents[] = ['type' => 'Fitness Certificate', 'date' => $this->fitness_expiry];
        }

        if (empty($documents)) {
            return null;
        }

        // Sort by date and return the earliest
        usort($documents, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        return $documents[0];
    }

    /**
     * Get the seat layouts for this bus
     */
    public function seatLayouts()
    {
        return $this->hasMany(SeatLayout::class);
    }

    /**
     * Get the active seat layout for this bus
     */
    public function activeSeatLayout()
    {
        return $this->hasOne(SeatLayout::class)->where('is_active', true);
    }
}