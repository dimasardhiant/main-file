<?php

namespace Database\Seeders;

use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeavePolicySeeder extends Seeder
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

        // Fixed leave policies mapped to leave types
        $leavePolicies = [
            'Annual Leave' => [
                'name' => 'Annual Leave Policy',
                'description' => 'Standard annual leave policy with yearly accrual and carry forward provisions',
                'accrual_type' => 'yearly',
                'accrual_rate' => 21.00,
                'carry_forward_limit' => 5,
                'min_days_per_application' => 1,
                'max_days_per_application' => 15,
                'requires_approval' => true,
                'status' => 'active'
            ],
            'Sick Leave' => [
                'name' => 'Sick Leave Policy',
                'description' => 'Medical leave policy for health-related absences with flexible application',
                'accrual_type' => 'yearly',
                'accrual_rate' => 10.00,
                'carry_forward_limit' => 2,
                'min_days_per_application' => 1,
                'max_days_per_application' => 10,
                'requires_approval' => false,
                'status' => 'active'
            ],
            'Maternity Leave' => [
                'name' => 'Maternity Leave Policy',
                'description' => 'Comprehensive maternity leave policy with extended duration and full pay',
                'accrual_type' => 'yearly',
                'accrual_rate' => 90.00,
                'carry_forward_limit' => 0,
                'min_days_per_application' => 30,
                'max_days_per_application' => 90,
                'requires_approval' => true,
                'status' => 'active'
            ],
            'Paternity Leave' => [
                'name' => 'Paternity Leave Policy',
                'description' => 'Paternity leave policy for new fathers with flexible scheduling',
                'accrual_type' => 'yearly',
                'accrual_rate' => 15.00,
                'carry_forward_limit' => 0,
                'min_days_per_application' => 1,
                'max_days_per_application' => 15,
                'requires_approval' => true,
                'status' => 'active'
            ],
            'Emergency Leave' => [
                'name' => 'Emergency Leave Policy',
                'description' => 'Urgent leave policy for unexpected situations with immediate approval',
                'accrual_type' => 'yearly',
                'accrual_rate' => 5.00,
                'carry_forward_limit' => 0,
                'min_days_per_application' => 1,
                'max_days_per_application' => 3,
                'requires_approval' => false,
                'status' => 'active'
            ],
            'Bereavement Leave' => [
                'name' => 'Bereavement Leave Policy',
                'description' => 'Compassionate leave policy for family bereavement with immediate effect',
                'accrual_type' => 'yearly',
                'accrual_rate' => 7.00,
                'carry_forward_limit' => 0,
                'min_days_per_application' => 1,
                'max_days_per_application' => 7,
                'requires_approval' => false,
                'status' => 'active'
            ],
            'Study Leave' => [
                'name' => 'Study Leave Policy',
                'description' => 'Educational leave policy for professional development and skill enhancement',
                'accrual_type' => 'yearly',
                'accrual_rate' => 10.00,
                'carry_forward_limit' => 5,
                'min_days_per_application' => 1,
                'max_days_per_application' => 10,
                'requires_approval' => true,
                'status' => 'active'
            ],
            'Compensatory Leave' => [
                'name' => 'Compensatory Leave Policy',
                'description' => 'Time off policy for overtime work compensation with monthly accrual',
                'accrual_type' => 'monthly',
                'accrual_rate' => 1.00,
                'carry_forward_limit' => 3,
                'min_days_per_application' => 1,
                'max_days_per_application' => 5,
                'requires_approval' => true,
                'status' => 'active'
            ],
            'Personal Leave' => [
                'name' => 'Personal Leave Policy',
                'description' => 'Personal time off policy for individual matters without pay',
                'accrual_type' => 'yearly',
                'accrual_rate' => 5.00,
                'carry_forward_limit' => 0,
                'min_days_per_application' => 1,
                'max_days_per_application' => 3,
                'requires_approval' => true,
                'status' => 'active'
            ],
            'Marriage Leave' => [
                'name' => 'Marriage Leave Policy',
                'description' => 'Special leave policy for wedding ceremonies and related celebrations',
                'accrual_type' => 'yearly',
                'accrual_rate' => 7.00,
                'carry_forward_limit' => 0,
                'min_days_per_application' => 1,
                'max_days_per_application' => 7,
                'requires_approval' => true,
                'status' => 'active'
            ]
        ];

        foreach ($companies as $company) {
            // Get leave types for this company
            $leaveTypes = LeaveType::where('created_by', $company->id)->get();

            if ($leaveTypes->isEmpty()) {
                $this->command->warn('No leave types found for company: ' . $company->name . '. Please run LeaveTypeSeeder first.');
                continue;
            }

            foreach ($leaveTypes as $leaveType) {
                $policyData = $leavePolicies[$leaveType->name] ?? null;

                if (!$policyData) {
                    continue;
                }

                // Check if leave policy already exists for this leave type and company
                if (LeavePolicy::where('leave_type_id', $leaveType->id)->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    LeavePolicy::create([
                        'name' => $policyData['name'],
                        'description' => $policyData['description'],
                        'leave_type_id' => $leaveType->id,
                        'accrual_type' => $policyData['accrual_type'],
                        'accrual_rate' => $policyData['accrual_rate'],
                        'carry_forward_limit' => $policyData['carry_forward_limit'],
                        'min_days_per_application' => $policyData['min_days_per_application'],
                        'max_days_per_application' => $policyData['max_days_per_application'],
                        'requires_approval' => $policyData['requires_approval'],
                        'status' => $policyData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create leave policy: ' . $policyData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('LeavePolicy seeder completed successfully!');
    }
}
