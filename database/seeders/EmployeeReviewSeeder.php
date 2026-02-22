<?php

namespace Database\Seeders;

use App\Models\EmployeeReview;
use App\Models\ReviewCycle;
use App\Models\PerformanceIndicator;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeReviewSeeder extends Seeder
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

        // Fixed review data for consistent results
        $reviewStatuses = ['completed', 'completed', 'completed', 'completed', 'in_progress', 'in_progress', 'scheduled', 'scheduled'];
        $overallRatings = [3.5, 4.0, 4.2, 3.8, 4.5];
        $reviewComments = [
            'Employee demonstrates strong performance and meets expectations consistently',
            'Excellent work quality and dedication to achieving team objectives',
            'Shows good progress in skill development and professional growth',
            'Reliable team member with positive attitude and strong work ethic',
            'Outstanding performance with significant contributions to project success'
        ];

        // Fixed ratings for performance indicators
        $indicatorRatings = [
            ['rating' => 4.0, 'comment' => 'Consistently meets performance standards with room for improvement'],
            ['rating' => 4.5, 'comment' => 'Exceeds expectations and demonstrates strong competency'],
            ['rating' => 3.5, 'comment' => 'Meets basic requirements but could benefit from additional development'],
            ['rating' => 4.2, 'comment' => 'Strong performance with consistent quality output'],
            ['rating' => 3.8, 'comment' => 'Good performance with occasional areas for enhancement']
        ];

        foreach ($companies as $company) {
            // Get employees for this company
            $employees = User::where('type', 'employee')->where('created_by', $company->id)->get();

            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }

            // Get review cycles for this company
            $reviewCycles = ReviewCycle::where('created_by', $company->id)->get();

            if ($reviewCycles->isEmpty()) {
                $this->command->warn('No review cycles found for company: ' . $company->name . '. Please run ReviewCycleSeeder first.');
                continue;
            }

            // Get performance indicators for this company
            $performanceIndicators = PerformanceIndicator::where('created_by', $company->id)->get();

            if ($performanceIndicators->isEmpty()) {
                $this->command->warn('No performance indicators found for company: ' . $company->name . '. Please run PerformanceIndicatorSeeder first.');
                continue;
            }

            // Get managers/HR for reviewers
            $reviewers = User::whereIn('type', ['manager', 'hr', 'employee'])->where('created_by', $company->id)->get();
            $reviewer = $reviewers->isNotEmpty() ? $reviewers->first() : $company;

            // Create reviews for first 3 employees (consistent data)
            $selectedEmployees = $employees->take(4);

            foreach ($selectedEmployees as $empIndex => $employee) {
                // Create 2 reviews per employee (different cycles)
                $selectedCycles = $reviewCycles->take(2);

                foreach ($selectedCycles as $cycleIndex => $reviewCycle) {
                    $reviewIndex = ($empIndex * 2) + $cycleIndex;
                    $status = $reviewStatuses[$reviewIndex % 8];
                    $overallRating = $status === 'completed' ? $overallRatings[$reviewIndex % 5] : null;
                    $completionDate = $status === 'completed' ? $currentYear . '-06-15' : null;

                    try {
                        $employeeReview = EmployeeReview::create([
                            'employee_id' => $employee->id,
                            'reviewer_id' => $reviewer->id,
                            'review_cycle_id' => $reviewCycle->id,
                            'review_date' => $currentYear . '-03-01',
                            'completion_date' => $completionDate,
                            'overall_rating' => $overallRating,
                            'comments' => $status === 'completed' ? $reviewComments[$reviewIndex % 5] : null,
                            'status' => $status,
                            'created_by' => $company->id,
                        ]);

                        // Create review ratings for completed reviews
                        if ($status === 'completed') {
                            $this->createReviewRatings($employeeReview, $performanceIndicators, $indicatorRatings);
                        }
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create review for employee: ' . $employee->name . ' in company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('EmployeeReview seeder completed successfully!');
    }

    /**
     * Create review ratings for performance indicators
     */
    private function createReviewRatings($employeeReview, $performanceIndicators, $indicatorRatings)
    {
        // Create ratings for first 5 performance indicators
        $selectedIndicators = $performanceIndicators->take(5);

        foreach ($selectedIndicators as $index => $indicator) {
            $ratingData = $indicatorRatings[$index];

            try {
                DB::table('employee_review_ratings')->insert([
                    'employee_review_id' => $employeeReview->id,
                    'performance_indicator_id' => $indicator->id,
                    'rating' => $ratingData['rating'],
                    'comments' => $ratingData['comment'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
