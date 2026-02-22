<?php

namespace Database\Seeders;

use App\Models\EmployeeTransfer;
use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class EmployeeTransferSeeder extends Seeder
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

        // Transfer reasons
        $transferReasons = [
            'Business Expansion' => 'Employee transfer required to support business expansion and new market opportunities in different location.',
            'Skill Utilization' => 'Transfer to better utilize employee skills and expertise in department that requires specialized knowledge.',
            'Career Development' => 'Transfer provides employee with career growth opportunities and exposure to different business functions.',
            'Operational Requirements' => 'Transfer necessary to meet operational requirements and maintain adequate staffing levels across locations.',
            'Employee Request' => 'Transfer approved based on employee personal request for relocation due to family or personal circumstances.',
            'Project Assignment' => 'Employee assigned to specific project that requires presence at different branch or department location.',
            'Performance Improvement' => 'Transfer to environment better suited for employee development and performance improvement.',
            'Restructuring' => 'Transfer part of organizational restructuring to optimize resources and improve operational efficiency.',
            'Training Opportunity' => 'Transfer provides access to specialized training and development programs available at different location.',
            'Succession Planning' => 'Transfer supports succession planning initiatives and leadership development across organization.'
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

            // Get company resources
            $branches = Branch::where('created_by', $company->id)->get();
            $departments = Department::where('created_by', $company->id)->get();
            $designations = Designation::where('created_by', $company->id)->get();

            if ($branches->isEmpty() || $departments->isEmpty() || $designations->isEmpty()) {
                $this->command->warn('Missing branches, departments, or designations for company: ' . $company->name);
                continue;
            }

            // Get managers/HR for approval
            $approvers = User::whereIn('type', ['manager', 'hr'])
                ->where('created_by', $company->id)
                ->get();

            // Create 3-7 transfers for this company
            $transferCount = rand(3, 7);

            for ($i = 0; $i < $transferCount; $i++) {
                $employee = $employees->take(5)->random();

                // Get employee's current details from employee table
                $employeeRecord = Employee::where('user_id', $employee->id)->first();

                if (!$employeeRecord) {
                    continue; // Skip if no employee record found
                }

                $fromBranch = $employeeRecord->branch_id ? Branch::find($employeeRecord->branch_id) : $branches->random();
                $fromDepartment = $employeeRecord->department_id ? Department::find($employeeRecord->department_id) : $departments->random();
                $fromDesignation = $employeeRecord->designation_id ? Designation::find($employeeRecord->designation_id) : $designations->random();

                // Select different branch/department/designation for transfer
                $toBranch = $branches->where('id', '!=', $fromBranch?->id)->random();
                $toDepartment = $departments->where('id', '!=', $fromDepartment?->id)->random();
                $toDesignation = $designations->where('id', '!=', $fromDesignation?->id)->random();

                $transferDate = $faker->dateTimeBetween('-6 months', 'now');
                $effectiveDate = $faker->dateTimeBetween($transferDate, '+30 days');

                $reasonKey = $faker->randomElement(array_keys($transferReasons));
                $reason = $transferReasons[$reasonKey];

                $status = $faker->randomElement(['pending', 'approved', 'rejected']);
                $approver = $approvers->isNotEmpty() ? $approvers->random() : null;

                try {
                    EmployeeTransfer::create([
                        'employee_id' => $employee->id,
                        'from_branch_id' => $fromBranch?->id,
                        'to_branch_id' => $toBranch?->id,
                        'from_department_id' => $fromDepartment?->id,
                        'to_department_id' => $toDepartment?->id,
                        'from_designation_id' => $fromDesignation?->id,
                        'to_designation_id' => $toDesignation?->id,
                        'transfer_date' => $transferDate->format('Y-m-d'),
                        'effective_date' => $effectiveDate->format('Y-m-d'),
                        'reason' => $reason,
                        'status' => $status,
                        'documents' => randomImage(),
                        'approved_by' => $status === 'approved' ? $approver?->id : null,
                        'approved_at' => $status === 'approved' ? $faker->dateTimeBetween($transferDate, 'now') : null,
                        'notes' => $faker->optional(0.6)->sentence(10),
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create transfer for employee: ' . $employee->name . ' in company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('EmployeeTransfer seeder completed successfully!');
    }
}
