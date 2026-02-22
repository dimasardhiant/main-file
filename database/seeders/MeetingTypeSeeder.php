<?php

namespace Database\Seeders;

use App\Models\MeetingType;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeetingTypeSeeder extends Seeder
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
        
        // Fixed meeting types for consistent data
        $meetingTypes = [
            [
                'name' => 'Team Meeting',
                'description' => 'Regular team meetings for project updates, discussions, and coordination',
                'color' => '#3B82F6',
                'default_duration' => 60,
                'status' => 'active'
            ],
            [
                'name' => 'One-on-One',
                'description' => 'Individual meetings between manager and team member for performance discussions and feedback',
                'color' => '#10b77f',
                'default_duration' => 30,
                'status' => 'active'
            ],
            [
                'name' => 'Client Meeting',
                'description' => 'Meetings with clients for project discussions, presentations, and business development',
                'color' => '#F59E0B',
                'default_duration' => 90,
                'status' => 'active'
            ],
            [
                'name' => 'Board Meeting',
                'description' => 'Executive board meetings for strategic decisions and company governance',
                'color' => '#EF4444',
                'default_duration' => 120,
                'status' => 'active'
            ],
            [
                'name' => 'Training Session',
                'description' => 'Training and development sessions for skill enhancement and knowledge sharing',
                'color' => '#8B5CF6',
                'default_duration' => 120,
                'status' => 'active'
            ],
            [
                'name' => 'Interview',
                'description' => 'Job interviews for candidate evaluation and recruitment process',
                'color' => '#06B6D4',
                'default_duration' => 45,
                'status' => 'active'
            ],
            [
                'name' => 'Project Review',
                'description' => 'Project milestone reviews, progress assessments, and deliverable evaluations',
                'color' => '#84CC16',
                'default_duration' => 90,
                'status' => 'active'
            ],
            [
                'name' => 'All Hands',
                'description' => 'Company-wide meetings for announcements, updates, and organizational communication',
                'color' => '#F97316',
                'default_duration' => 60,
                'status' => 'active'
            ],
            [
                'name' => 'Performance Review',
                'description' => 'Employee performance evaluation meetings and appraisal discussions',
                'color' => '#EC4899',
                'default_duration' => 60,
                'status' => 'active'
            ],
            [
                'name' => 'Brainstorming',
                'description' => 'Creative sessions for idea generation, problem-solving, and innovation discussions',
                'color' => '#6366F1',
                'default_duration' => 90,
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($meetingTypes as $typeData) {
                // Check if meeting type already exists for this company
                if (MeetingType::where('name', $typeData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    MeetingType::create([
                        'name' => $typeData['name'],
                        'description' => $typeData['description'],
                        'color' => $typeData['color'],
                        'default_duration' => $typeData['default_duration'],
                        'status' => $typeData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create meeting type: ' . $typeData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('MeetingType seeder completed successfully!');
    }
}