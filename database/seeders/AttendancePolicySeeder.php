<?php

namespace Database\Seeders;

use App\Models\AttendancePolicy;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendancePolicySeeder extends Seeder
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
        
        // Fixed attendance policies for consistent data
        $attendancePolicies = [
            [
                'name' => 'Standard Attendance Policy',
                'description' => 'Default attendance policy with standard grace periods and overtime rates',
                'late_arrival_grace' => 15,
                'early_departure_grace' => 15,
                'half_day_threshold' => 4.00,
                'overtime_rate_per_hour' => 150.00,
                'status' => 'active'
            ],
            [
                'name' => 'Flexible Attendance Policy',
                'description' => 'Flexible attendance policy with extended grace periods for remote and flexible workers',
                'late_arrival_grace' => 30,
                'early_departure_grace' => 30,
                'half_day_threshold' => 3.50,
                'overtime_rate_per_hour' => 175.00,
                'status' => 'active'
            ],
            [
                'name' => 'Strict Attendance Policy',
                'description' => 'Strict attendance policy with minimal grace periods for critical operations',
                'late_arrival_grace' => 5,
                'early_departure_grace' => 5,
                'half_day_threshold' => 4.50,
                'overtime_rate_per_hour' => 200.00,
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($attendancePolicies as $policyData) {
                // Check if attendance policy already exists for this company
                if (AttendancePolicy::where('name', $policyData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    AttendancePolicy::create([
                        'name' => $policyData['name'],
                        'description' => $policyData['description'],
                        'late_arrival_grace' => $policyData['late_arrival_grace'],
                        'early_departure_grace' => $policyData['early_departure_grace'],
                        'half_day_threshold' => $policyData['half_day_threshold'],
                        'overtime_rate_per_hour' => $policyData['overtime_rate_per_hour'],
                        'status' => $policyData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create attendance policy: ' . $policyData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        // Assign attendance policies to employees after creating policies
        foreach ($companies as $company) {
            $companyPolicies = AttendancePolicy::where('created_by', $company->id)->get();
            $employeeUsers = User::where('type', 'employee')->where('created_by', $company->id)->get();

            if ($companyPolicies->isNotEmpty() && $employeeUsers->isNotEmpty()) {
                // Assign Standard Attendance Policy to all employees by default
                $standardPolicy = $companyPolicies->where('name', 'Standard Attendance Policy')->first();
                if ($standardPolicy) {
                    foreach ($employeeUsers as $employeeUser) {
                        // Get employee record from employee table using user_id
                        $employee = \App\Models\Employee::where('user_id', $employeeUser->id)->first();
                        if ($employee && !$employee->attendance_policy_id) {
                            $employee->update(['attendance_policy_id' => $standardPolicy->id]);
                        }
                    }
                }
            }
        }
        
        $this->command->info('AttendancePolicy seeder completed successfully!');
    }
}