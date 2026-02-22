<?php

namespace Database\Seeders;

use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobCategorySeeder extends Seeder
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
        
        // Fixed job categories for consistent data
        $jobCategories = [
            [
                'name' => 'Information Technology',
                'description' => 'Software development, system administration, cybersecurity, data analysis, and technical support roles',
                'status' => 'active'
            ],
            [
                'name' => 'Sales and Marketing',
                'description' => 'Sales representatives, marketing specialists, digital marketing, business development, and customer acquisition roles',
                'status' => 'active'
            ],
            [
                'name' => 'Human Resources',
                'description' => 'HR generalists, talent acquisition, employee relations, compensation and benefits, and organizational development roles',
                'status' => 'active'
            ],
            [
                'name' => 'Finance and Accounting',
                'description' => 'Financial analysts, accountants, auditors, tax specialists, and financial planning and analysis roles',
                'status' => 'active'
            ],
            [
                'name' => 'Operations and Management',
                'description' => 'Operations managers, project managers, team leads, supervisors, and executive management positions',
                'status' => 'active'
            ],
            [
                'name' => 'Customer Service',
                'description' => 'Customer support representatives, call center agents, client relations, and customer success managers',
                'status' => 'active'
            ],
            [
                'name' => 'Engineering',
                'description' => 'Mechanical engineers, electrical engineers, civil engineers, quality assurance, and technical engineering roles',
                'status' => 'active'
            ],
            [
                'name' => 'Healthcare',
                'description' => 'Medical professionals, nurses, healthcare administrators, medical technicians, and healthcare support staff',
                'status' => 'active'
            ],
            [
                'name' => 'Education and Training',
                'description' => 'Teachers, trainers, instructional designers, curriculum developers, and educational administrators',
                'status' => 'active'
            ],
            [
                'name' => 'Legal and Compliance',
                'description' => 'Legal counsel, compliance officers, paralegals, contract specialists, and regulatory affairs professionals',
                'status' => 'active'
            ],
            [
                'name' => 'Research and Development',
                'description' => 'Research scientists, product developers, innovation specialists, and research and development engineers',
                'status' => 'active'
            ],
            [
                'name' => 'Administrative and Support',
                'description' => 'Administrative assistants, office managers, data entry clerks, receptionists, and general support staff',
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($jobCategories as $categoryData) {
                // Check if job category already exists for this company
                if (JobCategory::where('name', $categoryData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    JobCategory::create([
                        'name' => $categoryData['name'],
                        'description' => $categoryData['description'],
                        'status' => $categoryData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create job category: ' . $categoryData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('JobCategory seeder completed successfully!');
    }
}