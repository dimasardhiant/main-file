<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_program_id',
        'name',
        'description',
        'type',
        'passing_score',
        'criteria',
        'created_by'
    ];

    protected $casts = [
        'passing_score' => 'decimal:2',
    ];

    /**
     * Get the training program for this assessment.
     */
    public function trainingProgram()
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    /**
     * Get the user who created this assessment.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the employee assessment results for this assessment.
     */
    public function employeeResults()
    {
        return $this->hasMany(EmployeeAssessmentResult::class);
    }

    /**
     * Scope a query to only include quiz assessments.
     */
    public function scopeQuiz($query)
    {
        return $query->where('type', 'quiz');
    }

    /**
     * Scope a query to only include practical assessments.
     */
    public function scopePractical($query)
    {
        return $query->where('type', 'practical');
    }

    /**
     * Scope a query to only include presentation assessments.
     */
    public function scopePresentation($query)
    {
        return $query->where('type', 'presentation');
    }
}