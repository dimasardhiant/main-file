<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DepartmentSeeder extends Seeder
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

        // Department names with descriptions
        $departments = [
            ['name' => 'Human Resources', 'description' => 'Manages employee relations, recruitment, training, benefits administration, and organizational development'],
            ['name' => 'Information Technology', 'description' => 'Responsible for managing IT infrastructure, software development, system maintenance, and technical support'],
            ['name' => 'Finance & Accounting', 'description' => 'Handles financial planning, budgeting, accounting, financial reporting, and compliance with financial regulations'],
            ['name' => 'Marketing', 'description' => 'Develops marketing strategies, manages brand promotion, digital marketing campaigns, and market research'],
            ['name' => 'Sales', 'description' => 'Focuses on revenue generation, client acquisition, customer relationship management, and sales target achievement'],
            ['name' => 'Operations', 'description' => 'Oversees daily business operations, process optimization, quality control, and operational efficiency'],
            ['name' => 'Customer Service', 'description' => 'Provides customer support, handles inquiries and complaints, and ensures customer satisfaction and retention'],
            ['name' => 'Research & Development', 'description' => 'Conducts research, develops new products and services, innovation management, and technology advancement'],
            ['name' => 'Legal', 'description' => 'Manages legal compliance, contract negotiations, risk management, and provides legal counsel to the organization'],
            ['name' => 'Administration', 'description' => 'Handles administrative functions, office management, documentation, and general administrative support services']
        ];

        foreach ($companies as $company) {
            // Get all branches for this company
            $branches = Branch::where('created_by', $company->id)->get();

            if ($branches->isEmpty()) {
                $this->command->warn('No branches found for company: ' . $company->name . '. Please run BranchSeeder first.');
                continue;
            }

            foreach ($branches as $branch) {
                // Create 5-8 departments for each branch
                $departmentCount = rand(2,3);

                for ($i = 0; $i < $departmentCount; $i++) {
                    $department = $departments[$i];
                    $departmentName = $department['name'];
                    $departmentDescription = $department['description'];

                    // Check if department already exists for this branch
                    if (Department::where('name', $departmentName)->where('branch_id', $branch->id)->exists()) {
                        continue;
                    }

                    try {
                        Department::create([
                            'name' => $departmentName,
                            'branch_id' => $branch->id,
                            'description' => $departmentDescription,
                            'status' => 'active',
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create department: ' . $departmentName . ' for branch: ' . $branch->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('Department seeder completed successfully!');
    }
}
