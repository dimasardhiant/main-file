<?php

namespace Database\Seeders;

use App\Models\CustomQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomQuestionSeeder extends Seeder
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
        
        // Custom questions array
        $customQuestions = [
            ['question' => 'What is your expected salary range?', 'required' => 1],
            ['question' => 'Do you have any previous experience in this field?', 'required' => 1],
            ['question' => 'Are you willing to relocate for this position?', 'required' => 0],
            ['question' => 'What are your career goals for the next 5 years?', 'required' => 0],
            ['question' => 'Do you have any certifications relevant to this role?', 'required' => 0],
            ['question' => 'What is your notice period in your current job?', 'required' => 1],
            ['question' => 'Are you comfortable working in a team environment?', 'required' => 1],
            ['question' => 'Do you have any questions about the company culture?', 'required' => 0],
        ];
        
        foreach ($companies as $company) {
            foreach ($customQuestions as $questionData) {
                // Check if question already exists for this company
                if (CustomQuestion::where('question', $questionData['question'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    CustomQuestion::create([
                        'question' => $questionData['question'],
                        'required' => $questionData['required'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create custom question: ' . $questionData['question'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('Custom Question seeder completed successfully!');
    }
}