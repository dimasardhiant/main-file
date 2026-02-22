<?php

namespace Database\Seeders;

use App\Models\PerformanceIndicator;
use App\Models\PerformanceIndicatorCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class PerformanceIndicatorSeeder extends Seeder
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

        // Fixed performance indicators by category for consistent data
        $indicatorsByCategory = [
            'Job Performance' => [
                ['name' => 'Task Completion Rate', 'description' => 'Percentage of assigned tasks completed within specified timeframes', 'unit' => 'Percentage', 'target' => '95%'],
                ['name' => 'Goal Achievement', 'description' => 'Success rate in achieving individual and team objectives', 'unit' => 'Percentage', 'target' => '90%'],
                ['name' => 'Productivity Level', 'description' => 'Output efficiency compared to established benchmarks', 'unit' => 'Rating', 'target' => '4/5']
            ],
            'Quality of Work' => [
                ['name' => 'Error Rate', 'description' => 'Frequency of mistakes or defects in work output', 'unit' => 'Percentage', 'target' => '<5%'],
                ['name' => 'Accuracy Level', 'description' => 'Precision and correctness in completing tasks and deliverables', 'unit' => 'Percentage', 'target' => '98%'],
                ['name' => 'Attention to Detail', 'description' => 'Thoroughness and care in reviewing and completing work', 'unit' => 'Rating', 'target' => '4/5']
            ],
            'Communication Skills' => [
                ['name' => 'Verbal Communication', 'description' => 'Effectiveness in spoken communication and presentations', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Written Communication', 'description' => 'Clarity and professionalism in written correspondence', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Active Listening', 'description' => 'Ability to understand and respond appropriately to others', 'unit' => 'Rating', 'target' => '4/5']
            ],
            'Teamwork and Collaboration' => [
                ['name' => 'Team Contribution', 'description' => 'Active participation and valuable input in team activities', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Cooperation Level', 'description' => 'Willingness to work with others and support team goals', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Conflict Resolution', 'description' => 'Ability to handle disagreements constructively', 'unit' => 'Rating', 'target' => '3/5']
            ],
            'Leadership and Initiative' => [
                ['name' => 'Decision Making', 'description' => 'Quality and timeliness of decisions made independently', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Initiative Taking', 'description' => 'Proactive approach to identifying and addressing opportunities', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Mentoring Ability', 'description' => 'Effectiveness in guiding and developing junior team members', 'unit' => 'Rating', 'target' => '3/5']
            ],
            'Problem Solving' => [
                ['name' => 'Analytical Thinking', 'description' => 'Ability to break down complex problems and analyze components', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Creative Solutions', 'description' => 'Innovation and creativity in developing problem solutions', 'unit' => 'Rating', 'target' => '3/5'],
                ['name' => 'Resolution Speed', 'description' => 'Efficiency in identifying and implementing solutions', 'unit' => 'Rating', 'target' => '4/5']
            ],
            'Adaptability and Flexibility' => [
                ['name' => 'Change Adaptation', 'description' => 'Ability to adjust to new processes, systems, or requirements', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Learning Agility', 'description' => 'Speed and effectiveness in acquiring new skills and knowledge', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Stress Management', 'description' => 'Ability to maintain performance under pressure', 'unit' => 'Rating', 'target' => '3/5']
            ],
            'Time Management' => [
                ['name' => 'Deadline Adherence', 'description' => 'Consistency in meeting project and task deadlines', 'unit' => 'Percentage', 'target' => '95%'],
                ['name' => 'Priority Management', 'description' => 'Effectiveness in organizing and prioritizing multiple tasks', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Efficiency Rating', 'description' => 'Optimal use of time and resources to achieve objectives', 'unit' => 'Rating', 'target' => '4/5']
            ],
            'Customer Service' => [
                ['name' => 'Customer Satisfaction', 'description' => 'Level of customer satisfaction with service provided', 'unit' => 'Rating', 'target' => '4.5/5'],
                ['name' => 'Response Time', 'description' => 'Speed in responding to customer inquiries and requests', 'unit' => 'Hours', 'target' => '<24'],
                ['name' => 'Issue Resolution', 'description' => 'Effectiveness in resolving customer problems and complaints', 'unit' => 'Percentage', 'target' => '90%']
            ],
            'Technical Competency' => [
                ['name' => 'Skill Proficiency', 'description' => 'Level of expertise in job-related technical skills', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Tool Utilization', 'description' => 'Effective use of software, systems, and technical tools', 'unit' => 'Rating', 'target' => '4/5'],
                ['name' => 'Knowledge Application', 'description' => 'Practical application of technical knowledge to solve problems', 'unit' => 'Rating', 'target' => '4/5']
            ],
            'Professional Development' => [
                ['name' => 'Training Completion', 'description' => 'Participation and completion rate in professional development programs', 'unit' => 'Percentage', 'target' => '100%'],
                ['name' => 'Skill Enhancement', 'description' => 'Progress in developing new competencies and improving existing skills', 'unit' => 'Rating', 'target' => '3/5'],
                ['name' => 'Certification Achievement', 'description' => 'Success in obtaining relevant professional certifications', 'unit' => 'Count', 'target' => '2/year']
            ],
            'Attendance and Punctuality' => [
                ['name' => 'Attendance Rate', 'description' => 'Percentage of scheduled work days attended', 'unit' => 'Percentage', 'target' => '98%'],
                ['name' => 'Punctuality Score', 'description' => 'Consistency in arriving on time for work and meetings', 'unit' => 'Percentage', 'target' => '95%'],
                ['name' => 'Leave Management', 'description' => 'Appropriate use of leave policies and advance notification', 'unit' => 'Rating', 'target' => '4/5']
            ]
        ];

        foreach ($companies as $company) {
            // Get performance indicator categories for this company
            $categories = PerformanceIndicatorCategory::where('created_by', $company->id)->get();

            if ($categories->isEmpty()) {
                $this->command->warn('No performance indicator categories found for company: ' . $company->name . '. Please run PerformanceIndicatorCategorySeeder first.');
                continue;
            }

            foreach ($categories as $category) {
                $categoryIndicators = $indicatorsByCategory[$category->name] ?? [];

                foreach ($categoryIndicators as $indicatorData) {
                    // Check if indicator already exists for this category
                    if (PerformanceIndicator::where('name', $indicatorData['name'])->where('category_id', $category->id)->exists()) {
                        continue;
                    }

                    try {
                        PerformanceIndicator::create([
                            'category_id' => $category->id,
                            'name' => $indicatorData['name'],
                            'description' => $indicatorData['description'],
                            'measurement_unit' => $indicatorData['unit'],
                            'target_value' => $indicatorData['target'],
                            'status' => 'active',
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create performance indicator: ' . $indicatorData['name'] . ' for company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('PerformanceIndicator seeder completed successfully!');
    }
}
