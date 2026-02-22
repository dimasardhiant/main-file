<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use App\Models\OnboardingChecklist;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChecklistItemSeeder extends Seeder
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

        // Fixed checklist items by checklist type
        $checklistItems = [
            'Standard Employee Onboarding' => [
                ['task_name' => 'Complete Employment Contract', 'description' => 'Review and sign employment contract and related documents', 'category' => 'Documentation', 'assigned_to_role' => 'HR', 'due_day' => 1, 'is_required' => true],
                ['task_name' => 'Submit Personal Documents', 'description' => 'Provide ID proof, address proof, and educational certificates', 'category' => 'Documentation', 'assigned_to_role' => 'HR', 'due_day' => 1, 'is_required' => true],
                ['task_name' => 'IT Equipment Setup', 'description' => 'Receive laptop, phone, and other necessary IT equipment', 'category' => 'IT Setup', 'assigned_to_role' => 'IT', 'due_day' => 1, 'is_required' => true],
                ['task_name' => 'Create System Accounts', 'description' => 'Setup email, system access, and security credentials', 'category' => 'IT Setup', 'assigned_to_role' => 'IT', 'due_day' => 2, 'is_required' => true],
                ['task_name' => 'Company Orientation Session', 'description' => 'Attend company overview and culture orientation', 'category' => 'Training', 'assigned_to_role' => 'HR', 'due_day' => 3, 'is_required' => true],
                ['task_name' => 'Office Tour and Introductions', 'description' => 'Tour office facilities and meet team members', 'category' => 'Facilities', 'assigned_to_role' => 'Manager', 'due_day' => 3, 'is_required' => true],
                ['task_name' => 'Benefits Enrollment', 'description' => 'Complete health insurance and benefits registration', 'category' => 'HR', 'assigned_to_role' => 'HR', 'due_day' => 5, 'is_required' => true],
                ['task_name' => 'Safety Training', 'description' => 'Complete workplace safety and emergency procedures training', 'category' => 'Training', 'assigned_to_role' => 'HR', 'due_day' => 7, 'is_required' => true]
            ],
            'Technical Team Onboarding' => [
                ['task_name' => 'Development Environment Setup', 'description' => 'Install and configure development tools and IDEs', 'category' => 'IT Setup', 'assigned_to_role' => 'IT', 'due_day' => 1, 'is_required' => true],
                ['task_name' => 'Code Repository Access', 'description' => 'Grant access to version control systems and repositories', 'category' => 'IT Setup', 'assigned_to_role' => 'IT', 'due_day' => 2, 'is_required' => true],
                ['task_name' => 'Technical Documentation Review', 'description' => 'Review system architecture and technical documentation', 'category' => 'Training', 'assigned_to_role' => 'Manager', 'due_day' => 3, 'is_required' => true],
                ['task_name' => 'Code Standards Training', 'description' => 'Learn coding standards and development practices', 'category' => 'Training', 'assigned_to_role' => 'Manager', 'due_day' => 5, 'is_required' => true],
                ['task_name' => 'Project Assignment', 'description' => 'Assign initial project and define responsibilities', 'category' => 'Other', 'assigned_to_role' => 'Manager', 'due_day' => 7, 'is_required' => true],
                ['task_name' => 'Security Clearance', 'description' => 'Complete security training and access permissions', 'category' => 'IT Setup', 'assigned_to_role' => 'IT', 'due_day' => 3, 'is_required' => true]
            ],
            'Management Level Onboarding' => [
                ['task_name' => 'Executive Briefing', 'description' => 'Strategic overview and company direction briefing', 'category' => 'Training', 'assigned_to_role' => 'CEO', 'due_day' => 1, 'is_required' => true],
                ['task_name' => 'Leadership Team Introductions', 'description' => 'Meet with senior leadership and key stakeholders', 'category' => 'Other', 'assigned_to_role' => 'CEO', 'due_day' => 2, 'is_required' => true],
                ['task_name' => 'Department Overview', 'description' => 'Review department structure, goals, and challenges', 'category' => 'Training', 'assigned_to_role' => 'HR', 'due_day' => 3, 'is_required' => true],
                ['task_name' => 'Budget and Financial Review', 'description' => 'Review departmental budget and financial responsibilities', 'category' => 'Training', 'assigned_to_role' => 'Finance', 'due_day' => 5, 'is_required' => true],
                ['task_name' => 'Management Tools Access', 'description' => 'Setup access to management dashboards and reporting tools', 'category' => 'IT Setup', 'assigned_to_role' => 'IT', 'due_day' => 3, 'is_required' => true]
            ],
            'Sales Team Onboarding' => [
                ['task_name' => 'CRM System Training', 'description' => 'Complete training on customer relationship management system', 'category' => 'Training', 'assigned_to_role' => 'Sales Manager', 'due_day' => 2, 'is_required' => true],
                ['task_name' => 'Product Knowledge Training', 'description' => 'Learn about products, services, and value propositions', 'category' => 'Training', 'assigned_to_role' => 'Sales Manager', 'due_day' => 5, 'is_required' => true],
                ['task_name' => 'Sales Process Training', 'description' => 'Understand sales methodology and processes', 'category' => 'Training', 'assigned_to_role' => 'Sales Manager', 'due_day' => 7, 'is_required' => true],
                ['task_name' => 'Territory Assignment', 'description' => 'Assign sales territory and customer accounts', 'category' => 'Other', 'assigned_to_role' => 'Sales Manager', 'due_day' => 3, 'is_required' => true],
                ['task_name' => 'Sales Tools Setup', 'description' => 'Configure sales tools, presentations, and materials', 'category' => 'IT Setup', 'assigned_to_role' => 'IT', 'due_day' => 2, 'is_required' => true]
            ],
            'Remote Employee Onboarding' => [
                ['task_name' => 'Equipment Shipping', 'description' => 'Ship laptop, monitor, and other equipment to home address', 'category' => 'IT Setup', 'assigned_to_role' => 'IT', 'due_day' => 1, 'is_required' => true],
                ['task_name' => 'Remote Setup Assistance', 'description' => 'Provide technical support for home office setup', 'category' => 'IT Setup', 'assigned_to_role' => 'IT', 'due_day' => 2, 'is_required' => true],
                ['task_name' => 'Virtual Team Introductions', 'description' => 'Schedule video calls with team members and stakeholders', 'category' => 'Other', 'assigned_to_role' => 'Manager', 'due_day' => 3, 'is_required' => true],
                ['task_name' => 'Remote Work Policy Training', 'description' => 'Review remote work policies and expectations', 'category' => 'Training', 'assigned_to_role' => 'HR', 'due_day' => 3, 'is_required' => true],
                ['task_name' => 'Communication Tools Training', 'description' => 'Training on video conferencing and collaboration tools', 'category' => 'Training', 'assigned_to_role' => 'IT', 'due_day' => 2, 'is_required' => true]
            ],
            'Intern Onboarding Program' => [
                ['task_name' => 'Internship Agreement', 'description' => 'Complete internship agreement and program expectations', 'category' => 'Documentation', 'assigned_to_role' => 'HR', 'due_day' => 1, 'is_required' => true],
                ['task_name' => 'Mentor Assignment', 'description' => 'Assign mentor and schedule regular check-ins', 'category' => 'Other', 'assigned_to_role' => 'Manager', 'due_day' => 1, 'is_required' => true],
                ['task_name' => 'Project Overview', 'description' => 'Present internship project goals and deliverables', 'category' => 'Training', 'assigned_to_role' => 'Manager', 'due_day' => 2, 'is_required' => true],
                ['task_name' => 'Basic System Access', 'description' => 'Provide limited system access for internship duties', 'category' => 'IT Setup', 'assigned_to_role' => 'IT', 'due_day' => 1, 'is_required' => true]
            ],
            'Customer Service Onboarding' => [
                ['task_name' => 'Customer Service System Training', 'description' => 'Training on helpdesk and ticketing systems', 'category' => 'Training', 'assigned_to_role' => 'CS Manager', 'due_day' => 2, 'is_required' => true],
                ['task_name' => 'Product Knowledge Training', 'description' => 'Comprehensive training on products and services', 'category' => 'Training', 'assigned_to_role' => 'CS Manager', 'due_day' => 5, 'is_required' => true],
                ['task_name' => 'Customer Interaction Protocols', 'description' => 'Learn customer communication standards and escalation procedures', 'category' => 'Training', 'assigned_to_role' => 'CS Manager', 'due_day' => 3, 'is_required' => true],
                ['task_name' => 'Quality Assurance Training', 'description' => 'Understand quality metrics and performance standards', 'category' => 'Training', 'assigned_to_role' => 'CS Manager', 'due_day' => 7, 'is_required' => true]
            ],
            'Finance Department Onboarding' => [
                ['task_name' => 'Financial Systems Access', 'description' => 'Setup access to accounting and financial management systems', 'category' => 'IT Setup', 'assigned_to_role' => 'IT', 'due_day' => 1, 'is_required' => true],
                ['task_name' => 'Compliance Training', 'description' => 'Training on financial regulations and compliance requirements', 'category' => 'Training', 'assigned_to_role' => 'Finance Manager', 'due_day' => 3, 'is_required' => true],
                ['task_name' => 'Accounting Procedures Training', 'description' => 'Learn company accounting processes and procedures', 'category' => 'Training', 'assigned_to_role' => 'Finance Manager', 'due_day' => 5, 'is_required' => true],
                ['task_name' => 'Financial Reporting Training', 'description' => 'Training on financial reporting requirements and schedules', 'category' => 'Training', 'assigned_to_role' => 'Finance Manager', 'due_day' => 7, 'is_required' => true]
            ]
        ];

        foreach ($companies as $company) {
            // Get onboarding checklists for this company
            $onboardingChecklists = OnboardingChecklist::where('created_by', $company->id)->get();

            if ($onboardingChecklists->isEmpty()) {
                $this->command->warn('No onboarding checklists found for company: ' . $company->name . '. Please run OnboardingChecklistSeeder first.');
                continue;
            }

            foreach ($onboardingChecklists as $checklist) {
                $items = $checklistItems[$checklist->name] ?? [];

                foreach ($items as $itemData) {
                    // Check if checklist item already exists
                    if (ChecklistItem::where('task_name', $itemData['task_name'])
                        ->where('checklist_id', $checklist->id)
                        ->exists()
                    ) {
                        continue;
                    }

                    try {
                        ChecklistItem::create([
                            'checklist_id' => $checklist->id,
                            'task_name' => $itemData['task_name'],
                            'description' => $itemData['description'],
                            'category' => $itemData['category'],
                            'assigned_to_role' => $itemData['assigned_to_role'],
                            'due_day' => $itemData['due_day'],
                            'is_required' => $itemData['is_required'],
                            'status' => 'active',
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create checklist item: ' . $itemData['task_name'] . ' for checklist: ' . $checklist->name . ' in company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('ChecklistItem seeder completed successfully!');
    }
}
