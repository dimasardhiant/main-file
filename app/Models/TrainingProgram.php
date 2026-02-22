<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'training_type_id',
        'description',
        'duration',
        'cost',
        'capacity',
        'status',
        'materials',
        'prerequisites',
        'is_mandatory',
        'is_self_enrollment',
        'created_by'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'is_self_enrollment' => 'boolean',
    ];

    /**
     * Get the training type of this program.
     */
    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }

    /**
     * Get the user who created this training program.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the sessions for this training program.
     */
    public function sessions()
    {
        return $this->hasMany(TrainingSession::class);
    }

    /**
     * Get the employee trainings for this program.
     */
    public function employeeTrainings()
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    /**
     * Get the assessments for this training program.
     */
    public function assessments()
    {
        return $this->hasMany(TrainingAssessment::class);
    }

    /**
     * Scope a query to only include active training programs.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include draft training programs.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include completed training programs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include cancelled training programs.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include mandatory training programs.
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope a query to only include self-enrollment training programs.
     */
    public function scopeSelfEnrollment($query)
    {
        return $query->where('is_self_enrollment', true);
    }
}