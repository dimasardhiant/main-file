<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'leave_type_id',
        'accrual_type',
        'accrual_rate',
        'carry_forward_limit',
        'min_days_per_application',
        'max_days_per_application',
        'requires_approval',
        'status',
        'created_by'
    ];

    protected $casts = [
        'accrual_rate' => 'decimal:2',
        'requires_approval' => 'boolean',
    ];

    /**
     * Get the leave type that owns the policy.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the user who created the policy.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}