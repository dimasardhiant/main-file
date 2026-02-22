<?php

namespace Database\Seeders;

use App\Models\GoalType;
use App\Models\User;
use Illuminate\Database\Seeder;

class GoalTypeSeeder extends Seeder
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
        
        // Fixed goal types for consistent data
        $goalTypes = [
            [
                'name' => 'Performance Goals',
                'description' => 'Goals focused on improving individual or team performance metrics, productivity, and efficiency in daily work activities',
                'status' => 'active'
            ],
            [
                'name' => 'Career Development Goals',
                'description' => 'Goals aimed at professional growth, skill enhancement, and career advancement opportunities within the organization',
                'status' => 'active'
            ],
            [
                'name' => 'Learning and Training Goals',
                'description' => 'Goals related to acquiring new knowledge, completing training programs, and developing competencies required for current or future roles',
                'status' => 'active'
            ],
            [
                'name' => 'Project Goals',
                'description' => 'Specific objectives related to project completion, deliverables, milestones, and project success criteria',
                'status' => 'active'
            ],
            [
                'name' => 'Sales and Revenue Goals',
                'description' => 'Targets focused on achieving sales quotas, revenue generation, customer acquisition, and business growth objectives',
                'status' => 'active'
            ],
            [
                'name' => 'Quality Improvement Goals',
                'description' => 'Goals aimed at enhancing work quality, reducing errors, improving processes, and maintaining high standards of excellence',
                'status' => 'active'
            ],
            [
                'name' => 'Customer Service Goals',
                'description' => 'Objectives focused on improving customer satisfaction, service quality, response times, and customer relationship management',
                'status' => 'active'
            ],
            [
                'name' => 'Innovation Goals',
                'description' => 'Goals related to creative thinking, process innovation, new idea generation, and implementing innovative solutions',
                'status' => 'active'
            ],
            [
                'name' => 'Team Collaboration Goals',
                'description' => 'Objectives aimed at improving teamwork, communication, cross-functional collaboration, and team effectiveness',
                'status' => 'active'
            ],
            [
                'name' => 'Leadership Goals',
                'description' => 'Goals focused on developing leadership skills, mentoring abilities, decision-making capabilities, and team management competencies',
                'status' => 'active'
            ],
            [
                'name' => 'Operational Excellence Goals',
                'description' => 'Objectives related to improving operational efficiency, streamlining processes, and optimizing resource utilization',
                'status' => 'active'
            ],
            [
                'name' => 'Compliance and Safety Goals',
                'description' => 'Goals focused on maintaining regulatory compliance, workplace safety standards, and adherence to company policies and procedures',
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($goalTypes as $goalTypeData) {
                // Check if goal type already exists for this company
                if (GoalType::where('name', $goalTypeData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    GoalType::create([
                        'name' => $goalTypeData['name'],
                        'description' => $goalTypeData['description'],
                        'status' => $goalTypeData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create goal type: ' . $goalTypeData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('GoalType seeder completed successfully!');
    }
}