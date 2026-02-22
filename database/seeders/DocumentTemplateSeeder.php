<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentTemplateSeeder extends Seeder
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

        // Fixed document templates for consistent data
        $documentTemplates = [
            [
                'name' => 'Employment Offer Letter',
                'description' => 'Standard template for employment offer letters with position details and compensation',
                'category' => 'Employment Documents',
                'template_content' => 'Dear {{candidate_name}},

We are pleased to offer you the position of {{job_title}} at {{company_name}}.

Position Details:
- Job Title: {{job_title}}
- Department: {{department}}
- Reporting Manager: {{manager_name}}
- Start Date: {{start_date}}
- Work Location: {{work_location}}

Compensation Package:
- Annual Salary: {{annual_salary}}
- Benefits: {{benefits_package}}
- Probation Period: {{probation_period}} months

Terms and Conditions:
- This offer is contingent upon successful completion of background verification
- Please confirm your acceptance by {{response_deadline}}
- Notice period: {{notice_period}} days

We look forward to welcoming you to our team.

Best regards,
{{hr_manager_name}}
Human Resources
{{company_name}}',
                'placeholders' => ['candidate_name', 'job_title', 'company_name', 'department', 'manager_name', 'start_date', 'work_location', 'annual_salary', 'benefits_package', 'probation_period', 'response_deadline', 'notice_period', 'hr_manager_name'],
                'default_values' => ['probation_period' => '6', 'notice_period' => '30', 'benefits_package' => 'Health Insurance, Provident Fund'],
                'is_default' => true,
                'file_format' => 'pdf',
                'status' => 'active'
            ],
            [
                'name' => 'Experience Certificate',
                'description' => 'Template for employee experience certificates upon resignation or completion of service',
                'category' => 'Employment Documents',
                'template_content' => 'EXPERIENCE CERTIFICATE

This is to certify that {{employee_name}} was employed with {{company_name}} from {{joining_date}} to {{leaving_date}}.

Employment Details:
- Employee ID: {{employee_id}}
- Designation: {{designation}}
- Department: {{department}}
- Employment Type: {{employment_type}}

During the tenure, {{employee_name}} worked with dedication and professionalism. {{he_she}} demonstrated excellent skills in {{key_skills}} and contributed significantly to {{achievements}}.

{{employee_name}} is leaving the organization on {{leaving_date}} due to {{reason_for_leaving}}.

We wish {{him_her}} all the best for future endeavors.

Issued on: {{issue_date}}

{{hr_manager_name}}
Human Resources Manager
{{company_name}}
{{company_address}}',
                'placeholders' => ['employee_name', 'company_name', 'joining_date', 'leaving_date', 'employee_id', 'designation', 'department', 'employment_type', 'he_she', 'key_skills', 'achievements', 'reason_for_leaving', 'him_her', 'issue_date', 'hr_manager_name', 'company_address'],
                'default_values' => ['employment_type' => 'Full-time', 'reason_for_leaving' => 'Personal reasons'],
                'is_default' => false,
                'file_format' => 'pdf',
                'status' => 'active'
            ],
            [
                'name' => 'Salary Certificate',
                'description' => 'Template for salary certificates required for loan applications and official purposes',
                'category' => 'Financial Documents',
                'template_content' => 'SALARY CERTIFICATE

To Whom It May Concern,

This is to certify that {{employee_name}} is employed with {{company_name}} as {{designation}} in the {{department}} department.

Employment Details:
- Employee ID: {{employee_id}}
- Date of Joining: {{joining_date}}
- Employment Status: {{employment_status}}

Salary Details:
- Basic Salary: {{basic_salary}} per month
- Allowances: {{allowances}} per month
- Gross Salary: {{gross_salary}} per month
- Annual CTC: {{annual_ctc}}

This certificate is issued for {{purpose}} as requested by the employee.

Issued on: {{issue_date}}

{{hr_manager_name}}
Human Resources Manager
{{company_name}}
{{company_seal}}',
                'placeholders' => ['employee_name', 'company_name', 'designation', 'department', 'employee_id', 'joining_date', 'employment_status', 'basic_salary', 'allowances', 'gross_salary', 'annual_ctc', 'purpose', 'issue_date', 'hr_manager_name', 'company_seal'],
                'default_values' => ['employment_status' => 'Permanent', 'purpose' => 'Official purposes'],
                'is_default' => false,
                'file_format' => 'pdf',
                'status' => 'active'
            ],
            [
                'name' => 'Training Certificate',
                'description' => 'Template for training completion certificates and skill development programs',
                'category' => 'Training Certificates',
                'template_content' => 'CERTIFICATE OF COMPLETION

This is to certify that

{{participant_name}}

has successfully completed the training program

"{{training_title}}"

Training Details:
- Duration: {{training_duration}}
- Training Period: {{start_date}} to {{end_date}}
- Training Mode: {{training_mode}}
- Trainer: {{trainer_name}}
- Training Hours: {{total_hours}} hours

Topics Covered:
{{training_topics}}

Performance:
- Assessment Score: {{assessment_score}}%
- Grade: {{grade}}
- Attendance: {{attendance_percentage}}%

This certificate is awarded in recognition of successful completion of the training program.

Issued on: {{issue_date}}

{{trainer_signature}}
{{trainer_name}}
Training Coordinator

{{hr_manager_signature}}
{{hr_manager_name}}
Human Resources Manager',
                'placeholders' => ['participant_name', 'training_title', 'training_duration', 'start_date', 'end_date', 'training_mode', 'trainer_name', 'total_hours', 'training_topics', 'assessment_score', 'grade', 'attendance_percentage', 'issue_date', 'trainer_signature', 'trainer_name', 'hr_manager_signature', 'hr_manager_name'],
                'default_values' => ['training_mode' => 'Online', 'grade' => 'Pass'],
                'is_default' => false,
                'file_format' => 'pdf',
                'status' => 'active'
            ],
            [
                'name' => 'Performance Appraisal Form',
                'description' => 'Standard template for employee performance evaluation and appraisal documentation',
                'category' => 'Performance Records',
                'template_content' => 'PERFORMANCE APPRAISAL FORM

Employee Information:
- Name: {{employee_name}}
- Employee ID: {{employee_id}}
- Designation: {{designation}}
- Department: {{department}}
- Appraisal Period: {{appraisal_period}}

Performance Ratings:
1. Job Knowledge: {{job_knowledge_rating}}/5
2. Quality of Work: {{quality_rating}}/5
3. Productivity: {{productivity_rating}}/5
4. Communication: {{communication_rating}}/5
5. Teamwork: {{teamwork_rating}}/5
6. Leadership: {{leadership_rating}}/5

Overall Performance: {{overall_rating}}/5

Key Achievements:
{{key_achievements}}

Areas for Improvement:
{{improvement_areas}}

Goals for Next Period:
{{future_goals}}

Training Recommendations:
{{training_recommendations}}

Employee Comments:
{{employee_comments}}

Appraiser Comments:
{{appraiser_comments}}

{{employee_signature}}
Employee Signature

{{appraiser_signature}}
{{appraiser_name}}
Reporting Manager

Date: {{appraisal_date}}',
                'placeholders' => ['employee_name', 'employee_id', 'designation', 'department', 'appraisal_period', 'job_knowledge_rating', 'quality_rating', 'productivity_rating', 'communication_rating', 'teamwork_rating', 'leadership_rating', 'overall_rating', 'key_achievements', 'improvement_areas', 'future_goals', 'training_recommendations', 'employee_comments', 'appraiser_comments', 'employee_signature', 'appraiser_signature', 'appraiser_name', 'appraisal_date'],
                'default_values' => ['overall_rating' => '3'],
                'is_default' => false,
                'file_format' => 'pdf',
                'status' => 'active'
            ],
            [
                'name' => 'Medical Fitness Certificate',
                'description' => 'Template for medical fitness certificates required for employment and health records',
                'category' => 'Medical Records',
                'template_content' => 'MEDICAL FITNESS CERTIFICATE

Patient Information:
- Name: {{patient_name}}
- Age: {{age}} years
- Gender: {{gender}}
- Employee ID: {{employee_id}}
- Department: {{department}}

Medical Examination Details:
- Examination Date: {{examination_date}}
- Examining Doctor: {{doctor_name}}
- Medical Registration No: {{doctor_registration}}

Examination Results:
- Height: {{height}} cm
- Weight: {{weight}} kg
- Blood Pressure: {{blood_pressure}}
- Pulse Rate: {{pulse_rate}} bpm
- Vision: {{vision_status}}
- Hearing: {{hearing_status}}

Medical History:
{{medical_history}}

Current Health Status:
{{health_status}}

Fitness Declaration:
Based on the medical examination, {{patient_name}} is declared MEDICALLY FIT for employment in the position of {{job_position}}.

Valid Until: {{validity_date}}

{{doctor_signature}}
Dr. {{doctor_name}}
{{doctor_qualification}}
Medical Officer',
                'placeholders' => ['patient_name', 'age', 'gender', 'employee_id', 'department', 'examination_date', 'doctor_name', 'doctor_registration', 'height', 'weight', 'blood_pressure', 'pulse_rate', 'vision_status', 'hearing_status', 'medical_history', 'health_status', 'job_position', 'validity_date', 'doctor_signature', 'doctor_qualification'],
                'default_values' => ['vision_status' => 'Normal', 'hearing_status' => 'Normal', 'health_status' => 'Good'],
                'is_default' => false,
                'file_format' => 'pdf',
                'status' => 'active'
            ]
        ];

        foreach ($companies as $company) {
            // Get document categories for this company
            $documentCategories = DocumentCategory::where('created_by', $company->id)->get();

            if ($documentCategories->isEmpty()) {
                $this->command->warn('No document categories found for company: ' . $company->name . '. Please run DocumentCategorySeeder first.');
                continue;
            }

            foreach ($documentTemplates as $templateData) {
                // Find matching category
                $category = $documentCategories->where('name', $templateData['category'])->first();
                if (!$category) $category = $documentCategories->first();

                // Check if template already exists for this company
                if (DocumentTemplate::where('name', $templateData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    DocumentTemplate::create([
                        'name' => $templateData['name'],
                        'description' => $templateData['description'],
                        'category_id' => $category->id,
                        'template_content' => $templateData['template_content'],
                        'placeholders' => $templateData['placeholders'],
                        'default_values' => $templateData['default_values'],
                        'is_default' => $templateData['is_default'],
                        'file_format' => $templateData['file_format'],
                        'status' => $templateData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create document template: ' . $templateData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('DocumentTemplate seeder completed successfully!');
    }
}
