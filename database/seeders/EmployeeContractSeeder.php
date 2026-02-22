<?php

namespace Database\Seeders;

use App\Models\EmployeeContract;
use App\Models\ContractType;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeContractSeeder extends Seeder
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
        
        // Fixed contract data for consistent results
        $contractData = [
            ['basic_salary' => 120000, 'allowances' => 42000, 'benefits' => 'Health Insurance', 'status' => 'Active', 'contract_type' => 'Permanent Full-time'],
            ['basic_salary' => 85000, 'allowances' => 29500, 'benefits' => 'Provident Fund', 'status' => 'Active', 'contract_type' => 'Permanent Full-time'],
            ['basic_salary' => 75000, 'allowances' => 26000, 'benefits' => 'Health Insurance', 'status' => 'Active', 'contract_type' => 'Fixed-term Contract'],
            ['basic_salary' => 95000, 'allowances' => 33500, 'benefits' => 'Annual Bonus', 'status' => 'Active', 'contract_type' => 'Permanent Full-time'],
            ['basic_salary' => 65000, 'allowances' => 22500, 'benefits' => 'Training Allowance', 'status' => 'Active', 'contract_type' => 'Fixed-term Contract'],
            ['basic_salary' => 110000, 'allowances' => 38500, 'benefits' => 'Performance Bonus', 'status' => 'Active', 'contract_type' => 'Permanent Full-time'],
            ['basic_salary' => 35000, 'allowances' => 5500, 'benefits' => 'Learning Stipend', 'status' => 'Active', 'contract_type' => 'Internship Contract'],
            ['basic_salary' => 55000, 'allowances' => 19000, 'benefits' => 'Health Insurance', 'status' => 'Active', 'contract_type' => 'Part-time Contract'],
            ['basic_salary' => 135000, 'allowances' => 57500, 'benefits' => 'Executive Health Plan', 'status' => 'Active', 'contract_type' => 'Permanent Full-time'],
            ['basic_salary' => 88000, 'allowances' => 30800, 'benefits' => 'Remote Work Allowance', 'status' => 'Active', 'contract_type' => 'Permanent Full-time']
        ];
        
        foreach ($companies as $company) {
            // Get employees for this company
            $employees = User::where('type', 'employee')->where('created_by', $company->id)->get();
            
            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }
            
            // Get contract types for this company
            $contractTypes = ContractType::where('created_by', $company->id)->get();
            
            if ($contractTypes->isEmpty()) {
                $this->command->warn('No contract types found for company: ' . $company->name . '. Please run ContractTypeSeeder first.');
                continue;
            }
            
            // Get managers/HR for approval
            $approvers = User::whereIn('type', ['manager', 'hr'])
                           ->where('created_by', $company->id)
                           ->get();
            
            $contractCounter = ($company->id - 1) * 100 + 1;
            
            // Create contracts for first 10 employees
            $selectedEmployees = $employees->take(10);
            
            foreach ($selectedEmployees as $index => $employee) {
                // Check if contract already exists for this employee
                if (EmployeeContract::where('employee_id', $employee->id)->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                $contract = $contractData[$index % 10];
                $contractNumber = 'EMP-' . $currentYear . '-' . str_pad($contractCounter, 4, '0', STR_PAD_LEFT);
                
                // Find matching contract type
                $contractType = $contractTypes->where('name', $contract['contract_type'])->first();
                if (!$contractType) $contractType = $contractTypes->first();
                
                // Select approver
                $approver = $approvers->isNotEmpty() ? $approvers->first() : null;
                
                $startDate = date('Y-m-d', strtotime('-' . ($index + 30) . ' days'));
                $endDate = $contractType->default_duration_months ? 
                    date('Y-m-d', strtotime($startDate . ' +' . $contractType->default_duration_months . ' months')) : null;
                
                $termsConditions = 'Standard employment terms and conditions as per company policy. Employee is subject to ' . 
                                 $contractType->probation_period_months . ' months probation period and ' . 
                                 $contractType->notice_period_days . ' days notice period for termination.';
                
                try {
                    EmployeeContract::create([
                        'contract_number' => $contractNumber,
                        'employee_id' => $employee->id,
                        'contract_type_id' => $contractType->id,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'basic_salary' => $contract['basic_salary'],
                        'allowances' => json_encode(['total' => $contract['allowances']]),
                        'benefits' => json_encode([$contract['benefits']]),
                        'terms_conditions' => $termsConditions,
                        'status' => $contract['status'],
                        'approved_by' => $contract['status'] === 'Active' ? $approver?->id : null,
                        'approved_at' => $contract['status'] === 'Active' ? now() : null,
                        'created_by' => $company->id,
                    ]);
                    
                    $contractCounter++;
                    
                } catch (\Exception $e) {
                    $this->command->error('Failed to create contract for employee: ' . $employee->name . ' in company: ' . $company->name);
                    $contractCounter++;
                    continue;
                }
            }
        }
        
        $this->command->info('EmployeeContract seeder completed successfully!');
    }
}