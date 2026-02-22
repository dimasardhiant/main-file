<?php

namespace Database\Seeders;

use App\Models\EmployeeSalary;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSalarySeeder extends Seeder
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

        foreach ($companies as $company) {
            // Get employees for this company
            $employees = User::where('type', 'employee')
                ->where('created_by', $company->id)
                ->get();

            if ($employees->isEmpty()) {
                continue;
            }

            // Get salary components for this company
            $salaryComponents = SalaryComponent::where('created_by', $company->id)
                ->where('status', 'active')
                ->get();

            if ($salaryComponents->isEmpty()) {
                continue;
            }

            foreach ($employees as $index => $employee) {
                // Fixed basic salary based on employee index for consistency
                $basicSalaries = [30000, 35000, 40000, 45000, 15000, 55000, 60000, 20000, 70000, 75000];
                $basicSalary = $basicSalaries[$index % count($basicSalaries)];
                // dd($employee,$basicSalaries,$basicSalary);

                // Select components based on employee index for consistency
                $selectedComponents = $this->getComponentsForEmployee($salaryComponents, employeeIndex: $index);
                

                try {
                    EmployeeSalary::updateOrCreate(
                        ['employee_id' => $employee->id],
                        [
                            'basic_salary' => $basicSalary,
                            'components' => $selectedComponents,
                            'is_active' => true,
                            'calculation_status' => 'pending',
                            'notes' => 'Salary setup for employee',
                            'created_by' => $company->id,
                        ]
                    );
                } catch (\Exception $e) {
                    $this->command->error('Failed to create/update employee salary for employee: ' . $employee->name . ' in company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('EmployeeSalary seeder completed successfully!');
    }

    /**
     * Get salary components for employee based on index for consistency
     */
    private function getComponentsForEmployee($salaryComponents, $employeeIndex)
    {
        $earnings = $salaryComponents->where('type', 'earning');
        $deductions = $salaryComponents->where('type', 'deduction');

        $selectedComponents = [];

        // Select earnings based on employee index
        $earningsPattern = $employeeIndex % 3;
        switch ($earningsPattern) {
            case 0: // Basic package
                $selectedComponents = array_merge(
                    $selectedComponents,
                    $earnings->whereIn('name', ['House Rent Allowance (HRA)', 'Transport Allowance'])->pluck('id')->toArray()
                );
                break;
            case 1: // Standard package
                $selectedComponents = array_merge(
                    $selectedComponents,
                    $earnings->whereIn('name', ['House Rent Allowance (HRA)', 'Transport Allowance', 'Medical Allowance'])->pluck('id')->toArray()
                );
                break;
            case 2: // Premium package
                $selectedComponents = array_merge(
                    $selectedComponents,
                    $earnings->whereIn('name', ['House Rent Allowance (HRA)', 'Transport Allowance', 'Medical Allowance', 'Dearness Allowance (DA)', 'Special Allowance'])->pluck('id')->toArray()
                );
                break;
        }

        // Select deductions based on employee index
        $deductionsPattern = $employeeIndex % 2;
        switch ($deductionsPattern) {
            case 0: // Standard deductions
                $selectedComponents = array_merge(
                    $selectedComponents,
                    $deductions->whereIn('name', ['Provident Fund (PF)', 'Professional Tax'])->pluck('id')->toArray()
                );
                break;
            case 1: // Full deductions
                $selectedComponents = array_merge(
                    $selectedComponents,
                    $deductions->whereIn('name', ['Provident Fund (PF)', 'Employee State Insurance (ESI)', 'Professional Tax', 'Income Tax (TDS)'])->pluck('id')->toArray()
                );
                break;
        }        

        return $selectedComponents;
    }
}