<?php

namespace Database\Seeders;

use App\Models\JobRequisition;
use App\Models\JobCategory;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobRequisitionSeeder extends Seeder
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

        // Fixed job requisitions by category for consistent data
        $requisitionsByCategory = [
            'Information Technology' => [
                ['title' => 'Senior Software Developer', 'positions' => 2, 'budget_min' => 80000, 'budget_max' => 120000, 'priority' => 'High', 'status' => 'Approved'],
                ['title' => 'DevOps Engineer', 'positions' => 1, 'budget_min' => 70000, 'budget_max' => 100000, 'priority' => 'Medium', 'status' => 'Pending Approval'],
                ['title' => 'UI/UX Designer', 'positions' => 1, 'budget_min' => 60000, 'budget_max' => 85000, 'priority' => 'Medium', 'status' => 'Approved']
            ],
            'Sales and Marketing' => [
                ['title' => 'Sales Manager', 'positions' => 1, 'budget_min' => 65000, 'budget_max' => 90000, 'priority' => 'High', 'status' => 'Approved'],
                ['title' => 'Digital Marketing Specialist', 'positions' => 2, 'budget_min' => 45000, 'budget_max' => 65000, 'priority' => 'Medium', 'status' => 'Draft'],
                ['title' => 'Business Development Executive', 'positions' => 3, 'budget_min' => 50000, 'budget_max' => 70000, 'priority' => 'High', 'status' => 'Approved']
            ],
            'Human Resources' => [
                ['title' => 'HR Business Partner', 'positions' => 1, 'budget_min' => 70000, 'budget_max' => 95000, 'priority' => 'Medium', 'status' => 'Approved'],
                ['title' => 'Talent Acquisition Specialist', 'positions' => 2, 'budget_min' => 55000, 'budget_max' => 75000, 'priority' => 'High', 'status' => 'Pending Approval']
            ],
            'Finance and Accounting' => [
                ['title' => 'Financial Analyst', 'positions' => 1, 'budget_min' => 60000, 'budget_max' => 80000, 'priority' => 'Medium', 'status' => 'Approved'],
                ['title' => 'Senior Accountant', 'positions' => 1, 'budget_min' => 55000, 'budget_max' => 75000, 'priority' => 'Low', 'status' => 'On Hold']
            ],
            'Operations and Management' => [
                ['title' => 'Operations Manager', 'positions' => 1, 'budget_min' => 85000, 'budget_max' => 110000, 'priority' => 'High', 'status' => 'Approved'],
                ['title' => 'Project Manager', 'positions' => 2, 'budget_min' => 75000, 'budget_max' => 95000, 'priority' => 'Medium', 'status' => 'Approved']
            ],
            'Customer Service' => [
                ['title' => 'Customer Success Manager', 'positions' => 1, 'budget_min' => 50000, 'budget_max' => 70000, 'priority' => 'Medium', 'status' => 'Approved'],
                ['title' => 'Customer Support Representative', 'positions' => 4, 'budget_min' => 35000, 'budget_max' => 45000, 'priority' => 'High', 'status' => 'Approved']
            ]
        ];

        // Fixed job details
        $jobDetails = [
            'Senior Software Developer' => [
                'skills' => 'JavaScript, React, Node.js, Python, SQL, Git, Agile methodologies',
                'education' => 'Bachelor\'s degree in Computer Science or related field',
                'experience' => '5+ years of software development experience',
                'description' => 'We are seeking an experienced Senior Software Developer to join our dynamic development team',
                'responsibilities' => 'Design and develop scalable web applications, mentor junior developers, participate in code reviews, collaborate with cross-functional teams'
            ],
            'DevOps Engineer' => [
                'skills' => 'AWS, Docker, Kubernetes, Jenkins, Terraform, Linux, CI/CD pipelines',
                'education' => 'Bachelor\'s degree in Computer Science or Engineering',
                'experience' => '3+ years of DevOps or system administration experience',
                'description' => 'Looking for a skilled DevOps Engineer to manage our cloud infrastructure and deployment processes',
                'responsibilities' => 'Manage cloud infrastructure, implement CI/CD pipelines, monitor system performance, ensure security compliance'
            ],
            'Sales Manager' => [
                'skills' => 'Sales leadership, CRM software, negotiation, team management, market analysis',
                'education' => 'Bachelor\'s degree in Business, Marketing, or related field',
                'experience' => '5+ years of sales experience with 2+ years in management',
                'description' => 'Seeking an experienced Sales Manager to lead our sales team and drive revenue growth',
                'responsibilities' => 'Lead sales team, develop sales strategies, manage key accounts, analyze market trends, achieve sales targets'
            ],
            'HR Business Partner' => [
                'skills' => 'HR strategy, employee relations, performance management, organizational development',
                'education' => 'Bachelor\'s degree in Human Resources, Business, or Psychology',
                'experience' => '4+ years of HR experience with business partnering focus',
                'description' => 'We need an HR Business Partner to support our business units and drive HR initiatives',
                'responsibilities' => 'Partner with business leaders, manage employee relations, develop HR policies, support organizational change'
            ],
            'Financial Analyst' => [
                'skills' => 'Financial modeling, Excel, SQL, data analysis, budgeting, forecasting',
                'education' => 'Bachelor\'s degree in Finance, Accounting, or Economics',
                'experience' => '2-4 years of financial analysis experience',
                'description' => 'Looking for a detail-oriented Financial Analyst to support our finance team',
                'responsibilities' => 'Prepare financial reports, conduct variance analysis, support budgeting process, provide financial insights'
            ],
            'Operations Manager' => [
                'skills' => 'Operations management, process improvement, team leadership, project management',
                'education' => 'Bachelor\'s degree in Business, Operations, or Engineering',
                'experience' => '5+ years of operations management experience',
                'description' => 'Seeking an Operations Manager to optimize our business processes and operations',
                'responsibilities' => 'Manage daily operations, improve processes, lead operational teams, ensure quality standards'
            ]
        ];

        foreach ($companies as $company) {
            // Get job categories for this company
            $jobCategories = JobCategory::where('created_by', $company->id)->get();

            if ($jobCategories->isEmpty()) {
                $this->command->warn('No job categories found for company: ' . $company->name . '. Please run JobCategorySeeder first.');
                continue;
            }

            // Get departments for this company
            $departments = Department::where('created_by', $company->id)->get();

            // Get managers/HR for approval
            $approvers = User::whereIn('type', ['manager', 'hr'])->where('created_by', $company->id)->get();
            $approver = $approvers->isNotEmpty() ? $approvers->first() : null;

            $requisitionCounter = ($company->id - 1) * 100 + 1;

            foreach ($jobCategories as $category) {
                $categoryRequisitions = $requisitionsByCategory[$category->name] ?? [];

                foreach ($categoryRequisitions as $reqData) {
                    $requisitionCode = 'REQ-' . $currentYear . '-' . str_pad($requisitionCounter, 4, '0', STR_PAD_LEFT);

                    // Check if requisition already exists
                    if (JobRequisition::where('requisition_code', $requisitionCode)->exists()) {
                        $requisitionCounter++;
                        continue;
                    }

                    $selectedDepartments = $departments->take(5);
                    $department = $selectedDepartments->isNotEmpty() ? $selectedDepartments->random() : null;
                    $details = $jobDetails[$reqData['title']] ?? [
                        'skills' => 'Relevant skills for the position',
                        'education' => 'Bachelor\'s degree or equivalent',
                        'experience' => '2+ years of relevant experience',
                        'description' => 'We are looking for a qualified candidate for this position',
                        'responsibilities' => 'Perform duties as assigned and contribute to team success'
                    ];

                    try {
                        JobRequisition::create([
                            'requisition_code' => $requisitionCode,
                            'title' => $reqData['title'],
                            'job_category_id' => $category->id,
                            'department_id' => $department?->id,
                            'positions_count' => $reqData['positions'],
                            'budget_min' => $reqData['budget_min'],
                            'budget_max' => $reqData['budget_max'],
                            'skills_required' => $details['skills'],
                            'education_required' => $details['education'],
                            'experience_required' => $details['experience'],
                            'description' => $details['description'],
                            'responsibilities' => $details['responsibilities'],
                            'status' => $reqData['status'],
                            'approved_by' => $reqData['status'] === 'Approved' ? $approver?->id : null,
                            'approval_date' => $reqData['status'] === 'Approved' ? now() : null,
                            'priority' => $reqData['priority'],
                            'created_by' => $company->id,
                        ]);

                        $requisitionCounter++;
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create job requisition: ' . $reqData['title'] . ' for company: ' . $company->name);
                        $requisitionCounter++;
                        continue;
                    }
                }
            }
        }

        $this->command->info('JobRequisition seeder completed successfully!');
    }
}
