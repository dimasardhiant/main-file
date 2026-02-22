<?php

namespace Database\Seeders;

use App\Models\ContractRenewal;
use App\Models\EmployeeContract;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContractRenewalSeeder extends Seeder
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

        // Fixed renewal data for consistent results
        $renewalData = [
            ['salary_increase' => 10000, 'allowances' => 5000, 'benefits' => 'Health Insurance Premium', 'status' => 'Approved', 'reason' => 'Annual performance review and salary increment', 'changes' => 'Salary increased by 10%, additional health benefits added'],
            ['salary_increase' => 8000, 'allowances' => 3000, 'benefits' => 'Performance Bonus', 'status' => 'Processed', 'reason' => 'Contract renewal with performance-based increment', 'changes' => 'Salary adjustment based on market standards'],
            ['salary_increase' => 5000, 'allowances' => 2000, 'benefits' => 'Training Allowance', 'status' => 'Pending', 'reason' => 'Standard contract renewal request', 'changes' => 'Minor salary adjustment and training benefits'],
            ['salary_increase' => 12000, 'allowances' => 6000, 'benefits' => 'Executive Benefits', 'status' => 'Approved', 'reason' => 'Promotion and role enhancement', 'changes' => 'Significant salary increase due to promotion'],
            ['salary_increase' => 0, 'allowances' => 1000, 'benefits' => 'Basic Benefits', 'status' => 'Rejected', 'reason' => 'Contract renewal without salary change', 'changes' => 'No salary change, only allowance adjustment'],
            ['salary_increase' => 15000, 'allowances' => 7000, 'benefits' => 'Comprehensive Package', 'status' => 'Approved', 'reason' => 'Retention package for key employee', 'changes' => 'Comprehensive benefits upgrade and salary increase'],
            ['salary_increase' => 3000, 'allowances' => 1500, 'benefits' => 'Standard Benefits', 'status' => 'Pending', 'reason' => 'Regular contract renewal', 'changes' => 'Standard increment as per company policy'],
            ['salary_increase' => 7000, 'allowances' => 3500, 'benefits' => 'Enhanced Benefits', 'status' => 'Processed', 'reason' => 'Contract extension with benefits upgrade', 'changes' => 'Benefits package enhanced with additional perks']
        ];

        foreach ($companies as $company) {
            // Get employee contracts for this company that can be renewed
            $contracts = EmployeeContract::where('created_by', $company->id)
                ->whereIn('status', ['Active', 'Expired'])
                ->whereNotNull('end_date')
                ->get();

            if ($contracts->isEmpty()) {
                $this->command->warn('No renewable contracts found for company: ' . $company->name . '. Please run EmployeeContractSeeder first.');
                continue;
            }

            // Get managers/HR for approval
            $approvers = User::whereIn('type', ['manager', 'hr'])
                ->where('created_by', $company->id)
                ->get();

            $renewalCounter = ($company->id - 1) * 100 + 1;

            // Create renewals for first 5 contracts
            $selectedContracts = $contracts->take(7);

            foreach ($selectedContracts as $index => $contract) {
                // Check if renewal already exists for this contract
                if (ContractRenewal::where('contract_id', $contract->id)->where('created_by', $company->id)->exists()) {
                    continue;
                }

                $renewal = $renewalData[$index % 8];
                $renewalNumber = 'REN-' . $currentYear . '-' . str_pad($renewalCounter, 4, '0', STR_PAD_LEFT);

                // Select approver and requester
                $approver = $approvers->isNotEmpty() ? $approvers->first() : null;
                $requester = $approvers->isNotEmpty() ? $approvers->first() : $contract->employee;

                $currentEndDate = $contract->end_date;
                $newStartDate = date('Y-m-d', strtotime($currentEndDate . ' +1 day'));
                $newEndDate = date('Y-m-d', strtotime($newStartDate . ' +12 months'));

                $newBasicSalary = $contract->basic_salary + $renewal['salary_increase'];
                $newAllowances = json_encode(['total' => ($renewal['allowances'])]);
                $newBenefits = json_encode([$renewal['benefits']]);

                $newTermsConditions = 'Renewed contract terms and conditions. Previous contract extended with updated compensation and benefits package.';

                try {
                    ContractRenewal::create([
                        'contract_id' => $contract->id,
                        'renewal_number' => $renewalNumber,
                        'current_end_date' => $currentEndDate,
                        'new_start_date' => $newStartDate,
                        'new_end_date' => $newEndDate,
                        'new_basic_salary' => $newBasicSalary,
                        'new_allowances' => $newAllowances,
                        'new_benefits' => $newBenefits,
                        'new_terms_conditions' => $newTermsConditions,
                        'changes_summary' => $renewal['changes'],
                        'status' => $renewal['status'],
                        'reason' => $renewal['reason'],
                        'requested_by' => $requester->id,
                        'approved_by' => in_array($renewal['status'], ['Approved', 'Processed']) ? $approver?->id : null,
                        'approved_at' => in_array($renewal['status'], ['Approved', 'Processed']) ? now() : null,
                        'approval_notes' => in_array($renewal['status'], ['Approved', 'Processed']) ? 'Renewal approved based on performance and company policy' : null,
                        'created_by' => $company->id,
                    ]);

                    $renewalCounter++;
                } catch (\Exception $e) {
                    $this->command->error('Failed to create contract renewal for contract: ' . $contract->contract_number . ' in company: ' . $company->name);
                    $renewalCounter++;
                    continue;
                }
            }
        }

        $this->command->info('ContractRenewal seeder completed successfully!');
    }
}
