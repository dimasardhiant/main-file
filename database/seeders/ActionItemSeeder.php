<?php

namespace Database\Seeders;

use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActionItemSeeder extends Seeder
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

        // Fixed action items for consistent data
        $actionItemsData = [
            [
                'title' => 'Implement Code Review Process',
                'description' => 'Research and implement automated code review process using GitHub Actions for better code quality',
                'due_days' => 7,
                'priority' => 'High',
                'status' => 'In Progress',
                'progress_percentage' => 60,
                'notes' => 'GitHub Actions workflow created, testing in progress'
            ],
            [
                'title' => 'Prepare Weekly Status Report',
                'description' => 'Create comprehensive weekly status report including progress metrics and risk assessments',
                'due_days' => 3,
                'priority' => 'Medium',
                'status' => 'Completed',
                'progress_percentage' => 100,
                'notes' => 'Report completed and shared with stakeholders'
            ],
            [
                'title' => 'Schedule Follow-up Training',
                'description' => 'Organize advanced training session for team members on new technologies and best practices',
                'due_days' => 14,
                'priority' => 'Medium',
                'status' => 'Not Started',
                'progress_percentage' => 0,
                'notes' => 'Waiting for trainer availability confirmation'
            ],
            [
                'title' => 'Update Project Documentation',
                'description' => 'Review and update project documentation to reflect recent changes and new requirements',
                'due_days' => 5,
                'priority' => 'Low',
                'status' => 'In Progress',
                'progress_percentage' => 30,
                'notes' => 'Started with API documentation updates'
            ],
            [
                'title' => 'Conduct Risk Assessment',
                'description' => 'Perform comprehensive risk analysis for upcoming project phases and prepare mitigation strategies',
                'due_days' => 10,
                'priority' => 'High',
                'status' => 'Not Started',
                'progress_percentage' => 0,
                'notes' => 'Risk assessment template being prepared'
            ],
            [
                'title' => 'Client Presentation Preparation',
                'description' => 'Prepare quarterly business review presentation with performance metrics and future roadmap',
                'due_days' => -2,
                'priority' => 'Critical',
                'status' => 'Overdue',
                'progress_percentage' => 80,
                'notes' => 'Presentation 80% complete, pending final review'
            ],
            [
                'title' => 'Team Performance Evaluation',
                'description' => 'Complete individual performance evaluations for all team members and prepare development plans',
                'due_days' => 21,
                'priority' => 'Medium',
                'status' => 'Not Started',
                'progress_percentage' => 0,
                'notes' => 'Performance evaluation forms being finalized'
            ],
            [
                'title' => 'Budget Review and Planning',
                'description' => 'Review current budget allocation and prepare budget plan for next quarter',
                'due_days' => 15,
                'priority' => 'High',
                'status' => 'In Progress',
                'progress_percentage' => 45,
                'notes' => 'Current budget analysis completed, planning phase started'
            ],
            [
                'title' => 'Security Audit Implementation',
                'description' => 'Implement security recommendations from recent audit and update security protocols',
                'due_days' => 30,
                'priority' => 'Critical',
                'status' => 'Not Started',
                'progress_percentage' => 0,
                'notes' => 'Security team assigned, implementation plan pending'
            ],
            [
                'title' => 'Customer Feedback Analysis',
                'description' => 'Analyze recent customer feedback and prepare improvement recommendations',
                'due_days' => -5,
                'priority' => 'Medium',
                'status' => 'Completed',
                'progress_percentage' => 100,
                'notes' => 'Analysis completed, recommendations shared with product team'
            ]
        ];

        foreach ($companies as $company) {
            // Get meetings for this company
            $meetings = Meeting::where('created_by', $company->id)->get();

            if ($meetings->isEmpty()) {
                $this->command->warn('No meetings found for company: ' . $company->name . '. Please run MeetingSeeder first.');
                continue;
            }

            // Get employees for assignment
            $employees = User::whereIn('type', ['manager', 'hr', 'employee'])
                ->where('created_by', $company->id)
                ->get();

            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name);
                continue;
            }

            // Create action items for first 5 meetings
            $selectedMeetings = $meetings->take(5);

            foreach ($selectedMeetings as $meetingIndex => $meeting) {
                // Create 2 action items per meeting
                for ($itemIndex = 0; $itemIndex < 2; $itemIndex++) {
                    $dataIndex = ($meetingIndex * 2) + $itemIndex;
                    $actionData = $actionItemsData[$dataIndex % 10];

                    // Select assignee from first 8 employees
                    $selectedEmployees = $employees->take(8);
                    $assignee = $selectedEmployees->skip($dataIndex % 8)->first() ?: $selectedEmployees->first();

                    $dueDate = date('Y-m-d', strtotime($meeting->meeting_date . ' +' . $actionData['due_days'] . ' days'));
                    $completedDate = $actionData['status'] === 'Completed' ?
                        date('Y-m-d', strtotime($dueDate . ' -1 day')) : null;

                    // Check if action item already exists for this meeting and title
                    if (ActionItem::where('meeting_id', $meeting->id)
                                 ->where('title', $actionData['title'])
                                 ->exists()) {
                        continue;
                    }

                    try {
                        ActionItem::create([
                            'meeting_id' => $meeting->id,
                            'title' => $actionData['title'],
                            'description' => $actionData['description'],
                            'assigned_to' => $assignee->id,
                            'due_date' => $dueDate,
                            'priority' => $actionData['priority'],
                            'status' => $actionData['status'],
                            'progress_percentage' => $actionData['progress_percentage'],
                            'notes' => $actionData['notes'],
                            'completed_date' => $completedDate,
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create action item: ' . $actionData['title'] . ' for meeting: ' . $meeting->title . ' in company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('ActionItem seeder completed successfully!');
    }
}
