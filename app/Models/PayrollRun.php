<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollRun extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'payroll_frequency',
        'pay_period_start',
        'pay_period_end',
        'pay_date',
        'total_gross_pay',
        'total_deductions',
        'total_net_pay',
        'employee_count',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'pay_date' => 'date',
        'total_gross_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net_pay' => 'decimal:2',
    ];

    /**
     * Get the payroll entries.
     */
    public function payrollEntries()
    {
        return $this->hasMany(PayrollEntry::class);
    }

    /**
     * Get the payslips through payroll entries.
     */
    public function payslips()
    {
        return $this->hasManyThrough(Payslip::class, PayrollEntry::class);
    }

    /**
     * Get the user who created the payroll run.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate and update totals.
     */
    public function calculateTotals()
    {
        $entries = $this->payrollEntries;

        $this->total_gross_pay = $entries->sum('gross_pay');
        $this->total_deductions = $entries->sum('total_deductions');
        $this->total_net_pay = $entries->sum('net_pay');
        $this->employee_count = $entries->count();

        $this->save();
    }

    /**
     * Process payroll for all employees.
     */
    public function processPayroll()
    {
        if ($this->status !== 'draft') {
            return false;
        }

        $this->status = 'draft';
        $this->save();

        try {
            // Get all active employees
            $employees = User::with('employee')->where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->whereHas('employee', function ($q) {
                    $q->whereIn('employee_status', ['active', 'probation']);
                })
                ->orderby('id', 'desc')
                ->get();

            foreach ($employees as $employee) {
                $this->processEmployeePayroll($employee);
            }

            $this->calculateTotals();
            $this->status = 'completed';
            $this->save();

            return true;
        } catch (\Exception $e) {
            $this->status = 'draft';
            $this->save();
            throw $e;
        }
    }

    /**
     * Process payroll for individual employee.
     */
    private function processEmployeePayroll($employee)
    {
        // Get working days from settings
        $globalSettings = settings();
        $workingDaysIndices = json_decode($globalSettings['working_days'] ?? '[]', true);

        if (empty($workingDaysIndices)) {
            throw new \Exception(__('Please configure working days first.'));
        }
        // Get employee salary (basic salary is already set according to company policy)
        $employeeSalary = EmployeeSalary::getActiveSalary($employee->id);

        if (! $employeeSalary) {
            return;
        }

        // Calculate salary breakdown using selected components
        $salaryBreakdown = $employeeSalary->calculateAllComponents();

        // Get attendance records for pay period
        $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('date', [$this->pay_period_start, $this->pay_period_end])
            ->orderBy('date')
            ->get();

        // Calculate working days in period (excluding weekends)
        // $totalWorkingDays = $this->pay_period_start->diffInDaysFiltered(function ($date) {
        //     return !$date->isWeekend(); // count only weekdays
        // }, $this->pay_period_end->copy()->addDay());

        // Calculate working days in pay period
        $startDate = new \DateTime($this->pay_period_start);
        $endDate = new \DateTime($this->pay_period_end);
        $totalWorkingDays = 0;

        for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
            $dayIndex = (int) $date->format('w');
            if (in_array($dayIndex, $workingDaysIndices)) {
                $totalWorkingDays++;
            }
        }

        // Calculate attendance summary
        $presentDays = $attendanceRecords
            ->whereIn('status', ['present', 'holiday'])
            ->count();
        $halfDays = $attendanceRecords->where('status', 'half_day')->count();
        $absentDays = $attendanceRecords->where('status', 'absent')->count();
        $holidayDays = $attendanceRecords->where('status', 'holiday')->count();
        $overtimeHours = $attendanceRecords->sum('overtime_hours');
        $overtimeAmount = $attendanceRecords->sum('overtime_amount');

        // Calculate leave days
        $leaveData = $this->getEmployeeLeaveData($employee->id);
        $leaveDays = $leaveData['paid_leave_days'] + $leaveData['unpaid_leave_days'];
        $unpaidLeaveDaysFromLeave = $leaveData['unpaid_leave_days'];

        // Total unpaid days = unpaid leaves + absent days + half days (0.5 each)
        $unpaidLeaveDays = $leaveData['unpaid_leave_days'] + $absentDays + ($halfDays * 0.5);

        // Calculate per day salary for deductions (basic salary from employee_salaries table)
        $perDaySalary = $totalWorkingDays > 0 ? $employeeSalary->basic_salary / $totalWorkingDays : 0;

        // Final Salary calculation following EmployeeSalaryController logic
        $totalEarnings = $salaryBreakdown['total_earnings'];
        $unpaidLeaveDeduction = $perDaySalary * $unpaidLeaveDays;
        $totalDeductions = $salaryBreakdown['total_deductions'];
        $grossSalary = $totalEarnings - $unpaidLeaveDeduction + $overtimeAmount;
        $netSalary = $grossSalary - $totalDeductions;

        // Calculate component earnings (total earnings - basic salary)
        $componentEarnings = $totalEarnings - $employeeSalary->basic_salary;

        // Create payroll entry
        PayrollEntry::create([
            'payroll_run_id' => $this->id,
            'employee_id' => $employee->id,
            'basic_salary' => $employeeSalary->basic_salary,
            'component_earnings' => $componentEarnings,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'gross_pay' => $grossSalary,
            'net_pay' => $netSalary,
            'working_days' => $totalWorkingDays,
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'holiday_days' => $holidayDays,
            'paid_leave_days' => $leaveData['paid_leave_days'],
            'unpaid_leave_days' => $unpaidLeaveDays,
            'absent_days' => $absentDays,
            'overtime_hours' => $overtimeHours,
            'overtime_amount' => $overtimeAmount,
            'per_day_salary' => $perDaySalary,
            'unpaid_leave_deduction' => $unpaidLeaveDeduction,
            'earnings_breakdown' => $salaryBreakdown['earnings'],
            'deductions_breakdown' => $salaryBreakdown['deductions'],
            'created_by' => $this->created_by,
        ]);
    }

    /**
     * Get employee leave data for pay period.
     */
    private function getEmployeeLeaveData($employeeId)
    {
        $leaveApplications = \App\Models\LeaveApplication::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereBetween('start_date', [$this->pay_period_start, $this->pay_period_end])
                    ->orWhereBetween('end_date', [$this->pay_period_start, $this->pay_period_end])
                    ->orWhere(function ($q) {
                        $q->where('start_date', '<=', $this->pay_period_start)
                            ->where('end_date', '>=', $this->pay_period_end);
                    });
            })
            ->with('leaveType')
            ->get();

        $paidLeaveDays = 0;
        $unpaidLeaveDays = 0;

        foreach ($leaveApplications as $leave) {
            // Calculate days within pay period
            $leaveStart = max($leave->start_date, $this->pay_period_start);
            $leaveEnd = min($leave->end_date, $this->pay_period_end);
            $leaveDays = $leaveStart->diffInDays($leaveEnd) + 1;

            if ($leave->leaveType->is_paid) {
                $paidLeaveDays += $leaveDays;
            } else {
                $unpaidLeaveDays += $leaveDays;
            }
        }

        return [
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
        ];
    }
}
