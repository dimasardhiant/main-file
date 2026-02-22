<?php

namespace Database\Seeders;

use App\Models\Interview;
use App\Models\Candidate;
use App\Models\InterviewRound;
use App\Models\InterviewType;
use App\Models\User;
use Illuminate\Database\Seeder;

class InterviewSeeder extends Seeder
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

        // Fixed interview data (70% completed)
        $interviewData = [
            [
                'scheduled_date' => '-1 day',
                'scheduled_time' => '10:00:00',
                'duration' => 60,
                'location' => 'Conference Room A',
                'meeting_link' => null,
                'status' => 'Completed',
                'feedback_submitted' => true,
                'interview_type' => 'Technical Interview'
            ],
            [
                'scheduled_date' => '-2 days',
                'scheduled_time' => '14:00:00',
                'duration' => 45,
                'location' => null,
                'meeting_link' => 'https://zoom.us/j/123456789',
                'status' => 'Completed',
                'feedback_submitted' => true,
                'interview_type' => 'Video Interview'
            ],
            [
                'scheduled_date' => '-3 days',
                'scheduled_time' => '11:00:00',
                'duration' => 30,
                'location' => 'HR Office',
                'meeting_link' => null,
                'status' => 'Completed',
                'feedback_submitted' => true,
                'interview_type' => 'HR Interview'
            ],
            [
                'scheduled_date' => '-4 days',
                'scheduled_time' => '15:30:00',
                'duration' => 90,
                'location' => 'Meeting Room B',
                'meeting_link' => null,
                'status' => 'Completed',
                'feedback_submitted' => true,
                'interview_type' => 'Panel Interview'
            ],
            [
                'scheduled_date' => '-5 days',
                'scheduled_time' => '09:30:00',
                'duration' => 60,
                'location' => null,
                'meeting_link' => 'https://teams.microsoft.com/meeting/join',
                'status' => 'Completed',
                'feedback_submitted' => true,
                'interview_type' => 'Behavioral Interview'
            ],
            [
                'scheduled_date' => '-6 days',
                'scheduled_time' => '16:00:00',
                'duration' => 45,
                'location' => 'Training Room',
                'meeting_link' => null,
                'status' => 'Completed',
                'feedback_submitted' => true,
                'interview_type' => 'Practical Assessment'
            ],
            [
                'scheduled_date' => '+1 day',
                'scheduled_time' => '13:00:00',
                'duration' => 30,
                'location' => null,
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
                'status' => 'Scheduled',
                'feedback_submitted' => false,
                'interview_type' => 'Phone Screening'
            ],
            [
                'scheduled_date' => '+2 days',
                'scheduled_time' => '12:00:00',
                'duration' => 120,
                'location' => 'Boardroom',
                'meeting_link' => null,
                'status' => 'Scheduled',
                'feedback_submitted' => false,
                'interview_type' => 'Case Study Interview'
            ]
        ];

        foreach ($companies as $company) {
            // Get candidates for this company
            $candidates = Candidate::where('created_by', $company->id)->where('status', 'Interview')->get();

            if ($candidates->isEmpty()) {
                $this->command->warn('No candidates found for company: ' . $company->name . '. Please run CandidateSeeder first.');
                continue;
            }

            // Get interview types for this company
            $interviewTypes = InterviewType::where('created_by', $company->id)->get();

            if ($interviewTypes->isEmpty()) {
                $this->command->warn('No interview types found for company: ' . $company->name . '. Please run InterviewTypeSeeder first.');
                continue;
            }

            // Get employees for interviewers
            $employees = User::whereIn('type', ['manager', 'hr', 'employee'])
                ->where('created_by', $company->id)
                ->get();

            // Create interviews for first 4 candidates
            $selectedCandidates = $candidates->take(5);

            foreach ($selectedCandidates as $candIndex => $candidate) {
                // Get interview rounds for this job
                $interviewRounds = InterviewRound::where('job_id', $candidate->job_id)->get();

                if ($interviewRounds->isEmpty()) {
                    continue;
                }

                // Create 2 interviews per candidate (first 3 rounds)
                $selectedRounds = $interviewRounds->take(3);

                foreach ($selectedRounds as $roundIndex => $round) {
                    // Check if interview already exists for this candidate and round
                    if (Interview::where('candidate_id', $candidate->id)
                        ->where('round_id', $round->id)
                        ->exists()) {
                        continue;
                    }

                    $dataIndex = ($candIndex * 2) + $roundIndex;
                    $interview = $interviewData[$dataIndex % 8];

                    // Find matching interview type
                    $interviewType = $interviewTypes->where('name', $interview['interview_type'])->first();
                    if (!$interviewType)
                        $interviewType = $interviewTypes->first();

                    // Select interviewers
                    $selectedInterviewers = $employees->take(2);
                    $interviewers = $selectedInterviewers->pluck('id')->map(function ($id) {
                        return (string) $id; })->toArray();

                    $scheduledDate = date('Y-m-d', strtotime($interview['scheduled_date']));

                    try {
                        Interview::create([
                            'candidate_id' => $candidate->id,
                            'job_id' => $candidate->job_id,
                            'round_id' => $round->id,
                            'interview_type_id' => $interviewType->id,
                            'scheduled_date' => $scheduledDate,
                            'scheduled_time' => $interview['scheduled_time'],
                            'duration' => $interview['duration'],
                            'location' => $interview['location'],
                            'meeting_link' => $interview['meeting_link'],
                            'interviewers' => $interviewers,
                            'status' => $interview['status'],
                            'feedback_submitted' => $interview['feedback_submitted'],
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create interview for candidate: ' . $candidate->first_name . ' ' . $candidate->last_name . ' in company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('Interview seeder completed successfully!');
    }
}
