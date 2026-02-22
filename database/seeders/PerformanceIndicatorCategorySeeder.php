<?php

namespace Database\Seeders;

use App\Models\PerformanceIndicatorCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class PerformanceIndicatorCategorySeeder extends Seeder
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

        // Fixed performance indicator categories for consistent data
        $categories = [
            [
                'name' => 'Job Performance',
                'description' => 'Measures employee effectiveness in completing assigned tasks, meeting deadlines, and achieving job-specific objectives',
                'status' => 'active'
            ],
            [
                'name' => 'Quality of Work',
                'description' => 'Evaluates accuracy, attention to detail, and overall quality standards in work output and deliverables',
                'status' => 'active'
            ],
            [
                'name' => 'Communication Skills',
                'description' => 'Assesses verbal and written communication abilities, listening skills, and effectiveness in conveying information',
                'status' => 'active'
            ],
            [
                'name' => 'Teamwork and Collaboration',
                'description' => 'Measures ability to work effectively with colleagues, contribute to team goals, and maintain positive working relationships',
                'status' => 'active'
            ],
            [
                'name' => 'Leadership and Initiative',
                'description' => 'Evaluates leadership qualities, proactive approach, decision-making skills, and ability to guide and motivate others',
                'status' => 'active'
            ],
            [
                'name' => 'Problem Solving',
                'description' => 'Assesses analytical thinking, creativity in finding solutions, and ability to resolve challenges effectively',
                'status' => 'active'
            ],
            [
                'name' => 'Adaptability and Flexibility',
                'description' => 'Measures ability to adapt to changes, learn new skills, and handle varying work demands and environments',
                'status' => 'active'
            ],
            [
                'name' => 'Time Management',
                'description' => 'Evaluates efficiency in managing time, prioritizing tasks, and meeting deadlines consistently',
                'status' => 'active'
            ],
            [
                'name' => 'Customer Service',
                'description' => 'Measures effectiveness in serving customers, handling inquiries, and maintaining positive customer relationships',
                'status' => 'active'
            ],
            [
                'name' => 'Technical Competency',
                'description' => 'Assesses proficiency in job-related technical skills, software applications, and industry-specific knowledge',
                'status' => 'active'
            ],
            [
                'name' => 'Professional Development',
                'description' => 'Evaluates commitment to continuous learning, skill enhancement, and career growth initiatives',
                'status' => 'active'
            ],
            [
                'name' => 'Attendance and Punctuality',
                'description' => 'Measures reliability in attendance, punctuality, and adherence to work schedules and policies',
                'status' => 'active'
            ]
        ];

        foreach ($companies as $company) {
            foreach ($categories as $categoryData) {
                // Check if category already exists for this company
                if (PerformanceIndicatorCategory::where('name', $categoryData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    PerformanceIndicatorCategory::create([
                        'name' => $categoryData['name'],
                        'description' => $categoryData['description'],
                        'status' => $categoryData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create performance indicator category: ' . $categoryData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('PerformanceIndicatorCategory seeder completed successfully!');
    }
}
