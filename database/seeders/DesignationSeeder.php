<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DesignationSeeder extends Seeder
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

        // Designation names and descriptions by department type
        $designationsByDepartment = [
            'Human Resources' => [
                ['name' => 'HR Manager', 'description' => 'Oversees all HR functions including recruitment, employee relations, and policy implementation'],
                ['name' => 'HR Executive', 'description' => 'Handles day-to-day HR operations, employee onboarding, and maintains employee records'],
                ['name' => 'Recruiter', 'description' => 'Responsible for talent acquisition, conducting interviews, and managing recruitment processes'],
                ['name' => 'HR Assistant', 'description' => 'Provides administrative support to HR team and assists in various HR activities']
            ],
            'Information Technology' => [
                ['name' => 'IT Manager', 'description' => 'Manages IT infrastructure, oversees technical team, and ensures system security and performance'],
                ['name' => 'Software Developer', 'description' => 'Designs, develops, and maintains software applications and systems'],
                ['name' => 'System Administrator', 'description' => 'Maintains and configures computer systems, networks, and servers'],
                ['name' => 'Technical Lead', 'description' => 'Leads technical projects, mentors developers, and ensures code quality standards'],
                ['name' => 'QA Engineer', 'description' => 'Tests software applications, identifies bugs, and ensures quality standards are met']
            ],
            'Finance & Accounting' => [
                ['name' => 'Finance Manager', 'description' => 'Oversees financial planning, budgeting, and financial reporting activities'],
                ['name' => 'Accountant', 'description' => 'Manages financial records, prepares financial statements, and ensures compliance'],
                ['name' => 'Financial Analyst', 'description' => 'Analyzes financial data, prepares reports, and provides insights for decision making'],
                ['name' => 'Accounts Executive', 'description' => 'Handles accounts payable/receivable, invoice processing, and vendor management']
            ],
            'Marketing' => [
                ['name' => 'Marketing Manager', 'description' => 'Develops marketing strategies, manages campaigns, and oversees brand promotion'],
                ['name' => 'Marketing Executive', 'description' => 'Executes marketing campaigns, manages social media, and coordinates promotional activities'],
                ['name' => 'Digital Marketing Specialist', 'description' => 'Manages online marketing campaigns, SEO/SEM, and digital advertising strategies'],
                ['name' => 'Content Writer', 'description' => 'Creates engaging content for websites, blogs, marketing materials, and social media']
            ],
            'Sales' => [
                ['name' => 'Sales Manager', 'description' => 'Leads sales team, develops sales strategies, and manages client relationships'],
                ['name' => 'Sales Executive', 'description' => 'Generates leads, conducts sales presentations, and closes deals with potential clients'],
                ['name' => 'Sales Representative', 'description' => 'Represents company products/services, maintains customer relationships, and achieves sales targets'],
                ['name' => 'Business Development Executive', 'description' => 'Identifies new business opportunities, develops partnerships, and expands market reach']
            ],
            'Operations' => [
                ['name' => 'Operations Manager', 'description' => 'Oversees daily operations, ensures efficiency, and manages operational processes'],
                ['name' => 'Operations Executive', 'description' => 'Coordinates operational activities, monitors performance, and implements process improvements'],
                ['name' => 'Process Analyst', 'description' => 'Analyzes business processes, identifies inefficiencies, and recommends optimization solutions'],
                ['name' => 'Operations Coordinator', 'description' => 'Coordinates between departments, schedules activities, and ensures smooth operations']
            ],
            'Customer Service' => [
                ['name' => 'Customer Service Manager', 'description' => 'Manages customer service team, handles escalations, and ensures customer satisfaction'],
                ['name' => 'Customer Support Executive', 'description' => 'Provides customer support, resolves queries, and maintains positive customer relationships'],
                ['name' => 'Call Center Agent', 'description' => 'Handles inbound/outbound calls, provides information, and assists customers with their needs']
            ],
            'Research & Development' => [
                ['name' => 'R&D Manager', 'description' => 'Leads research initiatives, manages R&D projects, and drives innovation strategies'],
                ['name' => 'Research Analyst', 'description' => 'Conducts market research, analyzes data, and provides insights for product development'],
                ['name' => 'Product Developer', 'description' => 'Develops new products, improves existing offerings, and ensures market readiness'],
                ['name' => 'Innovation Specialist', 'description' => 'Identifies emerging trends, evaluates new technologies, and drives innovation initiatives']
            ],
            'Legal' => [
                ['name' => 'Legal Manager', 'description' => 'Manages legal affairs, oversees contracts, and ensures regulatory compliance'],
                ['name' => 'Legal Advisor', 'description' => 'Provides legal counsel, reviews agreements, and advises on legal matters'],
                ['name' => 'Compliance Officer', 'description' => 'Ensures company compliance with laws and regulations, manages risk assessments']
            ],
            'Administration' => [
                ['name' => 'Admin Manager', 'description' => 'Oversees administrative functions, manages office operations, and ensures smooth workflow'],
                ['name' => 'Administrative Assistant', 'description' => 'Provides administrative support, manages schedules, and handles office correspondence'],
                ['name' => 'Office Coordinator', 'description' => 'Coordinates office activities, manages supplies, and ensures efficient office operations']
            ]
        ];

        foreach ($companies as $company) {
            // Get all departments for this company
            $departments = Department::where('created_by', $company->id)->get();

            if ($departments->isEmpty()) {
                $this->command->warn('No departments found for company: ' . $company->name . '. Please run DepartmentSeeder first.');
                continue;
            }

            foreach ($departments as $department) {
                // Get designations for this department type
                $designations = $designationsByDepartment[$department->name] ?? [
                    ['name' => 'Manager', 'description' => 'Manages department operations and oversees team performance'],
                    ['name' => 'Executive', 'description' => 'Executes departmental tasks and supports management activities'],
                    ['name' => 'Assistant', 'description' => 'Provides administrative support and assists in daily operations']
                ];

                // Create 1-3 designations for each department
                $designationCount = rand(1, min(3, count($designations)));

                for ($i = 0; $i < $designationCount; $i++) {
                    $designation = $designations[$i];
                    $designationName = $designation['name'];
                    $designationDescription = $designation['description'];

                    // Check if designation already exists for this department
                    if (Designation::where('name', $designationName)->where('department_id', $department->id)->exists()) {
                        continue;
                    }

                    try {
                        Designation::create([
                            'name' => $designationName,
                            'department_id' => $department->id,
                            'description' => $designationDescription,
                            'status' => 'active',
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create designation: ' . $designationName . ' for department: ' . $department->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('Designation seeder completed successfully!');
    }
}
