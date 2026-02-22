<?php

namespace Database\Seeders;

use App\Models\CandidateSource;
use App\Models\User;
use Illuminate\Database\Seeder;

class CandidateSourceSeeder extends Seeder
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
        
        // Fixed candidate sources for consistent data
        $candidateSources = [
            [
                'name' => 'Company Website',
                'description' => 'Candidates who applied directly through the company career page and job portal',
                'status' => 'active'
            ],
            [
                'name' => 'LinkedIn',
                'description' => 'Professional networking platform for recruiting and sourcing qualified candidates',
                'status' => 'active'
            ],
            [
                'name' => 'Naukri.com',
                'description' => 'Leading job portal in India for posting jobs and finding qualified candidates',
                'status' => 'active'
            ],
            [
                'name' => 'Indeed',
                'description' => 'Global job search engine and recruitment platform for candidate sourcing',
                'status' => 'active'
            ],
            [
                'name' => 'Employee Referral',
                'description' => 'Candidates referred by existing employees through internal referral program',
                'status' => 'active'
            ],
            [
                'name' => 'Recruitment Agency',
                'description' => 'External recruitment agencies and headhunters providing candidate sourcing services',
                'status' => 'active'
            ],
            [
                'name' => 'Campus Recruitment',
                'description' => 'Fresh graduates recruited directly from colleges and universities through campus drives',
                'status' => 'active'
            ],
            [
                'name' => 'Walk-in Interview',
                'description' => 'Candidates who attended walk-in interviews and recruitment events organized by company',
                'status' => 'active'
            ],
            [
                'name' => 'Social Media',
                'description' => 'Candidates sourced through social media platforms like Facebook, Twitter, and Instagram',
                'status' => 'active'
            ],
            [
                'name' => 'Job Fair',
                'description' => 'Candidates met and recruited through job fairs and career expo events',
                'status' => 'active'
            ],
            [
                'name' => 'Direct Application',
                'description' => 'Unsolicited applications received directly from candidates via email or post',
                'status' => 'active'
            ],
            [
                'name' => 'Professional Network',
                'description' => 'Candidates sourced through professional contacts, industry connections, and networking events',
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($candidateSources as $sourceData) {
                // Check if candidate source already exists for this company
                if (CandidateSource::where('name', $sourceData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    CandidateSource::create([
                        'name' => $sourceData['name'],
                        'description' => $sourceData['description'],
                        'status' => $sourceData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create candidate source: ' . $sourceData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('CandidateSource seeder completed successfully!');
    }
}