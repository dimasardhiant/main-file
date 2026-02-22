<?php

namespace Database\Seeders;

use App\Models\CandidateAssessment;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Database\Seeder;

class CandidateAssessmentSeeder extends Seeder
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

        // Fixed assessment data for consistent results
        $assessmentData = [
            [
                'assessment_name' => 'Technical Skills Assessment',
                'score' => 85,
                'max_score' => 100,
                'pass_fail_status' => 'Pass',
                'comments' => 'Strong technical knowledge demonstrated. Excellent problem-solving skills and good understanding of programming concepts.',
                'days_ago' => 3
            ],
            [
                'assessment_name' => 'Aptitude Test',
                'score' => 78,
                'max_score' => 100,
                'pass_fail_status' => 'Pass',
                'comments' => 'Good analytical and logical reasoning abilities. Shows potential for learning and adaptation.',
                'days_ago' => 5
            ],
            [
                'assessment_name' => 'Communication Skills Test',
                'score' => 92,
                'max_score' => 100,
                'pass_fail_status' => 'Pass',
                'comments' => 'Excellent verbal and written communication skills. Clear articulation and professional presentation.',
                'days_ago' => 2
            ],
            [
                'assessment_name' => 'Personality Assessment',
                'score' => 88,
                'max_score' => 100,
                'pass_fail_status' => 'Pass',
                'comments' => 'Well-balanced personality traits. Shows good teamwork potential and leadership qualities.',
                'days_ago' => 4
            ],
            [
                'assessment_name' => 'Domain Knowledge Test',
                'score' => 65,
                'max_score' => 100,
                'pass_fail_status' => 'Fail',
                'comments' => 'Basic understanding of domain concepts but lacks depth in specialized areas. Requires additional training.',
                'days_ago' => 6
            ],
            [
                'assessment_name' => 'Coding Challenge',
                'score' => 95,
                'max_score' => 100,
                'pass_fail_status' => 'Pass',
                'comments' => 'Outstanding coding skills with clean, efficient solutions. Demonstrates excellent algorithmic thinking.',
                'days_ago' => 1
            ],
            [
                'assessment_name' => 'Case Study Analysis',
                'score' => 72,
                'max_score' => 100,
                'pass_fail_status' => 'Pass',
                'comments' => 'Good analytical approach to business problems. Shows practical thinking and solution-oriented mindset.',
                'days_ago' => 7
            ],
            [
                'assessment_name' => 'Group Discussion',
                'score' => 80,
                'max_score' => 100,
                'pass_fail_status' => 'Pass',
                'comments' => 'Active participation in group discussions. Good listening skills and ability to contribute meaningfully.',
                'days_ago' => 8
            ],
            [
                'assessment_name' => 'Presentation Skills',
                'score' => 45,
                'max_score' => 100,
                'pass_fail_status' => 'Fail',
                'comments' => 'Needs significant improvement in presentation delivery. Lacks confidence and clarity in communication.',
                'days_ago' => 9
            ],
            [
                'assessment_name' => 'Logical Reasoning Test',
                'score' => null,
                'max_score' => 100,
                'pass_fail_status' => 'Pending',
                'comments' => 'Assessment scheduled but not yet completed. Candidate to appear for test next week.',
                'days_ago' => 0
            ]
        ];

        foreach ($companies as $company) {
            // Get candidates for this company
            $candidates = Candidate::where('created_by', $company->id)->get();

            if ($candidates->isEmpty()) {
                $this->command->warn('No candidates found for company: ' . $company->name . '. Please run CandidateSeeder first.');
                continue;
            }

            // Get employees who can conduct assessments
            $assessors = User::whereIn('type', ['manager', 'hr', 'employee'])
                ->where('created_by', $company->id)
                ->get();

            if ($assessors->isEmpty()) {
                $this->command->warn('No assessors found for company: ' . $company->name);
                continue;
            }

            // Create assessments for first 5 candidates
            $selectedCandidates = $candidates->take(7);

            foreach ($selectedCandidates as $candIndex => $candidate) {
                // Create 2 assessments per candidate
                for ($assessIndex = 0; $assessIndex < 2; $assessIndex++) {
                    $dataIndex = ($candIndex * 2) + $assessIndex;
                    $assessment = $assessmentData[$dataIndex % 10];

                    // Select assessor from first 5
                    $selectedAssessors = $assessors->take(5);
                    $assessor = $selectedAssessors->random();

                    $assessmentDate = $assessment['days_ago'] > 0 ?
                        date('Y-m-d', strtotime('-' . $assessment['days_ago'] . ' days')) :
                        date('Y-m-d');

                    try {
                        CandidateAssessment::create([
                            'candidate_id' => $candidate->id,
                            'assessment_name' => $assessment['assessment_name'],
                            'score' => $assessment['score'],
                            'max_score' => $assessment['max_score'],
                            'pass_fail_status' => $assessment['pass_fail_status'],
                            'comments' => $assessment['comments'],
                            'conducted_by' => $assessor->id,
                            'assessment_date' => $assessmentDate,
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create assessment for candidate: ' . $candidate->first_name . ' ' . $candidate->last_name . ' in company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('CandidateAssessment seeder completed successfully!');
    }
}
