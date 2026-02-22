<?php

namespace Database\Seeders;

use App\Models\OnboardingChecklist;
use App\Models\User;
use Illuminate\Database\Seeder;

class OnboardingChecklistSeeder extends Seeder
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

        // Fixed onboarding checklists for consistent data
        $onboardingChecklists = [
            [
                'name' => 'Standard Employee Onboarding',
                'description' => 'Comprehensive onboarding checklist for all new employees covering documentation, orientation, and initial setup requirements',
                'is_default' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Technical Team Onboarding',
                'description' => 'Specialized onboarding process for technical roles including development environment setup, code access, and technical training',
                'is_default' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Management Level Onboarding',
                'description' => 'Executive and management onboarding checklist including leadership orientation, strategic briefings, and team introductions',
                'is_default' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Sales Team Onboarding',
                'description' => 'Sales-focused onboarding covering CRM training, product knowledge, sales processes, and territory assignments',
                'is_default' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Remote Employee Onboarding',
                'description' => 'Onboarding checklist specifically designed for remote workers including equipment setup, virtual introductions, and remote work policies',
                'is_default' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Intern Onboarding Program',
                'description' => 'Streamlined onboarding process for interns and temporary employees with essential orientation and basic requirements',
                'is_default' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Customer Service Onboarding',
                'description' => 'Customer service team onboarding including product training, customer interaction protocols, and support system access',
                'is_default' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Finance Department Onboarding',
                'description' => 'Finance team specific onboarding covering financial systems, compliance requirements, and accounting procedures',
                'is_default' => false,
                'status' => 'active'
            ]
        ];

        foreach ($companies as $company) {
            foreach ($onboardingChecklists as $checklistData) {
                // Check if onboarding checklist already exists for this company
                if (OnboardingChecklist::where('name', $checklistData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    OnboardingChecklist::create([
                        'name' => $checklistData['name'],
                        'description' => $checklistData['description'],
                        'is_default' => $checklistData['is_default'],
                        'status' => $checklistData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create onboarding checklist: ' . $checklistData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('OnboardingChecklist seeder completed successfully!');
    }
}
