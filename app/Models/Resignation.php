<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resignation extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'resignation_date',
        'last_working_day',
        'notice_period',
        'reason',
        'description',
        'status',
        'documents',
        'approved_by',
        'approved_at',
        'exit_feedback',
        'exit_interview_conducted',
        'exit_interview_date',
        'created_by'
    ];

    protected $casts = [
        'resignation_date' => 'date',
        'last_working_day' => 'date',
        'approved_at' => 'datetime',
        'exit_interview_conducted' => 'boolean',
        'exit_interview_date' => 'date',
    ];

    /**
     * Get the employee who submitted this resignation.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the user who approved this resignation.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this resignation.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}