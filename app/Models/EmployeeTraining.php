<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTraining extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'training_program_id',
        'status',
        'assigned_date',
        'completion_date',
        'certification',
        'score',
        'is_passed',
        'feedback',
        'notes',
        'assigned_by',
        'created_by',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'completion_date' => 'date',
        'score' => 'decimal:2',
        'is_passed' => 'boolean',
    ];

    /**
     * Get the employee for this training assignment.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the training program for this assignment.
     */
    public function trainingProgram()
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    /**
     * Get the user who assigned this training.
     */
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the assessment results for this employee training.
     */
    public function assessmentResults()
    {
        return $this->hasMany(EmployeeAssessmentResult::class);
    }

    /**
     * Scope a query to only include assigned trainings.
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    /**
     * Scope a query to only include in-progress trainings.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope a query to only include completed trainings.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed trainings.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include passed trainings.
     */
    public function scopePassed($query)
    {
        return $query->where('is_passed', true);
    }
}