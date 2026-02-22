<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Database\Seeder;

class SalaryComponentSeeder extends Seeder
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

        // Fixed salary components for consistent data
        $salaryComponents = [
            // Earnings
            [
                'name' => 'House Rent Allowance (HRA)',
                'description' => 'House rent allowance for accommodation expenses',
                'type' => 'earning',
                'calculation_type' => 'percentage',
                'default_amount' => 0.00,
                'percentage_of_basic' => 40.00,
                'is_taxable' => true,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Transport Allowance',
                'description' => 'Transportation allowance for commuting expenses',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'default_amount' => 2000.00,
                'percentage_of_basic' => null,
                'is_taxable' => false,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Medical Allowance',
                'description' => 'Medical allowance for healthcare expenses',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'default_amount' => 1500.00,
                'percentage_of_basic' => null,
                'is_taxable' => false,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Dearness Allowance (DA)',
                'description' => 'Dearness allowance to offset inflation impact',
                'type' => 'earning',
                'calculation_type' => 'percentage',
                'default_amount' => 0.00,
                'percentage_of_basic' => 15.00,
                'is_taxable' => true,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Special Allowance',
                'description' => 'Special allowance for additional responsibilities',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'default_amount' => 3000.00,
                'percentage_of_basic' => null,
                'is_taxable' => true,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            // Deductions
            [
                'name' => 'Provident Fund (PF)',
                'description' => 'Employee provident fund contribution',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'default_amount' => 0.00,
                'percentage_of_basic' => 12.00,
                'is_taxable' => false,
                'is_mandatory' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Employee State Insurance (ESI)',
                'description' => 'Employee state insurance contribution',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'default_amount' => 0.00,
                'percentage_of_basic' => 0.75,
                'is_taxable' => false,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Professional Tax',
                'description' => 'Professional tax deduction as per state regulations',
                'type' => 'deduction',
                'calculation_type' => 'fixed',
                'default_amount' => 200.00,
                'percentage_of_basic' => null,
                'is_taxable' => false,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Income Tax (TDS)',
                'description' => 'Tax deducted at source on salary income',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'default_amount' => 0.00,
                'percentage_of_basic' => 10.00,
                'is_taxable' => false,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Loan Deduction',
                'description' => 'Employee loan repayment deduction',
                'type' => 'deduction',
                'calculation_type' => 'fixed',
                'default_amount' => 1000.00,
                'percentage_of_basic' => null,
                'is_taxable' => false,
                'is_mandatory' => false,
                'status' => 'active'
            ]
        ];

        foreach ($companies as $company) {
            foreach ($salaryComponents as $componentData) {
                // Check if salary component already exists for this company
                if (SalaryComponent::where('name', $componentData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    SalaryComponent::create([
                        'name' => $componentData['name'],
                        'description' => $componentData['description'],
                        'type' => $componentData['type'],
                        'calculation_type' => $componentData['calculation_type'],
                        'default_amount' => $componentData['default_amount'],
                        'percentage_of_basic' => $componentData['percentage_of_basic'],
                        'is_taxable' => $componentData['is_taxable'],
                        'is_mandatory' => $componentData['is_mandatory'],
                        'status' => $componentData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create salary component: ' . $componentData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('SalaryComponent seeder completed successfully!');
    }
}
