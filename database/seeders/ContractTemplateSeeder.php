<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use App\Models\ContractType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
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

        // Fixed contract templates for consistent data
        $contractTemplates = [
            [
                'name' => 'Standard Permanent Employment Contract',
                'description' => 'Standard template for permanent full-time employees with comprehensive terms and conditions',
                'contract_type' => 'Permanent Full-time',
                'template_content' => 'EMPLOYMENT AGREEMENT

This Employment Agreement is entered into between {{company_name}} and {{employee_name}}.

POSITION AND DUTIES:
Employee is hired as {{job_title}} in the {{department}} department, reporting to {{manager_name}}.

COMPENSATION:
- Basic Salary: {{basic_salary}} per annum
- Allowances: {{allowances}}
- Benefits: {{benefits}}

EMPLOYMENT TERMS:
- Start Date: {{start_date}}
- Probation Period: {{probation_period}} months
- Notice Period: {{notice_period}} days
- Working Hours: {{working_hours}}

CONFIDENTIALITY:
Employee agrees to maintain confidentiality of company information and trade secrets.

TERMINATION:
Either party may terminate this agreement with {{notice_period}} days written notice.

This agreement is governed by the laws of {{jurisdiction}}.

Signed:
{{employee_name}} - Employee
{{hr_manager}} - HR Manager
Date: {{contract_date}}',
                'variables' => ['company_name', 'employee_name', 'job_title', 'department', 'manager_name', 'basic_salary', 'allowances', 'benefits', 'start_date', 'probation_period', 'notice_period', 'working_hours', 'jurisdiction', 'hr_manager', 'contract_date'],
                'clauses' => ['Confidentiality', 'Non-Compete', 'Termination', 'Benefits', 'Working Hours'],
                'is_default' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Fixed-Term Contract Template',
                'description' => 'Template for fixed-term contracts with specific start and end dates',
                'contract_type' => 'Fixed-term Contract',
                'template_content' => 'FIXED-TERM EMPLOYMENT CONTRACT

This Fixed-Term Contract is between {{company_name}} and {{employee_name}}.

CONTRACT PERIOD:
- Start Date: {{start_date}}
- End Date: {{end_date}}
- Duration: {{contract_duration}} months

POSITION:
Employee is engaged as {{job_title}} for the specified contract period.

COMPENSATION:
- Monthly Salary: {{monthly_salary}}
- Total Contract Value: {{total_value}}
- Payment Terms: {{payment_terms}}

RENEWAL:
This contract may be renewed by mutual agreement before expiration.

EARLY TERMINATION:
Contract may be terminated early with {{notice_period}} days notice or payment in lieu.

DELIVERABLES:
{{project_deliverables}}

Signed:
{{employee_name}} - Contractor
{{project_manager}} - Project Manager
Date: {{contract_date}}',
                'variables' => ['company_name', 'employee_name', 'start_date', 'end_date', 'contract_duration', 'job_title', 'monthly_salary', 'total_value', 'payment_terms', 'notice_period', 'project_deliverables', 'project_manager', 'contract_date'],
                'clauses' => ['Contract Period', 'Renewal Terms', 'Early Termination', 'Deliverables'],
                'is_default' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Part-Time Employment Contract',
                'description' => 'Contract template for part-time employees with flexible working arrangements',
                'contract_type' => 'Part-time Contract',
                'template_content' => 'PART-TIME EMPLOYMENT AGREEMENT

Agreement between {{company_name}} and {{employee_name}} for part-time employment.

WORKING ARRANGEMENT:
- Position: {{job_title}}
- Working Hours: {{weekly_hours}} hours per week
- Schedule: {{work_schedule}}
- Flexible Hours: {{flexible_arrangement}}

COMPENSATION:
- Hourly Rate: {{hourly_rate}}
- Monthly Salary: {{monthly_salary}}
- Pro-rated Benefits: {{prorated_benefits}}

BENEFITS:
- Health Insurance: {{health_coverage}}
- Paid Leave: {{leave_entitlement}} days annually
- Professional Development: {{training_budget}}

PERFORMANCE:
Performance will be evaluated based on {{performance_metrics}}.

TERMINATION:
Either party may terminate with {{notice_period}} days notice.

Signed:
{{employee_name}} - Employee
{{hr_representative}} - HR Representative
Date: {{contract_date}}',
                'variables' => ['company_name', 'employee_name', 'job_title', 'weekly_hours', 'work_schedule', 'flexible_arrangement', 'hourly_rate', 'monthly_salary', 'prorated_benefits', 'health_coverage', 'leave_entitlement', 'training_budget', 'performance_metrics', 'notice_period', 'hr_representative', 'contract_date'],
                'clauses' => ['Working Hours', 'Flexible Arrangement', 'Pro-rated Benefits', 'Performance Metrics'],
                'is_default' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Consultant Agreement Template',
                'description' => 'Independent contractor agreement for consulting services',
                'contract_type' => 'Consultant Agreement',
                'template_content' => 'INDEPENDENT CONTRACTOR AGREEMENT

This Agreement is between {{company_name}} and {{contractor_name}}.

SERVICES:
Contractor will provide {{service_description}} as an independent contractor.

SCOPE OF WORK:
{{scope_of_work}}

COMPENSATION:
- Contract Rate: {{contract_rate}}
- Payment Schedule: {{payment_schedule}}
- Total Contract Value: {{total_value}}
- Expenses: {{expense_policy}}

DELIVERABLES:
{{deliverables_list}}

TIMELINE:
- Start Date: {{start_date}}
- Completion Date: {{end_date}}
- Milestones: {{project_milestones}}

INTELLECTUAL PROPERTY:
All work products belong to {{company_name}}.

CONFIDENTIALITY:
Contractor agrees to maintain strict confidentiality.

TERMINATION:
Either party may terminate with {{notice_period}} days notice.

Signed:
{{contractor_name}} - Contractor
{{project_manager}} - Project Manager
Date: {{contract_date}}',
                'variables' => ['company_name', 'contractor_name', 'service_description', 'scope_of_work', 'contract_rate', 'payment_schedule', 'total_value', 'expense_policy', 'deliverables_list', 'start_date', 'end_date', 'project_milestones', 'notice_period', 'project_manager', 'contract_date'],
                'clauses' => ['Scope of Work', 'Payment Terms', 'Intellectual Property', 'Confidentiality', 'Termination'],
                'is_default' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Internship Agreement Template',
                'description' => 'Agreement template for internship programs and student placements',
                'contract_type' => 'Internship Contract',
                'template_content' => 'INTERNSHIP AGREEMENT

Agreement between {{company_name}} and {{intern_name}} for internship program.

INTERNSHIP DETAILS:
- Position: {{internship_title}}
- Department: {{department}}
- Duration: {{internship_duration}}
- Start Date: {{start_date}}
- End Date: {{end_date}}

SUPERVISION:
- Supervisor: {{supervisor_name}}
- Mentor: {{mentor_name}}

LEARNING OBJECTIVES:
{{learning_objectives}}

COMPENSATION:
- Monthly Stipend: {{stipend_amount}}
- Benefits: {{intern_benefits}}

EVALUATION:
Performance will be evaluated {{evaluation_frequency}} based on {{evaluation_criteria}}.

CONFIDENTIALITY:
Intern agrees to maintain confidentiality of company information.

CERTIFICATE:
Upon successful completion, intern will receive {{certificate_type}}.

Signed:
{{intern_name}} - Intern
{{supervisor_name}} - Supervisor
Date: {{contract_date}}',
                'variables' => ['company_name', 'intern_name', 'internship_title', 'department', 'internship_duration', 'start_date', 'end_date', 'supervisor_name', 'mentor_name', 'learning_objectives', 'stipend_amount', 'intern_benefits', 'evaluation_frequency', 'evaluation_criteria', 'certificate_type', 'contract_date'],
                'clauses' => ['Learning Objectives', 'Supervision', 'Evaluation', 'Confidentiality', 'Certificate'],
                'is_default' => false,
                'status' => 'active'
            ]
        ];

        foreach ($companies as $company) {
            // Get contract types for this company
            $contractTypes = ContractType::where('created_by', $company->id)->get();

            if ($contractTypes->isEmpty()) {
                $this->command->warn('No contract types found for company: ' . $company->name . '. Please run ContractTypeSeeder first.');
                continue;
            }

            foreach ($contractTemplates as $templateData) {
                // Find matching contract type
                $contractType = $contractTypes->where('name', $templateData['contract_type'])->first();
                if (!$contractType) $contractType = $contractTypes->first();

                // Check if template already exists for this company
                if (ContractTemplate::where('name', $templateData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    ContractTemplate::create([
                        'name' => $templateData['name'],
                        'description' => $templateData['description'],
                        'contract_type_id' => $contractType->id,
                        'template_content' => $templateData['template_content'],
                        'variables' => $templateData['variables'],
                        'clauses' => $templateData['clauses'],
                        'is_default' => $templateData['is_default'],
                        'status' => $templateData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create contract template: ' . $templateData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('ContractTemplate seeder completed successfully!');
    }
}
