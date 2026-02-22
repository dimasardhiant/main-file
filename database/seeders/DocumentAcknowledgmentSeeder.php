<?php

namespace Database\Seeders;

use App\Models\DocumentAcknowledgment;
use App\Models\HrDocument;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentAcknowledgmentSeeder extends Seeder
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

        // Fixed acknowledgment statuses for consistent data
        $acknowledgmentStatuses = ['Acknowledged', 'Acknowledged', 'Pending', 'Acknowledged', 'Overdue', 'Acknowledged', 'Pending', 'Acknowledged'];

        foreach ($companies as $company) {
            // Get HR documents that require acknowledgment for this company
            $hrDocuments = HrDocument::where('created_by', $company->id)
                ->where('requires_acknowledgment', true)
                ->where('status', 'Published')
                ->get();

            if ($hrDocuments->isEmpty()) {
                $this->command->warn('No HR documents requiring acknowledgment found for company: ' . $company->name . '. Please run HrDocumentSeeder first.');
                continue;
            }

            // Get employees for this company
            $employees = User::where('type', 'employee')
                ->where('created_by', $company->id)
                ->get();

            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }

            // Get HR users for assignment
            $hrUsers = User::whereIn('type', ['hr', 'manager'])
                ->where('created_by', $company->id)
                ->get();

            if ($hrUsers->isEmpty()) {
                $this->command->warn('No HR users found for company: ' . $company->name);
                continue;
            }

            // Create acknowledgments for each document and employee combination
            foreach ($hrDocuments as $docIndex => $document) {
                // Assign to first 8 employees
                $selectedEmployees = $employees->take(8);

                foreach ($selectedEmployees as $empIndex => $employee) {
                    // Check if acknowledgment already exists
                    if (DocumentAcknowledgment::where('document_id', $document->id)
                        ->where('user_id', $employee->id)
                        ->exists()
                    ) {
                        continue;
                    }

                    $status = $acknowledgmentStatuses[$empIndex % 8];
                    $assignedBy = $hrUsers->first();

                    $assignedAt = date('Y-m-d H:i:s', strtotime('-' . ($docIndex + $empIndex + 1) . ' days'));
                    $dueDate = date('Y-m-d', strtotime($assignedAt . ' +7 days'));

                    $acknowledgedAt = null;
                    $acknowledgmentNote = null;
                    $ipAddress = null;
                    $userAgent = null;

                    if ($status === 'Acknowledged') {
                        $acknowledgedAt = date('Y-m-d H:i:s', strtotime($assignedAt . ' +' . ($empIndex + 1) . ' days'));
                        $acknowledgmentNote = 'I have read and understood the document contents and agree to comply with the policies outlined.';
                        $ipAddress = '192.168.1.' . (100 + $empIndex);
                        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
                    } elseif ($status === 'Overdue') {
                        // Due date has passed but not acknowledged
                        $dueDate = date('Y-m-d', strtotime('-2 days'));
                    }

                    try {
                        DocumentAcknowledgment::create([
                            'document_id' => $document->id,
                            'user_id' => $employee->id,
                            'status' => $status,
                            'acknowledged_at' => $acknowledgedAt,
                            'due_date' => $dueDate,
                            'acknowledgment_note' => $acknowledgmentNote,
                            'ip_address' => $ipAddress,
                            'user_agent' => $userAgent,
                            'assigned_by' => $assignedBy->id,
                            'assigned_at' => $assignedAt,
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create document acknowledgment for document: ' . $document->title . ' and employee: ' . $employee->name . ' in company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('DocumentAcknowledgment seeder completed successfully!');
    }
}
