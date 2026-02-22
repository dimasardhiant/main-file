<?php

namespace App\Models;

use App\Services\MailConfigService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lab404\Impersonate\Models\Impersonate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends BaseAuthenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Impersonate, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'type',
        'avatar',
        'lang',
        'delete_status',
        'is_enable_login',
        'mode',
        'created_by',
        'google2fa_enable',
        'google2fa_secret',
        'status',
        'active_module',
        'slug',
    ];

    public function getFillable()
    {
        $fillable = parent::getFillable();

        if (isSaas()) {
            $fillable = array_merge($fillable, [
                'plan_id',
                'plan_expire_date',
                'requested_plan',
                'plan_is_active',
                'storage_limit',
                'referral_code',
                'used_referral_code',
                'is_trial',
                'trial_day',
                'trial_expire_date',
                'commission_amount',
            ]);
        }

        return $fillable;
    }

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plan_expire_date' => 'date',
            'trial_expire_date' => 'date',
            'plan_is_active' => 'integer',
            'is_active' => 'integer',
            'is_enable_login' => 'integer',
            'google2fa_enable' => 'integer',
            'storage_limit' => 'float',
        ];
    }

    public $not_emp_type = [
        'superadmin',
        'company',
    ];

    public function scopeEmp($query, $additionalTypes = [], $includeTypes = [])
    {
        $excludeTypes = array_diff(array_merge($this->not_emp_type, $additionalTypes), $includeTypes);

        return $query->whereNotIn('type', $excludeTypes);
    }

    /**
     * Get the creator ID based on user type
     */
    public function creatorId()
    {
        if (isSaas()) {
            if ($this->type == 'superadmin' || $this->type == 'super admin' || $this->type == 'admin') {
                return $this->id;
            } else {
                return $this->created_by;
            }
        } else {
            // Non-SaaS: Company is the top level
            if ($this->type == 'company') {
                return $this->id;
            } else {
                return $this->created_by;
            }
        }
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        if (! isSaas()) {
            return false; // No super admin in non-SaaS
        }

        return $this->type === 'superadmin' || $this->type === 'super admin';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->type === 'admin';
    }

    // Businesses relationship removed

    /**
     * Get the plan associated with the user.
     */
    public function plan()
    {
        if (! isSaas()) {
            return null; // No plans in non-SaaS
        }

        return $this->belongsTo(Plan::class);
    }

    /**
     * Check if user is on free plan
     */
    public function isOnFreePlan()
    {
        if (! isSaas()) {
            return false; // No plans in non-SaaS
        }

        return $this->plan && $this->plan->is_default;
    }

    /**
     * Get current plan or default plan
     */
    public function getCurrentPlan()
    {
        if (! isSaas()) {
            return null; // No plans in non-SaaS
        }

        if ($this->plan) {
            return $this->plan;
        }

        return Plan::getDefaultPlan();
    }

    /**
     * Check if user has an active plan subscription
     */
    public function hasActivePlan()
    {
        if (! isSaas()) {
            return true; // Always active in non-SaaS
        }

        return $this->plan_id &&
            $this->plan_is_active &&
            ($this->plan_expire_date === null || $this->plan_expire_date > now());
    }

    /**
     * Check if user's plan has expired
     */
    public function isPlanExpired()
    {
        if (! isSaas()) {
            return false; // No expiration in non-SaaS
        }

        return $this->plan_expire_date && $this->plan_expire_date < now();
    }

    /**
     * Check if user's trial has expired
     */
    public function isTrialExpired()
    {
        if (! isSaas()) {
            return false; // No trials in non-SaaS
        }

        return $this->is_trial && $this->trial_expire_date && $this->trial_expire_date < now();
    }

    /**
     * Check if user needs to subscribe to a plan
     */
    public function needsPlanSubscription()
    {
        if (! isSaas()) {
            return false; // No subscriptions in non-SaaS
        }

        if ($this->isSuperAdmin()) {
            return false;
        }

        if ($this->type !== 'company') {
            return false;
        }

        // Check if user has no plan and no default plan exists
        if (! $this->plan_id) {
            return ! Plan::getDefaultPlan();
        }

        // Check if trial is expired
        if ($this->isTrialExpired()) {
            return true;
        }

        // Check if plan is expired (but not on trial)
        if (! $this->is_trial && $this->isPlanExpired()) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can be impersonated
     */
    public function canBeImpersonated()
    {
        return $this->type === 'company';
    }

    /**
     * Check if user can impersonate others
     */
    public function canImpersonate()
    {
        if (! isSaas()) {
            return false; // No impersonation in non-SaaS
        }

        return $this->isSuperAdmin();
    }

    /**
     * Get referrals made by this company
     */
    public function referrals()
    {
        if (! isSaas()) {
            return $this->hasMany(Referral::class, 'user_id')->whereRaw('1 = 0'); // Empty relation in non-SaaS
        }

        return $this->hasMany(Referral::class, 'user_id');
    }

    /**
     * Get payout requests made by this company
     */
    public function payoutRequests()
    {
        if (! isSaas()) {
            return $this->hasMany(PayoutRequest::class, 'company_id')->whereRaw('1 = 0'); // Empty relation in non-SaaS
        }

        return $this->hasMany(PayoutRequest::class, 'company_id');
    }

    /**
     * Get the user who created this user
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the employee record associated with the user
     */
    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    /**
     * Get referral balance for company
     */
    public function getReferralBalance()
    {
        if (! isSaas()) {
            return 0; // No referrals in non-SaaS
        }

        $totalEarned = $this->referrals()->sum('amount');
        $totalRequested = $this->payoutRequests()->whereIn('status', ['pending', 'approved'])->sum('amount');

        return $totalEarned - $totalRequested;
    }

    /**
     * Send the email verification notification with dynamic config.
     */
    public function sendEmailVerificationNotification()
    {
        try {
            MailConfigService::setDynamicConfig();
            parent::sendEmailVerificationNotification();

            return ['success' => true, 'message' => 'Verification email sent successfully'];
        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'user_id' => $this->id,
                'email' => $this->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'message' => 'Failed to send verification email: '.$e->getMessage()];
        }
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (isSaas() && $user->type === 'company' && ! $user->referral_code) {
                // Generate referral code after the user is saved to get the ID
                static::created(function ($createdUser) {
                    if (! $createdUser->referral_code) {
                        $createdUser->referral_code = 'REF'.str_pad($createdUser->id, 6, '0', STR_PAD_LEFT);
                        $createdUser->save();
                    }
                });
            }
        });

        static::created(function ($user) {
            // Assign default plan to company users only in SaaS mode
            if (isSaas() && $user->type === 'company' && ! $user->plan_id) {
                $defaultPlan = Plan::getDefaultPlan();
                if ($defaultPlan) {
                    $user->plan_id = $defaultPlan->id;
                    $user->plan_is_active = 1;
                    $user->save();
                }
            }
        });

        // Generate Slug When New user Creating
        static::creating(function ($user) {
            if (empty($user->slug)) {
                $user->slug = static::generateUniqueSlug($user->name);
            }
        });

        // Generate Slug When Update the User if Slug is Empty then only
        static::updating(function ($user) {
            if (empty($user->slug) && ! empty($user->name)) {
                $user->slug = static::generateUniqueSlug($user->name);
            }
        });
    }

    public static function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function planOrders()
    {
        if (! isSaas()) {
            return $this->hasMany(PlanOrder::class)->whereRaw('1 = 0'); // Empty relation in non-SaaS
        }

        return $this->hasMany(PlanOrder::class);
    }

    public function companyDefaultData($company)
    {
        $roles = [
            'employee' => [
                'label' => 'Employee',
                'description' => 'Employee Role',
                'permissions' => $this->getEmployeePermissions(),
            ],
            'manager' => [
                'label' => 'Manager',
                'description' => 'Manager Role',
                'permissions' => $this->getManagerPermissions(),
            ],
            'hr' => [
                'label' => 'HR',
                'description' => 'HR Role',
                'permissions' => $this->getHRPermissions(),
            ],
        ];

        foreach ($roles as $name => $data) {
            $role = Role::firstOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_by' => $company->id,
                ],
                [
                    'label' => $data['label'],
                    'description' => $data['description'],
                    'created_by' => $company->id,
                ]
            );

            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $role->syncPermissions($permissions);
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
        ];
    }

    private function getHRPermissions(): array
    {
        return $this->getManagerPermissions();
    }
}
