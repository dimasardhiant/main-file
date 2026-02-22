<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultCompanyUserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run CompanySeeder first.');

            return;
        }

        foreach ($companies as $company) {
            $this->createRoles($company);
            $this->createUsers($company, $faker);
        }
    }

    private function createRoles($company)
    {
        // Employee Role
        $employeeRole = Role::firstOrCreate(
            ['name' => 'employee', 'guard_name' => 'web', 'created_by' => $company->id],
            ['label' => 'Employee', 'description' => 'Employee Role', 'created_by' => $company->id]
        );
        $employeeRole->syncPermissions(Permission::whereIn('name', $this->getEmployeePermissions())->get());

        // Manager Role
        $managerRole = Role::firstOrCreate(
            ['name' => 'manager', 'guard_name' => 'web', 'created_by' => $company->id],
            ['label' => 'Manager', 'description' => 'Manager Role', 'created_by' => $company->id]
        );
        $managerRole->syncPermissions(Permission::whereIn('name', $this->getManagerPermissions())->get());

        // HR Role
        $hrRole = Role::firstOrCreate(
            ['name' => 'hr', 'guard_name' => 'web', 'created_by' => $company->id],
            ['label' => 'HR', 'description' => 'HR Role', 'created_by' => $company->id]
        );
        $hrRole->syncPermissions(Permission::whereIn('name', $this->getHRPermissions())->get());
    }

    private function createUsers($company, $faker)
    {
        $isDemo = config('app.is_demo');
        $isSaas = isSaas();

        if ($isSaas && $isDemo) {
            // SaaS + Demo: 2 managers, 2 HR, 10 employees
            $roles = [
                ['role' => 'manager', 'count' => 2],
                ['role' => 'hr', 'count' => 2],
                ['role' => 'employee', 'count' => 10],
            ];
        } elseif ($isSaas && ! $isDemo) {
            // SaaS + Non-Demo: 1 manager, 1 HR, 0 employees
            $roles = [
                ['role' => 'manager', 'count' => 1],
                ['role' => 'hr', 'count' => 1],
                ['role' => 'employee', 'count' => 0],
            ];
        } elseif (! $isSaas && $isDemo) {
            // Non-SaaS + Demo: 2 managers, 2 HR, 10 employees
            $roles = [
                ['role' => 'manager', 'count' => 2],
                ['role' => 'hr', 'count' => 2],
                ['role' => 'employee', 'count' => 10],
            ];
        } else {
            // Non-SaaS + Non-Demo: 1 manager, 1 HR, 0 employees
            $roles = [
                ['role' => 'manager', 'count' => 1],
                ['role' => 'hr', 'count' => 1],
                ['role' => 'employee', 'count' => 0],
            ];
        }

        foreach ($roles as $roleData) {
            for ($i = 1; $i <= $roleData['count']; $i++) {
                $userData = $this->getUserData($roleData['role'], $faker, $isDemo, $i);

                $user = User::firstOrCreate(
                    ['email' => $userData['email']],
                    [
                        'name' => $userData['name'],
                        'type' => $roleData['role'],
                        'password' => Hash::make('password'),
                        'status' => 'active',
                        'created_by' => $company->id,
                    ]
                );

                $user->assignRole(Role::where('name', $roleData['role'])->where('created_by', $company->id)->first());
            }
        }
    }

    private function getUserData($roleType, $faker, $isDemo, $index)
    {
        // If index > 1, use faker data to avoid duplicates
        if ($index > 1) {
            return ['name' => $faker->name, 'email' => $faker->unique()->safeEmail];
        }

        switch ($roleType) {
            case 'manager':
                return ['name' => 'Manager', 'email' => 'manager@example.com'];
            case 'hr':
                return ['name' => 'HR', 'email' => 'hr@example.com'];
            case 'employee':
                return ['name' => 'Employee', 'email' => 'employee@example.com'];
            default:
                return ['name' => $faker->name, 'email' => $faker->unique()->safeEmail];
        }
    }

    private function getEmployeePermissions(): array
    {
        return [
            'manage-dashboard',
            'view-dashboard',

            'manage-calendar',
            'view-calendar',
            'manage-analytics',

            // Media Permissions
            'manage-media',
            'manage-any-media',
            'create-media',
            'edit-media',
            'delete-media',
            'view-media',
            'download-media',

            // Media Directory
            'manage-media-directories',
            'manage-any-media-directories',
            'create-media-directories',
            'edit-media-directories',
            'delete-media-directories',

            // Employee permissions
            'manage-employees',
            'manage-own-employees',
            'view-employees',

            // Award permissions
            'manage-awards',
            'manage-own-awards',
            'view-awards',

            // Promotion permissions
            'manage-promotions',
            'manage-own-promotions',
            'view-promotions',

            // Resignation permissions
            'manage-resignations',
            'view-resignations',
            'manage-own-resignations',
            'create-resignations',
            'edit-resignations',
            'delete-resignations',

            // Termination permissions
            'manage-terminations',
            'manage-own-terminations',
            'view-terminations',

            // Warning permissions
            'manage-warnings',
            'manage-own-warnings',
            'view-warnings',

            // Trip permissions
            'manage-trips',
            'manage-own-trips',
            'view-trips',

            // Complaint permissions
            'manage-complaints',
            'manage-own-complaints',
            'view-complaints',
            'create-complaints',
            'edit-complaints',
            'delete-complaints',

            // Employee Transfer permissions
            'manage-employee-transfers',
            'manage-own-employee-transfers',
            'view-employee-transfers',

            // Holiday permissions
            'manage-holidays',
            'manage-any-holidays',
            'view-holidays',

            // Announcement permissions
            'manage-announcements',
            'manage-any-announcements',
            'view-announcements',

            // Asset Type permissions
            'manage-asset-types',
            'manage-any-asset-types',
            'view-asset-types',

            // Asset permissions
            'manage-assets',
            'view-assets',

            // Training Program permissions
            'manage-training-programs',
            'manage-any-training-programs',
            'view-training-programs',

            // Training Session permissions
            'manage-training-sessions',
            'manage-own-training-sessions',
            'view-training-sessions',
            'manage-attendance',

            // Employee Training permissions
            'manage-employee-trainings',
            'manage-own-employee-trainings',
            'view-employee-trainings',
            'assign-trainings',
            'manage-assessments',
            'record-assessment-results',

            // Performance Indicators
            'manage-performance-indicators',
            'manage-own-performance-indicators',
            'view-performance-indicators',

            // Employee Goals
            'manage-employee-goals',
            'manage-own-employee-goals',
            'view-employee-goals',

            // Review Cycles
            'manage-review-cycles',
            'manage-own-review-cycles',
            'view-review-cycles',

            // Employee Reviews
            'manage-employee-reviews',
            'manage-own-employee-reviews',
            'view-employee-reviews',

            // Job Requisitions management
            'manage-job-requisitions',
            'manage-    -job-requisitions',
            'view-job-requisitions',

            // Job Locations management
            'manage-job-locations',
            'manage-any-job-locations',
            'view-job-locations',

            // Job Postings management
            'manage-job-postings',
            'manage-any-job-postings',
            'view-job-postings',

            // Interview Rounds management
            'manage-interview-rounds',
            'manage-any-interview-rounds',
            'view-interview-rounds',

            // Interviews management
            'manage-interviews',
            'manage-own-interviews',
            'view-interviews',

            // Interview Feedback management
            'manage-interview-feedback',
            'manage-own-interview-feedback',
            'view-interview-feedback',
            'create-interview-feedback',
            'edit-interview-feedback',
            'delete-interview-feedback',

            // Candidate Assessments management
            'manage-candidate-assessments',
            'manage-own-candidate-assessments',
            'view-candidate-assessments',
            'create-candidate-assessments',
            'edit-candidate-assessments',
            'delete-candidate-assessments',

            // Candidate Onboarding management
            'manage-candidate-onboarding',
            'manage-own-candidate-onboarding',
            'manage-candidate-onboarding-status',
            'view-candidate-onboarding',

            // Meetings management
            'manage-meetings',
            'manage-own-meetings',
            'view-meetings',

            // Meeting Attendees management
            'manage-meeting-attendees',
            'manage-any-meeting-attendees',
            'view-meeting-attendees',
            'edit-meeting-attendees',

            // Meeting Minutes management
            'manage-meeting-minutes',
            'manage-own-meeting-minutes',
            'view-meeting-minutes',

            // Action Items management
            'manage-action-items',
            'manage-own-action-items',
            'view-action-items',

            // Employee Contracts management
            'manage-employee-contracts',
            'manage-own-employee-contracts',
            'view-employee-contracts',

            // Contract Renewals management
            'manage-contract-renewals',
            'view-contract-renewals',

            // HR Documents management
            'manage-hr-documents',
            'manage-any-hr-documents',
            'view-hr-documents',

            // Document Acknowledgments management
            'manage-document-acknowledgments',
            'manage-own-document-acknowledgments',
            'view-document-acknowledgments',

            // Leave Policies management
            'manage-leave-policies',
            'manage-any-leave-policies',

            // Leave Applications management
            'manage-leave-applications',
            'manage-own-leave-applications',
            'view-leave-applications',
            'create-leave-applications',
            'edit-leave-applications',
            'delete-leave-applications',

            // Leave Balances management
            'manage-leave-balances',
            'manage-own-leave-balances',
            'view-leave-balances',

            // Shifts management
            'manage-shifts',
            'manage-any-shifts',
            'view-shifts',

            // Attendance Policies management
            'manage-attendance-policies',
            'manage-any-attendance-policies',
            'view-attendance-policies',

            // Attendance Records management
            'manage-attendance-records',
            'manage-own-attendance-records',
            'view-attendance-records',
            'create-attendance-records',
            'edit-attendance-records',
            'delete-attendance-records',
            'clock-in-out',

            // Attendance Regularizations management
            'manage-attendance-regularizations',
            'manage-own-attendance-regularizations',
            'view-attendance-regularizations',
            'create-attendance-regularizations',
            'edit-attendance-regularizations',
            'delete-attendance-regularizations',

            // Time Entries management
            'manage-time-entries',
            'manage-own-time-entries',
            'view-time-entries',
            'create-time-entries',
            'edit-time-entries',

            // Employee Salaries management
            'manage-employee-salaries',
            'manage-own-employee-salaries',
            'view-employee-salaries',

            // Payslips management
            'manage-payslips',
            'manage-own-payslips',
            'download-payslips',

            // career page
            'manage-career-page',
        ];
    }

    private function getManagerPermissions(): array
    {
        return [
            'manage-dashboard',
            'view-dashboard',

            'manage-calendar',
            'view-calendar',
            'manage-analytics',

            // Media Permissions
            'manage-media',
            'manage-any-media',
            'create-media',
            'edit-media',
            'delete-media',
            'view-media',
            'download-media',

            // Media Directory
            'manage-media-directories',
            'manage-any-media-directories',
            'create-media-directories',
            'edit-media-directories',
            'delete-media-directories',

            // Branch Permissions
            'manage-branches',
            'manage-any-branches',
            'view-branches',
            'create-branches',
            'edit-branches',
            'delete-branches',
            'toggle-status-branches',

            // Department Permissions
            'manage-departments',
            'manage-any-departments',
            'view-departments',
            'create-departments',
            'edit-departments',
            'delete-departments',
            'toggle-status-departments',

            // Designation Permissions
            'manage-designations',
            'manage-any-designations',
            'view-designations',
            'create-designations',
            'edit-designations',
            'delete-designations',
            'toggle-status-designations',

            // Document Type Permissions
            'manage-document-types',
            'manage-any-document-types',
            'view-document-types',
            'create-document-types',
            'edit-document-types',
            'delete-document-types',

            // Employee permissions
            'manage-employees',
            'manage-any-employees',
            'view-employees',
            'create-employees',
            'edit-employees',

            // Award Type management
            'manage-award-types',
            'manage-any-award-types',
            'view-award-types',
            'create-award-types',
            'edit-award-types',
            'delete-award-types',

            // Award Type management
            'manage-award-types',
            'manage-any-award-types',
            'view-award-types',
            'create-award-types',
            'edit-award-types',
            'delete-award-types',

            // Award management
            'manage-awards',
            'manage-any-awards',
            'view-awards',
            'create-awards',
            'edit-awards',
            'delete-awards',

            // Promotion management
            'manage-promotions',
            'manage-any-promotions',
            'view-promotions',
            'create-promotions',
            'edit-promotions',
            'delete-promotions',
            'approve-promotions',
            'reject-promotions',

            // Resignation management
            'manage-resignations',
            'manage-any-resignations',
            'view-resignations',
            'create-resignations',
            'edit-resignations',
            'delete-resignations',
            'approve-resignations',
            'reject-resignations',

            // Termination management
            'manage-terminations',
            'manage-any-terminations',
            'view-terminations',
            'create-terminations',
            'edit-terminations',
            'delete-terminations',
            'approve-terminations',
            'reject-terminations',

            // Warning management
            'manage-warnings',
            'manage-any-warnings',
            'view-warnings',
            'create-warnings',
            'edit-warnings',
            'delete-warnings',
            'approve-warnings',
            'acknowledge-warnings',

            // Trip management
            'manage-trips',
            'manage-any-trips',
            'view-trips',
            'create-trips',
            'edit-trips',
            'delete-trips',
            'approve-trips',
            'manage-trip-expenses',
            'approve-trip-expenses',

            // Complaint management
            'manage-complaints',
            'manage-any-complaints',
            'view-complaints',
            'create-complaints',
            'edit-complaints',
            'delete-complaints',
            'assign-complaints',
            'resolve-complaints',

            // Employee Transfer management
            'manage-employee-transfers',
            'manage-any-employee-transfers',
            'view-employee-transfers',
            'create-employee-transfers',
            'edit-employee-transfers',
            'delete-employee-transfers',
            'approve-employee-transfers',
            'reject-employee-transfers',

            // Holiday management
            'manage-holidays',
            'manage-any-holidays',
            'view-holidays',
            'create-holidays',
            'edit-holidays',
            'delete-holidays',

            // Announcement management
            'manage-announcements',
            'manage-any-announcements',
            'view-announcements',
            'create-announcements',
            'edit-announcements',
            'delete-announcements',

            // Asset Type management
            'manage-asset-types',
            'manage-any-asset-types',
            'view-asset-types',
            'create-asset-types',
            'edit-asset-types',
            'delete-asset-types',

            // Asset management
            'manage-assets',
            'manage-any-assets',
            'view-assets',
            'create-assets',
            'edit-assets',
            'delete-assets',
            'assign-assets',
            'manage-asset-maintenance',

            // Training Type management
            'manage-training-types',
            'manage-any-training-types',
            'view-training-types',
            'create-training-types',
            'edit-training-types',
            'delete-training-types',

            // Training Program management
            'manage-training-programs',
            'manage-any-training-programs',
            'view-training-programs',
            'create-training-programs',
            'edit-training-programs',
            'delete-training-programs',

            // Training Session management
            'manage-training-sessions',
            'manage-any-training-sessions',
            'view-training-sessions',
            'create-training-sessions',
            'edit-training-sessions',
            'delete-training-sessions',
            'manage-attendance',

            // Employee Training management
            'manage-employee-trainings',
            'manage-any-employee-trainings',
            'view-employee-trainings',
            'create-employee-trainings',
            'edit-employee-trainings',
            'delete-employee-trainings',
            'assign-trainings',
            'manage-assessments',
            'record-assessment-results',

            // Performance Indicator Category management
            'manage-performance-indicator-categories',
            'manage-any-performance-indicator-categories',
            'view-performance-indicator-categories',
            'create-performance-indicator-categories',
            'edit-performance-indicator-categories',
            'delete-performance-indicator-categories',

            // Performance Indicators management
            'manage-performance-indicators',
            'manage-any-performance-indicators',
            'view-performance-indicators',
            'create-performance-indicators',
            'edit-performance-indicators',
            'delete-performance-indicators',

            // Goal Types
            'manage-goal-types',
            'manage-any-goal-types',
            'view-goal-types',
            'create-goal-types',
            'edit-goal-types',
            'delete-goal-types',

            // Employee Goals
            'manage-employee-goals',
            'manage-any-employee-goals',
            'view-employee-goals',
            'create-employee-goals',
            'edit-employee-goals',
            'delete-employee-goals',

            // Review Cycles
            'manage-review-cycles',
            'manage-any-review-cycles',
            'view-review-cycles',
            'create-review-cycles',
            'edit-review-cycles',
            'delete-review-cycles',

            // Employee Reviews
            'manage-employee-reviews',
            'manage-any-employee-reviews',
            'view-employee-reviews',
            'create-employee-reviews',
            'edit-employee-reviews',
            'delete-employee-reviews',

            // Job Categories
            'manage-job-categories',
            'manage-any-job-categories',
            'view-job-categories',
            'create-job-categories',
            'edit-job-categories',
            'delete-job-categories',

            // Job Requisitions
            'manage-job-requisitions',
            'manage-any-job-requisitions',
            'view-job-requisitions',
            'create-job-requisitions',
            'edit-job-requisitions',
            'delete-job-requisitions',
            'approve-job-requisitions',

            // Job Types
            'manage-job-types',
            'manage-any-job-types',
            'view-job-types',
            'create-job-types',
            'edit-job-types',
            'delete-job-types',

            // Job Locations
            'manage-job-locations',
            'manage-any-job-locations',
            'view-job-locations',
            'create-job-locations',
            'edit-job-locations',
            'delete-job-locations',

            // Job Postings
            'manage-job-postings',
            'manage-any-job-postings',
            'view-job-postings',
            'create-job-postings',
            'edit-job-postings',
            'delete-job-postings',
            'publish-job-postings',

            // Candidate Sources
            'manage-candidate-sources',
            'manage-any-candidate-sources',
            'view-candidate-sources',
            'create-candidate-sources',
            'edit-candidate-sources',
            'delete-candidate-sources',

            // Candidates
            'manage-candidates',
            'manage-any-candidates',
            'view-candidates',
            'convert-to-employee',
            // 'create-candidates',
            'edit-candidates',
            'delete-candidates',

            // Interview Types
            'manage-interview-types',
            'manage-any-interview-types',
            'view-interview-types',
            'create-interview-types',
            'edit-interview-types',
            'delete-interview-types',

            // Interview Rounds
            'manage-interview-rounds',
            'manage-any-interview-rounds',
            'view-interview-rounds',
            'create-interview-rounds',
            'edit-interview-rounds',
            'delete-interview-rounds',

            // Interviews
            'manage-interviews',
            'manage-any-interviews',
            'view-interviews',
            'create-interviews',
            'edit-interviews',
            'delete-interviews',

            // Interview Feedback
            'manage-interview-feedback',
            'manage-any-interview-feedback',
            'view-interview-feedback',
            'create-interview-feedback',
            'edit-interview-feedback',
            'delete-interview-feedback',

            // Candidate Assessments
            'manage-candidate-assessments',
            'manage-any-candidate-assessments',
            'view-candidate-assessments',
            'create-candidate-assessments',
            'edit-candidate-assessments',
            'delete-candidate-assessments',

            // Offer Templates
            'manage-offer-templates',
            'manage-any-offer-templates',
            'view-offer-templates',
            'create-offer-templates',
            'edit-offer-templates',
            'delete-offer-templates',

            // Offers
            'manage-offers',
            'manage-any-offers',
            'view-offers',
            'create-offers',
            'edit-offers',
            'delete-offers',
            'approve-offers',

            // Onboarding Checklists management
            'manage-onboarding-checklists',
            'manage-any-onboarding-checklists',
            'view-onboarding-checklists',
            'create-onboarding-checklists',
            'edit-onboarding-checklists',
            'delete-onboarding-checklists',

            // Checklist Items management
            'manage-checklist-items',
            'manage-any-checklist-items',
            'view-checklist-items',
            'create-checklist-items',
            'edit-checklist-items',
            'delete-checklist-items',

            // Candidate Onboarding management
            'manage-candidate-onboarding',
            'manage-any-candidate-onboarding',
            'manage-candidate-onboarding-status',
            'view-candidate-onboarding',
            'create-candidate-onboarding',
            'edit-candidate-onboarding',
            'delete-candidate-onboarding',

            // Meeting Types management
            'manage-meeting-types',
            'manage-any-meeting-types',
            'view-meeting-types',
            'create-meeting-types',
            'edit-meeting-types',
            'delete-meeting-types',

            // Meeting Rooms management
            'manage-meeting-rooms',
            'manage-any-meeting-rooms',
            'view-meeting-rooms',
            'create-meeting-rooms',
            'edit-meeting-rooms',
            'delete-meeting-rooms',

            // Meetings management
            'manage-meetings',
            'manage-any-meetings',
            'view-meetings',
            'create-meetings',
            'edit-meetings',
            'delete-meetings',
            'manage-meeting-status',

            // Meeting Attendees management
            'manage-meeting-attendees',
            'manage-any-meeting-attendees',
            'view-meeting-attendees',
            'create-meeting-attendees',
            'edit-meeting-attendees',
            'delete-meeting-attendees',
            'manage-meeting-rsvp-status',
            'manage-meeting-attendance',

            // Meeting Minutes management
            'manage-meeting-minutes',
            'manage-any-meeting-minutes',
            'view-meeting-minutes',
            'create-meeting-minutes',
            'edit-meeting-minutes',
            'delete-meeting-minutes',

            // Action Items management
            'manage-action-items',
            'manage-any-action-items',
            'view-action-items',
            'create-action-items',
            'edit-action-items',
            'delete-action-items',

            // Contract Types management
            'manage-contract-types',
            'manage-any-contract-types',
            'view-contract-types',
            'create-contract-types',
            'edit-contract-types',
            'delete-contract-types',

            // Employee Contracts management
            'manage-employee-contracts',
            'manage-any-employee-contracts',
            'view-employee-contracts',
            'create-employee-contracts',
            'edit-employee-contracts',
            'delete-employee-contracts',
            'approve-employee-contracts',
            'reject-employee-contracts',

            // Contract Renewals management
            'manage-contract-renewals',
            'manage-any-contract-renewals',
            'view-contract-renewals',
            'create-contract-renewals',
            'edit-contract-renewals',
            'delete-contract-renewals',
            'approve-contract-renewals',
            'reject-contract-renewals',

            // Contract Templates management
            'manage-contract-templates',
            'manage-any-contract-templates',
            'view-contract-templates',
            'create-contract-templates',
            'edit-contract-templates',
            'delete-contract-templates',

            // Document Categories management
            'manage-document-categories',
            'manage-any-document-categories',
            'view-document-categories',
            'create-document-categories',
            'edit-document-categories',
            'delete-document-categories',

            // HR Documents management
            'manage-hr-documents',
            'manage-any-hr-documents',
            'view-hr-documents',
            'create-hr-documents',
            'edit-hr-documents',
            'delete-hr-documents',

            // Document Acknowledgments management
            'manage-document-acknowledgments',
            'manage-any-document-acknowledgments',
            'view-document-acknowledgments',
            'create-document-acknowledgments',
            'edit-document-acknowledgments',
            'delete-document-acknowledgments',
            'acknowledge-document-acknowledgments',

            // Document Templates management
            'manage-document-templates',
            'manage-any-document-templates',
            'view-document-templates',
            'create-document-templates',
            'edit-document-templates',
            'delete-document-templates',

            // Leave Types management
            'manage-leave-types',
            'manage-any-leave-types',
            'view-leave-types',
            'create-leave-types',
            'edit-leave-types',
            'delete-leave-types',

            // Leave Policies management
            'manage-leave-policies',
            'manage-any-leave-policies',
            'view-leave-policies',
            'create-leave-policies',
            'edit-leave-policies',
            'delete-leave-policies',

            // Leave Applications management
            'manage-leave-applications',
            'manage-any-leave-applications',
            'view-leave-applications',
            'create-leave-applications',
            'edit-leave-applications',
            'delete-leave-applications',
            'approve-leave-applications',
            'reject-leave-applications',

            // Leave Balances management
            'manage-leave-balances',
            'manage-any-leave-balances',
            'view-leave-balances',
            'create-leave-balances',
            'edit-leave-balances',
            'delete-leave-balances',
            'adjust-leave-balances',

            // Shifts management
            'manage-shifts',
            'manage-any-shifts',
            'view-shifts',
            'create-shifts',
            'edit-shifts',
            'delete-shifts',

            // Attendance Policies management
            'manage-attendance-policies',
            'manage-any-attendance-policies',
            'view-attendance-policies',
            'create-attendance-policies',
            'edit-attendance-policies',
            'delete-attendance-policies',

            // Attendance Records management
            'manage-attendance-records',
            'manage-any-attendance-records',
            'view-attendance-records',
            'create-attendance-records',
            'edit-attendance-records',
            'delete-attendance-records',
            'clock-in-out',

            // Attendance Regularizations management
            'manage-attendance-regularizations',
            'manage-any-attendance-regularizations',
            'view-attendance-regularizations',
            'create-attendance-regularizations',
            'edit-attendance-regularizations',
            'delete-attendance-regularizations',
            'approve-attendance-regularizations',
            'reject-attendance-regularizations',

            // Time Entries management
            'manage-time-entries',
            'manage-any-time-entries',
            'view-time-entries',
            'create-time-entries',
            'edit-time-entries',
            'delete-time-entries',
            'approve-time-entries',
            'reject-time-entries',

            // Salary Components management
            'manage-salary-components',
            'manage-any-salary-components',
            'view-salary-components',
            'create-salary-components',
            'edit-salary-components',
            'delete-salary-components',

            // Employee Salaries management
            'manage-employee-salaries',
            'manage-any-employee-salaries',
            'view-employee-salaries',
            'create-employee-salaries',
            'edit-employee-salaries',
            'delete-employee-salaries',

            // Payroll Runs management
            'manage-payroll-runs',
            'manage-any-payroll-runs',
            'view-payroll-runs',
            'create-payroll-runs',
            'edit-payroll-runs',
            'delete-payroll-runs',
            'process-payroll-runs',

            // Payslips management
            'manage-payslips',
            'manage-any-payslips',
            'view-payslips',
            'create-payslips',
            'download-payslips',
            'send-payslips',

            // career page
            'manage-career-page',
        ];
    }

    private function getHRPermissions(): array
    {
        return $this->getManagerPermissions();
    }
}
