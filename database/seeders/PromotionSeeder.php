<?php

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\User;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get all companies
        $companies = User::where('type', 'company')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }
        
        // Promotion reasons based on performance
        $promotionReasons = [
            'Exceptional performance and consistent achievement of targets over the past year',
            'Demonstrated strong leadership qualities and successfully managed team projects',
            'Outstanding contribution to company growth and revenue generation',
            'Excellent technical skills and innovative approach to problem-solving',
            'Consistent high-quality work delivery and meeting all project deadlines',
            'Strong communication skills and effective collaboration across departments',
            'Proactive approach in identifying process improvements and cost-saving initiatives',
            'Mentoring junior team members and contributing to their professional development',
            'Successfully completed advanced training and acquired new certifications',
            'Exceeded performance expectations and received positive client feedback',
            'Demonstrated reliability, dedication, and commitment to organizational values',
            'Led successful implementation of new systems and processes'
        ];
        
        foreach ($companies as $company) {
            // Get employees for this company
            $employees = User::where('type', 'employee')
                           ->where('created_by', $company->id)
                           ->get();
            
            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }
            
            // Get designations for this company
            $designations = Designation::where('created_by', $company->id)->get();
            
            if ($designations->isEmpty()) {
                $this->command->warn('No designations found for company: ' . $company->name . '. Please run DesignationSeeder first.');
                continue;
            }
            
            // Create 5-10 promotions for this company
            $promotionCount = rand(5, 7);
            
            for ($i = 0; $i < $promotionCount; $i++) {
                $employee = $employees->take(7)->random();
                $newDesignation = $designations->random();
                
                // Get employee's current designation from employee table
                $employeeRecord = Employee::where('user_id', $employee->id)->first();
                $currentDesignation = null;
                
                if ($employeeRecord && $employeeRecord->designation_id) {
                    $currentDesignationRecord = Designation::find($employeeRecord->designation_id);
                    $currentDesignation = $currentDesignationRecord ? $currentDesignationRecord->name : 'Previous Position';
                } else {
                    $currentDesignation = 'Previous Position';
                }
                
                $promotionDate = $faker->dateTimeBetween('-1 year', 'now');
                $effectiveDate = $faker->dateTimeBetween($promotionDate, '+1 month');
                
                try {
                    Promotion::create([
                        'employee_id' => $employee->id,
                        'previous_designation' => $currentDesignation,
                        'designation_id' => $newDesignation->id,
                        'promotion_date' => $promotionDate->format('Y-m-d'),
                        'effective_date' => $effectiveDate->format('Y-m-d'),
                        'salary_adjustment' => $faker->optional(0.8)->randomFloat(2, 5000, 50000),
                        'reason' => $faker->randomElement($promotionReasons),
                        'document' => randomImage(),
                        'status' => $faker->randomElement(['pending', 'approved', 'rejected']),
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create promotion for employee: ' . $employee->name . ' in company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('Promotion seeder completed successfully!');
    }
}