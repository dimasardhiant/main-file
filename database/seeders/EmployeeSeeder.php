<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class EmployeeSeeder extends Seeder
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

        foreach ($companies as $company) {
            // Get all employee users for this company
            $employeeUsers = User::where('type', 'employee')
                ->where('created_by', $company->id)
                ->get();

            if ($employeeUsers->isEmpty()) {
                $this->command->warn('No employee users found for company: ' . $company->name . '. Please run DefaultCompanyUserSeeder first.');
                continue;
            }

            // Get company branches, departments, and designations
            $branches = Branch::where('created_by', $company->id)->get();
            $departments = Department::where('created_by', $company->id)->get();
            $designations = Designation::where('created_by', $company->id)->get();

            foreach ($employeeUsers as $employeeUser) {
                // Check if employee record already exists
                if (Employee::where('user_id', $employeeUser->id)->exists()) {
                    continue;
                }

                // Generate unique employee ID
                do {
                    $employeeId = 'EMP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                } while (Employee::where('employee_id', $employeeId)->exists());

                // Select random branch, department, and designation
                $branch = $branches->isNotEmpty() ? $branches->random() : null;
                $department = $departments->isNotEmpty() ? $departments->random() : null;
                $designation = $designations->isNotEmpty() ? $designations->random() : null;

                try {
                    Employee::create([
                        // Basic Information
                        'employee_id' => $employeeId,
                        'phone' => $faker->phoneNumber,
                        'date_of_birth' => $faker->dateTimeBetween('-60 years', '-22 years')->format('Y-m-d'),
                        'gender' => $faker->randomElement(['male', 'female']),

                        // Employment Details
                        'branch_id' => $branch?->id,
                        'department_id' => $department?->id,
                        'designation_id' => $designation?->id,
                        'shift_id' => null, // Will be set when shift seeder runs
                        'attendance_policy_id' => null, // Will be set when attendance policy seeder runs
                        'date_of_joining' => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                        'employment_type' => $faker->randomElement(['Full-time', 'Part-time', 'Contract', 'Temporary']),

                        // Contact Information
                        'address_line_1' => $faker->streetAddress,
                        'address_line_2' => $faker->optional()->secondaryAddress,
                        'city' => $faker->city,
                        'state' => $faker->state,
                        'country' => $faker->country,
                        'postal_code' => $faker->postcode,
                        'emergency_contact_name' => $faker->name,
                        'emergency_contact_relationship' => $faker->randomElement(['Father', 'Mother', 'Spouse', 'Brother', 'Sister', 'Friend']),
                        'emergency_contact_number' => $faker->phoneNumber,

                        // Banking Information
                        'bank_name' => $faker->randomElement(['State Bank', 'HDFC Bank', 'ICICI Bank', 'Axis Bank', 'Kotak Bank']),
                        'account_holder_name' => $employeeUser->name,
                        'account_number' => $faker->numerify('##########'),
                        'bank_identifier_code' => $faker->regexify('[A-Z]{4}[0-9]{7}'),
                        'bank_branch' => $faker->city . ' Branch',
                        'tax_payer_id' => $faker->regexify('[A-Z]{5}[0-9]{4}[A-Z]{1}'),

                        // System fields
                        'user_id' => $employeeUser->id,
                        'created_by' => $company->id,
                    ]);

                    // Create employee documents
                    $this->createEmployeeDocuments($employeeUser, $company, $faker);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create employee record for user: ' . $employeeUser->name . ' in company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('Employee seeder completed successfully!');
    }

    /**
     * Create employee documents
     */
    private function createEmployeeDocuments($employeeUser, $company, $faker)
    {
        // Get document types for this company
        $documentTypes = DocumentType::where('created_by', $company->id)->get();

        if ($documentTypes->isEmpty()) {
            return;
        }

        // Create 3-5 random documents for each employee
        $documentCount = rand(3, min(5, $documentTypes->count()));
        $selectedDocumentTypes = $documentTypes->random($documentCount);

        foreach ($selectedDocumentTypes as $documentType) {
            try {
                EmployeeDocument::create([
                    'employee_id' => $employeeUser->id,
                    'document_type_id' => $documentType->id,
                    'file_path' => randomImage(),
                    'expiry_date' => $documentType->name === 'Identity Proof' || $documentType->name === 'Medical Certificate'
                        ? $faker->dateTimeBetween('now', '+5 years')->format('Y-m-d')
                        : null,
                    'verification_status' => $faker->randomElement(['pending', 'verified', 'rejected']),
                    'notes' => $faker->optional(0.3)->sentence(),
                    'created_by' => $company->id,
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
