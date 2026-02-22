<?php

namespace Database\Seeders;

use App\Models\InterviewType;
use App\Models\User;
use Illuminate\Database\Seeder;

class InterviewTypeSeeder extends Seeder
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
        
        // Fixed interview types for consistent data
        $interviewTypes = [
            [
                'name' => 'Phone Screening',
                'description' => 'Initial phone interview to assess basic qualifications, communication skills, and interest level',
                'status' => 'active'
            ],
            [
                'name' => 'Video Interview',
                'description' => 'Remote video interview conducted via video conferencing platforms to evaluate candidate fit',
                'status' => 'active'
            ],
            [
                'name' => 'Technical Interview',
                'description' => 'In-depth technical assessment focusing on job-specific skills, problem-solving, and technical knowledge',
                'status' => 'active'
            ],
            [
                'name' => 'Behavioral Interview',
                'description' => 'Interview focusing on past behavior, situational responses, and cultural fit assessment',
                'status' => 'active'
            ],
            [
                'name' => 'Panel Interview',
                'description' => 'Interview conducted by multiple interviewers simultaneously to get diverse perspectives',
                'status' => 'active'
            ],
            [
                'name' => 'HR Interview',
                'description' => 'Human resources interview covering company policies, benefits, salary negotiation, and final assessment',
                'status' => 'active'
            ],
            [
                'name' => 'Case Study Interview',
                'description' => 'Problem-solving interview where candidates analyze and present solutions to business scenarios',
                'status' => 'active'
            ],
            [
                'name' => 'Group Interview',
                'description' => 'Interview format where multiple candidates are assessed together through group activities and discussions',
                'status' => 'active'
            ],
            [
                'name' => 'Final Interview',
                'description' => 'Final round interview typically conducted by senior management or hiring manager for final decision',
                'status' => 'active'
            ],
            [
                'name' => 'Practical Assessment',
                'description' => 'Hands-on evaluation where candidates demonstrate skills through practical tasks and assignments',
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($interviewTypes as $typeData) {
                // Check if interview type already exists for this company
                if (InterviewType::where('name', $typeData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    InterviewType::create([
                        'name' => $typeData['name'],
                        'description' => $typeData['description'],
                        'status' => $typeData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create interview type: ' . $typeData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('InterviewType seeder completed successfully!');
    }
}