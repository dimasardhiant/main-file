<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Termination extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'termination_type',
        'termination_date',
        'notice_date',
        'notice_period',
        'reason',
        'description',
        'status',
        'documents',
        'approved_by',
        'approved_at',
        'exit_interview_conducted',
        'exit_interview_date',
        'exit_feedback',
        'created_by'
    ];

    protected $casts = [
        'termination_date' => 'date',
        'notice_date' => 'date',
        'approved_at' => 'datetime',
        'exit_interview_conducted' => 'boolean',
        'exit_interview_date' => 'date',
    ];

    /**
     * Get the employee who is being terminated.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the user who approved this termination.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this termination.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}