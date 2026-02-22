<?php

namespace Database\Seeders;

use App\Models\EmployeeTraining;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeTrainingSeeder extends Seeder
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
        
        $currentYear = date('Y');
        
        // Fixed training statuses and scores for consistent data
        $trainingStatuses = ['assigned', 'in_progress', 'completed', 'completed', 'completed']; // 60% completed
        $trainingScores = [85.5, 92.0, 78.5, 88.0, 95.5];
        $feedbacks = [
            'Excellent training program with practical examples and hands-on exercises',
            'Very informative content, well-structured modules, and knowledgeable instructors',
            'Good training overall, could benefit from more interactive sessions',
            'Outstanding program that significantly improved my skills and knowledge',
            'Comprehensive training with relevant case studies and real-world applications'
        ];
        
        // Fixed assessment data by training program type
        $assessmentsByProgram = [
            'New Employee Orientation Program' => [
                ['name' => 'Company Policy Quiz', 'type' => 'quiz', 'passing_score' => 80.0, 'criteria' => 'Understanding of company policies and procedures'],
                ['name' => 'Safety Procedures Assessment', 'type' => 'practical', 'passing_score' => 85.0, 'criteria' => 'Demonstration of safety protocols and emergency procedures']
            ],
            'Advanced Software Development' => [
                ['name' => 'Coding Skills Assessment', 'type' => 'practical', 'passing_score' => 75.0, 'criteria' => 'Programming proficiency and code quality'],
                ['name' => 'Technical Presentation', 'type' => 'presentation', 'passing_score' => 70.0, 'criteria' => 'Technical knowledge presentation and communication skills']
            ],
            'Executive Leadership Program' => [
                ['name' => 'Leadership Case Study', 'type' => 'presentation', 'passing_score' => 80.0, 'criteria' => 'Strategic thinking and leadership decision-making'],
                ['name' => 'Management Skills Evaluation', 'type' => 'practical', 'passing_score' => 85.0, 'criteria' => 'Team management and conflict resolution abilities']
            ],
            'Effective Business Communication' => [
                ['name' => 'Communication Skills Test', 'type' => 'quiz', 'passing_score' => 75.0, 'criteria' => 'Written and verbal communication proficiency'],
                ['name' => 'Presentation Skills Demo', 'type' => 'presentation', 'passing_score' => 80.0, 'criteria' => 'Public speaking and presentation delivery skills']
            ],
            'Workplace Safety Certification' => [
                ['name' => 'Safety Knowledge Test', 'type' => 'quiz', 'passing_score' => 90.0, 'criteria' => 'Comprehensive understanding of safety regulations and procedures'],
                ['name' => 'Emergency Response Drill', 'type' => 'practical', 'passing_score' => 95.0, 'criteria' => 'Proper execution of emergency response procedures']
            ],
            'Customer Experience Mastery' => [
                ['name' => 'Customer Service Scenarios', 'type' => 'practical', 'passing_score' => 80.0, 'criteria' => 'Customer interaction skills and problem-solving abilities'],
                ['name' => 'Service Excellence Presentation', 'type' => 'presentation', 'passing_score' => 75.0, 'criteria' => 'Customer service best practices and improvement strategies']
            ]
        ];
        
        foreach ($companies as $company) {
            // Get training programs for this company
            $trainingPrograms = TrainingProgram::where('created_by', $company->id)->get();
            
            if ($trainingPrograms->isEmpty()) {
                $this->command->warn('No training programs found for company: ' . $company->name . '. Please run TrainingProgramSeeder first.');
                continue;
            }
            
            // Get employees for this company
            $employees = User::where('type', 'employee')->where('created_by', $company->id)->get();
            
            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }
            
            // Get managers/HR for assignment
            $assigners = User::whereIn('type', ['manager', 'hr','employee'])->where('created_by', $company->id)->get();
            $assigner = $assigners->isNotEmpty() ? $assigners->first() : $company;
            
            // Create assessments for training programs first
            foreach ($trainingPrograms as $program) {
                $programAssessments = $assessmentsByProgram[$program->name] ?? [];
                
                foreach ($programAssessments as $assessmentData) {
                    // Check if assessment already exists
                    if (DB::table('training_assessments')->where('name', $assessmentData['name'])->where('training_program_id', $program->id)->exists()) {
                        continue;
                    }
                    
                    try {
                        DB::table('training_assessments')->insert([
                            'training_program_id' => $program->id,
                            'name' => $assessmentData['name'],
                            'description' => 'Assessment for ' . $program->name . ' - ' . $assessmentData['name'],
                            'type' => $assessmentData['type'],
                            'passing_score' => $assessmentData['passing_score'],
                            'criteria' => $assessmentData['criteria'],
                            'created_by' => $company->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
            
            // Create employee trainings for first 3 employees and first 3 programs
            $selectedEmployees = $employees->take(5);
            $selectedPrograms = $trainingPrograms->take(5);
            
            foreach ($selectedEmployees as $empIndex => $employee) {
                foreach ($selectedPrograms as $progIndex => $program) {
                    $trainingIndex = ($empIndex * 3) + $progIndex;
                    $status = $trainingStatuses[$trainingIndex % 5];
                    $score = $status === 'completed' ? $trainingScores[$trainingIndex % 5] : null;
                    $isPassed = $score ? $score >= 70 : null;
                    
                    $assignedDate = $currentYear . '-' . str_pad($progIndex + 2, 2, '0', STR_PAD_LEFT) . '-01';
                    $completionDate = $status === 'completed' ? 
                        date('Y-m-d', strtotime($assignedDate . ' +30 days')) : null;
                    
                    try {
                        $employeeTraining = EmployeeTraining::create([
                            'employee_id' => $employee->id,
                            'training_program_id' => $program->id,
                            'status' => $status,
                            'assigned_date' => $assignedDate,
                            'completion_date' => $completionDate,
                            'certification' => randomImage(),
                            'score' => $score,
                            'is_passed' => $isPassed,
                            'feedback' => $status === 'completed' ? $feedbacks[$trainingIndex % 5] : null,
                            'notes' => 'Training assigned for skill development and compliance requirements',
                            'assigned_by' => $assigner->id,
                            'created_by' => $assigner->id,
                        ]);
                        
                        // Create assessment results for completed trainings
                        if ($status === 'completed') {
                            $this->createAssessmentResults($employeeTraining, $program, $assigner, $trainingIndex);
                        }
                        
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create employee training for: ' . $employee->name . ' in company: ' . $company->name);
                        continue;
                    }
                }
            }
        }
        
        $this->command->info('EmployeeTraining seeder completed successfully!');
    }
    
    /**
     * Create assessment results for completed training
     */
    private function createAssessmentResults($employeeTraining, $program, $assigner, $trainingIndex)
    {
        // Get assessments for this training program
        $assessments = DB::table('training_assessments')
            ->where('training_program_id', $program->id)
            ->get();
        
        // Fixed assessment scores
        $assessmentScores = [
            [82.5, 88.0], // First set of assessment scores
            [91.5, 85.5], // Second set
            [76.0, 92.0], // Third set
            [89.5, 87.0], // Fourth set
            [94.0, 90.5]  // Fifth set
        ];
        
        $scoreSet = $assessmentScores[$trainingIndex % 5];
        
        foreach ($assessments as $index => $assessment) {
            $score = $scoreSet[$index % 2];
            $isPassed = $score >= $assessment->passing_score;
            
            try {
                DB::table('employee_assessment_results')->insert([
                    'employee_training_id' => $employeeTraining->id,
                    'training_assessment_id' => $assessment->id,
                    'score' => $score,
                    'is_passed' => $isPassed,
                    'feedback' => $isPassed ? 
                        'Excellent performance, demonstrates strong understanding of the subject matter' : 
                        'Good effort, but needs improvement in some areas to meet the required standards',
                    'assessment_date' => $employeeTraining->completion_date,
                    'assessed_by' => $assigner->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}