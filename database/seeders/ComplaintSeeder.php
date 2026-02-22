<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ComplaintSeeder extends Seeder
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

        // Complaint types and their subjects/descriptions
        $complaintData = [
            'Harassment' => [
                'subjects' => ['Verbal Harassment', 'Sexual Harassment', 'Bullying Behavior', 'Intimidation'],
                'descriptions' => [
                    'Verbal Harassment' => 'Employee has been subjected to inappropriate verbal comments, offensive language, and unprofessional behavior that creates a hostile work environment.',
                    'Sexual Harassment' => 'Employee experienced unwelcome sexual advances, inappropriate comments, or conduct of a sexual nature that affects work performance and comfort.',
                    'Bullying Behavior' => 'Employee is being bullied through aggressive behavior, public humiliation, and persistent negative treatment by colleagues or supervisors.',
                    'Intimidation' => 'Employee feels intimidated and threatened by aggressive behavior, verbal abuse, and hostile actions from other team members.'
                ]
            ],
            'Discrimination' => [
                'subjects' => ['Age Discrimination', 'Gender Discrimination', 'Racial Discrimination', 'Religious Discrimination'],
                'descriptions' => [
                    'Age Discrimination' => 'Employee believes they are being treated unfairly due to their age, affecting career opportunities and workplace treatment.',
                    'Gender Discrimination' => 'Employee experiences unequal treatment, opportunities, or compensation based on gender identity or expression.',
                    'Racial Discrimination' => 'Employee faces discriminatory treatment, comments, or actions based on race, ethnicity, or cultural background.',
                    'Religious Discrimination' => 'Employee encounters discrimination related to religious beliefs, practices, or observances in the workplace.'
                ]
            ],
            'Workplace Conditions' => [
                'subjects' => ['Unsafe Working Conditions', 'Poor Hygiene Standards', 'Inadequate Equipment', 'Excessive Workload'],
                'descriptions' => [
                    'Unsafe Working Conditions' => 'Workplace poses safety risks due to inadequate safety measures, faulty equipment, or hazardous environmental conditions.',
                    'Poor Hygiene Standards' => 'Workplace maintains poor cleanliness and hygiene standards that affect employee health and comfort.',
                    'Inadequate Equipment' => 'Employee lacks proper tools, equipment, or resources necessary to perform job duties effectively and safely.',
                    'Excessive Workload' => 'Employee is consistently assigned unreasonable workload that affects work-life balance and job performance.'
                ]
            ],
            'Policy Violation' => [
                'subjects' => ['Attendance Policy Violation', 'Code of Conduct Breach', 'Conflict of Interest', 'Misuse of Resources'],
                'descriptions' => [
                    'Attendance Policy Violation' => 'Colleague consistently violates attendance policies without consequences, creating unfair work distribution among team members.',
                    'Code of Conduct Breach' => 'Employee witnessed or experienced behavior that violates company code of conduct and professional standards.',
                    'Conflict of Interest' => 'Supervisor or colleague has undisclosed conflicts of interest that affect decision-making and fairness in the workplace.',
                    'Misuse of Resources' => 'Company resources, equipment, or facilities are being misused for personal purposes by employees or management.'
                ]
            ],
            'Management Issues' => [
                'subjects' => ['Unfair Treatment', 'Lack of Support', 'Poor Communication', 'Favoritism'],
                'descriptions' => [
                    'Unfair Treatment' => 'Employee receives unfair treatment from management regarding assignments, evaluations, or opportunities compared to colleagues.',
                    'Lack of Support' => 'Management fails to provide necessary support, guidance, or resources needed for employee success and development.',
                    'Poor Communication' => 'Management demonstrates poor communication practices, leading to confusion, misunderstandings, and workplace inefficiency.',
                    'Favoritism' => 'Management shows clear favoritism towards certain employees, affecting fair treatment and equal opportunities for all staff.'
                ]
            ]
        ];

        foreach ($companies as $company) {
            // Get employees for this company
            $employees = User::where('type', 'employee')
                ->where('created_by', $company->id)
                ->get();

            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }

            // Get HR personnel for assignment
            $hrPersonnel = User::where('type', 'hr')
                ->where('created_by', $company->id)
                ->get();

            // Create 4-8 complaints for this company
            $complaintCount = rand(4, 8);

            for ($i = 0; $i < $complaintCount; $i++) {
                $complainant = $employees->random();
                $complaintType = $faker->randomElement(array_keys($complaintData));
                $typeData = $complaintData[$complaintType];
                $subject = $faker->randomElement($typeData['subjects']);
                $description = $typeData['descriptions'][$subject];

                // 70% chance of complaint against another employee
                $otherEmployees = $employees->where('id', '!=', $complainant->id);
                $againstEmployee = $faker->boolean(70) && $otherEmployees->isNotEmpty() ? $otherEmployees->random() : null;

                $complaintDate = $faker->dateTimeBetween('-1 year', 'now');
                $status = $faker->randomElement(['submitted', 'under investigation', 'resolved', 'dismissed']);
                $isAnonymous = $faker->boolean(20); // 20% anonymous complaints

                $assignedTo = $hrPersonnel->isNotEmpty() ? $hrPersonnel->random() : null;
                $resolutionDeadline = $status !== 'resolved' && $status !== 'dismissed' ?
                    $faker->dateTimeBetween('now', '+30 days') : null;

                // Resolution data for resolved/dismissed complaints
                $resolutionDate = in_array($status, ['resolved', 'dismissed']) ?
                    $faker->dateTimeBetween($complaintDate, 'now') : null;

                $resolutionAction = $resolutionDate ? $this->getResolutionAction($status, $complaintType) : null;

                $followUpDate = $resolutionDate && $faker->boolean(60) ?
                    $faker->dateTimeBetween($resolutionDate, '+30 days') : null;

                try {
                    Complaint::create([
                        'employee_id' => $complainant->id,
                        'against_employee_id' => $againstEmployee?->id,
                        'complaint_type' => $complaintType,
                        'subject' => $subject,
                        'complaint_date' => $complaintDate->format('Y-m-d'),
                        'description' => $description,
                        'status' => $status,
                        'documents' => randomImage(),
                        'is_anonymous' => $isAnonymous,
                        'assigned_to' => $assignedTo?->id,
                        'resolution_deadline' => $resolutionDeadline?->format('Y-m-d'),
                        'investigation_notes' => $status !== 'submitted' ? $faker->paragraph(2) : null,
                        'resolution_action' => $resolutionAction,
                        'resolution_date' => $resolutionDate?->format('Y-m-d'),
                        'follow_up_action' => $followUpDate ? $faker->sentence(8) : null,
                        'follow_up_date' => $followUpDate?->format('Y-m-d'),
                        'feedback' => $resolutionDate && $faker->boolean(40) ? $faker->sentence(10) : null,
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create complaint for employee: ' . $complainant->name . ' in company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('Complaint seeder completed successfully!');
    }

    /**
     * Get resolution action based on status and complaint type
     */
    private function getResolutionAction($status, $complaintType)
    {
        if ($status === 'resolved') {
            $resolutionActions = [
                'Harassment' => 'Conducted thorough investigation, provided counseling to involved parties, and implemented additional training on workplace behavior.',
                'Discrimination' => 'Investigated the matter, found evidence of discriminatory behavior, provided sensitivity training, and updated company policies.',
                'Workplace Conditions' => 'Assessed workplace conditions, implemented safety improvements, and provided necessary equipment and resources.',
                'Policy Violation' => 'Reviewed policy violations, took appropriate disciplinary action, and reinforced policy awareness through training.',
                'Management Issues' => 'Addressed management concerns through coaching, improved communication protocols, and established regular feedback mechanisms.'
            ];

            return $resolutionActions[$complaintType] ?? 'Matter investigated and resolved through appropriate corrective measures and policy implementation.';
        } else {
            return 'After thorough investigation, the complaint was found to be unsubstantiated and dismissed with proper documentation.';
        }
    }
}
