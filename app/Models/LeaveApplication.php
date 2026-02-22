<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'leave_policy_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'attachment',
        'status',
        'manager_comments',
        'approved_by',
        'approved_at',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the employee who applied for leave.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the leave type.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the leave policy.
     */
    public function leavePolicy()
    {
        return $this->belongsTo(LeavePolicy::class);
    }

    /**
     * Get the manager who approved/rejected.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created the application.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Create attendance records and update leave balance when leave is approved.
     */
    public function createAttendanceRecords()
    {
        if ($this->status === 'approved') {
            $startDate = $this->start_date;
            $endDate = $this->end_date;
            
            // Loop through each day of leave
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                // Skip weekends (optional - depends on company policy)
                if ($date->isWeekend()) {
                    continue;
                }
                
                // Check if attendance record already exists
                $existingRecord = \App\Models\AttendanceRecord::where('employee_id', $this->employee_id)
                    ->where('date', $date->format('Y-m-d'))
                    ->first();
                
                if (!$existingRecord) {
                    \App\Models\AttendanceRecord::create([
                        'employee_id' => $this->employee_id,
                        'date' => $date->format('Y-m-d'),
                        'status' => 'on_leave',
                        'is_absent' => false,
                        'total_hours' => 0,
                        'notes' => 'Leave: ' . $this->leaveType->name,
                        'created_by' => $this->created_by,
                    ]);
                } else {
                    // Update existing record to on_leave
                    $existingRecord->update([
                        'status' => 'on_leave',
                        'notes' => 'Leave: ' . $this->leaveType->name,
                    ]);
                }
            }
            
            // Update leave balance - deduct used days
            $this->updateLeaveBalance();
        }
    }
    
    /**
     * Update employee leave balance when leave is approved.
     */
    public function updateLeaveBalance()
    {
        $currentYear = now()->year;
        
        // Find or create leave balance for this employee, leave type, and year
        $leaveBalance = \App\Models\LeaveBalance::firstOrCreate(
            [
                'employee_id' => $this->employee_id,
                'leave_type_id' => $this->leave_type_id,
                'year' => $currentYear,
            ],
            [
                'leave_policy_id' => $this->leave_policy_id,
                'allocated_days' => $this->leavePolicy->max_days_per_year ?? 10,
                'used_days' => 0,
                'remaining_days' => $this->leavePolicy->max_days_per_year ?? 10,
                'created_by' => $this->created_by,
            ]
        );
        
        // Deduct the leave days
        $leaveBalance->used_days += $this->total_days;
        $leaveBalance->remaining_days = $leaveBalance->allocated_days - $leaveBalance->used_days;
        $leaveBalance->save();
    }
}