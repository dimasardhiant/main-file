<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentCategorySeeder extends Seeder
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
        
        // Fixed document categories for consistent data
        $documentCategories = [
            [
                'name' => 'Identity Documents',
                'description' => 'Personal identification documents including passport, national ID, driving license',
                'color' => '#3B82F6',
                'icon' => 'IdCard',
                'sort_order' => 1,
                'is_mandatory' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Educational Certificates',
                'description' => 'Academic qualifications, degrees, diplomas, and professional certifications',
                'color' => '#10b77f',
                'icon' => 'GraduationCap',
                'sort_order' => 2,
                'is_mandatory' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Employment Documents',
                'description' => 'Employment contracts, offer letters, job descriptions, and work agreements',
                'color' => '#F59E0B',
                'icon' => 'Briefcase',
                'sort_order' => 3,
                'is_mandatory' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Financial Documents',
                'description' => 'Bank statements, salary slips, tax documents, and financial records',
                'color' => '#EF4444',
                'icon' => 'DollarSign',
                'sort_order' => 4,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Medical Records',
                'description' => 'Health certificates, medical reports, fitness certificates, and insurance documents',
                'color' => '#8B5CF6',
                'icon' => 'Heart',
                'sort_order' => 5,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Legal Documents',
                'description' => 'Legal agreements, compliance certificates, background verification reports',
                'color' => '#6B7280',
                'icon' => 'Scale',
                'sort_order' => 6,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Training Certificates',
                'description' => 'Professional training certificates, skill development programs, workshop completions',
                'color' => '#06B6D4',
                'icon' => 'Award',
                'sort_order' => 7,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Performance Records',
                'description' => 'Performance appraisals, feedback reports, achievement certificates, and reviews',
                'color' => '#84CC16',
                'icon' => 'TrendingUp',
                'sort_order' => 8,
                'is_mandatory' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Personal Documents',
                'description' => 'Personal information, emergency contacts, family details, and personal references',
                'color' => '#F97316',
                'icon' => 'User',
                'sort_order' => 9,
                'is_mandatory' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Compliance Documents',
                'description' => 'Regulatory compliance documents, policy acknowledgments, and statutory requirements',
                'color' => '#DC2626',
                'icon' => 'Shield',
                'sort_order' => 10,
                'is_mandatory' => true,
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($documentCategories as $categoryData) {
                // Check if document category already exists for this company
                if (DocumentCategory::where('name', $categoryData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    DocumentCategory::create([
                        'name' => $categoryData['name'],
                        'description' => $categoryData['description'],
                        'color' => $categoryData['color'],
                        'icon' => $categoryData['icon'],
                        'sort_order' => $categoryData['sort_order'],
                        'is_mandatory' => $categoryData['is_mandatory'],
                        'status' => $categoryData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create document category: ' . $categoryData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('DocumentCategory seeder completed successfully!');
    }
}