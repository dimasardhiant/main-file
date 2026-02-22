<?php

namespace Database\Seeders;

use App\Models\HrDocument;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class HrDocumentSeeder extends Seeder
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
        
        // Fixed HR documents for consistent data
        $hrDocuments = [
            [
                'title' => 'Employee Handbook 2024',
                'description' => 'Comprehensive employee handbook covering company policies, procedures, and guidelines for all employees',
                'category' => 'Compliance Documents',
                'file_name' => 'employee_handbook_2024.png',
                'file_path' => randomImage(),
                'file_type' => 'image/png',
                'file_size' => 2048576,
                'version' => '2.1',
                'status' => 'Published',
                'effective_date' => '2024-01-01',
                'expiry_date' => '2024-12-31',
                'requires_acknowledgment' => true,
                'download_count' => 45
            ],
            [
                'title' => 'Code of Conduct Policy',
                'description' => 'Company code of conduct and ethical guidelines for professional behavior and workplace standards',
                'category' => 'Compliance Documents',
                'file_name' => 'code_of_conduct_policy.png',
                'file_path' => randomImage(),
                'file_type' => 'image/png',
                'file_size' => 1024768,
                'version' => '1.5',
                'status' => 'Published',
                'effective_date' => '2024-01-15',
                'expiry_date' => null,
                'requires_acknowledgment' => true,
                'download_count' => 38
            ],
            [
                'title' => 'Leave Policy Document',
                'description' => 'Detailed leave policy including annual leave, sick leave, maternity/paternity leave, and special leave provisions',
                'category' => 'Employment Documents',
                'file_name' => 'leave_policy_2024.png',
                'file_path' => randomImage(),
                'file_type' => 'image/png',
                'file_size' => 768432,
                'version' => '1.3',
                'status' => 'Published',
                'effective_date' => '2024-02-01',
                'expiry_date' => '2024-12-31',
                'requires_acknowledgment' => true,
                'download_count' => 52
            ],
            [
                'title' => 'Health and Safety Guidelines',
                'description' => 'Workplace health and safety procedures, emergency protocols, and safety compliance requirements',
                'category' => 'Medical Records',
                'file_name' => 'health_safety_guidelines.png',
                'file_path' => randomImage(),
                'file_type' => 'image/png',
                'file_size' => 1536789,
                'version' => '1.8',
                'status' => 'Published',
                'effective_date' => '2024-01-10',
                'expiry_date' => null,
                'requires_acknowledgment' => true,
                'download_count' => 29
            ],
            [
                'title' => 'Performance Evaluation Form',
                'description' => 'Standard performance evaluation form template for annual and quarterly employee assessments',
                'category' => 'Performance Records',
                'file_name' => 'performance_evaluation_form.docx',
                'file_path' => randomImage(),
                'file_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'file_size' => 245760,
                'version' => '2.0',
                'status' => 'Published',
                'effective_date' => '2024-03-01',
                'expiry_date' => null,
                'requires_acknowledgment' => false,
                'download_count' => 67
            ],
            [
                'title' => 'Training and Development Policy',
                'description' => 'Company policy on employee training, skill development programs, and professional growth opportunities',
                'category' => 'Training Certificates',
                'file_name' => 'training_development_policy.png',
                'file_path' => randomImage(),
                'file_type' => 'image/png',
                'file_size' => 892345,
                'version' => '1.2',
                'status' => 'Published',
                'effective_date' => '2024-02-15',
                'expiry_date' => '2025-02-14',
                'requires_acknowledgment' => false,
                'download_count' => 34
            ],
            [
                'title' => 'Remote Work Policy',
                'description' => 'Guidelines and procedures for remote work arrangements, equipment, and productivity expectations',
                'category' => 'Employment Documents',
                'file_name' => 'remote_work_policy.png',
                'file_path' => randomImage(),
                'file_type' => 'image/png',
                'file_size' => 654321,
                'version' => '1.4',
                'status' => 'Published',
                'effective_date' => '2024-01-20',
                'expiry_date' => null,
                'requires_acknowledgment' => true,
                'download_count' => 78
            ],
            [
                'title' => 'Expense Reimbursement Policy',
                'description' => 'Policy and procedures for business expense reimbursements, travel expenses, and claim processes',
                'category' => 'Financial Documents',
                'file_name' => 'expense_reimbursement_policy.png',
                'file_path' => randomImage(),
                'file_type' => 'image/png',
                'file_size' => 432198,
                'version' => '1.6',
                'status' => 'Published',
                'effective_date' => '2024-01-05',
                'expiry_date' => null,
                'requires_acknowledgment' => false,
                'download_count' => 41
            ],
            [
                'title' => 'Data Privacy and Security Policy',
                'description' => 'Company data protection policy, privacy guidelines, and information security protocols',
                'category' => 'Legal Documents',
                'file_name' => 'data_privacy_security_policy.png',
                'file_path' => randomImage(),
                'file_type' => 'image/png',
                'file_size' => 1234567,
                'version' => '2.3',
                'status' => 'Published',
                'effective_date' => '2024-01-01',
                'expiry_date' => null,
                'requires_acknowledgment' => true,
                'download_count' => 56
            ],
            [
                'title' => 'Emergency Contact Form',
                'description' => 'Standard form for employees to provide emergency contact information and medical details',
                'category' => 'Personal Documents',
                'file_name' => 'emergency_contact_form.png',
                'file_path' => randomImage(),
                'file_type' => 'image/png',
                'file_size' => 187654,
                'version' => '1.1',
                'status' => 'Published',
                'effective_date' => '2024-01-01',
                'expiry_date' => null,
                'requires_acknowledgment' => false,
                'download_count' => 89
            ]
        ];
        
        foreach ($companies as $company) {
            // Get document categories for this company
            $documentCategories = DocumentCategory::where('created_by', $company->id)->get();
            
            if ($documentCategories->isEmpty()) {
                $this->command->warn('No document categories found for company: ' . $company->name . '. Please run DocumentCategorySeeder first.');
                continue;
            }
            
            // Get HR users for uploading and approval
            $hrUsers = User::whereIn('type', ['hr', 'manager'])
                         ->where('created_by', $company->id)
                         ->get();
            
            if ($hrUsers->isEmpty()) {
                $this->command->warn('No HR users found for company: ' . $company->name);
                continue;
            }
            
            foreach ($hrDocuments as $index => $documentData) {
                // Find matching category
                $category = $documentCategories->where('name', $documentData['category'])->first();
                if (!$category) $category = $documentCategories->first();
                
                // Check if document already exists for this company
                if (HrDocument::where('title', $documentData['title'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                // Select uploader and approver
                $uploader = $hrUsers->first();
                $approver = $hrUsers->count() > 1 ? $hrUsers->skip(1)->first() : $hrUsers->first();
                
                try {
                    HrDocument::create([
                        'title' => $documentData['title'],
                        'description' => $documentData['description'],
                        'category_id' => $category->id,
                        'file_name' => $documentData['file_name'],
                        'file_path' => $documentData['file_path'],
                        'file_type' => $documentData['file_type'],
                        'file_size' => $documentData['file_size'],
                        'version' => $documentData['version'],
                        'status' => $documentData['status'],
                        'effective_date' => $documentData['effective_date'],
                        'expiry_date' => $documentData['expiry_date'],
                        'requires_acknowledgment' => $documentData['requires_acknowledgment'],
                        'download_count' => $documentData['download_count'],
                        'uploaded_by' => $company->id,
                        'approved_by' => $documentData['status'] === 'Published' ? $approver->id : null,
                        'approved_at' => $documentData['status'] === 'Published' ? now() : null,
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create HR document: ' . $documentData['title'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('HrDocument seeder completed successfully!');
    }
}