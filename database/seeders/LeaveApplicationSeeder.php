<?php

namespace Database\Seeders;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LeaveApplicationSeeder extends Seeder
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

        // Fixed leave application patterns for each month
        $monthlyLeavePatterns = [
            1 => ['leave_type' => 'Annual Leave', 'days' => 2, 'reason' => 'New Year vacation', 'status' => 'approved'],
            2 => ['leave_type' => 'Sick Leave', 'days' => 1, 'reason' => 'Fever and cold symptoms', 'status' => 'approved'],
            3 => ['leave_type' => 'Personal Leave', 'days' => 1, 'reason' => 'Personal appointment', 'status' => 'approved'],
            4 => ['leave_type' => 'Annual Leave', 'days' => 3, 'reason' => 'Family vacation', 'status' => 'approved'],
            5 => ['leave_type' => 'Emergency Leave', 'days' => 1, 'reason' => 'Family emergency', 'status' => 'approved'],
            6 => ['leave_type' => 'Annual Leave', 'days' => 2, 'reason' => 'Weekend extension', 'status' => 'approved'],
            7 => ['leave_type' => 'Sick Leave', 'days' => 2, 'reason' => 'Medical checkup and recovery', 'status' => 'approved'],
            8 => ['leave_type' => 'Annual Leave', 'days' => 1, 'reason' => 'Personal work', 'status' => 'approved'],
            9 => ['leave_type' => 'Annual Leave', 'days' => 3, 'reason' => 'Festival celebration', 'status' => 'approved'],
            10 => ['leave_type' => 'Sick Leave', 'days' => 1, 'reason' => 'Routine medical appointment', 'status' => 'approved'],
            11 => ['leave_type' => 'Annual Leave', 'days' => 2, 'reason' => 'Long weekend break', 'status' => 'approved'],
            12 => ['leave_type' => 'Annual Leave', 'days' => 4, 'reason' => 'Year end vacation', 'status' => 'approved']
        ];

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

            // Get managers for approval
            $managers = User::whereIn('type', ['manager', 'hr'])->where('created_by', $company->id)->get();

            // Select first 5 employees for leave applications
            $selectedEmployees = $employees->take(5);

            foreach ($selectedEmployees as $empIndex => $employee) {
                // Create leave applications for each month of current year
                for ($month = 1; $month <= 12; $month++) {
                    $pattern = $monthlyLeavePatterns[$month];

                    // Find matching leave type and policy
                    $leaveType = $leaveTypes->where('name', $pattern['leave_type'])->first();
                    if (!$leaveType) $leaveType = $leaveTypes->first();

                    $leavePolicy = $leavePolicies->where('leave_type_id', $leaveType->id)->first();
                    if (!$leavePolicy) $leavePolicy = $leavePolicies->first();

                    // Calculate leave dates (avoid weekends and vary dates)
                    $baseDay = 5 + ($empIndex * 3) + ($month * 2); // Vary start day based on employee and month
                    $startCarbon = Carbon::create($currentYear, $month, min($baseDay, 28));

                    // Skip weekends for start date
                    while ($startCarbon->isWeekend()) {
                        $startCarbon->addDay();
                    }

                    $endCarbon = $startCarbon->copy()->addDays($pattern['days'] - 1);

                    // Skip weekends for end date
                    while ($endCarbon->isWeekend()) {
                        $endCarbon->addDay();
                    }

                    $startDate = Carbon::parse($startCarbon->format('Y-m-d'));
                    $endDate   = Carbon::parse($endCarbon->format('Y-m-d'));
                    $leaveDays = $startDate->diffInDays($endDate) + 1;



                    // Select approver
                    $approver = $managers->isNotEmpty() ? $managers->first() : null;

                    // Check if leave application already exists
                    if (LeaveApplication::where('employee_id', $employee->id)
                        ->where('start_date', $startDate)
                        ->where('leave_type_id', $leaveType->id)
                        ->exists()
                    ) {
                        continue;
                    }

                    try {
                        LeaveApplication::create([
                            'employee_id' => $employee->id,
                            'leave_type_id' => $leaveType->id,
                            'leave_policy_id' => $leavePolicy->id,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'total_days' => $leaveDays,
                            'reason' => $pattern['reason'],
                            'attachment' => randomImage(),
                            'status' => $pattern['status'],
                            'manager_comments' => $pattern['status'] === 'approved' ? 'Leave approved as per company policy' : null,
                            'approved_by' => $pattern['status'] === 'approved' ? $approver?->id : null,
                            'approved_at' => $pattern['status'] === 'approved' ? now() : null,
                            'created_by' => $employee->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create leave application for employee: ' . $employee->name . ' in month: ' . $month . ' for company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('LeaveApplication seeder completed successfully!');
    }
}
