<?php

namespace Database\Seeders;

use App\Models\MeetingMinute;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeetingMinuteSeeder extends Seeder
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

        // Fixed meeting minutes templates by meeting type
        $minuteTemplates = [
            'Team Meeting' => [
                ['topic' => 'Sprint Progress Review', 'content' => 'Team reviewed current sprint progress. 80% of planned tasks completed. Discussed blockers and dependencies for remaining tasks.', 'type' => 'Discussion'],
                ['topic' => 'Resource Allocation Decision', 'content' => 'Decided to allocate additional developer resources to Project Alpha. Sarah will join the team starting next Monday.', 'type' => 'Decision'],
                ['topic' => 'Code Review Process', 'content' => 'Action item: Implement automated code review process using GitHub Actions. John to research and propose solution by Friday.', 'type' => 'Action Item'],
                ['topic' => 'Team Building Event', 'content' => 'Note: Team building event scheduled for next month. HR will send details via email.', 'type' => 'Note']
            ],
            'Client Meeting' => [
                ['topic' => 'Project Scope Discussion', 'content' => 'Client requested additional features for the mobile app. Discussed timeline implications and budget adjustments required.', 'type' => 'Discussion'],
                ['topic' => 'Delivery Timeline Approval', 'content' => 'Client approved the revised delivery timeline. New deadline set for March 15th with phased rollout approach.', 'type' => 'Decision'],
                ['topic' => 'Weekly Status Reports', 'content' => 'Action item: Provide weekly status reports every Friday. Include progress metrics and risk assessments.', 'type' => 'Action Item'],
                ['topic' => 'Next Meeting Schedule', 'content' => 'Note: Next client meeting scheduled for two weeks from today at 2 PM.', 'type' => 'Note']
            ],
            'Board Meeting' => [
                ['topic' => 'Financial Performance Review', 'content' => 'Q4 financial results exceeded expectations. Revenue increased by 15% compared to previous quarter. Cost optimization initiatives showing positive results.', 'type' => 'Discussion'],
                ['topic' => 'Strategic Investment Approval', 'content' => 'Board approved $2M investment in new technology infrastructure. Implementation to begin in Q2.', 'type' => 'Decision'],
                ['topic' => 'Market Expansion Plan', 'content' => 'Action item: Prepare detailed market expansion plan for European markets. Present findings at next board meeting.', 'type' => 'Action Item'],
                ['topic' => 'Regulatory Compliance', 'content' => 'Note: New regulatory requirements effective from next quarter. Legal team monitoring developments.', 'type' => 'Note']
            ],
            'Training Session' => [
                ['topic' => 'Learning Objectives Review', 'content' => 'Reviewed training objectives and expected outcomes. Participants demonstrated good understanding of core concepts.', 'type' => 'Discussion'],
                ['topic' => 'Certification Requirements', 'content' => 'Decided that all participants must complete assessment within 30 days to receive certification.', 'type' => 'Decision'],
                ['topic' => 'Follow-up Training', 'content' => 'Action item: Schedule follow-up training session for advanced topics. Trainer to prepare curriculum by next week.', 'type' => 'Action Item'],
                ['topic' => 'Training Materials', 'content' => 'Note: Training materials will be shared via company portal within 24 hours.', 'type' => 'Note']
            ],
            'Project Review' => [
                ['topic' => 'Milestone Achievement', 'content' => 'Project successfully completed Phase 2 milestones. All deliverables met quality standards and timeline requirements.', 'type' => 'Discussion'],
                ['topic' => 'Budget Reallocation', 'content' => 'Approved budget reallocation from marketing to development to address technical challenges in Phase 3.', 'type' => 'Decision'],
                ['topic' => 'Risk Mitigation Plan', 'content' => 'Action item: Develop comprehensive risk mitigation plan for identified technical risks. Present plan next week.', 'type' => 'Action Item'],
                ['topic' => 'Stakeholder Communication', 'content' => 'Note: Regular stakeholder updates to be sent bi-weekly starting from next month.', 'type' => 'Note']
            ],
            'Performance Review' => [
                ['topic' => 'Goal Achievement Assessment', 'content' => 'Employee exceeded 90% of set objectives for the review period. Demonstrated strong performance in key areas.', 'type' => 'Discussion'],
                ['topic' => 'Promotion Recommendation', 'content' => 'Decided to recommend employee for promotion to Senior level based on consistent performance and leadership qualities.', 'type' => 'Decision'],
                ['topic' => 'Professional Development Plan', 'content' => 'Action item: Create personalized professional development plan focusing on leadership skills and technical expertise.', 'type' => 'Action Item'],
                ['topic' => 'Next Review Schedule', 'content' => 'Note: Next performance review scheduled for six months from today.', 'type' => 'Note']
            ]
        ];

        foreach ($companies as $company) {
            // Get completed meetings for this company
            $meetings = Meeting::where('created_by', $company->id)
                ->where('status', 'Completed')
                ->get();            

            if ($meetings->isEmpty()) {
                $this->command->warn('No completed meetings found for company: ' . $company->name . '. Please run MeetingSeeder first.');
                continue;
            }

            // Get employees for recording minutes
            $employees = User::whereIn('type', ['manager', 'hr', 'employee'])
                ->where('created_by', $company->id)
                ->get();

            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name);
                continue;
            }

            foreach ($meetings as $meeting) {
                $meetingTypeName = $meeting->type->name ?? 'Team Meeting';
                $minutes = $minuteTemplates[$meetingTypeName] ?? $minuteTemplates['Team Meeting'];

                // Select recorder from first 5 employees
                $selectedEmployees = $employees->take(5);
                $recorder = $selectedEmployees->first();

                $recordedAt = now()->subDays(1)->format('Y-m-d H:i:s');

                foreach ($minutes as $index => $minute) {
                    try {
                        MeetingMinute::create([
                            'meeting_id' => $meeting->id,
                            'topic' => $minute['topic'],
                            'content' => $minute['content'],
                            'type' => $minute['type'],
                            'recorded_by' => $meeting->organizer_id,
                            'recorded_at' => $recordedAt,
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error($e->getMessage());
                        continue;
                    }
                }
            }
        }

        $this->command->info('MeetingMinute seeder completed successfully!');
    }
}
