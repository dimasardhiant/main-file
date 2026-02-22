<?php

namespace Database\Seeders;

use App\Models\ContractType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
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

        // Fixed contract types for consistent data
        $contractTypes = [
            [
                'name' => 'Permanent Full-time',
                'description' => 'Permanent employment contract with full-time working hours and comprehensive benefits package',
                'default_duration_months' => null,
                'probation_period_months' => 6,
                'notice_period_days' => 60,
                'is_renewable' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Fixed-term Contract',
                'description' => 'Fixed-term employment contract for specific duration with defined start and end dates',
                'default_duration_months' => 12,
                'probation_period_months' => 3,
                'notice_period_days' => 30,
                'is_renewable' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Part-time Contract',
                'description' => 'Part-time employment contract with reduced working hours and pro-rated benefits',
                'default_duration_months' => null,
                'probation_period_months' => 3,
                'notice_period_days' => 30,
                'is_renewable' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Temporary Contract',
                'description' => 'Short-term temporary contract for immediate staffing needs or project-based work',
                'default_duration_months' => 6,
                'probation_period_months' => 1,
                'notice_period_days' => 15,
                'is_renewable' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Consultant Agreement',
                'description' => 'Independent contractor agreement for specialized consulting services and expertise',
                'default_duration_months' => 6,
                'probation_period_months' => 0,
                'notice_period_days' => 30,
                'is_renewable' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Internship Contract',
                'description' => 'Educational internship contract for students and recent graduates with learning objectives',
                'default_duration_months' => 3,
                'probation_period_months' => 0,
                'notice_period_days' => 7,
                'is_renewable' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Probationary Contract',
                'description' => 'Initial probationary employment contract with extended evaluation period',
                'default_duration_months' => 6,
                'probation_period_months' => 6,
                'notice_period_days' => 15,
                'is_renewable' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Seasonal Contract',
                'description' => 'Seasonal employment contract for specific periods or seasonal business requirements',
                'default_duration_months' => 4,
                'probation_period_months' => 1,
                'notice_period_days' => 15,
                'is_renewable' => true,
                'status' => 'active'
            ]
        ];

        foreach ($companies as $company) {
            foreach ($contractTypes as $typeData) {
                // Check if contract type already exists for this company
                if (ContractType::where('name', $typeData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    ContractType::create([
                        'name' => $typeData['name'],
                        'description' => $typeData['description'],
                        'default_duration_months' => $typeData['default_duration_months'],
                        'probation_period_months' => $typeData['probation_period_months'],
                        'notice_period_days' => $typeData['notice_period_days'],
                        'is_renewable' => $typeData['is_renewable'],
                        'status' => $typeData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create contract type: ' . $typeData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('ContractType seeder completed successfully!');
    }
}
