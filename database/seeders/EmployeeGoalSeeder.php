<?php

namespace Database\Seeders;

use App\Models\EmployeeGoal;
use App\Models\GoalType;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeGoalSeeder extends Seeder
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
        
        $currentYear = date('Y');
        
        // Fixed employee goals by goal type for consistent data
        $goalsByType = [
            'Performance Goals' => [
                ['title' => 'Increase Task Completion Rate', 'description' => 'Improve task completion rate from 85% to 95% by implementing better time management strategies', 'target' => '95% completion rate', 'progress' => 75, 'status' => 'in_progress'],
                ['title' => 'Enhance Productivity Metrics', 'description' => 'Achieve 20% increase in daily productivity by optimizing workflow processes', 'target' => '20% productivity increase', 'progress' => 60, 'status' => 'in_progress'],
                ['title' => 'Meet Quality Standards', 'description' => 'Maintain consistent quality standards with zero critical errors in deliverables', 'target' => 'Zero critical errors', 'progress' => 90, 'status' => 'in_progress']
            ],
            'Career Development Goals' => [
                ['title' => 'Obtain Professional Certification', 'description' => 'Complete industry-relevant certification to enhance professional credentials', 'target' => '1 certification', 'progress' => 40, 'status' => 'in_progress'],
                ['title' => 'Develop Leadership Skills', 'description' => 'Participate in leadership development program and apply learned skills', 'target' => 'Complete leadership program', 'progress' => 30, 'status' => 'in_progress'],
                ['title' => 'Expand Technical Expertise', 'description' => 'Master new technology stack relevant to current role and future opportunities', 'target' => 'Master 2 new technologies', 'progress' => 50, 'status' => 'in_progress']
            ],
            'Learning and Training Goals' => [
                ['title' => 'Complete Mandatory Training', 'description' => 'Finish all required compliance and safety training modules within deadline', 'target' => '100% training completion', 'progress' => 100, 'status' => 'completed'],
                ['title' => 'Attend Professional Workshops', 'description' => 'Participate in at least 3 professional development workshops this year', 'target' => '3 workshops', 'progress' => 67, 'status' => 'in_progress'],
                ['title' => 'Learn New Software Tools', 'description' => 'Gain proficiency in advanced features of job-related software applications', 'target' => 'Advanced proficiency', 'progress' => 45, 'status' => 'in_progress']
            ],
            'Project Goals' => [
                ['title' => 'Complete Project Alpha', 'description' => 'Successfully deliver Project Alpha within budget and timeline constraints', 'target' => 'On-time delivery', 'progress' => 80, 'status' => 'in_progress'],
                ['title' => 'Implement Process Improvement', 'description' => 'Lead initiative to streamline departmental processes and reduce inefficiencies', 'target' => '25% efficiency gain', 'progress' => 55, 'status' => 'in_progress'],
                ['title' => 'Launch New Product Feature', 'description' => 'Coordinate cross-functional team to launch new product feature successfully', 'target' => 'Successful launch', 'progress' => 35, 'status' => 'in_progress']
            ],
            'Sales and Revenue Goals' => [
                ['title' => 'Achieve Sales Quota', 'description' => 'Meet or exceed quarterly sales targets and contribute to revenue growth', 'target' => '110% of quota', 'progress' => 85, 'status' => 'in_progress'],
                ['title' => 'Acquire New Clients', 'description' => 'Identify and onboard 15 new clients to expand customer base', 'target' => '15 new clients', 'progress' => 70, 'status' => 'in_progress'],
                ['title' => 'Increase Customer Retention', 'description' => 'Improve customer retention rate through enhanced service and relationship management', 'target' => '95% retention rate', 'progress' => 88, 'status' => 'in_progress']
            ],
            'Quality Improvement Goals' => [
                ['title' => 'Reduce Error Rate', 'description' => 'Implement quality control measures to reduce work errors by 50%', 'target' => '50% error reduction', 'progress' => 65, 'status' => 'in_progress'],
                ['title' => 'Enhance Review Process', 'description' => 'Establish comprehensive review process to ensure deliverable quality', 'target' => 'Implement review process', 'progress' => 100, 'status' => 'completed'],
                ['title' => 'Achieve Quality Certification', 'description' => 'Work towards obtaining quality management certification for the department', 'target' => 'Quality certification', 'progress' => 25, 'status' => 'in_progress']
            ]
        ];
        
        foreach ($companies as $company) {
            // Get employees for this company
            $employees = User::where('type', 'employee')->where('created_by', $company->id)->get();
            
            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }
            
            // Get goal types for this company
            $goalTypes = GoalType::where('created_by', $company->id)->get();
            
            if ($goalTypes->isEmpty()) {
                $this->command->warn('No goal types found for company: ' . $company->name . '. Please run GoalTypeSeeder first.');
                continue;
            }
            
            // Create goals for first 3 employees (consistent data)
            $selectedEmployees = $employees->take(3);
            
            foreach ($selectedEmployees as $index => $employee) {
                foreach ($goalTypes as $goalType) {
                    $typeGoals = $goalsByType[$goalType->name] ?? [];
                    
                    // Create one goal per type for each employee
                    if (!empty($typeGoals)) {
                        $goalData = $typeGoals[$index % count($typeGoals)];
                        
                        // Check if goal already exists
                        if (EmployeeGoal::where('title', $goalData['title'])->where('employee_id', $employee->id)->exists()) {
                            continue;
                        }
                        
                        try {
                            EmployeeGoal::create([
                                'employee_id' => $employee->id,
                                'goal_type_id' => $goalType->id,
                                'title' => $goalData['title'],
                                'description' => $goalData['description'],
                                'start_date' => $currentYear . '-01-01',
                                'end_date' => $currentYear . '-12-31',
                                'target' => $goalData['target'],
                                'progress' => $goalData['progress'],
                                'status' => $goalData['status'],
                                'created_by' => $company->id,
                            ]);
                        } catch (\Exception $e) {
                            $this->command->error('Failed to create goal: ' . $goalData['title'] . ' for employee: ' . $employee->name);
                            continue;
                        }
                    }
                }
            }
        }
        
        $this->command->info('EmployeeGoal seeder completed successfully!');
    }
}