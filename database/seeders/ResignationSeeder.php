<?php

namespace Database\Seeders;

use App\Models\Resignation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ResignationSeeder extends Seeder
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

        // Resignation reasons
        $resignationReasons = [
            'Better Career Opportunity',
            'Higher Salary Package',
            'Personal Reasons',
            'Family Relocation',
            'Health Issues',
            'Further Studies',
            'Career Change',
            'Work-Life Balance',
            'Company Culture Mismatch',
            'Lack of Growth Opportunities',
            'Starting Own Business',
            'Remote Work Preference'
        ];

        // Resignation descriptions based on reasons
        $resignationDescriptions = [
            'Better Career Opportunity' => 'I have received an offer that aligns better with my career goals and provides opportunities for professional growth in my field of expertise.',
            'Higher Salary Package' => 'I have been offered a position with significantly better compensation package that will help me meet my financial commitments and career aspirations.',
            'Personal Reasons' => 'Due to personal circumstances that require my immediate attention, I need to step away from my current role to focus on family matters.',
            'Family Relocation' => 'My family is relocating to another city/country, and I need to resign from my current position to accompany them and settle in the new location.',
            'Health Issues' => 'Due to health concerns that require extended treatment and recovery time, I am unable to continue in my current role and need to focus on my well-being.',
            'Further Studies' => 'I have been accepted into a full-time academic program that will enhance my qualifications and require my complete dedication to studies.',
            'Career Change' => 'I have decided to pursue a different career path that aligns better with my interests and long-term professional objectives.',
            'Work-Life Balance' => 'I am seeking a role that offers better work-life balance to spend more quality time with my family and pursue personal interests.',
            'Company Culture Mismatch' => 'After careful consideration, I feel that my values and working style do not align well with the current organizational culture.',
            'Lack of Growth Opportunities' => 'I feel that I have reached a plateau in my current role and there are limited opportunities for advancement and skill development.',
            'Starting Own Business' => 'I have decided to pursue entrepreneurship and start my own business venture, which requires my full-time commitment and attention.',
            'Remote Work Preference' => 'I am seeking opportunities that offer remote work flexibility, which is important for my current life situation and productivity.'
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

            // Get managers/HR for approval
            $approvers = User::whereIn('type', ['manager', 'hr'])
                ->where('created_by', $company->id)
                ->get();

            // Create 3-6 resignations for this company
            $resignationCount = rand(3, 6);

            for ($i = 0; $i < $resignationCount; $i++) {
                $employee = $employees->take(7)->random();
                $reason = $faker->randomElement($resignationReasons);
                $description = $resignationDescriptions[$reason];

                $resignationDate = $faker->dateTimeBetween('-6 months', 'now');
                $noticePeriod = $faker->randomElement(['30 days', '60 days', '90 days', '2 weeks', '1 month']);

                // Calculate last working day based on notice period
                $noticeDays = match ($noticePeriod) {
                    '2 weeks' => 14,
                    '30 days', '1 month' => 30,
                    '60 days' => 60,
                    '90 days' => 90,
                    default => 30
                };

                $lastWorkingDay = (clone $resignationDate)->modify("+{$noticeDays} days");

                $status = $faker->randomElement(['pending', 'approved', 'rejected', 'completed']);
                $approver = $approvers->isNotEmpty() ? $approvers->random() : null;

                try {
                    Resignation::create([
                        'employee_id' => $employee->id,
                        'resignation_date' => $resignationDate->format('Y-m-d'),
                        'last_working_day' => $lastWorkingDay->format('Y-m-d'),
                        'notice_period' => $noticePeriod,
                        'reason' => $reason,
                        'description' => $description,
                        'status' => $status,
                        'documents' => randomImage(),
                        'approved_by' => $status === 'approved' || $status === 'completed' ? $approver?->id : null,
                        'approved_at' => $status === 'approved' || $status === 'completed' ? $faker->dateTimeBetween($resignationDate, 'now') : null,
                        'exit_feedback' => $status === 'completed' ? $faker->sentence(15) : null,
                        'exit_interview_conducted' => $status === 'completed' ? $faker->boolean(80) : false,
                        'exit_interview_date' => $status === 'completed' && $faker->boolean(80) ? $lastWorkingDay->format('Y-m-d') : null,
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create resignation for employee: ' . $employee->name . ' in company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('Resignation seeder completed successfully!');
    }
}
