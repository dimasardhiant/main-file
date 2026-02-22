<?php

namespace Database\Seeders;

use App\Models\CandidateOnboarding;
use App\Models\Candidate;
use App\Models\OnboardingChecklist;
use App\Models\User;
use Illuminate\Database\Seeder;

class CandidateOnboardingSeeder extends Seeder
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

        // Fixed onboarding statuses for consistent data
        $onboardingStatuses = ['Completed', 'In Progress', 'Completed', 'Pending', 'Completed', 'In Progress', 'Completed', 'Pending', 'Completed', 'In Progress'];

        foreach ($companies as $company) {
            // Get employees for this company
            $employees = User::where('type', 'employee')
                ->where('created_by', $company->id)
                ->where('status', 'active')
                ->get();
                
            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }

            // Get onboarding checklists for this company
            $onboardingChecklists = OnboardingChecklist::where('created_by', $company->id)->get();

            if ($onboardingChecklists->isEmpty()) {
                $this->command->warn('No onboarding checklists found for company: ' . $company->name . '. Please run OnboardingChecklistSeeder first.');
                continue;
            }

            // Get employees for buddy assignment (excluding current employee)
            $buddyEmployees = User::where('type', 'employee')->where('created_by', $company->id)->get();

            foreach ($employees as $index => $employee) {
                // Check if onboarding already exists for this employee
                if (CandidateOnboarding::where('employee_id', $employee->id)->where('created_by', $company->id)->exists()) {
                    continue;
                }

                // Select checklist based on job title or use default
                $checklist = $this->selectChecklistForEmployee($employee, $onboardingChecklists);
                $buddyEmployee = $buddyEmployees->where('id', '!=', $employee->id)->random();

                $status = $onboardingStatuses[$index % 10];
                $startDate = date('Y-m-d', strtotime('-' . ($index + 1) . ' days'));

                try {
                    CandidateOnboarding::create([
                        'candidate_id' => null,
                        'employee_id' => $employee->id,
                        'checklist_id' => $checklist->id,
                        'start_date' => $startDate,
                        'buddy_employee_id' => $buddyEmployee?->id,
                        'status' => $status,
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create onboarding for employee: ' . $employee->name . ' in company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('CandidateOnboarding seeder completed successfully!');
    }

    /**
     * Select appropriate checklist based on employee's job title
     */
    private function selectChecklistForEmployee($employee, $checklists)
    {
        $jobTitle = strtolower($employee->employee->designation->name ?? '');

        // Match checklist based on job title keywords
        if (str_contains($jobTitle, 'developer') || str_contains($jobTitle, 'engineer') || str_contains($jobTitle, 'devops')) {
            $checklist = $checklists->where('name', 'Technical Team Onboarding')->first();
            if ($checklist)
                return $checklist;
        }

        if (str_contains($jobTitle, 'manager') || str_contains($jobTitle, 'director') || str_contains($jobTitle, 'lead')) {
            $checklist = $checklists->where('name', 'Management Level Onboarding')->first();
            if ($checklist)
                return $checklist;
        }

        if (str_contains($jobTitle, 'sales') || str_contains($jobTitle, 'business development')) {
            $checklist = $checklists->where('name', 'Sales Team Onboarding')->first();
            if ($checklist)
                return $checklist;
        }

        if (str_contains($jobTitle, 'customer') || str_contains($jobTitle, 'support')) {
            $checklist = $checklists->where('name', 'Customer Service Onboarding')->first();
            if ($checklist)
                return $checklist;
        }

        if (str_contains($jobTitle, 'intern')) {
            $checklist = $checklists->where('name', 'Intern Onboarding Program')->first();
            if ($checklist)
                return $checklist;
        }

        // Default to standard onboarding or first available checklist
        $defaultChecklist = $checklists->where('is_default', true)->first();
        return $defaultChecklist ?: $checklists->first();
    }
}
