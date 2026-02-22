<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
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

        // Fixed offer data - Half Draft, Half Sent
        $offerTemplates = [
            ['status' => 'Draft', 'days_ago' => 0, 'expiry_days' => 10, 'start_days' => 21],
            ['status' => 'Sent', 'days_ago' => 2, 'expiry_days' => 10, 'start_days' => 21],
            ['status' => 'Draft', 'days_ago' => 0, 'expiry_days' => 14, 'start_days' => 28],
            ['status' => 'Sent', 'days_ago' => 3, 'expiry_days' => 14, 'start_days' => 28],
            ['status' => 'Draft', 'days_ago' => 0, 'expiry_days' => 7, 'start_days' => 14],
            ['status' => 'Sent', 'days_ago' => 1, 'expiry_days' => 7, 'start_days' => 14],
            ['status' => 'Draft', 'days_ago' => 0, 'expiry_days' => 10, 'start_days' => 21],
            ['status' => 'Sent', 'days_ago' => 2, 'expiry_days' => 10, 'start_days' => 21]
        ];

        foreach ($companies as $company) {
            // Get candidates with Offer status for this company
            $offerCandidates = Candidate::where('created_by', $company->id)
                ->where('status', 'Offer')
                ->get();

            if ($offerCandidates->isEmpty()) {
                $this->command->warn('No offer candidates found for company: ' . $company->name . '. Please run CandidateSeeder first.');
                continue;
            }

            // Get managers/HR for approval
            $approvers = User::whereIn('type', ['manager', 'hr'])
                ->where('created_by', $company->id)
                ->get();

            // Create one offer per candidate
            foreach ($offerCandidates as $index => $candidate) {
                // Check if offer already exists for this candidate in this company
                if (Offer::where('candidate_id', $candidate->id)->where('created_by', $company->id)->exists()) {
                    continue;
                }

                $offerTemplate = $offerTemplates[$index % count($offerTemplates)];

                // Get job posting to extract position and department
                $jobPosting = $candidate->job;
                if (!$jobPosting) {
                    continue;
                }

                // Select approver
                $approver = $approvers->isNotEmpty() ? $approvers->first() : null;

                $offerDate = date('Y-m-d', strtotime('-' . $offerTemplate['days_ago'] . ' days'));
                $expirationDate = date('Y-m-d', strtotime($offerDate . ' +' . $offerTemplate['expiry_days'] . ' days'));
                $startDate = date('Y-m-d', strtotime($offerDate . ' +' . $offerTemplate['start_days'] . ' days'));

                try {
                    Offer::create([
                        'candidate_id' => $candidate->id,
                        'job_id' => $candidate->job_id,
                        'offer_date' => $offerDate,
                        'position' => $candidate->job_id,
                        'department_id' => $jobPosting->department_id,
                        'salary' => $candidate->expected_salary ?? 50000,
                        'bonus' => null,
                        'equity' => null,
                        'benefits' => $jobPosting->benefits ?? null,
                        'start_date' => $startDate,
                        'expiration_date' => $expirationDate,
                        'offer_letter_path' => 'offers/' . $candidate->id . '_offer_letter.pdf',
                        'status' => $offerTemplate['status'],
                        'response_date' => null,
                        'decline_reason' => null,
                        'created_by' => $company->id,
                        'approved_by' => $offerTemplate['status'] === 'Sent' ? $approver?->id : null,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create offer for candidate: ' . $candidate->first_name . ' ' . $candidate->last_name . ' in company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('Offer seeder completed successfully!');
    }
}
