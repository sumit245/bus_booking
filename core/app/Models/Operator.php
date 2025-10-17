<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Operator extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'address',
        'company_name',
        'city',
        'state',
        'photo',
        'pan_card',
        'aadhaar_card_front',
        'aadhaar_card_back',
        'driving_license',
        'business_license',
        'cancelled_cheque',
        'bank_details',
        'status',
        'basic_details_completed',
        'company_details_completed',
        'documents_completed',
        'bank_details_completed',
        'all_details_completed',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'gst_number',
        'bank_name'
    ];

    protected $casts = [
        'bank_details' => 'json',
        'status' => 'boolean',
        'basic_details_completed' => 'boolean',
        'company_details_completed' => 'boolean',
        'documents_completed' => 'boolean',
        'bank_details_completed' => 'boolean',
        'all_details_completed' => 'boolean'
    ];

    protected $hidden = [
        'password'
    ];

    /**
     * Get the user that owns the operator.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active operators.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include inactive operators.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope a query to only include operators with all details completed.
     */
    public function scopeFullyCompleted($query)
    {
        return $query->where('all_details_completed', 1);
    }

    /**
     * Scope a query to only include operators with incomplete details.
     */
    public function scopeIncomplete($query)
    {
        return $query->where('all_details_completed', 0);
    }

    /**
     * Check if basic details are completed.
     */
    public function hasBasicDetails()
    {
        return !empty($this->name) && !empty($this->email) && !empty($this->mobile) && !empty($this->address) && !empty($this->password);
    }

    /**
     * Check if company details are completed.
     */
    public function hasCompanyDetails()
    {
        return !empty($this->company_name) && !empty($this->city) && !empty($this->state);
    }

    /**
     * Check if documents are completed.
     */
    public function hasDocuments()
    {
        return !empty($this->photo) && !empty($this->pan_card) &&
            !empty($this->aadhaar_card_front) && !empty($this->aadhaar_card_back) &&
            !empty($this->business_license);
    }

    /**
     * Check if bank details are completed.
     */
    public function hasBankDetails()
    {
        return !empty($this->account_holder_name) && !empty($this->account_number) &&
            !empty($this->ifsc_code) && !empty($this->bank_name) && !empty($this->cancelled_cheque);
    }

    /**
     * Get the routes for this operator.
     */
    public function routes()
    {
        return $this->hasMany(OperatorRoute::class);
    }

    /**
     * Get the active routes for this operator.
     */
    public function activeRoutes()
    {
        return $this->hasMany(OperatorRoute::class)->where('status', 1);
    }

    /**
     * Get all buses owned by this operator.
     */
    public function buses()
    {
        return $this->hasMany(OperatorBus::class);
    }

    /**
     * Get active buses owned by this operator.
     */
    public function activeBuses()
    {
        return $this->buses()->where('status', 1);
    }

    /**
     * Get all staff members for this operator.
     */
    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Get active staff members for this operator.
     */
    public function activeStaff()
    {
        return $this->staff()->where('is_active', true);
    }

    /**
     * Get the default travel name for this operator.
     */
    public function getDefaultTravelNameAttribute()
    {
        if ($this->company_name) {
            return $this->company_name;
        }

        $firstName = $this->first_name ?: 'Operator';
        $lastName = $this->last_name ?: '';
        return trim($firstName . ' ' . $lastName) . ' Travels';
    }

    /**
     * Update completion status based on current data.
     */
    public function updateCompletionStatus()
    {
        $this->basic_details_completed = $this->hasBasicDetails();
        $this->company_details_completed = $this->hasCompanyDetails();
        $this->documents_completed = $this->hasDocuments();
        $this->bank_details_completed = $this->hasBankDetails();
        $this->all_details_completed = $this->basic_details_completed &&
            $this->company_details_completed &&
            $this->documents_completed &&
            $this->bank_details_completed;

        // Only activate if all details are completed
        if ($this->all_details_completed) {
            $this->status = 1;
        }

        $this->save();
        return $this;
    }
}