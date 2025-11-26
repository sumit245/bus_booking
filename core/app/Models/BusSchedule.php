<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BusSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'operator_bus_id',
        'operator_route_id',
        'schedule_name',
        'departure_time',
        'arrival_time',
        'estimated_duration_minutes',
        'days_of_operation',
        'is_daily',
        'start_date',
        'end_date',
        'is_active',
        'status',
        'notes',
        'sort_order'
    ];

    protected $casts = [
        'days_of_operation' => 'array',
        'is_daily' => 'boolean',
        'is_active' => 'boolean',
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relationships
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function operatorBus()
    {
        return $this->belongsTo(OperatorBus::class);
    }

    public function operatorRoute()
    {
        return $this->belongsTo(OperatorRoute::class);
    }

    /**
     * Get the boarding points for this schedule.
     */
    public function boardingPoints()
    {
        return $this->hasMany(BoardingPoint::class)->orderBy('point_index');
    }

    /**
     * Get the dropping points for this schedule.
     */
    public function droppingPoints()
    {
        return $this->hasMany(DroppingPoint::class)->orderBy('point_index');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    public function scopeByOperator($query, $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    public function scopeByBus($query, $busId)
    {
        return $query->where('operator_bus_id', $busId);
    }

    public function scopeByRoute($query, $routeId)
    {
        return $query->where('operator_route_id', $routeId);
    }

    public function scopeForDate($query, $date)
    {
        $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));

        return $query->where(function ($q) use ($date, $dayOfWeek) {
            $q->where('is_daily', true)
                ->orWhereJsonContains('days_of_operation', $dayOfWeek);
        })->where(function ($q) use ($date) {
            $q->whereNull('start_date')
                ->orWhere('start_date', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $date);
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('departure_time');
    }

    // Accessors & Mutators
    public function getFormattedDepartureTimeAttribute()
    {
        return Carbon::parse($this->departure_time)->format('H:i');
    }

    public function getFormattedArrivalTimeAttribute()
    {
        return Carbon::parse($this->arrival_time)->format('H:i');
    }

    public function getDurationAttribute()
    {
        if ($this->estimated_duration_minutes) {
            $hours = floor($this->estimated_duration_minutes / 60);
            $minutes = $this->estimated_duration_minutes % 60;
            return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
        }
        return null;
    }

    public function getDaysOfOperationTextAttribute()
    {
        if ($this->is_daily) {
            return 'Daily';
        }

        if (!$this->days_of_operation) {
            return 'Not set';
        }

        $dayNames = [
            'monday' => 'Mon',
            'tuesday' => 'Tue',
            'wednesday' => 'Wed',
            'thursday' => 'Thu',
            'friday' => 'Fri',
            'saturday' => 'Sat',
            'sunday' => 'Sun'
        ];

        $days = collect($this->days_of_operation)->map(function ($day) use ($dayNames) {
            return $dayNames[$day] ?? $day;
        });

        return $days->implode(', ');
    }

    // Helper Methods
    public function isOperatingOn($date)
    {
        $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));

        // Check if daily or specific day
        if (!$this->is_daily && !in_array($dayOfWeek, $this->days_of_operation ?? [])) {
            return false;
        }

        // Check date range
        if ($this->start_date && Carbon::parse($date)->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && Carbon::parse($date)->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    public function calculateDuration()
    {
        $departure = Carbon::parse($this->departure_time);
        $arrival = Carbon::parse($this->arrival_time);

        // Handle next day arrival
        if ($arrival->lt($departure)) {
            $arrival->addDay();
        }

        $this->estimated_duration_minutes = $departure->diffInMinutes($arrival);
        return $this->estimated_duration_minutes;
    }

    public function generateResultIndex()
    {
        // Generate unique result index for this schedule
        // Format: OP_{bus_id}_{schedule_id}
        return "OP_{$this->operator_bus_id}_{$this->id}";
    }

    // Boot method to auto-calculate duration
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($schedule) {
            if ($schedule->departure_time && $schedule->arrival_time) {
                $schedule->calculateDuration();
            }
        });
    }
}