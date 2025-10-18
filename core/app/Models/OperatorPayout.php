<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OperatorPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'total_revenue',
        'platform_fee',
        'payment_gateway_fee',
        'tds_amount',
        'other_deductions',
        'net_payable',
        'amount_paid',
        'pending_amount',
        'payment_status',
        'payout_period_start',
        'payout_period_end',
        'paid_date',
        'payment_method',
        'transaction_reference',
        'payment_notes',
        'admin_notes',
        'created_by_admin_id'
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'payment_gateway_fee' => 'decimal:2',
        'tds_amount' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'pending_amount' => 'decimal:2',
        'payout_period_start' => 'date',
        'payout_period_end' => 'date',
        'paid_date' => 'date'
    ];

    // Relationships
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function createdByAdmin()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    // Scopes
    public function scopeForOperator($query, $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopePartial($query)
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('payout_period_start', '>=', $startDate)
            ->where('payout_period_end', '<=', $endDate);
    }

    // Accessors
    public function getPayoutPeriodAttribute()
    {
        return $this->payout_period_start->format('M d, Y') . ' - ' . $this->payout_period_end->format('M d, Y');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'partial' => 'info',
            'paid' => 'success',
            'cancelled' => 'danger'
        ];

        return $badges[$this->payment_status] ?? 'secondary';
    }

    public function getFormattedNetPayableAttribute()
    {
        return '₹' . number_format($this->net_payable, 2);
    }

    public function getFormattedAmountPaidAttribute()
    {
        return '₹' . number_format($this->amount_paid, 2);
    }

    public function getFormattedPendingAmountAttribute()
    {
        return '₹' . number_format($this->pending_amount, 2);
    }

    // Methods
    public function isPending()
    {
        return $this->payment_status === 'pending';
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    public function isPartial()
    {
        return $this->payment_status === 'partial';
    }

    public function isCancelled()
    {
        return $this->payment_status === 'cancelled';
    }

    public function markAsPaid($amount, $paymentMethod = null, $transactionRef = null, $notes = null)
    {
        $this->update([
            'amount_paid' => $this->amount_paid + $amount,
            'pending_amount' => $this->net_payable - ($this->amount_paid + $amount),
            'payment_status' => ($this->amount_paid + $amount) >= $this->net_payable ? 'paid' : 'partial',
            'paid_date' => $this->paid_date ?: now(),
            'payment_method' => $paymentMethod ?: $this->payment_method,
            'transaction_reference' => $transactionRef ?: $this->transaction_reference,
            'payment_notes' => $notes ?: $this->payment_notes
        ]);
    }

    public function calculateDeductions()
    {
        // Calculate platform fee (e.g., 5% of total revenue)
        $platformFee = $this->total_revenue * 0.05;

        // Calculate payment gateway fee (e.g., 2% of total revenue)
        $gatewayFee = $this->total_revenue * 0.02;

        // Calculate TDS (e.g., 10% of net amount)
        $netBeforeTds = $this->total_revenue - $platformFee - $gatewayFee;
        $tdsAmount = $netBeforeTds * 0.10;

        $this->update([
            'platform_fee' => $platformFee,
            'payment_gateway_fee' => $gatewayFee,
            'tds_amount' => $tdsAmount,
            'net_payable' => $netBeforeTds - $tdsAmount,
            'pending_amount' => $netBeforeTds - $tdsAmount
        ]);
    }

    public static function generatePayoutForPeriod($operatorId, $startDate, $endDate)
    {
        // Check if payout already exists for this period
        $existingPayout = static::forOperator($operatorId)
            ->where('payout_period_start', $startDate)
            ->where('payout_period_end', $endDate)
            ->first();

        if ($existingPayout) {
            return $existingPayout;
        }

        // Calculate total revenue for the period
        $totalRevenue = BookedTicket::where('operator_id', $operatorId)
            ->whereBetween('date_of_journey', [$startDate, $endDate])
            ->whereIn('status', [0, 1, 2])
            ->sum('total_amount');

        // Create new payout record
        $payout = static::create([
            'operator_id' => $operatorId,
            'total_revenue' => $totalRevenue,
            'payout_period_start' => $startDate,
            'payout_period_end' => $endDate,
            'payment_status' => 'pending'
        ]);

        // Calculate deductions
        $payout->calculateDeductions();

        return $payout;
    }
}
