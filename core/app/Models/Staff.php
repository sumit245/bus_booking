<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'whatsapp_number',
        'role',
        'gender',
        'date_of_birth',
        'address',
        'city',
        'state',
        'pincode',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'joining_date',
        'employment_type',
        'basic_salary',
        'allowances',
        'total_salary',
        'salary_frequency',
        'aadhar_number',
        'pan_number',
        'driving_license_number',
        'driving_license_expiry',
        'passport_number',
        'passport_expiry',
        'profile_photo',
        'aadhar_document',
        'pan_document',
        'driving_license_document',
        'passport_document',
        'other_documents',
        'is_active',
        'whatsapp_notifications_enabled',
        'notes',
        'preferences',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'driving_license_expiry' => 'date',
        'passport_expiry' => 'date',
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'total_salary' => 'decimal:2',
        'is_active' => 'boolean',
        'whatsapp_notifications_enabled' => 'boolean',
        'other_documents' => 'array',
        'preferences' => 'array',
    ];

    // Relationships
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function crewAssignments(): HasMany
    {
        return $this->hasMany(CrewAssignment::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function salaryRecords(): HasMany
    {
        return $this->hasMany(SalaryRecord::class);
    }

    public function assignedBuses(): HasManyThrough
    {
        return $this->hasManyThrough(OperatorBus::class, CrewAssignment::class, 'staff_id', 'id', 'id', 'operator_bus_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByOperator($query, $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getDisplayNameAttribute()
    {
        return $this->full_name . ' (' . $this->employee_id . ')';
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getExperienceAttribute()
    {
        return $this->joining_date ? $this->joining_date->diffInYears(now()) : 0;
    }

    // Methods
    public function isDriver()
    {
        return $this->role === 'driver';
    }

    public function isConductor()
    {
        return $this->role === 'conductor';
    }

    public function isAttendant()
    {
        return $this->role === 'attendant';
    }

    public function canReceiveWhatsAppNotifications()
    {
        return $this->whatsapp_notifications_enabled && !empty($this->whatsapp_number);
    }

    public function getCurrentAssignment($date = null)
    {
        $date = $date ?: now()->toDateString();

        return $this->crewAssignments()
            ->where('assignment_date', $date)
            ->where('status', 'active')
            ->with('operatorBus')
            ->first();
    }

    public function getAttendanceForMonth($year, $month)
    {
        return $this->attendance()
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get();
    }

    public function getAttendanceStats($year, $month)
    {
        $attendance = $this->getAttendanceForMonth($year, $month);

        return [
            'total_days' => $attendance->count(),
            'present_days' => $attendance->where('status', 'present')->count(),
            'absent_days' => $attendance->where('status', 'absent')->count(),
            'late_days' => $attendance->where('status', 'late')->count(),
            'half_days' => $attendance->where('status', 'half_day')->count(),
            'leave_days' => $attendance->whereIn('status', ['on_leave', 'sick_leave', 'emergency_leave'])->count(),
            'total_hours' => $attendance->sum('hours_worked'),
            'overtime_hours' => $attendance->sum('overtime_hours'),
        ];
    }

    // Static methods
    public static function generateEmployeeId($operatorId, $role)
    {
        $prefix = strtoupper(substr($role, 0, 3)); // DRV, CON, ATT, etc.
        $operator = Operator::find($operatorId);
        $operatorCode = strtoupper(substr($operator->company_name, 0, 3));

        $lastStaff = self::where('operator_id', $operatorId)
            ->where('role', $role)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastStaff ? (intval(substr($lastStaff->employee_id, -4)) + 1) : 1;

        return $operatorCode . $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}