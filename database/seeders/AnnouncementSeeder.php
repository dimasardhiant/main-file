<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnnouncementSeeder extends Seeder
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

        $currentYear = date('Y');

        // Fixed announcements for consistent data
        $announcements = [
            [
                'title' => 'Welcome to New Financial Year ' . $currentYear,
                'category' => 'Company News',
                'description' => 'Important updates and goals for the new financial year',
                'content' => 'Dear Team, As we begin the new financial year, we are excited to share our strategic goals and initiatives. This year, we focus on innovation, employee development, and sustainable growth. We have planned several training programs, team building activities, and performance improvement initiatives. Your dedication and hard work are the foundation of our success. Let us work together to achieve new milestones and create a positive impact in our industry.',
                'start_date' => $currentYear . '-01-01',
                'end_date' => $currentYear . '-01-31',
                'is_featured' => true,
                'is_high_priority' => true,
                'is_company_wide' => true
            ],
            [
                'title' => 'Updated Employee Handbook and Policies',
                'category' => 'Policy Updates',
                'description' => 'Important changes to company policies and procedures',
                'content' => 'We have updated our Employee Handbook to reflect current best practices and regulatory requirements. Key changes include updated leave policies, remote work guidelines, performance evaluation criteria, and code of conduct. All employees are required to review the updated handbook and acknowledge receipt. The new policies are effective immediately. Please contact HR department for any clarifications or questions regarding the policy changes.',
                'start_date' => $currentYear . '-02-01',
                'end_date' => $currentYear . '-02-28',
                'is_featured' => true,
                'is_high_priority' => true,
                'is_company_wide' => true
            ],
            [
                'title' => 'Annual Performance Review Process',
                'category' => 'HR Updates',
                'description' => 'Guidelines for the annual performance evaluation cycle',
                'content' => 'The annual performance review process will commence next month. This comprehensive evaluation includes self-assessment, peer feedback, and supervisor evaluation. Performance reviews are crucial for career development, goal setting, and compensation decisions. Please prepare your self-assessment forms and gather relevant project documentation. HR will schedule individual meetings with each employee. We encourage open communication and constructive feedback during this process.',
                'start_date' => $currentYear . '-03-01',
                'end_date' => $currentYear . '-04-30',
                'is_featured' => false,
                'is_high_priority' => true,
                'is_company_wide' => true
            ],
            [
                'title' => 'New Employee Benefits Program Launch',
                'category' => 'Benefits',
                'description' => 'Enhanced benefits package for all employees',
                'content' => 'We are pleased to announce the launch of our enhanced employee benefits program. New benefits include expanded health insurance coverage, flexible spending accounts, professional development allowances, and wellness programs. The program also includes mental health support, childcare assistance, and transportation allowances. Enrollment is mandatory for all eligible employees. Detailed information and enrollment forms are available on the employee portal.',
                'start_date' => $currentYear . '-04-01',
                'end_date' => $currentYear . '-05-31',
                'is_featured' => true,
                'is_high_priority' => false,
                'is_company_wide' => true
            ],
            [
                'title' => 'IT Department System Maintenance',
                'category' => 'IT Updates',
                'description' => 'Scheduled maintenance for IT department systems',
                'content' => 'The IT department will undergo scheduled system maintenance to upgrade servers and network infrastructure. This maintenance is specific to IT department operations and will not affect other departments. IT staff should prepare for temporary system downtime and coordinate with the maintenance team. All IT projects and deployments will be paused during this period. Please ensure all critical data is backed up before the maintenance window.',
                'start_date' => $currentYear . '-05-15',
                'end_date' => $currentYear . '-05-16',
                'is_featured' => false,
                'is_high_priority' => false,
                'is_company_wide' => false
            ],
            [
                'title' => 'Sales Team Quarterly Meeting',
                'category' => 'Events',
                'description' => 'Important quarterly review for sales department',
                'content' => 'The sales department will conduct its quarterly review meeting to discuss performance metrics, target achievements, and upcoming strategies. This meeting is mandatory for all sales team members. We will review individual and team performance, discuss market trends, and plan for the next quarter. Sales managers will present departmental goals and new client acquisition strategies.',
                'start_date' => $currentYear . '-06-01',
                'end_date' => $currentYear . '-06-15',
                'is_featured' => false,
                'is_high_priority' => true,
                'is_company_wide' => false
            ],
            [
                'title' => 'HR Department Policy Training',
                'category' => 'Training',
                'description' => 'Specialized training for HR department staff',
                'content' => 'The HR department will conduct specialized training on new employment laws, compliance requirements, and updated HR policies. This training is mandatory for all HR staff and covers recent regulatory changes, best practices in employee relations, and new HR technology implementations. External trainers will provide certification upon completion.',
                'start_date' => $currentYear . '-07-01',
                'end_date' => $currentYear . '-12-31',
                'is_featured' => true,
                'is_high_priority' => false,
                'is_company_wide' => false
            ],
            [
                'title' => 'Finance Department Audit Preparation',
                'category' => 'Finance',
                'description' => 'Annual audit preparation for finance team',
                'content' => 'The finance department must prepare for the annual external audit. All finance team members are required to organize financial records, prepare documentation, and coordinate with auditors. This process is critical for compliance and requires full cooperation from all finance staff. Detailed preparation guidelines and timelines will be provided.',
                'start_date' => $currentYear . '-08-01',
                'end_date' => $currentYear . '-08-31',
                'is_featured' => false,
                'is_high_priority' => true,
                'is_company_wide' => false
            ]
        ];

        foreach ($companies as $company) {
            // Get HR users who can create announcements
            $hrUsers = User::where('type', 'hr')
                ->where('created_by', $company->id)
                ->get();

            $creator = $hrUsers->isNotEmpty() ? $hrUsers->first() : $company;

            // Get branches and departments for this company
            $branches = Branch::where('created_by', $company->id)->get();
            $departments = Department::where('created_by', $company->id)->get();

            foreach ($announcements as $index => $announcementData) {
                // Check if announcement already exists for this company
                if (Announcement::where('title', $announcementData['title'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    $announcement = Announcement::create([
                        'title' => $announcementData['title'],
                        'category' => $announcementData['category'],
                        'description' => $announcementData['description'],
                        'content' => $announcementData['content'],
                        'start_date' => $announcementData['start_date'],
                        'end_date' => $announcementData['end_date'],
                        'attachments' => null,
                        'is_featured' => $announcementData['is_featured'],
                        'is_high_priority' => $announcementData['is_high_priority'],
                        'is_company_wide' => $announcementData['is_company_wide'],
                        'created_by' => $creator->id,
                    ]);

                    // Attach to branches and departments based on scope
                    if ($announcementData['is_company_wide']) {
                        // Company-wide: attach to all branches and departments
                        if ($branches->isNotEmpty()) {
                            $announcement->branches()->attach($branches->pluck('id'));
                        }
                        if ($departments->isNotEmpty()) {
                            $announcement->departments()->attach($departments->pluck('id'));
                        }
                    } else {
                        // Department/Branch specific: attach to one branch and one department
                        if ($branches->isNotEmpty()) {
                            $announcement->branches()->attach($branches->first()->id);
                        }
                        if ($departments->isNotEmpty()) {
                            $announcement->departments()->attach($departments->first()->id);
                        }
                    }

                    // Create announcement views for some employees
                    $this->createAnnouncementViews($announcement, $company);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create announcement: ' . $announcementData['title'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('Announcement seeder completed successfully!');
    }

    /**
     * Create announcement views for employees
     */
    private function createAnnouncementViews($announcement, $company)
    {
        // Get employees for this company
        $employees = User::where('type', 'employee')
            ->where('created_by', $company->id)
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        // Create views for first 3 employees (consistent data)
        $viewedEmployees = $employees->take(5);

        foreach ($viewedEmployees as $employee) {
            try {
                DB::table('announcement_views')->insert([
                    'announcement_id' => $announcement->id,
                    'employee_id' => $employee->id,
                    'viewed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
