<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warning extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'warning_by',
        'warning_type',
        'subject',
        'severity',
        'warning_date',
        'description',
        'status',
        'documents',
        'acknowledgment_date',
        'employee_response',
        'approved_by',
        'approved_at',
        'expiry_date',
        'has_improvement_plan',
        'improvement_plan_goals',
        'improvement_plan_start_date',
        'improvement_plan_end_date',
        'improvement_plan_progress',
        'created_by'
    ];

    protected $casts = [
        'warning_date' => 'date',
        'acknowledgment_date' => 'date',
        'approved_at' => 'datetime',
        'expiry_date' => 'date',
        'has_improvement_plan' => 'boolean',
        'improvement_plan_start_date' => 'date',
        'improvement_plan_end_date' => 'date',
    ];

    /**
     * Get the employee who received this warning.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the user who issued this warning.
     */
    public function issuer()
    {
        return $this->belongsTo(User::class, 'warning_by');
    }

    /**
     * Get the user who approved this warning.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this warning.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}