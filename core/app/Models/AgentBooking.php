<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'booked_ticket_id',
        'commission_amount',
        'commission_type',
        'base_amount_paid',
        'total_commission_earned',
        'passenger_amount_charged',
        'booking_status',
        'payment_status',
        'commission_paid_at',
        'commission_details',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'base_amount_paid' => 'decimal:2',
        'total_commission_earned' => 'decimal:2',
        'passenger_amount_charged' => 'decimal:2',
        'commission_paid_at' => 'datetime',
        'commission_details' => 'array',
    ];

    // Relationships
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function bookedTicket()
    {
        return $this->belongsTo(BookedTicket::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('booking_status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('booking_status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('booking_status', 'cancelled');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'pending');
    }

    // Accessors & Mutators
    public function getIsConfirmedAttribute()
    {
        return $this->booking_status === 'confirmed';
    }

    public function getIsPaidAttribute()
    {
        return $this->payment_status === 'paid';
    }

    public function getCommissionPercentageAttribute()
    {
        if ($this->commission_type === 'percentage' && $this->passenger_amount_charged > 0) {
            return round(($this->commission_amount / $this->passenger_amount_charged) * 100, 2);
        }
        return 0;
    }

    public function getAgentProfitAttribute()
    {
        return $this->total_commission_earned;
    }

    public function getPassengerSavingAttribute()
    {
        // Passenger pays base price + commission, but agent only pays base price
        return $this->passenger_amount_charged - $this->base_amount_paid;
    }

    // Methods
    public function markAsConfirmed()
    {
        $this->update(['booking_status' => 'confirmed']);
    }

    public function markAsCancelled()
    {
        $this->update(['booking_status' => 'cancelled']);
    }

    public function markAsPaid()
    {
        $this->update([
            'payment_status' => 'paid',
            'commission_paid_at' => now(),
        ]);

        // Update agent's pending earnings
        $this->agent->markEarningsAsPaid($this->total_commission_earned);
    }

    public function getCommissionBreakdown()
    {
        return [
            'commission_amount' => $this->commission_amount,
            'commission_type' => $this->commission_type,
            'commission_percentage' => $this->commission_percentage,
            'base_amount_paid' => $this->base_amount_paid,
            'passenger_amount_charged' => $this->passenger_amount_charged,
            'agent_profit' => $this->agent_profit,
            'passenger_saving' => $this->passenger_saving,
        ];
    }

    public static function createFromBooking($bookedTicket, $agentId, $commissionDetails)
    {
        return self::create([
            'agent_id' => $agentId,
            'booked_ticket_id' => $bookedTicket->id,
            'commission_amount' => $commissionDetails['commission_amount'],
            'commission_type' => $commissionDetails['commission_type'],
            'base_amount_paid' => $commissionDetails['base_amount_paid'],
            'total_commission_earned' => $commissionDetails['commission_amount'],
            'passenger_amount_charged' => $commissionDetails['passenger_amount_charged'],
            'booking_status' => 'confirmed',
            'payment_status' => 'paid', // Commission is automatically paid via ticket pricing
            'commission_paid_at' => now(),
            'commission_details' => $commissionDetails,
        ]);
    }
}