<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrewAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'operator_bus_id',
        'staff_id',
        'role',
        'assignment_date',
        'start_date',
        'end_date',
        'shift_start_time',
        'shift_end_time',
        'status',
        'notes',
        'additional_details',
    ];

    protected $casts = [
        'assignment_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'shift_start_time' => 'datetime:H:i',
        'shift_end_time' => 'datetime:H:i',
        'additional_details' => 'array',
    ];

    // Relationships
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function operatorBus(): BelongsTo
    {
        return $this->belongsTo(OperatorBus::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('assignment_date', $date);
    }

    public function scopeByBus($query, $busId)
    {
        return $query->where('operator_bus_id', $busId);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function getDurationAttribute()
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date) + 1;
        }
        return null;
    }

    public function getShiftDurationAttribute()
    {
        if ($this->shift_start_time && $this->shift_end_time) {
            $start = \Carbon\Carbon::parse($this->shift_start_time);
            $end = \Carbon\Carbon::parse($this->shift_end_time);
            return $start->diffInHours($end);
        }
        return null;
    }
}