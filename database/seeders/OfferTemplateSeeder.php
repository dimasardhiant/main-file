<?php

namespace Database\Seeders;

use App\Models\OfferTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class OfferTemplateSeeder extends Seeder
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

        // Fixed offer templates for consistent data
        $offerTemplates = [
            [
                'name' => 'Standard Full-Time Offer',
                'template_content' => 'Dear {{candidate_name}},

We are pleased to extend an offer of employment for the position of {{job_title}} at {{company_name}}. We believe your skills and experience will be a valuable addition to our team.

Position Details:
- Job Title: {{job_title}}
- Department: {{department}}
- Start Date: {{start_date}}
- Reporting Manager: {{manager_name}}

Compensation Package:
- Annual Salary: {{salary}}
- Performance Bonus: {{bonus}}
- Benefits: {{benefits}}

Terms and Conditions:
- Employment Type: Full-time
- Probation Period: {{probation_period}}
- Notice Period: {{notice_period}}
- Working Hours: {{working_hours}}

This offer is contingent upon successful completion of background verification and reference checks. Please confirm your acceptance by {{offer_expiry_date}}.

We look forward to welcoming you to our team.

Best regards,
{{hr_manager_name}}
Human Resources
{{company_name}}',
                'variables' => [
                    'candidate_name',
                    'job_title',
                    'company_name',
                    'department',
                    'start_date',
                    'manager_name',
                    'salary',
                    'bonus',
                    'benefits',
                    'probation_period',
                    'notice_period',
                    'working_hours',
                    'offer_expiry_date',
                    'hr_manager_name'
                ],
                'status' => 'active'
            ],
            [
                'name' => 'Contract Position Offer',
                'template_content' => 'Dear {{candidate_name}},

We are pleased to offer you a contract position for {{job_title}} at {{company_name}}.

Contract Details:
- Position: {{job_title}}
- Contract Duration: {{contract_duration}}
- Start Date: {{start_date}}
- End Date: {{end_date}}
- Location: {{work_location}}

Compensation:
- Contract Rate: {{contract_rate}}
- Payment Terms: {{payment_terms}}
- Additional Benefits: {{contract_benefits}}

Terms:
- Contract Type: {{contract_type}}
- Renewal Options: {{renewal_terms}}
- Termination Clause: {{termination_clause}}

Please review the attached contract agreement and confirm your acceptance by {{acceptance_deadline}}.

Best regards,
{{hr_contact_name}}
{{company_name}}',
                'variables' => [
                    'candidate_name',
                    'job_title',
                    'company_name',
                    'contract_duration',
                    'start_date',
                    'end_date',
                    'work_location',
                    'contract_rate',
                    'payment_terms',
                    'contract_benefits',
                    'contract_type',
                    'renewal_terms',
                    'termination_clause',
                    'acceptance_deadline',
                    'hr_contact_name'
                ],
                'status' => 'active'
            ],
            [
                'name' => 'Senior Management Offer',
                'template_content' => 'Dear {{candidate_name}},

On behalf of {{company_name}}, I am delighted to extend an offer for the position of {{job_title}}.

Executive Position Details:
- Title: {{job_title}}
- Division: {{division}}
- Reporting: {{reporting_structure}}
- Start Date: {{start_date}}

Executive Compensation Package:
- Base Salary: {{base_salary}}
- Variable Compensation: {{variable_pay}}
- Equity/Stock Options: {{equity_details}}
- Executive Benefits: {{executive_benefits}}
- Car Allowance: {{car_allowance}}
- Club Membership: {{club_membership}}

Additional Terms:
- Employment Agreement: {{employment_agreement}}
- Confidentiality: {{confidentiality_terms}}
- Non-Compete: {{non_compete_clause}}

This offer reflects our confidence in your ability to contribute significantly to our organization\'s success.

Please confirm your acceptance by {{response_deadline}}.

Sincerely,
{{ceo_name}}
Chief Executive Officer
{{company_name}}',
                'variables' => [
                    'candidate_name',
                    'company_name',
                    'job_title',
                    'division',
                    'reporting_structure',
                    'start_date',
                    'base_salary',
                    'variable_pay',
                    'equity_details',
                    'executive_benefits',
                    'car_allowance',
                    'club_membership',
                    'employment_agreement',
                    'confidentiality_terms',
                    'non_compete_clause',
                    'response_deadline',
                    'ceo_name'
                ],
                'status' => 'active'
            ],
            [
                'name' => 'Internship Offer',
                'template_content' => 'Dear {{candidate_name}},

Congratulations! We are pleased to offer you an internship position at {{company_name}}.

Internship Details:
- Position: {{internship_title}}
- Department: {{department}}
- Duration: {{internship_duration}}
- Start Date: {{start_date}}
- End Date: {{end_date}}
- Supervisor: {{supervisor_name}}

Internship Benefits:
- Monthly Stipend: {{stipend_amount}}
- Learning Opportunities: {{learning_programs}}
- Mentorship: {{mentorship_details}}
- Certificate: {{certificate_details}}

Expectations:
- Working Hours: {{working_hours}}
- Project Assignments: {{project_details}}
- Performance Evaluation: {{evaluation_process}}

We are excited to have you join our team and contribute to your professional development.

Please confirm your acceptance by {{confirmation_date}}.

Best regards,
{{internship_coordinator}}
{{company_name}}',
                'variables' => [
                    'candidate_name',
                    'company_name',
                    'internship_title',
                    'department',
                    'internship_duration',
                    'start_date',
                    'end_date',
                    'supervisor_name',
                    'stipend_amount',
                    'learning_programs',
                    'mentorship_details',
                    'certificate_details',
                    'working_hours',
                    'project_details',
                    'evaluation_process',
                    'confirmation_date',
                    'internship_coordinator'
                ],
                'status' => 'active'
            ],
            [
                'name' => 'Remote Work Offer',
                'template_content' => 'Dear {{candidate_name}},

We are excited to offer you the remote position of {{job_title}} at {{company_name}}.

Remote Work Details:
- Position: {{job_title}}
- Work Model: {{work_model}}
- Time Zone: {{time_zone}}
- Start Date: {{start_date}}
- Team: {{team_name}}

Compensation & Benefits:
- Annual Salary: {{annual_salary}}
- Remote Work Allowance: {{remote_allowance}}
- Equipment Provided: {{equipment_list}}
- Health Benefits: {{health_benefits}}
- Vacation Days: {{vacation_days}}

Remote Work Policies:
- Communication Tools: {{communication_tools}}
- Meeting Schedule: {{meeting_schedule}}
- Performance Tracking: {{performance_metrics}}
- Office Visits: {{office_visit_requirements}}

Technology Setup:
- Laptop/Desktop: {{computer_specs}}
- Software Licenses: {{software_provided}}
- Internet Reimbursement: {{internet_allowance}}

Please confirm your acceptance and provide your shipping address for equipment delivery by {{acceptance_date}}.

Welcome to the team!

{{hiring_manager_name}}
{{company_name}}',
                'variables' => [
                    'candidate_name',
                    'job_title',
                    'company_name',
                    'work_model',
                    'time_zone',
                    'start_date',
                    'team_name',
                    'annual_salary',
                    'remote_allowance',
                    'equipment_list',
                    'health_benefits',
                    'vacation_days',
                    'communication_tools',
                    'meeting_schedule',
                    'performance_metrics',
                    'office_visit_requirements',
                    'computer_specs',
                    'software_provided',
                    'internet_allowance',
                    'acceptance_date',
                    'hiring_manager_name'
                ],
                'status' => 'active'
            ],
            [
                'name' => 'Part-Time Position Offer',
                'template_content' => 'Dear {{candidate_name}},

We are pleased to offer you a part-time position as {{job_title}} at {{company_name}}.

Part-Time Position Details:
- Job Title: {{job_title}}
- Department: {{department}}
- Working Hours: {{part_time_hours}}
- Schedule: {{work_schedule}}
- Start Date: {{start_date}}

Compensation:
- Hourly Rate: {{hourly_rate}}
- Monthly Salary: {{monthly_salary}}
- Pro-rated Benefits: {{prorated_benefits}}

Work Arrangement:
- Flexible Hours: {{flexible_hours}}
- Core Hours: {{core_hours}}
- Remote Work: {{remote_options}}

Benefits (Pro-rated):
- Health Insurance: {{health_coverage}}
- Paid Time Off: {{pto_days}}
- Professional Development: {{training_budget}}

This position offers excellent work-life balance while contributing meaningfully to our organization.

Please respond by {{response_date}}.

Best regards,
{{hr_representative}}
{{company_name}}',
                'variables' => [
                    'candidate_name',
                    'job_title',
                    'company_name',
                    'department',
                    'part_time_hours',
                    'work_schedule',
                    'start_date',
                    'hourly_rate',
                    'monthly_salary',
                    'prorated_benefits',
                    'flexible_hours',
                    'core_hours',
                    'remote_options',
                    'health_coverage',
                    'pto_days',
                    'training_budget',
                    'response_date',
                    'hr_representative'
                ],
                'status' => 'active'
            ]
        ];

        foreach ($companies as $company) {
            foreach ($offerTemplates as $templateData) {
                // Check if offer template already exists for this company
                if (OfferTemplate::where('name', $templateData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    OfferTemplate::create([
                        'name' => $templateData['name'],
                        'template_content' => $templateData['template_content'],
                        'variables' => $templateData['variables'],
                        'status' => $templateData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create offer template: ' . $templateData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('OfferTemplate seeder completed successfully!');
    }
}
