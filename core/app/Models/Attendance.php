<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'operator_id',
        'staff_id',
        'crew_assignment_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'status',
        'hours_worked',
        'overtime_hours',
        'notes',
        'check_in_location',
        'check_out_location',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'hours_worked' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
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

    public function crewAssignment(): BelongsTo
    {
        return $this->belongsTo(CrewAssignment::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->where('attendance_date', $date);
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    // Methods
    public function isPresent()
    {
        return $this->status === 'present';
    }

    public function isAbsent()
    {
        return $this->status === 'absent';
    }

    public function isLate()
    {
        return $this->status === 'late';
    }

    public function isOnLeave()
    {
        return in_array($this->status, ['on_leave', 'sick_leave', 'emergency_leave']);
    }

    public function getTotalHoursAttribute()
    {
        return $this->hours_worked + $this->overtime_hours;
    }

    public function calculateHoursWorked()
    {
        if ($this->check_in_time && $this->check_out_time) {
            $checkIn = \Carbon\Carbon::parse($this->check_in_time);
            $checkOut = \Carbon\Carbon::parse($this->check_out_time);

            $totalMinutes = $checkOut->diffInMinutes($checkIn);
            $this->hours_worked = round($totalMinutes / 60, 2);

            // Calculate overtime (assuming 8 hours is standard)
            if ($this->hours_worked > 8) {
                $this->overtime_hours = $this->hours_worked - 8;
                $this->hours_worked = 8;
            } else {
                $this->overtime_hours = 0;
            }
        }
    }

    public function approve($approvedBy)
    {
        $this->update([
            'is_approved' => true,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }
}