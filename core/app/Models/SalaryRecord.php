<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'staff_id',
        'salary_period',
        'period_start_date',
        'period_end_date',
        'status',
        'basic_salary',
        'allowances',
        'overtime_amount',
        'bonus',
        'incentives',
        'gross_salary',
        'late_deduction',
        'absent_deduction',
        'advance_deduction',
        'other_deductions',
        'total_deductions',
        'net_salary',
        'amount_paid',
        'balance_amount',
        'payment_method',
        'payment_reference',
        'payment_date',
        'payment_notes',
        'calculated_by',
        'calculated_at',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'notes',
        'additional_data',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'payment_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'bonus' => 'decimal:2',
        'incentives' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'absent_deduction' => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'calculated_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'additional_data' => 'array',
    ];

    // Relationships
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'calculated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'paid_by');
    }

    // Scopes
    public function scopeByPeriod($query, $period)
    {
        return $query->where('salary_period', $period);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // Methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isCalculated()
    {
        return $this->status === 'calculated';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function calculateGrossSalary()
    {
        $this->gross_salary = $this->basic_salary + $this->allowances + $this->overtime_amount + $this->bonus + $this->incentives;
        return $this->gross_salary;
    }

    public function calculateDeductions()
    {
        $this->total_deductions = $this->late_deduction + $this->absent_deduction + $this->advance_deduction + $this->other_deductions;
        return $this->total_deductions;
    }

    public function calculateNetSalary()
    {
        $this->net_salary = $this->gross_salary - $this->total_deductions;
        return $this->net_salary;
    }

    public function calculateBalance()
    {
        $this->balance_amount = $this->net_salary - $this->amount_paid;
        return $this->balance_amount;
    }

    public function calculateAll()
    {
        $this->calculateGrossSalary();
        $this->calculateDeductions();
        $this->calculateNetSalary();
        $this->calculateBalance();
    }

    public function markAsCalculated($calculatedBy)
    {
        $this->update([
            'status' => 'calculated',
            'calculated_by' => $calculatedBy,
            'calculated_at' => now(),
        ]);
    }

    public function approve($approvedBy)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid($paidBy, $paymentMethod = null, $paymentReference = null, $notes = null)
    {
        $this->update([
            'status' => 'paid',
            'amount_paid' => $this->net_salary,
            'balance_amount' => 0,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
            'payment_date' => now()->toDateString(),
            'payment_notes' => $notes,
            'paid_by' => $paidBy,
            'paid_at' => now(),
        ]);
    }

    // Static methods
    public static function generateSalaryPeriod($year, $month)
    {
        return sprintf('%04d-%02d', $year, $month);
    }

    public static function getPeriodDates($period)
    {
        $year = substr($period, 0, 4);
        $month = substr($period, 5, 2);

        return [
            'start' => \Carbon\Carbon::create($year, $month, 1)->toDateString(),
            'end' => \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
        ];
    }
}