<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingSession extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'training_program_id',
        'name',
        'start_date',
        'end_date',
        'location',
        'location_type',
        'meeting_link',
        'status',
        'notes',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_count',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the training program for this session.
     */
    public function trainingProgram()
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    /**
     * Get the user who created this training session.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the trainers for this session.
     */
    public function trainers()
    {
        return $this->belongsToMany(User::class, 'training_session_trainer', 'training_session_id', 'employee_id');
    }

    /**
     * Get the attendance records for this session.
     */
    public function attendance()
    {
        return $this->hasMany(TrainingSessionAttendance::class);
    }

    /**
     * Scope a query to only include scheduled sessions.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include in-progress sessions.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope a query to only include completed sessions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include cancelled sessions.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include upcoming sessions.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now())
            ->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include sessions for today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('start_date', now()->toDateString());
    }

    /**
     * Check if the session is virtual.
     */
    public function isVirtual()
    {
        return $this->location_type === 'virtual';
    }

    /**
     * Check if the session is physical.
     */
    public function isPhysical()
    {
        return $this->location_type === 'physical';
    }

    /**
     * Get the duration of the session in hours.
     */
    public function getDurationInHours()
    {
        return $this->start_date->diffInHours($this->end_date);
    }
}