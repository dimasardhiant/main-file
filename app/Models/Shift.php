<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_time',
        'end_time',
        'break_duration',
        'break_start_time',
        'break_end_time',
        'grace_period',
        'is_night_shift',
        'status',
        'created_by'
    ];

    protected $casts = [
        'is_night_shift' => 'boolean',
    ];

    /**
     * Get the user who created the shift.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Calculate working hours for this shift.
     */
    public function getWorkingHoursAttribute()
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        
        // Handle night shifts
        if ($this->is_night_shift && $end->lt($start)) {
            $end->addDay();
        }
        
        $totalMinutes = abs($end->diffInMinutes($start)) - $this->break_duration;
        return round(max(0, $totalMinutes) / 60, 2);
    }
    
    /**
     * Format start time for frontend (H:i format).
     */
    public function getStartTimeAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('H:i') : null;
    }
    
    /**
     * Format end time for frontend (H:i format).
     */
    public function getEndTimeAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('H:i') : null;
    }
    
    /**
     * Format break start time for frontend (H:i format).
     */
    public function getBreakStartTimeAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('H:i') : null;
    }
    
    /**
     * Format break end time for frontend (H:i format).
     */
    public function getBreakEndTimeAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('H:i') : null;
    }
}