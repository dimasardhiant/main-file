<?php

namespace Database\Seeders;

use App\Models\Termination;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TerminationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all companies
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }

        // Termination types and their reasons
        $terminationData = [
            'involuntary' => [
                'reasons' => ['Performance Issues', 'Misconduct', 'Policy Violation', 'Attendance Issues', 'Insubordination'],
                'descriptions' => [
                    'Performance Issues' => 'Employee consistently failed to meet performance standards despite multiple warnings and performance improvement plans.',
                    'Misconduct' => 'Employee engaged in serious misconduct that violated company policies and professional standards.',
                    'Policy Violation' => 'Employee repeatedly violated company policies and procedures after receiving formal warnings and counseling.',
                    'Attendance Issues' => 'Employee had excessive absenteeism and tardiness that negatively impacted work productivity and team performance.',
                    'Insubordination' => 'Employee demonstrated insubordinate behavior and refused to follow direct instructions from supervisors.'
                ]
            ],
            'voluntary' => [
                'reasons' => ['Mutual Agreement', 'End of Contract', 'Job Abandonment', 'Voluntary Separation'],
                'descriptions' => [
                    'Mutual Agreement' => 'Both employee and company mutually agreed to terminate the employment relationship due to changing business needs.',
                    'End of Contract' => 'Employee\'s fixed-term contract has reached its natural expiration date and will not be renewed.',
                    'Job Abandonment' => 'Employee abandoned their position by failing to report to work for consecutive days without proper notification.',
                    'Voluntary Separation' => 'Employee requested voluntary separation as part of company restructuring or downsizing initiative.'
                ]
            ],
            'layoff' => [
                'reasons' => ['Economic Downturn', 'Restructuring', 'Budget Cuts', 'Department Closure'],
                'descriptions' => [
                    'Economic Downturn' => 'Position eliminated due to economic challenges and reduced business operations requiring workforce reduction.',
                    'Restructuring' => 'Role eliminated as part of organizational restructuring to improve efficiency and align with business strategy.',
                    'Budget Cuts' => 'Position terminated due to budget constraints and cost reduction measures implemented by the organization.',
                    'Department Closure' => 'Employee\'s department was closed due to strategic business decisions and operational changes.'
                ]
            ],
            'retirement' => [
                'reasons' => ['Normal Retirement', 'Early Retirement', 'Medical Retirement'],
                'descriptions' => [
                    'Normal Retirement' => 'Employee reached the standard retirement age and chose to retire with full benefits and pension eligibility.',
                    'Early Retirement' => 'Employee opted for early retirement package offered by the company with appropriate benefits and compensation.',
                    'Medical Retirement' => 'Employee retired due to medical conditions that prevent them from continuing their work responsibilities.'
                ]
            ]
        ];

        foreach ($companies as $company) {
            // Get employees for this company
            $employees = User::where('type', 'employee')
                ->where('created_by', $company->id)
                ->get();

            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }

            // Get managers/HR for approval
            $approvers = User::whereIn('type', ['manager', 'hr'])
                ->where('created_by', $company->id)
                ->get();

            // Create 2-4 terminations for this company
            $terminationCount = rand(2, 4);

            for ($i = 0; $i < $terminationCount; $i++) {
                $employee = $employees->take(5)->random();

                // Check if termination already exists for this employee
                if (Termination::where('employee_id', $employee->id)->exists()) {
                    continue;
                }

                $terminationType = $faker->randomElement(array_keys($terminationData));
                $typeData = $terminationData[$terminationType];
                $reason = $faker->randomElement($typeData['reasons']);
                $description = $typeData['descriptions'][$reason];

                $noticeDate = $faker->dateTimeBetween('-3 months', 'now');
                $noticePeriod = $faker->randomElement(['Immediate', '2 weeks', '30 days', '60 days']);

                // Calculate termination date based on notice period
                $noticeDays = match ($noticePeriod) {
                    'Immediate' => 0,
                    '2 weeks' => 14,
                    '30 days' => 30,
                    '60 days' => 60,
                    default => 0
                };

                $terminationDate = (clone $noticeDate)->modify("+{$noticeDays} days");

                $status = $faker->randomElement(['planned', 'in progress', 'completed']);
                $approver = $approvers->isNotEmpty() ? $approvers->random() : null;

                try {
                    Termination::create([
                        'employee_id' => $employee->id,
                        'termination_type' => $terminationType,
                        'termination_date' => $terminationDate->format('Y-m-d'),
                        'notice_date' => $noticeDate->format('Y-m-d'),
                        'notice_period' => $noticePeriod,
                        'reason' => $reason,
                        'description' => $description,
                        'status' => $status,
                        'documents' => randomImage(),
                        'approved_by' => $status === 'in progress' || $status === 'completed' ? $approver?->id : null,
                        'approved_at' => $status === 'in progress' || $status === 'completed' ? $faker->dateTimeBetween($noticeDate, 'now') : null,
                        'exit_interview_conducted' => $status === 'completed' ? $faker->boolean(70) : false,
                        'exit_interview_date' => $status === 'completed' && $faker->boolean(70) ? $terminationDate->format('Y-m-d') : null,
                        'exit_feedback' => $status === 'completed' ? $faker->sentence(12) : null,
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create termination for employee: ' . $employee->name . ' in company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('Termination seeder completed successfully!');
    }
}
