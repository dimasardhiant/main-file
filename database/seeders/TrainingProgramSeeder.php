<?php

namespace Database\Seeders;

use App\Models\TrainingProgram;
use App\Models\TrainingType;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrainingProgramSeeder extends Seeder
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

        // Fixed training programs by training type for consistent data
        $programsByType = [
            'Orientation and Onboarding' => [
                ['name' => 'New Employee Orientation Program', 'description' => 'Comprehensive 3-day orientation covering company culture, policies, and basic job requirements', 'duration' => 24, 'cost' => 5000, 'capacity' => 20, 'status' => 'active', 'prerequisites' => 'None', 'is_mandatory' => true, 'is_self_enrollment' => false],
                ['name' => 'Department Integration Workshop', 'description' => 'Specialized onboarding focused on department-specific processes and team integration', 'duration' => 8, 'cost' => 2000, 'capacity' => 15, 'status' => 'active', 'prerequisites' => 'Completion of basic orientation', 'is_mandatory' => true, 'is_self_enrollment' => false]
            ],
            'Technical Skills Training' => [
                ['name' => 'Advanced Software Development', 'description' => 'Intensive training on latest programming languages, frameworks, and development methodologies', 'duration' => 40, 'cost' => 15000, 'capacity' => 12, 'status' => 'active', 'prerequisites' => 'Basic programming knowledge', 'is_mandatory' => false, 'is_self_enrollment' => true],
                ['name' => 'Database Management Certification', 'description' => 'Comprehensive database design, optimization, and administration training program', 'duration' => 32, 'cost' => 12000, 'capacity' => 10, 'status' => 'draft', 'prerequisites' => 'SQL fundamentals', 'is_mandatory' => false, 'is_self_enrollment' => true]
            ],
            'Leadership Development' => [
                ['name' => 'Executive Leadership Program', 'description' => 'Strategic leadership development for senior management and high-potential employees', 'duration' => 48, 'cost' => 25000, 'capacity' => 8, 'status' => 'active', 'prerequisites' => 'Management experience', 'is_mandatory' => false, 'is_self_enrollment' => false],
                ['name' => 'Team Leadership Fundamentals', 'description' => 'Essential leadership skills for new managers and team leads', 'duration' => 16, 'cost' => 8000, 'capacity' => 15, 'status' => 'completed', 'prerequisites' => 'Supervisory role', 'is_mandatory' => true, 'is_self_enrollment' => false]
            ],
            'Communication Skills' => [
                ['name' => 'Effective Business Communication', 'description' => 'Professional communication skills including presentations, meetings, and written correspondence', 'duration' => 12, 'cost' => 4000, 'capacity' => 25, 'status' => 'active', 'prerequisites' => 'None', 'is_mandatory' => false, 'is_self_enrollment' => true],
                ['name' => 'Cross-Cultural Communication', 'description' => 'Communication strategies for diverse and multicultural work environments', 'duration' => 8, 'cost' => 3000, 'capacity' => 20, 'status' => 'active', 'prerequisites' => 'Basic communication skills', 'is_mandatory' => false, 'is_self_enrollment' => true]
            ],
            'Safety and Compliance' => [
                ['name' => 'Workplace Safety Certification', 'description' => 'Mandatory safety training covering OSHA regulations, emergency procedures, and risk management', 'duration' => 6, 'cost' => 1500, 'capacity' => 50, 'status' => 'active', 'prerequisites' => 'None', 'is_mandatory' => true, 'is_self_enrollment' => false],
                ['name' => 'Data Privacy and Security Training', 'description' => 'Compliance training on data protection, privacy regulations, and cybersecurity best practices', 'duration' => 4, 'cost' => 1000, 'capacity' => 100, 'status' => 'active', 'prerequisites' => 'None', 'is_mandatory' => true, 'is_self_enrollment' => false]
            ],
            'Customer Service Excellence' => [
                ['name' => 'Customer Experience Mastery', 'description' => 'Advanced customer service techniques, complaint handling, and relationship building strategies', 'duration' => 16, 'cost' => 6000, 'capacity' => 20, 'status' => 'active', 'prerequisites' => 'Customer service experience', 'is_mandatory' => false, 'is_self_enrollment' => true],
                ['name' => 'Digital Customer Support', 'description' => 'Modern customer support tools, chatbots, CRM systems, and omnichannel service delivery', 'duration' => 12, 'cost' => 4500, 'capacity' => 15, 'status' => 'draft', 'prerequisites' => 'Basic computer skills', 'is_mandatory' => false, 'is_self_enrollment' => true]
            ]
        ];

        foreach ($companies as $company) {
            // Get training types for this company
            $trainingTypes = TrainingType::where('created_by', $company->id)->get();

            if ($trainingTypes->isEmpty()) {
                $this->command->warn('No training types found for company: ' . $company->name . '. Please run TrainingTypeSeeder first.');
                continue;
            }

            foreach ($trainingTypes as $trainingType) {
                $typePrograms = $programsByType[$trainingType->name] ?? [];

                foreach ($typePrograms as $programData) {
                    // Check if program already exists for this training type
                    if (TrainingProgram::where('name', $programData['name'])->where('training_type_id', $trainingType->id)->exists()) {
                        continue;
                    }

                    try {
                        TrainingProgram::create([
                            'name' => $programData['name'],
                            'training_type_id' => $trainingType->id,
                            'description' => $programData['description'],
                            'duration' => $programData['duration'],
                            'cost' => $programData['cost'],
                            'capacity' => $programData['capacity'],
                            'status' => $programData['status'],
                            'materials' => randomImage(),
                            'prerequisites' => $programData['prerequisites'],
                            'is_mandatory' => $programData['is_mandatory'],
                            'is_self_enrollment' => $programData['is_self_enrollment'],
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create training program: ' . $programData['name'] . ' for company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('TrainingProgram seeder completed successfully!');
    }
}
