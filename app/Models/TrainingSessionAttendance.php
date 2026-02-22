<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingSessionAttendance extends Model
{
    use HasFactory;

    protected $table = 'training_session_attendance';

    protected $fillable = [
        'training_session_id',
        'employee_id',
        'is_present',
        'notes'
    ];

    protected $casts = [
        'is_present' => 'boolean',
    ];

    /**
     * Get the training session for this attendance record.
     */
    public function trainingSession()
    {
        return $this->belongsTo(TrainingSession::class);
    }

    /**
     * Get the employee for this attendance record.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Scope a query to only include present attendances.
     */
    public function scopePresent($query)
    {
        return $query->where('is_present', true);
    }

    /**
     * Scope a query to only include absent attendances.
     */
    public function scopeAbsent($query)
    {
        return $query->where('is_present', false);
    }
}