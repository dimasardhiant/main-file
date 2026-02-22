<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRegularization extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_record_id',
        'date',
        'requested_clock_in',
        'requested_clock_out',
        'original_clock_in',
        'original_clock_out',
        'reason',
        'status',
        'manager_comments',
        'approved_by',
        'approved_at',
        'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the employee.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the attendance record.
     */
    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    /**
     * Get the manager who approved/rejected.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created the regularization.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Apply approved regularization to attendance record.
     */
    public function applyToAttendanceRecord()
    {
        if ($this->status === 'approved' && $this->attendanceRecord) {
            // Update the attendance record with requested times
            $this->attendanceRecord->update([
                'clock_in' => $this->requested_clock_in,
                'clock_out' => $this->requested_clock_out,
            ]);

            // Process complete attendance calculation with shift and policy
            $this->attendanceRecord->processAttendance();
        }
    }
}