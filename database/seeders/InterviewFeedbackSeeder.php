<?php

namespace Database\Seeders;

use App\Models\InterviewFeedback;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Database\Seeder;

class InterviewFeedbackSeeder extends Seeder
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

        // Fixed feedback data for consistent results
        $feedbackData = [
            [
                'technical_rating' => 4,
                'communication_rating' => 5,
                'cultural_fit_rating' => 4,
                'overall_rating' => 4,
                'strengths' => 'Strong technical skills, excellent problem-solving abilities, good understanding of software development principles',
                'weaknesses' => 'Could improve knowledge in advanced algorithms, needs more experience with cloud technologies',
                'comments' => 'Candidate demonstrates solid technical foundation and shows potential for growth. Communication skills are excellent.',
                'recommendation' => 'Hire'
            ],
            [
                'technical_rating' => 5,
                'communication_rating' => 4,
                'cultural_fit_rating' => 5,
                'overall_rating' => 5,
                'strengths' => 'Exceptional technical expertise, innovative thinking, strong leadership potential, excellent team collaboration',
                'weaknesses' => 'Sometimes over-engineers solutions, could benefit from more business context understanding',
                'comments' => 'Outstanding candidate with impressive technical skills and great cultural fit. Highly recommended for the position.',
                'recommendation' => 'Strong Hire'
            ],
            [
                'technical_rating' => 3,
                'communication_rating' => 3,
                'cultural_fit_rating' => 3,
                'overall_rating' => 3,
                'strengths' => 'Basic technical knowledge, willing to learn, shows enthusiasm for the role',
                'weaknesses' => 'Limited experience with required technologies, needs improvement in communication skills',
                'comments' => 'Candidate has potential but requires significant training and mentoring to meet job requirements.',
                'recommendation' => 'Maybe'
            ],
            [
                'technical_rating' => 2,
                'communication_rating' => 2,
                'cultural_fit_rating' => 2,
                'overall_rating' => 2,
                'strengths' => 'Shows interest in learning, has basic understanding of fundamental concepts',
                'weaknesses' => 'Lacks required technical skills, poor communication, insufficient experience for the role',
                'comments' => 'Candidate does not meet the minimum requirements for this position. Significant skill gaps identified.',
                'recommendation' => 'Reject'
            ],
            [
                'technical_rating' => 4,
                'communication_rating' => 4,
                'cultural_fit_rating' => 4,
                'overall_rating' => 4,
                'strengths' => 'Good technical skills, clear communication, positive attitude, relevant experience',
                'weaknesses' => 'Could improve depth in certain technical areas, needs more exposure to large-scale systems',
                'comments' => 'Solid candidate with good all-around skills. Would be a good addition to the team with proper onboarding.',
                'recommendation' => 'Hire'
            ],
            [
                'technical_rating' => 1,
                'communication_rating' => 1,
                'cultural_fit_rating' => 1,
                'overall_rating' => 1,
                'strengths' => 'Punctual for the interview, shows basic interest in the field',
                'weaknesses' => 'Severely lacking technical skills, poor communication, does not understand job requirements',
                'comments' => 'Candidate is not suitable for this position. Major skill and experience gaps that cannot be addressed.',
                'recommendation' => 'Strong Reject'
            ],
            [
                'technical_rating' => 5,
                'communication_rating' => 5,
                'cultural_fit_rating' => 4,
                'overall_rating' => 5,
                'strengths' => 'Excellent technical expertise, outstanding communication, proven track record, strong problem-solving',
                'weaknesses' => 'May be overqualified for some aspects of the role, high salary expectations',
                'comments' => 'Exceptional candidate who would bring significant value to the organization. Highly recommended.',
                'recommendation' => 'Strong Hire'
            ],
            [
                'technical_rating' => 3,
                'communication_rating' => 4,
                'cultural_fit_rating' => 3,
                'overall_rating' => 3,
                'strengths' => 'Good communication skills, shows enthusiasm, has relevant educational background',
                'weaknesses' => 'Limited practical experience, needs training in key technical areas, lacks industry exposure',
                'comments' => 'Candidate has potential but would require significant investment in training and development.',
                'recommendation' => 'Maybe'
            ]
        ];

        foreach ($companies as $company) {
            // Get completed interviews for this company
            $interviews = Interview::where('created_by', $company->id)
                ->where('status', 'Completed')
                ->get();

            if ($interviews->isEmpty()) {
                $this->command->warn('No completed interviews found for company: ' . $company->name . '. Please run InterviewSeeder first.');
                continue;
            }

            foreach ($interviews as $index => $interview) {
                $feedback = $feedbackData[$index % 8];

                // Get interviewers from the interview
                $interviewers = is_array($interview->interviewers) ? $interview->interviewers : [];

                // Create feedback for each interviewer
                foreach ($interviewers as $interviewerIndex => $interviewerId) {
                    // Check if feedback already exists
                    if (InterviewFeedback::where('interview_id', $interview->id)
                        ->where('interviewer_id', $interviewerId)
                        ->exists()
                    ) {
                        continue;
                    }

                    try {
                        InterviewFeedback::create([
                            'interview_id' => $interview->id,
                            'interviewer_id' => $interviewerId,
                            'technical_rating' => $feedback['technical_rating'],
                            'communication_rating' => $feedback['communication_rating'],
                            'cultural_fit_rating' => $feedback['cultural_fit_rating'],
                            'overall_rating' => $feedback['overall_rating'],
                            'strengths' => $feedback['strengths'],
                            'weaknesses' => $feedback['weaknesses'],
                            'comments' => $feedback['comments'],
                            'recommendation' => $feedback['recommendation'],
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create interview feedback for interview ID: ' . $interview->id . ' in company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('InterviewFeedback seeder completed successfully!');
    }
}
