<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAssessmentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_training_id',
        'training_assessment_id',
        'score',
        'is_passed',
        'feedback',
        'assessment_date',
        'assessed_by'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'is_passed' => 'boolean',
        'assessment_date' => 'date',
    ];

    /**
     * Get the employee training for this assessment result.
     */
    public function employeeTraining()
    {
        return $this->belongsTo(EmployeeTraining::class);
    }

    /**
     * Get the training assessment for this result.
     */
    public function trainingAssessment()
    {
        return $this->belongsTo(TrainingAssessment::class);
    }

    /**
     * Get the user who assessed this result.
     */
    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /**
     * Scope a query to only include passed results.
     */
    public function scopePassed($query)
    {
        return $query->where('is_passed', true);
    }

    /**
     * Scope a query to only include failed results.
     */
    public function scopeFailed($query)
    {
        return $query->where('is_passed', false);
    }
}