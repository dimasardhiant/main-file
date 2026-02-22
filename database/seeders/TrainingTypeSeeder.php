<?php

namespace Database\Seeders;

use App\Models\TrainingType;
use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Database\Seeder;

class TrainingTypeSeeder extends Seeder
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

        // Fixed training types for consistent data
        $trainingTypes = [
            [
                'name' => 'Orientation and Onboarding',
                'description' => 'Comprehensive introduction program for new employees covering company culture, policies, procedures, and job-specific training'
            ],
            [
                'name' => 'Technical Skills Training',
                'description' => 'Specialized training focused on developing technical competencies, software proficiency, and job-specific technical skills'
            ],
            [
                'name' => 'Leadership Development',
                'description' => 'Training program designed to develop leadership capabilities, management skills, and strategic thinking abilities'
            ],
            [
                'name' => 'Communication Skills',
                'description' => 'Training to enhance verbal, written, and interpersonal communication skills for effective workplace interaction'
            ],
            [
                'name' => 'Safety and Compliance',
                'description' => 'Mandatory training covering workplace safety protocols, regulatory compliance, and risk management procedures'
            ],
            [
                'name' => 'Customer Service Excellence',
                'description' => 'Training focused on improving customer interaction skills, service quality, and customer satisfaction techniques'
            ],
            [
                'name' => 'Sales and Marketing',
                'description' => 'Training program covering sales techniques, marketing strategies, customer relationship management, and revenue generation'
            ],
            [
                'name' => 'Project Management',
                'description' => 'Training on project planning, execution, monitoring, and delivery methodologies including agile and traditional approaches'
            ],
            [
                'name' => 'Quality Management',
                'description' => 'Training focused on quality standards, process improvement, quality control measures, and continuous improvement methodologies'
            ],
            [
                'name' => 'Digital Literacy',
                'description' => 'Training to enhance digital skills, software applications, online tools, and technology adoption in the workplace'
            ],
            [
                'name' => 'Soft Skills Development',
                'description' => 'Training covering teamwork, problem-solving, time management, adaptability, and other essential workplace soft skills'
            ],
            [
                'name' => 'Professional Certification',
                'description' => 'Training programs designed to prepare employees for industry-recognized professional certifications and credentials'
            ]
        ];

        foreach ($companies as $company) {
            // Get branches for this company
            $branches = Branch::where('created_by', $company->id)->get();

            if ($branches->isEmpty()) {
                $this->command->warn('No branches found for company: ' . $company->name . '. Please run BranchSeeder first.');
                continue;
            }

            // Get departments for this company
            $departments = Department::where('created_by', $company->id)->get();

            if ($departments->isEmpty()) {
                $this->command->warn('No departments found for company: ' . $company->name . '. Please run DepartmentSeeder first.');
                continue;
            }

            foreach ($trainingTypes as $index => $trainingTypeData) {
                // Assign training type to specific branch (cycling through branches)
                $branch = $branches[$index % $branches->count()];

                // Check if training type already exists for this branch
                if (TrainingType::where('name', $trainingTypeData['name'])->where('branch_id', $branch->id)->exists()) {
                    continue;
                }

                try {
                    $trainingType = TrainingType::create([
                        'name' => $trainingTypeData['name'],
                        'description' => $trainingTypeData['description'],
                        'branch_id' => $branch->id,
                        'created_by' => $company->id,
                    ]);

                    // Attach training type to departments within the same branch
                    $branchDepartments = $departments->where('branch_id', $branch->id);

                    if ($branchDepartments->isNotEmpty()) {
                        if ($index < 6) {
                            // Company-wide training types: attach to all departments in this branch
                            $trainingType->departments()->attach($branchDepartments->pluck('id'));
                        } else {
                            // Department-specific training types: attach to first department in this branch
                            $trainingType->departments()->attach($branchDepartments->first()->id);
                        }
                    }
                } catch (\Exception $e) {
                    $this->command->error('Failed to create training type: ' . $trainingTypeData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        $this->command->info('TrainingType seeder completed successfully!');
    }
}
