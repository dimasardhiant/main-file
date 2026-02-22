<?php

namespace Database\Seeders;

use App\Models\LeaveBalance;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeaveBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }

        $currentYear = date('Y');

        foreach ($companies as $company) {
            // Get employees for this company
            $employees = User::where('type', 'employee')->where('created_by', $company->id)->get();

            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }

            // Get leave types and policies for this company
            $leaveTypes = LeaveType::where('created_by', $company->id)->get();
            $leavePolicies = LeavePolicy::where('created_by', $company->id)->get();

            if ($leaveTypes->isEmpty() || $leavePolicies->isEmpty()) {
                $this->command->warn('No leave types or policies found for company: ' . $company->name . '. Please run LeaveTypeSeeder and LeavePolicySeeder first.');
                continue;
            }

            foreach ($employees as $employee) {
                foreach ($leaveTypes as $leaveType) {
                    // Find matching leave policy
                    $leavePolicy = $leavePolicies->where('leave_type_id', $leaveType->id)->first();
                    if (!$leavePolicy) continue;

                    // Check if leave balance already exists
                    if (LeaveBalance::where('employee_id', $employee->id)
                        ->where('leave_type_id', $leaveType->id)
                        ->where('year', $currentYear)
                        ->exists()
                    ) {
                        continue;
                    }

                    // Calculate allocated days from policy
                    $allocatedDays = $leavePolicy->accrual_rate;

                    // Calculate used days from approved leave applications
                    $usedDays = LeaveApplication::where('employee_id', $employee->id)
                        ->where('leave_type_id', $leaveType->id)
                        ->where('status', 'approved')
                        ->whereYear('start_date', $currentYear)
                        ->sum('total_days');

                    // Calculate remaining days
                    $remainingDays = $allocatedDays - $usedDays;

                    // Set carried forward based on leave type (only for Annual Leave)
                    $carriedForward = 0;
                    if ($leaveType->name === 'Annual Leave') {
                        $carriedForward = 2; // Fixed 2 days carried forward for annual leave
                        $allocatedDays += $carriedForward;
                        $remainingDays += $carriedForward;
                    }

                    try {
                        LeaveBalance::create([
                            'employee_id' => $employee->id,
                            'leave_type_id' => $leaveType->id,
                            'leave_policy_id' => $leavePolicy->id,
                            'year' => $currentYear,
                            'allocated_days' => $allocatedDays,
                            'used_days' => $usedDays,
                            'remaining_days' => max(0, $remainingDays), // Ensure non-negative
                            'carried_forward' => $carriedForward,
                            'manual_adjustment' => 0,
                            'adjustment_reason' => null,
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create leave balance for employee: ' . $employee->name . ' and leave type: ' . $leaveType->name . ' in company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('LeaveBalance seeder completed successfully!');
    }
}
