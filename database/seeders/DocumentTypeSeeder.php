<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
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
        
        // Document types with realistic data
        $documentTypes = [
            ['name' => 'Identity Proof', 'is_required' => true, 'description' => 'Government issued identity document such as passport, driver license, or national ID'],
            ['name' => 'Address Proof', 'is_required' => true, 'description' => 'Document verifying current residential address like utility bill or bank statement'],
            ['name' => 'Educational Certificates', 'is_required' => true, 'description' => 'Academic certificates, degrees, diplomas, and transcripts'],
            ['name' => 'Experience Letters', 'is_required' => false, 'description' => 'Previous employment experience and recommendation letters from former employers'],
            ['name' => 'Medical Certificate', 'is_required' => false, 'description' => 'Health fitness certificate and medical examination reports'],
            ['name' => 'Bank Account Details', 'is_required' => true, 'description' => 'Bank account information for salary processing and financial transactions'],
            ['name' => 'Tax Documents', 'is_required' => false, 'description' => 'Tax identification numbers, PAN card, and tax-related documentation'],
            ['name' => 'Emergency Contact', 'is_required' => true, 'description' => 'Emergency contact information and relationship details'],
            ['name' => 'Passport Size Photo', 'is_required' => true, 'description' => 'Recent passport size photographs for employee identification'],
            ['name' => 'Offer Letter', 'is_required' => false, 'description' => 'Job offer letter and employment terms documentation'],
            ['name' => 'Non-Disclosure Agreement', 'is_required' => false, 'description' => 'Confidentiality and non-disclosure agreement signed by employee']
        ];
        
        foreach ($companies as $company) {
            // Create 8-10 document types for each company
            $documentCount = rand(4,6);
            
            for ($i = 0; $i < $documentCount; $i++) {                
                $documentType = $documentTypes[$i];
                
                // Check if document type already exists for this company
                if (DocumentType::where('name', $documentType['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    DocumentType::create([
                        'name' => $documentType['name'],
                        'is_required' => $documentType['is_required'],
                        'description' => $documentType['description'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create document type: ' . $documentType['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('DocumentType seeder completed successfully!');
    }
}