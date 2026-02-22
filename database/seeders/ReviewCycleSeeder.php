<?php

namespace Database\Seeders;

use App\Models\ReviewCycle;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewCycleSeeder extends Seeder
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
        
        // Fixed review cycles for consistent data
        $reviewCycles = [
            [
                'name' => 'Monthly Performance Review',
                'frequency' => 'Monthly',
                'description' => 'Monthly performance evaluation to track progress, provide feedback, and address immediate performance concerns',
                'status' => 'active'
            ],
            [
                'name' => 'Quarterly Business Review',
                'frequency' => 'Quarterly',
                'description' => 'Comprehensive quarterly assessment of employee performance, goal achievement, and development planning',
                'status' => 'active'
            ],
            [
                'name' => 'Mid-Year Performance Review',
                'frequency' => 'Semi-Annual',
                'description' => 'Semi-annual performance evaluation focusing on goal progress, skill development, and career planning',
                'status' => 'active'
            ],
            [
                'name' => 'Annual Performance Appraisal',
                'frequency' => 'Annual',
                'description' => 'Comprehensive annual performance review including goal assessment, competency evaluation, and career development planning',
                'status' => 'active'
            ],
            [
                'name' => 'Probationary Review',
                'frequency' => 'Quarterly',
                'description' => 'Performance evaluation for employees during probationary period to assess job fit and performance standards',
                'status' => 'active'
            ],
            [
                'name' => 'Project Completion Review',
                'frequency' => 'Monthly',
                'description' => 'Performance assessment conducted upon completion of major projects to evaluate contribution and outcomes',
                'status' => 'active'
            ],
            [
                'name' => 'Leadership Assessment Cycle',
                'frequency' => 'Semi-Annual',
                'description' => 'Specialized review cycle for leadership positions focusing on management effectiveness and strategic contribution',
                'status' => 'active'
            ],
            [
                'name' => 'Sales Performance Review',
                'frequency' => 'Quarterly',
                'description' => 'Performance evaluation specifically designed for sales team members focusing on targets and customer relationships',
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($reviewCycles as $cycleData) {
                // Check if review cycle already exists for this company
                if (ReviewCycle::where('name', $cycleData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    ReviewCycle::create([
                        'name' => $cycleData['name'],
                        'frequency' => $cycleData['frequency'],
                        'description' => $cycleData['description'],
                        'status' => $cycleData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create review cycle: ' . $cycleData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('ReviewCycle seeder completed successfully!');
    }
}