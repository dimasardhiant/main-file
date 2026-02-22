<?php

namespace Database\Seeders;

use App\Models\Warning;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class WarningSeeder extends Seeder
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

        // Warning types and their subjects/descriptions
        $warningData = [
            'attendance' => [
                'subjects' => ['Excessive Absenteeism', 'Frequent Tardiness', 'Unauthorized Leave', 'Pattern of Late Arrivals'],
                'descriptions' => [
                    'Excessive Absenteeism' => 'Employee has exceeded the acceptable number of absences without proper documentation or approval, affecting team productivity and work coverage.',
                    'Frequent Tardiness' => 'Employee consistently arrives late to work, disrupting team meetings and affecting overall work schedule and productivity.',
                    'Unauthorized Leave' => 'Employee took leave without following proper approval procedures and failed to notify supervisor in advance.',
                    'Pattern of Late Arrivals' => 'Employee shows a consistent pattern of arriving late to work, which impacts team coordination and project timelines.'
                ]
            ],
            'performance' => [
                'subjects' => ['Below Standard Performance', 'Missed Deadlines', 'Quality Issues', 'Productivity Concerns'],
                'descriptions' => [
                    'Below Standard Performance' => 'Employee\'s work performance has consistently fallen below established standards and expectations for their role and experience level.',
                    'Missed Deadlines' => 'Employee has repeatedly failed to meet project deadlines, causing delays in deliverables and affecting client satisfaction.',
                    'Quality Issues' => 'Employee\'s work output contains frequent errors and does not meet the quality standards expected for their position.',
                    'Productivity Concerns' => 'Employee\'s productivity levels are significantly below team average and departmental expectations.'
                ]
            ],
            'conduct' => [
                'subjects' => ['Inappropriate Behavior', 'Policy Violation', 'Unprofessional Conduct', 'Workplace Disruption'],
                'descriptions' => [
                    'Inappropriate Behavior' => 'Employee engaged in behavior that is inappropriate for the workplace environment and violates professional conduct standards.',
                    'Policy Violation' => 'Employee violated specific company policies and procedures that are clearly outlined in the employee handbook.',
                    'Unprofessional Conduct' => 'Employee demonstrated unprofessional behavior towards colleagues, clients, or supervisors that affects workplace harmony.',
                    'Workplace Disruption' => 'Employee\'s actions have disrupted the normal work environment and negatively impacted team morale and productivity.'
                ]
            ],
            'safety' => [
                'subjects' => ['Safety Protocol Violation', 'Unsafe Work Practices', 'Equipment Misuse', 'Failure to Use PPE'],
                'descriptions' => [
                    'Safety Protocol Violation' => 'Employee failed to follow established safety protocols, potentially endangering themselves and other employees.',
                    'Unsafe Work Practices' => 'Employee engaged in unsafe work practices that could result in injury or damage to company property.',
                    'Equipment Misuse' => 'Employee improperly used company equipment, potentially causing damage or creating safety hazards.',
                    'Failure to Use PPE' => 'Employee failed to use required personal protective equipment as mandated by safety regulations.'
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

            // Get managers/HR who can issue warnings
            $warningIssuers = User::whereIn('type', ['manager', 'hr'])
                ->where('created_by', $company->id)
                ->get();

            if ($warningIssuers->isEmpty()) {
                $this->command->warn('No managers/HR found for company: ' . $company->name . '. Please run DefaultCompanyUserSeeder first.');
                continue;
            }

            // Create 5-12 warnings for this company
            $warningCount = rand(5, 12);

            for ($i = 0; $i < $warningCount; $i++) {
                $employee = $employees->take(5)->random();
                $warningIssuer = $warningIssuers->random();
                $warningType = $faker->randomElement(array_keys($warningData));
                $typeData = $warningData[$warningType];
                $subject = $faker->randomElement($typeData['subjects']);
                $description = $typeData['descriptions'][$subject];

                $warningDate = $faker->dateTimeBetween('-1 year', 'now');
                $severity = $faker->randomElement(['verbal', 'written', 'final']);
                $status = $faker->randomElement(['draft', 'issued', 'acknowledged', 'expired']);

                // Calculate expiry date (warnings typically expire after 6-12 months)
                $expiryDate = (clone $warningDate)->modify('+' . rand(6, 12) . ' months');

                // Improvement plan details for performance/conduct warnings
                $hasImprovementPlan = in_array($warningType, ['performance', 'conduct']) && $faker->boolean(60);
                $improvementStartDate = $hasImprovementPlan ? $warningDate : null;
                $improvementEndDate = $hasImprovementPlan ? (clone $warningDate)->modify('+90 days') : null;

                try {
                    Warning::create([
                        'employee_id' => $employee->id,
                        'warning_by' => $warningIssuer->id,
                        'warning_type' => $warningType,
                        'subject' => $subject,
                        'severity' => $severity,
                        'warning_date' => $warningDate->format('Y-m-d'),
                        'description' => $description,
                        'status' => $status,
                        'documents' => randomImage(),
                        'acknowledgment_date' => $status === 'acknowledged' ? $faker->dateTimeBetween($warningDate, 'now')->format('Y-m-d') : null,
                        'employee_response' => $status === 'acknowledged' ? $faker->sentence(10) : null,
                        'approved_by' => $status !== 'draft' ? $warningIssuer->id : null,
                        'approved_at' => $status !== 'draft' ? $faker->dateTimeBetween($warningDate, 'now') : null,
                        'expiry_date' => $expiryDate->format('Y-m-d'),
                        'has_improvement_plan' => $hasImprovementPlan,
                        'improvement_plan_goals' => $hasImprovementPlan ? $faker->sentence(15) : null,
                        'improvement_plan_start_date' => $improvementStartDate?->format('Y-m-d'),
                        'improvement_plan_end_date' => $improvementEndDate?->format('Y-m-d'),
                        'improvement_plan_progress' => $hasImprovementPlan && $faker->boolean(40) ? $faker->sentence(12) : null,
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create warning for employee: ' . $employee->name . ' in company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('Warning seeder completed successfully!');
    }
}
