<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Database\Seeder;

class BpjsComponentSeeder extends Seeder
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

        // Fixed BPJS components for consistent data (math handled in EmployeeSalary model)
        $bpjsComponents = [
            [
                'name' => 'BPJS Kesehatan',
                'description' => 'BPJS Kesehatan (Health Insurance) - Math calculated by system',
                'type' => 'deduction',
                'calculation_type' => 'fixed',
                'default_amount' => 0.00,
                'percentage_of_basic' => null,
                'is_taxable' => false,
                'is_mandatory' => true,
                'status' => 'active'
            ],
            [
                'name' => 'BPJS Ketenagakerjaan JHT',
                'description' => 'Jaminan Hari Tua - Math calculated by system',
                'type' => 'deduction',
                'calculation_type' => 'fixed',
                'default_amount' => 0.00,
                'percentage_of_basic' => null,
                'is_taxable' => false,
                'is_mandatory' => true,
                'status' => 'active'
            ],
            [
                'name' => 'BPJS Ketenagakerjaan JP',
                'description' => 'Jaminan Pensiun - Math calculated by system',
                'type' => 'deduction',
                'calculation_type' => 'fixed',
                'default_amount' => 0.00,
                'percentage_of_basic' => null,
                'is_taxable' => false,
                'is_mandatory' => true,
                'status' => 'active'
            ],
            [
                'name' => 'BPJS Ketenagakerjaan JKK',
                'description' => 'Jaminan Kecelakaan Kerja - Math calculated by system',
                'type' => 'deduction',
                'calculation_type' => 'fixed',
                'default_amount' => 0.00,
                'percentage_of_basic' => null,
                'is_taxable' => false,
                'is_mandatory' => true,
                'status' => 'active'
            ],
            [
                'name' => 'BPJS Ketenagakerjaan JKM',
                'description' => 'Jaminan Kematian - Math calculated by system',
                'type' => 'deduction',
                'calculation_type' => 'fixed',
                'default_amount' => 0.00,
                'percentage_of_basic' => null,
                'is_taxable' => false,
                'is_mandatory' => true,
                'status' => 'active'
            ]
        ];

        foreach ($companies as $company) {
            foreach ($bpjsComponents as $componentData) {
                // Check if component already exists for this company
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
                    $this->command->error('Failed to create BPJS component: ' . $componentData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('BpjsComponentSeeder completed successfully!');
    }
}
