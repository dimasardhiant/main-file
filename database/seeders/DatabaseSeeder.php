<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (config('app.is_demo')) {
            $this->call([
                // Core system seeders
                PermissionSeeder::class,
                RoleSeeder::class,
                PlanSeeder::class,
                DefaultSuperAdminSeeder::class,
                DefaultCompanySeeder::class,
                DefaultCompanyUserSeeder::class,
                CurrencySeeder::class,
                EmailTemplateSeeder::class,
                LandingPageCustomPageSeeder::class,


                CouponSeeder::class,
                PlanOrderSeeder::class,
                PlanRequestSeeder::class,
                ReferralSettingSeeder::class,
                ReferralSeeder::class,
                PayoutRequestSeeder::class,
                WebhookSeeder::class,
                MediaItemSeeder::class,



                // HRM module seeders
                BranchSeeder::class,
                DepartmentSeeder::class,
                DesignationSeeder::class,
                DocumentTypeSeeder::class,
                EmployeeSeeder::class,
                AwardTypeSeeder::class,
                AwardSeeder::class,
                PromotionSeeder::class,
                ResignationSeeder::class,
                TerminationSeeder::class,
                WarningSeeder::class,
                TripSeeder::class,
                ComplaintSeeder::class,
                EmployeeTransferSeeder::class,
                HolidaySeeder::class,
                AnnouncementSeeder::class,
                AssetTypeSeeder::class,
                AssetSeeder::class,

                // Performance Module Seeders
                PerformanceIndicatorCategorySeeder::class,
                PerformanceIndicatorSeeder::class,
                GoalTypeSeeder::class,
                EmployeeGoalSeeder::class,
                ReviewCycleSeeder::class,
                EmployeeReviewSeeder::class,

                //    Trainning Seeders
                TrainingTypeSeeder::class,
                TrainingProgramSeeder::class,
                TrainingSessionSeeder::class,
                EmployeeTrainingSeeder::class,

                // Recruitment Module Seeders
                JobCategorySeeder::class,
                JobRequisitionSeeder::class,
                JobTypeSeeder::class,
                JobLocationSeeder::class,
                JobPostingSeeder::class,
                CandidateSourceSeeder::class,
                CandidateSeeder::class,
                InterviewTypeSeeder::class,
                InterviewRoundSeeder::class,
                InterviewSeeder::class,
                InterviewFeedbackSeeder::class,
                CandidateAssessmentSeeder::class,
                OfferTemplateSeeder::class,
                OfferSeeder::class,
                OnboardingChecklistSeeder::class,
                ChecklistItemSeeder::class,
                CandidateOnboardingSeeder::class,

                // Contract Management Seeders
                ContractTypeSeeder::class,
                EmployeeContractSeeder::class,
                ContractRenewalSeeder::class,
                ContractTemplateSeeder::class,

                // // Document Management Seeders
                DocumentCategorySeeder::class,
                HrDocumentSeeder::class,
                DocumentAcknowledgmentSeeder::class,
                DocumentTemplateSeeder::class,



                // // Meeting Management Seeders
                MeetingTypeSeeder::class,
                MeetingRoomSeeder::class,
                MeetingSeeder::class,
                MeetingAttendeeSeeder::class,
                MeetingMinuteSeeder::class,
                ActionItemSeeder::class,

                // Leave management Seeders
                LeaveTypeSeeder::class,
                LeavePolicySeeder::class,
                LeaveApplicationSeeder::class,
                LeaveBalanceSeeder::class,

                // // Attendance Management Seeders
                ShiftSeeder::class,
                AttendancePolicySeeder::class,
                AttendanceRecordSeeder::class,
                AttendanceRegularizationSeeder::class,

                // // Time Tracking Seeders
                TimeEntrySeeder::class,

                // // Payroll Management Seeders
                SalaryComponentSeeder::class,
                EmployeeSalarySeeder::class,
                PayrollRunSeeder::class,
                // PayslipSeeder::class,







                // User and business seeders
                //CompanySeeder::class,
                //StaffRoleSeeder::class,
                // Business-related seeders
                //ContactSeeder::class,
                //MediaItemSeeder::class,
                // System configuration seeders
                //CouponSeeder::class,
                //PlanOrderSeeder::class,
                //PlanRequestSeeder::class,
                //ReferralSettingSeeder::class,
                // New seeders
                //ReferralSeeder::class,
                //PayoutRequestSeeder::class,
                //WebhookSeeder::class,
            ]);
        } else {
            $this->call([
                PermissionSeeder::class,
                RoleSeeder::class,
                PlanSeeder::class,
                DefaultSuperAdminSeeder::class,
                DefaultCompanySeeder::class,
                DefaultCompanyUserSeeder::class,
                CurrencySeeder::class,
                EmailTemplateSeeder::class,
                LandingPageCustomPageSeeder::class,
            ]);
        }
    }
}
