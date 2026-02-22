<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceRecord extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'attendance_policy_id',
        'date',
        'clock_in',
        'clock_out',
        'total_hours',
        'break_hours',
        'overtime_hours',
        'overtime_amount',
        'is_late',
        'is_early_departure',
        'is_absent',
        'is_holiday',
        'is_weekend',
        'status',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'break_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'is_late' => 'boolean',
        'is_early_departure' => 'boolean',
        'is_absent' => 'boolean',
        'is_holiday' => 'boolean',
        'is_weekend' => 'boolean',
    ];

    /**
     * Get the employee.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the shift.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the attendance policy.
     */
    public function attendancePolicy()
    {
        return $this->belongsTo(AttendancePolicy::class);
    }

    /**
     * Get the user who created the record.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate total working hours.
     */
    public function calculateTotalHours()
    {
        if ($this->clock_in && $this->clock_out) {
            $clockIn = Carbon::parse($this->clock_in);
            $clockOut = Carbon::parse($this->clock_out);

            // Handle next day clock out (night shifts)
            if ($clockOut->lt($clockIn)) {
                $clockOut->addDay();
            }

            $totalMinutes = abs($clockOut->diffInMinutes($clockIn));

            // Use shift's break times for accurate calculation
            $breakMinutes = 0;
            if ($this->shift && $this->shift->break_start_time && $this->shift->break_end_time) {
                $breakStart = Carbon::parse($this->shift->break_start_time);
                $breakEnd = Carbon::parse($this->shift->break_end_time);

                // Handle next day break times for night shifts
                if ($breakEnd->lt($breakStart)) {
                    $breakEnd->addDay();
                }

                // Only deduct break if employee worked through the break period
                if ($clockIn->lte($breakStart) && $clockOut->gte($breakEnd)) {
                    // Worked through entire break - deduct full break
                    $breakMinutes = $this->shift->break_duration;
                } elseif ($clockIn->lte($breakStart) && $clockOut->gt($breakStart) && $clockOut->lte($breakEnd)) {
                    // Left during break - deduct time spent on break
                    $breakMinutes = abs($clockOut->diffInMinutes($breakStart));
                } elseif ($clockIn->gt($breakStart) && $clockIn->lt($breakEnd) && $clockOut->gte($breakEnd)) {
                    // Came during break - deduct partial break (missed part of break)
                    $breakMinutes = abs($breakEnd->diffInMinutes($clockIn));
                } elseif ($clockIn->gt($breakStart) && $clockOut->lt($breakEnd)) {
                    // Came and left during break - no break deduction
                    $breakMinutes = 0;
                }
            }

            $workingMinutes = max(0, $totalMinutes - $breakMinutes);
            $calculatedHours = round($workingMinutes / 60, 2);

            $this->attributes['total_hours'] = $calculatedHours;
        } else {
            $this->attributes['total_hours'] = 0;
        }

        return $this->attributes['total_hours'] ?? 0;
    }

    /**
     * Check if employee is late.
     */
    public function checkLateArrival()
    {
        if ($this->shift && $this->clock_in && $this->attendancePolicy) {
            $expectedTime = $this->shift->start_time;
            $this->is_late = $this->attendancePolicy->isLateArrival($this->clock_in, $expectedTime);
        }

        return $this->is_late;
    }

    /**
     * Check if employee left early.
     */
    public function checkEarlyDeparture()
    {
        if ($this->shift && $this->clock_out && $this->attendancePolicy) {
            $expectedTime = $this->shift->end_time;
            $this->is_early_departure = $this->attendancePolicy->isEarlyDeparture($this->clock_out, $expectedTime);
        }

        return $this->is_early_departure;
    }

    /**
     * Process complete attendance - calculate everything automatically.
     */
    public function processAttendance()
    {
        // Step 1: Calculate total working hours first
        $this->calculateTotalHours();

        // Step 2: Calculate overtime using shift working hours dynamically
        if ($this->shift && $this->shift->working_hours > 0) {
            $standardHours = $this->shift->working_hours; // Use actual shift hours
        } else {
            $standardHours = 8; // Fallback to 8 hours if no shift or invalid hours
        }

        $this->overtime_hours = max(0, round($this->total_hours - $standardHours, 2));

        // Step 3: Calculate overtime amount using policy
        if ($this->overtime_hours > 0 && $this->attendancePolicy) {
            $this->overtime_amount = round($this->overtime_hours * $this->attendancePolicy->overtime_rate_per_hour, 2);
        } else {
            $this->overtime_amount = 0;
        }

        // Step 4: Check late arrival and early departure
        if ($this->clock_in && $this->clock_out) {
            $this->checkLateArrival();
            $this->checkEarlyDeparture();
        }

        // Step 5: Set status based on holiday or total hours (only if not manually set)
        if ($this->is_holiday) {
            $this->status = 'holiday';
        } elseif (!$this->exists || $this->isDirty('clock_in') || $this->isDirty('clock_out')) {
            // Only auto-calculate status for new records or when times change
            $presentThreshold = $standardHours; // Full shift hours = present
            $halfDayThreshold = $presentThreshold / 2; // Half of shift hours = half day

            if ($this->total_hours >= $halfDayThreshold) {
                $this->status = 'present';
            } elseif ($this->total_hours > 0) {
                $this->status = 'half_day';
            } else {
                $this->status = 'absent';
            }
        }
        // If record exists and times haven't changed, keep manual status

        $this->save();
    }

    /**
     * Format clock in time for frontend (H:i format).
     */
    public function getClockInAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('H:i') : null;
    }
    
    /**
     * Format clock out time for frontend (H:i format).
     */
    public function getClockOutAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('H:i') : null;
    }
}
