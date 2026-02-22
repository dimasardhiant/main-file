<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollEntry extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'basic_salary',
        'component_earnings',
        'total_earnings',
        'total_deductions',
        'gross_pay',
        'net_pay',
        'working_days',
        'present_days',
        'full_present_days',
        'half_days',
        'holiday_days',
        'paid_leave_days',
        'unpaid_leave_days',
        'absent_days',
        'overtime_hours',
        'overtime_amount',
        'per_day_salary',
        'unpaid_leave_deduction',
        'earnings_breakdown',
        'deductions_breakdown',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'component_earnings' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'present_days' => 'decimal:2',
        'half_days' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'per_day_salary' => 'decimal:2',
        'unpaid_leave_deduction' => 'decimal:2',
        'earnings_breakdown' => 'array',
        'deductions_breakdown' => 'array',
    ];

    /**
     * Get the payroll run.
     */
    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    /**
     * Get the employee.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the user who created the entry.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get attendance percentage.
     */
    public function getAttendancePercentageAttribute()
    {
        if ($this->working_days == 0) {
            return 0;
        }
        
        return round(($this->present_days / $this->working_days) * 100, 2);
    }
    
    /**
     * Get complete salary breakdown showing all interconnections.
     */
    public function getCompleteSalaryBreakdown()
    {
        $breakdown = [
            'employee_name' => $this->employee->name,
            'pay_period' => $this->payrollRun->pay_period_start->format('M Y'),
            
            // Attendance Data (from Attendance Management)
            'attendance' => [
                'total_working_days' => $this->working_days,
                'present_days' => $this->present_days,
                'attendance_percentage' => $this->attendance_percentage . '%',
                'overtime_hours' => $this->overtime_hours,
            ],
            
            // Leave Data (from Leave Management)
            'leave_info' => [
                'leave_days_taken' => $this->working_days - $this->present_days,
                'note' => 'Leave days are counted as present for salary calculation'
            ],
            
            // Salary Components (from Payroll Management)
            'earnings' => $this->earnings_breakdown,
            'deductions' => $this->deductions_breakdown,
            
            // Final Calculation
            'calculation' => [
                'gross_pay' => $this->gross_pay,
                'total_deductions' => $this->total_deductions,
                'net_pay' => $this->net_pay,
                'formula' => 'Net Pay = Gross Pay - Total Deductions'
            ]
        ];
        
        return $breakdown;
    }
}