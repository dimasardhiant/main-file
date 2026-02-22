<?php

namespace Database\Seeders;

use App\Models\JobType;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobTypeSeeder extends Seeder
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
        
        // Fixed job types for consistent data
        $jobTypes = [
            [
                'name' => 'Full-time',
                'description' => 'Regular full-time employment with standard working hours, typically 40 hours per week with full benefits',
                'status' => 'active'
            ],
            [
                'name' => 'Part-time',
                'description' => 'Part-time employment with reduced working hours, typically less than 30 hours per week with limited benefits',
                'status' => 'active'
            ],
            [
                'name' => 'Contract',
                'description' => 'Fixed-term contract employment for specific projects or duration with defined start and end dates',
                'status' => 'active'
            ],
            [
                'name' => 'Temporary',
                'description' => 'Short-term temporary employment to cover immediate staffing needs or seasonal work requirements',
                'status' => 'active'
            ],
            [
                'name' => 'Freelance',
                'description' => 'Independent contractor arrangement for specific tasks or projects with flexible working arrangements',
                'status' => 'active'
            ],
            [
                'name' => 'Internship',
                'description' => 'Educational work experience program for students or recent graduates to gain practical skills',
                'status' => 'active'
            ],
            [
                'name' => 'Remote',
                'description' => 'Work-from-home or remote work arrangement allowing employees to work from any location',
                'status' => 'active'
            ],
            [
                'name' => 'Hybrid',
                'description' => 'Combination of office and remote work, typically requiring presence in office for certain days',
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($jobTypes as $typeData) {
                // Check if job type already exists for this company
                if (JobType::where('name', $typeData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    JobType::create([
                        'name' => $typeData['name'],
                        'description' => $typeData['description'],
                        'status' => $typeData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create job type: ' . $typeData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('JobType seeder completed successfully!');
    }
}