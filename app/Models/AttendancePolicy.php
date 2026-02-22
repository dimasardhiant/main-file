<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendancePolicy extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'late_arrival_grace',
        'early_departure_grace',
        'overtime_rate_per_hour',
        'status',
        'created_by'
    ];

    protected $casts = [
        'overtime_rate_per_hour' => 'decimal:2',
    ];

    /**
     * Get the user who created the policy.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if arrival time is late.
     */
    public function isLateArrival($actualTime, $expectedTime)
    {
        $actual = \Carbon\Carbon::parse($actualTime);
        $expected = \Carbon\Carbon::parse($expectedTime);
        $graceMinutes = $this->late_arrival_grace;
        
        return $actual->gt($expected->addMinutes($graceMinutes));
    }

    /**
     * Check if departure time is early.
     */
    public function isEarlyDeparture($actualTime, $expectedTime)
    {
        $actual = \Carbon\Carbon::parse($actualTime);
        $expected = \Carbon\Carbon::parse($expectedTime);
        $graceMinutes = $this->early_departure_grace;
        
        return $actual->lt($expected->subMinutes($graceMinutes));
    }

    /**
     * Calculate overtime amount.
     */
    public function calculateOvertimeAmount($overtimeHours)
    {
        return $overtimeHours * $this->overtime_rate_per_hour;
    }
}