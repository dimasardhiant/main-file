<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard permissions
            ['name' => 'manage-dashboard', 'module' => 'dashboard', 'label' => 'Manage Dashboard', 'description' => 'Can view dashboard'],

            // User management
            ['name' => 'manage-users', 'module' => 'users', 'label' => 'Manage Users', 'description' => 'Can manage users'],
            ['name' => 'manage-any-users', 'module' => 'users', 'label' => 'Manage All Users', 'description' => 'Manage Any Users'],
            ['name' => 'manage-own-users', 'module' => 'users', 'label' => 'Manage Own Users', 'description' => 'Manage Limited Users that is created by own'],
            ['name' => 'view-users', 'module' => 'users', 'label' => 'Manage Users', 'description' => 'View Users'],
            ['name' => 'create-users', 'module' => 'users', 'label' => 'Create Users', 'description' => 'Can create users'],
            ['name' => 'edit-users', 'module' => 'users', 'label' => 'Edit Users', 'description' => 'Can edit users'],
            ['name' => 'delete-users', 'module' => 'users', 'label' => 'Delete Users', 'description' => 'Can delete users'],
            ['name' => 'reset-password-users', 'module' => 'users', 'label' => 'Reset Password Users', 'description' => 'Can reset password users'],
            ['name' => 'toggle-status-users', 'module' => 'users', 'label' => 'Change Status Users', 'description' => 'Can change status users'],

            // Role management
            ['name' => 'manage-roles', 'module' => 'roles', 'label' => 'Manage Roles', 'description' => 'Can manage roles'],
            ['name' => 'manage-any-roles', 'module' => 'roles', 'label' => 'Manage All Roles', 'description' => 'Manage Any Roles'],
            ['name' => 'manage-own-roles', 'module' => 'roles', 'label' => 'Manage Own Roles', 'description' => 'Manage Limited Roles that is created by own'],
            ['name' => 'view-roles', 'module' => 'roles', 'label' => 'View Roles', 'description' => 'View Roles'],
            ['name' => 'create-roles', 'module' => 'roles', 'label' => 'Create Roles', 'description' => 'Can create roles'],
            ['name' => 'edit-roles', 'module' => 'roles', 'label' => 'Edit Roles', 'description' => 'Can edit roles'],
            ['name' => 'delete-roles', 'module' => 'roles', 'label' => 'Delete Roles', 'description' => 'Can delete roles'],

            // Permission management
            ['name' => 'manage-permissions', 'module' => 'permissions', 'label' => 'Manage Permissions', 'description' => 'Can manage permissions'],
            ['name' => 'manage-any-permissions', 'module' => 'permissions', 'label' => 'Manage All Permissions', 'description' => 'Manage Any Permissions'],
            ['name' => 'manage-own-permissions', 'module' => 'permissions', 'label' => 'Manage Own Permissions', 'description' => 'Manage Limited Permissions that is created by own'],
            ['name' => 'view-permissions', 'module' => 'permissions', 'label' => 'View Permissions', 'description' => 'View Permissions'],
            ['name' => 'create-permissions', 'module' => 'permissions', 'label' => 'Create Permissions', 'description' => 'Can create permissions'],
            ['name' => 'edit-permissions', 'module' => 'permissions', 'label' => 'Edit Permissions', 'description' => 'Can edit permissions'],
            ['name' => 'delete-permissions', 'module' => 'permissions', 'label' => 'Delete Permissions', 'description' => 'Can delete permissions'],

            // Company management
            ['name' => 'manage-companies', 'module' => 'companies', 'label' => 'Manage Companies', 'description' => 'Can manage Companies'],
            ['name' => 'manage-any-companies', 'module' => 'companies', 'label' => 'Manage All Companies', 'description' => 'Manage Any Companies'],
            ['name' => 'manage-own-companies', 'module' => 'companies', 'label' => 'Manage Own Companies', 'description' => 'Manage Limited Companies that is created by own'],
            ['name' => 'view-companies', 'module' => 'companies', 'label' => 'View Companies', 'description' => 'View Companies'],
            ['name' => 'create-companies', 'module' => 'companies', 'label' => 'Create Companies', 'description' => 'Can create Companies'],
            ['name' => 'edit-companies', 'module' => 'companies', 'label' => 'Edit Companies', 'description' => 'Can edit Companies'],
            ['name' => 'delete-companies', 'module' => 'companies', 'label' => 'Delete Companies', 'description' => 'Can delete Companies'],
            ['name' => 'reset-password-companies', 'module' => 'companies', 'label' => 'Reset Password Companies', 'description' => 'Can reset password Companies'],
            ['name' => 'toggle-status-companies', 'module' => 'companies', 'label' => 'Change Status Companies', 'description' => 'Can change status companies'],
            ['name' => 'manage-plans-companies', 'module' => 'companies', 'label' => 'Manage Plan Companies', 'description' => 'Can manage plans companies'],
            ['name' => 'upgrade-plan-companies', 'module' => 'companies', 'label' => 'Upgrade Plan Companies', 'description' => 'Can upgrade plan of companies'],

            // Plan management
            ['name' => 'manage-plans', 'module' => 'plans', 'label' => 'Manage Plans', 'description' => 'Can manage subscription plans'],
            ['name' => 'manage-any-plans', 'module' => 'plans', 'label' => 'Manage All Plans', 'description' => 'Manage Any Plans'],
            ['name' => 'manage-own-plans', 'module' => 'plans', 'label' => 'Manage Own Plans', 'description' => 'Manage Limited Plans that is created by own'],
            ['name' => 'view-plans', 'module' => 'plans', 'label' => 'View Plans', 'description' => 'View Plans'],
            ['name' => 'create-plans', 'module' => 'plans', 'label' => 'Create Plans', 'description' => 'Can create subscription plans'],
            ['name' => 'edit-plans', 'module' => 'plans', 'label' => 'Edit Plans', 'description' => 'Can edit subscription plans'],
            ['name' => 'delete-plans', 'module' => 'plans', 'label' => 'Delete Plans', 'description' => 'Can delete subscription plans'],
            ['name' => 'request-plans', 'module' => 'plans', 'label' => 'Request Plans', 'description' => 'Can request subscription plans'],
            ['name' => 'trial-plans', 'module' => 'plans', 'label' => 'Trial Plans', 'description' => 'Can start trial for subscription plans'],
            ['name' => 'subscribe-plans', 'module' => 'plans', 'label' => 'Subscribe Plans', 'description' => 'Can subscribe to subscription plans'],


            // Coupon management
            ['name' => 'manage-coupons', 'module' => 'coupons', 'label' => 'Manage Coupons', 'description' => 'Can manage subscription Coupons'],
            ['name' => 'manage-any-coupons', 'module' => 'coupons', 'label' => 'Manage All Coupons', 'description' => 'Manage Any Coupons'],
            ['name' => 'manage-own-coupons', 'module' => 'coupons', 'label' => 'Manage Own Coupons', 'description' => 'Manage Limited Coupons that is created by own'],
            ['name' => 'view-coupons', 'module' => 'coupons', 'label' => 'View Coupons', 'description' => 'View Coupons'],
            ['name' => 'create-coupons', 'module' => 'coupons', 'label' => 'Create Coupons', 'description' => 'Can create subscription Coupons'],
            ['name' => 'edit-coupons', 'module' => 'coupons', 'label' => 'Edit Coupons', 'description' => 'Can edit subscription Coupons'],
            ['name' => 'delete-coupons', 'module' => 'coupons', 'label' => 'Delete Coupons', 'description' => 'Can delete subscription Coupons'],
            ['name' => 'toggle-status-coupons', 'module' => 'coupons', 'label' => 'Change Status Coupons', 'description' => 'Can change status Coupons'],

            // Plan Requests management
            ['name' => 'manage-plan-requests', 'module' => 'plan_requests', 'label' => 'Manage Plan Requests', 'description' => 'Can manage plan requests'],
            ['name' => 'view-plan-requests', 'module' => 'plan_requests', 'label' => 'View Plan Requests', 'description' => 'View Plan Requests'],
            ['name' => 'create-plan-requests', 'module' => 'plan_requests', 'label' => 'Create Plan Requests', 'description' => 'Can create plan requests'],
            ['name' => 'edit-plan-requests', 'module' => 'plan_requests', 'label' => 'Edit Plan Requests', 'description' => 'Can edit plan requests'],
            ['name' => 'delete-plan-requests', 'module' => 'plan_requests', 'label' => 'Delete Plan Requests', 'description' => 'Can delete plan requests'],
            ['name' => 'approve-plan-requests', 'module' => 'plan_requests', 'label' => 'Approve plan requests', 'description' => 'Can approve plan requests'],
            ['name' => 'reject-plan-requests', 'module' => 'plan_requests', 'label' => 'Reject plan requests', 'description' => 'Can reject plplan requests'],

            // Plan Orders management
            ['name' => 'manage-plan-orders', 'module' => 'plan_orders', 'label' => 'Manage Plan Orders', 'description' => 'Can manage plan orders'],
            ['name' => 'view-plan-orders', 'module' => 'plan_orders', 'label' => 'View Plan Orders', 'description' => 'View Plan Orders'],
            ['name' => 'create-plan-orders', 'module' => 'plan_orders', 'label' => 'Create Plan Orders', 'description' => 'Can create plan orders'],
            ['name' => 'edit-plan-orders', 'module' => 'plan_orders', 'label' => 'Edit Plan Orders', 'description' => 'Can edit plan orders'],
            ['name' => 'delete-plan-orders', 'module' => 'plan_orders', 'label' => 'Delete Plan Orders', 'description' => 'Can delete plan orders'],
            ['name' => 'approve-plan-orders', 'module' => 'plan_orders', 'label' => 'Approve Plan Orders', 'description' => 'Can approve plan orders'],
            ['name' => 'reject-plan-orders', 'module' => 'plan_orders', 'label' => 'Reject Plan Orders', 'description' => 'Can reject plan orders'],


            // Settings
            ['name' => 'manage-settings', 'module' => 'settings', 'label' => 'Manage Settings', 'description' => 'Can manage All settings'],
            ['name' => 'manage-system-settings', 'module' => 'settings', 'label' => 'Manage System Settings', 'description' => 'Can manage system settings'],
            ['name' => 'manage-email-settings', 'module' => 'settings', 'label' => 'Manage Email Settings', 'description' => 'Can manage email settings'],
            ['name' => 'manage-brand-settings', 'module' => 'settings', 'label' => 'Manage Brand Settings', 'description' => 'Can manage brand settings'],
            ['name' => 'manage-company-settings', 'module' => 'settings', 'label' => 'Manage Company Settings', 'description' => 'Can manage Company settings'],
            ['name' => 'manage-storage-settings', 'module' => 'settings', 'label' => 'Manage Storage Settings', 'description' => 'Can manage storage settings'],
            ['name' => 'manage-payment-settings', 'module' => 'settings', 'label' => 'Manage Payment Settings', 'description' => 'Can manage payment settings'],
            ['name' => 'manage-currency-settings', 'module' => 'settings', 'label' => 'Manage Currency Settings', 'description' => 'Can manage currency settings'],
            ['name' => 'manage-recaptcha-settings', 'module' => 'settings', 'label' => 'Manage ReCaptch Settings', 'description' => 'Can manage recaptcha settings'],
            ['name' => 'manage-chatgpt-settings', 'module' => 'settings', 'label' => 'Manage ChatGpt Settings', 'description' => 'Can manage chatgpt settings'],
            ['name' => 'manage-cookie-settings', 'module' => 'settings', 'label' => 'Manage Cookie(GDPR) Settings', 'description' => 'Can manage cookie settings'],
            ['name' => 'manage-seo-settings', 'module' => 'settings', 'label' => 'Manage Seo Settings', 'description' => 'Can manage seo settings'],
            ['name' => 'manage-cache-settings', 'module' => 'settings', 'label' => 'Manage Cache Settings', 'description' => 'Can manage cache settings'],
            ['name' => 'manage-account-settings', 'module' => 'settings', 'label' => 'Manage Account Settings', 'description' => 'Can manage account settings'],


            // Currency management
            ['name' => 'manage-currencies', 'module' => 'currencies', 'label' => 'Manage Currencies', 'description' => 'Can manage currencies'],
            ['name' => 'manage-any-currencies', 'module' => 'currencies', 'label' => 'Manage All currencies', 'description' => 'Manage Any currencies'],
            ['name' => 'manage-own-currencies', 'module' => 'currencies', 'label' => 'Manage Own currencies', 'description' => 'Manage Limited currencies that is created by own'],
            ['name' => 'view-currencies', 'module' => 'currencies', 'label' => 'View Currencies', 'description' => 'View Currencies'],
            ['name' => 'create-currencies', 'module' => 'currencies', 'label' => 'Create Currencies', 'description' => 'Can create currencies'],
            ['name' => 'edit-currencies', 'module' => 'currencies', 'label' => 'Edit Currencies', 'description' => 'Can edit currencies'],
            ['name' => 'delete-currencies', 'module' => 'currencies', 'label' => 'Delete Currencies', 'description' => 'Can delete currencies'],



            // Referral management
            ['name' => 'manage-referral', 'module' => 'referral', 'label' => 'Manage Referral', 'description' => 'Can manage referral program'],
            ['name' => 'manage-users-referral', 'module' => 'referral', 'label' => 'Manage User Referral', 'description' => 'Can manage user referral program'],
            ['name' => 'manage-setting-referral', 'module' => 'referral', 'label' => 'Manage Referral Setting', 'description' => 'Can manage Referral Setting'],
            ['name' => 'manage-payout-referral', 'module' => 'referral', 'label' => 'Manage Referral Payout', 'description' => 'Can manage Referral Payout program'],
            ['name' => 'approve-payout-referral', 'module' => 'referral', 'label' => 'Manage Referral', 'description' => 'Can approve payout request'],
            ['name' => 'reject-payout-referral', 'module' => 'referral', 'label' => 'Manage Referral', 'description' => 'Can approve payout request'],

            // Language management
            ['name' => 'manage-language', 'module' => 'language', 'label' => 'Manage Language', 'description' => 'Can manage language'],
            ['name' => 'edit-language', 'module' => 'language', 'label' => 'Edit Language', 'description' => 'Edit Language'],
            ['name' => 'view-language', 'module' => 'language', 'label' => 'View Language', 'description' => 'View Language'],

            // Media management
            ['name' => 'manage-media', 'module' => 'media', 'label' => 'Manage Media', 'description' => 'Can manage media'],
            ['name' => 'manage-any-media', 'module' => 'media', 'label' => 'Manage All Media', 'description' => 'Manage Any media'],
            ['name' => 'manage-own-media', 'module' => 'media', 'label' => 'Manage Own Media', 'description' => 'Manage Limited media that is created by own'],
            ['name' => 'create-media', 'module' => 'media', 'label' => 'Create media', 'description' => 'Create media'],
            ['name' => 'edit-media', 'module' => 'media', 'label' => 'Edit media', 'description' => 'Edit media'],
            ['name' => 'delete-media', 'module' => 'media', 'label' => 'Delete media', 'description' => 'Delete media'],
            ['name' => 'view-media', 'module' => 'media', 'label' => 'View media', 'description' => 'View media'],
            ['name' => 'download-media', 'module' => 'media', 'label' => 'Download media', 'description' => 'Download media'],

            // Media Directory management

            ['name' => 'manage-media-directories', 'module' => 'media_directories', 'label' => 'Manage Media Directory', 'description' => 'Can manage media directories'],
            ['name' => 'manage-any-media-directories', 'module' => 'media_directories', 'label' => 'Manage All Media Directory', 'description' => 'Manage any media directories'],
            ['name' => 'manage-own-media-directories', 'module' => 'media_directories', 'label' => 'Manage Own Media Directory', 'description' => 'Manage only media directories created by self'],
            ['name' => 'create-media-directories', 'module' => 'media_directories', 'label' => 'Create Media Directory', 'description' => 'Create new media directories'],
            ['name' => 'edit-media-directories', 'module' => 'media_directories', 'label' => 'Edit Media Directory', 'description' => 'Edit existing media directories'],
            ['name' => 'delete-media-directories', 'module' => 'media_directories', 'label' => 'Delete Media Directory', 'description' => 'Delete media directories files'],



            // Webhook management
            ['name' => 'manage-webhook-settings', 'module' => 'settings', 'label' => 'Manage Webhook Settings', 'description' => 'Can manage webhook settings'],
            // Landing Page management
            ['name' => 'manage-landing-page', 'module' => 'landing_page', 'label' => 'Manage Landing Page', 'description' => 'Can manage landing page'],
            ['name' => 'view-landing-page', 'module' => 'landing_page', 'label' => 'View Landing Page', 'description' => 'View landing page'],
            ['name' => 'edit-landing-page', 'module' => 'landing_page', 'label' => 'Edit Landing Page', 'description' => 'Edit landing page'],

            // Branch management
            ['name' => 'manage-branches', 'module' => 'branches', 'label' => 'Manage Branches', 'description' => 'Can manage branches'],
            ['name' => 'manage-any-branches', 'module' => 'branches', 'label' => 'Manage All Branches', 'description' => 'Manage Any Branches'],
            ['name' => 'manage-own-branches', 'module' => 'branches', 'label' => 'Manage Own Branches', 'description' => 'Manage Limited Branches that is created by own'],
            ['name' => 'view-branches', 'module' => 'branches', 'label' => 'View Branches', 'description' => 'View Branches'],
            ['name' => 'create-branches', 'module' => 'branches', 'label' => 'Create Branches', 'description' => 'Can create branches'],
            ['name' => 'edit-branches', 'module' => 'branches', 'label' => 'Edit Branches', 'description' => 'Can edit branches'],
            ['name' => 'delete-branches', 'module' => 'branches', 'label' => 'Delete Branches', 'description' => 'Can delete branches'],
            ['name' => 'toggle-status-branches', 'module' => 'branches', 'label' => 'Toggle Status Branches', 'description' => 'Can toggle status of branches'],

            // Department management
            ['name' => 'manage-departments', 'module' => 'departments', 'label' => 'Manage Departments', 'description' => 'Can manage departments'],
            ['name' => 'manage-any-departments', 'module' => 'departments', 'label' => 'Manage All Departments', 'description' => 'Manage Any Departments'],
            ['name' => 'manage-own-departments', 'module' => 'departments', 'label' => 'Manage Own Departments', 'description' => 'Manage Limited Departments that is created by own'],
            ['name' => 'view-departments', 'module' => 'departments', 'label' => 'View Departments', 'description' => 'View Departments'],
            ['name' => 'create-departments', 'module' => 'departments', 'label' => 'Create Departments', 'description' => 'Can create departments'],
            ['name' => 'edit-departments', 'module' => 'departments', 'label' => 'Edit Departments', 'description' => 'Can edit departments'],
            ['name' => 'delete-departments', 'module' => 'departments', 'label' => 'Delete Departments', 'description' => 'Can delete departments'],
            ['name' => 'toggle-status-departments', 'module' => 'departments', 'label' => 'Toggle Status Departments', 'description' => 'Can toggle status of departments'],

            // Designation management
            ['name' => 'manage-designations', 'module' => 'designations', 'label' => 'Manage Designations', 'description' => 'Can manage designations'],
            ['name' => 'manage-any-designations', 'module' => 'designations', 'label' => 'Manage All Designations', 'description' => 'Manage Any Designations'],
            ['name' => 'manage-own-designations', 'module' => 'designations', 'label' => 'Manage Own Designations', 'description' => 'Manage Limited Designations that is created by own'],
            ['name' => 'view-designations', 'module' => 'designations', 'label' => 'View Designations', 'description' => 'View Designations'],
            ['name' => 'create-designations', 'module' => 'designations', 'label' => 'Create Designations', 'description' => 'Can create designations'],
            ['name' => 'edit-designations', 'module' => 'designations', 'label' => 'Edit Designations', 'description' => 'Can edit designations'],
            ['name' => 'delete-designations', 'module' => 'designations', 'label' => 'Delete Designations', 'description' => 'Can delete designations'],
            ['name' => 'toggle-status-designations', 'module' => 'designations', 'label' => 'Toggle Status Designations', 'description' => 'Can toggle status of designations'],

            // Document Type management
            ['name' => 'manage-document-types', 'module' => 'document_types', 'label' => 'Manage Document Types', 'description' => 'Can manage document types'],
            ['name' => 'manage-any-document-types', 'module' => 'document_types', 'label' => 'Manage All Document Types', 'description' => 'Manage Any Document Types'],
            ['name' => 'manage-own-document-types', 'module' => 'document_types', 'label' => 'Manage Own Document Types', 'description' => 'Manage Limited Document Types that is created by own'],
            ['name' => 'view-document-types', 'module' => 'document_types', 'label' => 'View Document Types', 'description' => 'View Document Types'],
            ['name' => 'create-document-types', 'module' => 'document_types', 'label' => 'Create Document Types', 'description' => 'Can create document types'],
            ['name' => 'edit-document-types', 'module' => 'document_types', 'label' => 'Edit Document Types', 'description' => 'Can edit document types'],
            ['name' => 'delete-document-types', 'module' => 'document_types', 'label' => 'Delete Document Types', 'description' => 'Can delete document types'],

            // Employee management
            ['name' => 'manage-employees', 'module' => 'employees', 'label' => 'Manage Employees', 'description' => 'Can manage employees'],
            ['name' => 'manage-any-employees', 'module' => 'employees', 'label' => 'Manage All Employees', 'description' => 'Manage Any Employees'],
            ['name' => 'manage-own-employees', 'module' => 'employees', 'label' => 'Manage Own Employees', 'description' => 'Manage Limited Employees that is created by own'],
            ['name' => 'view-employees', 'module' => 'employees', 'label' => 'View Employees', 'description' => 'View Employees'],
            ['name' => 'create-employees', 'module' => 'employees', 'label' => 'Create Employees', 'description' => 'Can create employees'],
            ['name' => 'edit-employees', 'module' => 'employees', 'label' => 'Edit Employees', 'description' => 'Can edit employees'],
            ['name' => 'delete-employees', 'module' => 'employees', 'label' => 'Delete Employees', 'description' => 'Can delete employees'],

            // Award Type management
            ['name' => 'manage-award-types', 'module' => 'award_types', 'label' => 'Manage Award Types', 'description' => 'Can manage award types'],
            ['name' => 'manage-any-award-types', 'module' => 'award_types', 'label' => 'Manage All Award Types', 'description' => 'Manage Any Award Types'],
            ['name' => 'manage-own-award-types', 'module' => 'award_types', 'label' => 'Manage Own Award Types', 'description' => 'Manage Limited Award Types that is created by own'],
            ['name' => 'view-award-types', 'module' => 'award_types', 'label' => 'View Award Types', 'description' => 'View Award Types'],
            ['name' => 'create-award-types', 'module' => 'award_types', 'label' => 'Create Award Types', 'description' => 'Can create award types'],
            ['name' => 'edit-award-types', 'module' => 'award_types', 'label' => 'Edit Award Types', 'description' => 'Can edit award types'],
            ['name' => 'delete-award-types', 'module' => 'award_types', 'label' => 'Delete Award Types', 'description' => 'Can delete award types'],

            // Award management
            ['name' => 'manage-awards', 'module' => 'awards', 'label' => 'Manage Awards', 'description' => 'Can manage awards'],
            ['name' => 'manage-any-awards', 'module' => 'awards', 'label' => 'Manage All Awards', 'description' => 'Manage Any Awards'],
            ['name' => 'manage-own-awards', 'module' => 'awards', 'label' => 'Manage Own Awards', 'description' => 'Manage Limited Awards that is created by own'],
            ['name' => 'view-awards', 'module' => 'awards', 'label' => 'View Awards', 'description' => 'View Awards'],
            ['name' => 'create-awards', 'module' => 'awards', 'label' => 'Create Awards', 'description' => 'Can create awards'],
            ['name' => 'edit-awards', 'module' => 'awards', 'label' => 'Edit Awards', 'description' => 'Can edit awards'],
            ['name' => 'delete-awards', 'module' => 'awards', 'label' => 'Delete Awards', 'description' => 'Can delete awards'],

            // Promotion management
            ['name' => 'manage-promotions', 'module' => 'promotions', 'label' => 'Manage Promotions', 'description' => 'Can manage promotions'],
            ['name' => 'manage-any-promotions', 'module' => 'promotions', 'label' => 'Manage All Promotions', 'description' => 'Manage Any Promotions'],
            ['name' => 'manage-own-promotions', 'module' => 'promotions', 'label' => 'Manage Own Promotions', 'description' => 'Manage Limited Promotions that is created by own'],
            ['name' => 'view-promotions', 'module' => 'promotions', 'label' => 'View Promotions', 'description' => 'View Promotions'],
            ['name' => 'create-promotions', 'module' => 'promotions', 'label' => 'Create Promotions', 'description' => 'Can create promotions'],
            ['name' => 'edit-promotions', 'module' => 'promotions', 'label' => 'Edit Promotions', 'description' => 'Can edit promotions'],
            ['name' => 'delete-promotions', 'module' => 'promotions', 'label' => 'Delete Promotions', 'description' => 'Can delete promotions'],
            ['name' => 'approve-promotions', 'module' => 'promotions', 'label' => 'Approve Promotions', 'description' => 'Can approve promotions'],
            ['name' => 'reject-promotions', 'module' => 'promotions', 'label' => 'Reject Promotions', 'description' => 'Can reject promotions'],

            // Resignation management
            ['name' => 'manage-resignations', 'module' => 'resignations', 'label' => 'Manage Resignations', 'description' => 'Can manage resignations'],
            ['name' => 'manage-any-resignations', 'module' => 'resignations', 'label' => 'Manage All Resignations', 'description' => 'Manage Any Resignations'],
            ['name' => 'manage-own-resignations', 'module' => 'resignations', 'label' => 'Manage Own Resignations', 'description' => 'Manage Limited Resignations that is created by own'],
            ['name' => 'view-resignations', 'module' => 'resignations', 'label' => 'View Resignations', 'description' => 'View Resignations'],
            ['name' => 'create-resignations', 'module' => 'resignations', 'label' => 'Create Resignations', 'description' => 'Can create resignations'],
            ['name' => 'edit-resignations', 'module' => 'resignations', 'label' => 'Edit Resignations', 'description' => 'Can edit resignations'],
            ['name' => 'delete-resignations', 'module' => 'resignations', 'label' => 'Delete Resignations', 'description' => 'Can delete resignations'],
            ['name' => 'approve-resignations', 'module' => 'resignations', 'label' => 'Approve Resignations', 'description' => 'Can approve resignations'],
            ['name' => 'reject-resignations', 'module' => 'resignations', 'label' => 'Reject Resignations', 'description' => 'Can reject resignations'],

            // Termination management
            ['name' => 'manage-terminations', 'module' => 'terminations', 'label' => 'Manage Terminations', 'description' => 'Can manage terminations'],
            ['name' => 'manage-any-terminations', 'module' => 'terminations', 'label' => 'Manage All Terminations', 'description' => 'Manage Any Terminations'],
            ['name' => 'manage-own-terminations', 'module' => 'terminations', 'label' => 'Manage Own Terminations', 'description' => 'Manage Limited Terminations that is created by own'],
            ['name' => 'view-terminations', 'module' => 'terminations', 'label' => 'View Terminations', 'description' => 'View Terminations'],
            ['name' => 'create-terminations', 'module' => 'terminations', 'label' => 'Create Terminations', 'description' => 'Can create terminations'],
            ['name' => 'edit-terminations', 'module' => 'terminations', 'label' => 'Edit Terminations', 'description' => 'Can edit terminations'],
            ['name' => 'delete-terminations', 'module' => 'terminations', 'label' => 'Delete Terminations', 'description' => 'Can delete terminations'],
            ['name' => 'approve-terminations', 'module' => 'terminations', 'label' => 'Approve Terminations', 'description' => 'Can approve terminations'],
            ['name' => 'reject-terminations', 'module' => 'terminations', 'label' => 'Reject Terminations', 'description' => 'Can reject terminations'],

            // Warning management
            ['name' => 'manage-warnings', 'module' => 'warnings', 'label' => 'Manage Warnings', 'description' => 'Can manage warnings'],
            ['name' => 'manage-any-warnings', 'module' => 'warnings', 'label' => 'Manage All Warnings', 'description' => 'Manage Any Warnings'],
            ['name' => 'manage-own-warnings', 'module' => 'warnings', 'label' => 'Manage Own Warnings', 'description' => 'Manage Limited Warnings that is created by own'],
            ['name' => 'view-warnings', 'module' => 'warnings', 'label' => 'View Warnings', 'description' => 'View Warnings'],
            ['name' => 'create-warnings', 'module' => 'warnings', 'label' => 'Create Warnings', 'description' => 'Can create warnings'],
            ['name' => 'edit-warnings', 'module' => 'warnings', 'label' => 'Edit Warnings', 'description' => 'Can edit warnings'],
            ['name' => 'delete-warnings', 'module' => 'warnings', 'label' => 'Delete Warnings', 'description' => 'Can delete warnings'],
            ['name' => 'approve-warnings', 'module' => 'warnings', 'label' => 'Approve Warnings', 'description' => 'Can approve warnings'],
            ['name' => 'acknowledge-warnings', 'module' => 'warnings', 'label' => 'Acknowledge Warnings', 'description' => 'Can acknowledge warnings'],

            // Trip management
            ['name' => 'manage-trips', 'module' => 'trips', 'label' => 'Manage Trips', 'description' => 'Can manage trips'],
            ['name' => 'manage-any-trips', 'module' => 'trips', 'label' => 'Manage All Trips', 'description' => 'Manage Any Trips'],
            ['name' => 'manage-own-trips', 'module' => 'trips', 'label' => 'Manage Own Trips', 'description' => 'Manage Limited Trips that is created by own'],
            ['name' => 'view-trips', 'module' => 'trips', 'label' => 'View Trips', 'description' => 'View Trips'],
            ['name' => 'create-trips', 'module' => 'trips', 'label' => 'Create Trips', 'description' => 'Can create trips'],
            ['name' => 'edit-trips', 'module' => 'trips', 'label' => 'Edit Trips', 'description' => 'Can edit trips'],
            ['name' => 'delete-trips', 'module' => 'trips', 'label' => 'Delete Trips', 'description' => 'Can delete trips'],
            ['name' => 'approve-trips', 'module' => 'trips', 'label' => 'Approve Trips', 'description' => 'Can approve trips'],
            ['name' => 'manage-trip-expenses', 'module' => 'trips', 'label' => 'Manage Trip Expenses', 'description' => 'Can manage trip expenses'],
            ['name' => 'approve-trip-expenses', 'module' => 'trips', 'label' => 'Approve Trip Expenses', 'description' => 'Can approve trip expenses'],

            // Complaint management
            ['name' => 'manage-complaints', 'module' => 'complaints', 'label' => 'Manage Complaints', 'description' => 'Can manage complaints'],
            ['name' => 'manage-any-complaints', 'module' => 'complaints', 'label' => 'Manage All Complaints', 'description' => 'Manage Any Complaints'],
            ['name' => 'manage-own-complaints', 'module' => 'complaints', 'label' => 'Manage Own Complaints', 'description' => 'Manage Limited Complaints that is created by own'],
            ['name' => 'view-complaints', 'module' => 'complaints', 'label' => 'View Complaints', 'description' => 'View Complaints'],
            ['name' => 'create-complaints', 'module' => 'complaints', 'label' => 'Create Complaints', 'description' => 'Can create complaints'],
            ['name' => 'edit-complaints', 'module' => 'complaints', 'label' => 'Edit Complaints', 'description' => 'Can edit complaints'],
            ['name' => 'delete-complaints', 'module' => 'complaints', 'label' => 'Delete Complaints', 'description' => 'Can delete complaints'],
            ['name' => 'assign-complaints', 'module' => 'complaints', 'label' => 'Assign Complaints', 'description' => 'Can assign complaints to HR personnel'],
            ['name' => 'resolve-complaints', 'module' => 'complaints', 'label' => 'Resolve Complaints', 'description' => 'Can resolve complaints'],

            // Employee Transfer management
            ['name' => 'manage-employee-transfers', 'module' => 'transfers', 'label' => 'Manage Transfers', 'description' => 'Can manage employee transfers'],
            ['name' => 'manage-any-employee-transfers', 'module' => 'transfers', 'label' => 'Manage All Transfers', 'description' => 'Manage Any Transfers'],
            ['name' => 'manage-own-employee-transfers', 'module' => 'transfers', 'label' => 'Manage Own Transfers', 'description' => 'Manage Limited Transfers that is created by own'],
            ['name' => 'view-employee-transfers', 'module' => 'transfers', 'label' => 'View Transfers', 'description' => 'View employee transfers'],
            ['name' => 'create-employee-transfers', 'module' => 'transfers', 'label' => 'Create Transfers', 'description' => 'Can create employee transfers'],
            ['name' => 'edit-employee-transfers', 'module' => 'transfers', 'label' => 'Edit Transfers', 'description' => 'Can edit employee transfers'],
            ['name' => 'delete-employee-transfers', 'module' => 'transfers', 'label' => 'Delete Transfers', 'description' => 'Can delete employee transfers'],
            ['name' => 'approve-employee-transfers', 'module' => 'transfers', 'label' => 'Approve Transfers', 'description' => 'Can approve employee transfers'],
            ['name' => 'reject-employee-transfers', 'module' => 'transfers', 'label' => 'Reject Transfers', 'description' => 'Can reject employee transfers'],

            // Holiday management
            ['name' => 'manage-holidays', 'module' => 'holidays', 'label' => 'Manage Holidays', 'description' => 'Can manage holidays'],
            ['name' => 'manage-any-holidays', 'module' => 'holidays', 'label' => 'Manage All Holidays', 'description' => 'Manage Any Holidays'],
            ['name' => 'manage-own-holidays', 'module' => 'holidays', 'label' => 'Manage Own Holidays', 'description' => 'Manage Limited Holidays that is created by own'],
            ['name' => 'view-holidays', 'module' => 'holidays', 'label' => 'View Holidays', 'description' => 'View holidays'],
            ['name' => 'create-holidays', 'module' => 'holidays', 'label' => 'Create Holidays', 'description' => 'Can create holidays'],
            ['name' => 'edit-holidays', 'module' => 'holidays', 'label' => 'Edit Holidays', 'description' => 'Can edit holidays'],
            ['name' => 'delete-holidays', 'module' => 'holidays', 'label' => 'Delete Holidays', 'description' => 'Can delete holidays'],

            // Announcement management
            ['name' => 'manage-announcements', 'module' => 'announcements', 'label' => 'Manage Announcements', 'description' => 'Can manage announcements'],
            ['name' => 'manage-any-announcements', 'module' => 'announcements', 'label' => 'Manage All Announcements', 'description' => 'Manage Any Announcements'],
            ['name' => 'manage-own-announcements', 'module' => 'announcements', 'label' => 'Manage Own Announcements', 'description' => 'Manage Limited Announcements that is created by own'],
            ['name' => 'view-announcements', 'module' => 'announcements', 'label' => 'View Announcements', 'description' => 'View announcements'],
            ['name' => 'create-announcements', 'module' => 'announcements', 'label' => 'Create Announcements', 'description' => 'Can create announcements'],
            ['name' => 'edit-announcements', 'module' => 'announcements', 'label' => 'Edit Announcements', 'description' => 'Can edit announcements'],
            ['name' => 'delete-announcements', 'module' => 'announcements', 'label' => 'Delete Announcements', 'description' => 'Can delete announcements'],

            // Asset Type management
            ['name' => 'manage-asset-types', 'module' => 'asset-types', 'label' => 'Manage Asset Types', 'description' => 'Can manage asset types'],
            ['name' => 'manage-any-asset-types', 'module' => 'asset-types', 'label' => 'Manage All Asset Types', 'description' => 'Manage Any Asset Types'],
            ['name' => 'manage-own-asset-types', 'module' => 'asset-types', 'label' => 'Manage Own Asset Types', 'description' => 'Manage Limited Asset Types that is created by own'],
            ['name' => 'view-asset-types', 'module' => 'asset-types', 'label' => 'View Asset Types', 'description' => 'View asset types'],
            ['name' => 'create-asset-types', 'module' => 'asset-types', 'label' => 'Create Asset Types', 'description' => 'Can create asset types'],
            ['name' => 'edit-asset-types', 'module' => 'asset-types', 'label' => 'Edit Asset Types', 'description' => 'Can edit asset types'],
            ['name' => 'delete-asset-types', 'module' => 'asset-types', 'label' => 'Delete Asset Types', 'description' => 'Can delete asset types'],

            // Asset management
            ['name' => 'manage-assets', 'module' => 'assets', 'label' => 'Manage Assets', 'description' => 'Can manage assets'],
            ['name' => 'manage-any-assets', 'module' => 'assets', 'label' => 'Manage All Assets', 'description' => 'Manage Any Assets'],
            ['name' => 'manage-own-assets', 'module' => 'assets', 'label' => 'Manage Own Assets', 'description' => 'Manage Limited Assets that is created by own'],
            ['name' => 'view-assets', 'module' => 'assets', 'label' => 'View Assets', 'description' => 'View assets'],
            ['name' => 'create-assets', 'module' => 'assets', 'label' => 'Create Assets', 'description' => 'Can create assets'],
            ['name' => 'edit-assets', 'module' => 'assets', 'label' => 'Edit Assets', 'description' => 'Can edit assets'],
            ['name' => 'delete-assets', 'module' => 'assets', 'label' => 'Delete Assets', 'description' => 'Can delete assets'],
            ['name' => 'assign-assets', 'module' => 'assets', 'label' => 'Assign Assets', 'description' => 'Can assign assets to employees'],
            ['name' => 'manage-asset-maintenance', 'module' => 'assets', 'label' => 'Manage Asset Maintenance', 'description' => 'Can manage asset maintenance'],

            // Training Type management
            ['name' => 'manage-training-types', 'module' => 'training-types', 'label' => 'Manage Training Types', 'description' => 'Can manage training types'],
            ['name' => 'manage-any-training-types', 'module' => 'training-types', 'label' => 'Manage All Training Types', 'description' => 'Manage Any Training Types'],
            ['name' => 'manage-own-training-types', 'module' => 'training-types', 'label' => 'Manage Own Training Types', 'description' => 'Manage Limited Training Types that is created by own'],
            ['name' => 'view-training-types', 'module' => 'training-types', 'label' => 'View Training Types', 'description' => 'View training types'],
            ['name' => 'create-training-types', 'module' => 'training-types', 'label' => 'Create Training Types', 'description' => 'Can create training types'],
            ['name' => 'edit-training-types', 'module' => 'training-types', 'label' => 'Edit Training Types', 'description' => 'Can edit training types'],
            ['name' => 'delete-training-types', 'module' => 'training-types', 'label' => 'Delete Training Types', 'description' => 'Can delete training types'],

            // Training Program management
            ['name' => 'manage-training-programs', 'module' => 'training-programs', 'label' => 'Manage Training Programs', 'description' => 'Can manage training programs'],
            ['name' => 'manage-any-training-programs', 'module' => 'training-programs', 'label' => 'Manage All Training Programs', 'description' => 'Manage Any Training Programs'],
            ['name' => 'manage-own-training-programs', 'module' => 'training-programs', 'label' => 'Manage Own Training Programs', 'description' => 'Manage Limited Training Programs that is created by own'],
            ['name' => 'view-training-programs', 'module' => 'training-programs', 'label' => 'View Training Programs', 'description' => 'View training programs'],
            ['name' => 'create-training-programs', 'module' => 'training-programs', 'label' => 'Create Training Programs', 'description' => 'Can create training programs'],
            ['name' => 'edit-training-programs', 'module' => 'training-programs', 'label' => 'Edit Training Programs', 'description' => 'Can edit training programs'],
            ['name' => 'delete-training-programs', 'module' => 'training-programs', 'label' => 'Delete Training Programs', 'description' => 'Can delete training programs'],

            // Training Session management
            ['name' => 'manage-training-sessions', 'module' => 'training-sessions', 'label' => 'Manage Training Sessions', 'description' => 'Can manage training sessions'],
            ['name' => 'manage-any-training-sessions', 'module' => 'training-sessions', 'label' => 'Manage All Training Sessions', 'description' => 'Manage Any Training Sessions'],
            ['name' => 'manage-own-training-sessions', 'module' => 'training-sessions', 'label' => 'Manage Own Training Sessions', 'description' => 'Manage Limited Training Sessions that is created by own'],
            ['name' => 'view-training-sessions', 'module' => 'training-sessions', 'label' => 'View Training Sessions', 'description' => 'View training sessions'],
            ['name' => 'create-training-sessions', 'module' => 'training-sessions', 'label' => 'Create Training Sessions', 'description' => 'Can create training sessions'],
            ['name' => 'edit-training-sessions', 'module' => 'training-sessions', 'label' => 'Edit Training Sessions', 'description' => 'Can edit training sessions'],
            ['name' => 'delete-training-sessions', 'module' => 'training-sessions', 'label' => 'Delete Training Sessions', 'description' => 'Can delete training sessions'],
            ['name' => 'manage-attendance', 'module' => 'training-sessions', 'label' => 'Manage Attendance', 'description' => 'Can manage training session attendance'],

            // Employee Training management
            ['name' => 'manage-employee-trainings', 'module' => 'employee-trainings', 'label' => 'Manage Employee Trainings', 'description' => 'Can manage employee trainings'],
            ['name' => 'manage-any-employee-trainings', 'module' => 'employee-trainings', 'label' => 'Manage All Employee Trainings', 'description' => 'Manage Any Employee Trainings'],
            ['name' => 'manage-own-employee-trainings', 'module' => 'employee-trainings', 'label' => 'Manage Own Employee Trainings', 'description' => 'Manage Limited Employee Trainings that is created by own'],
            ['name' => 'view-employee-trainings', 'module' => 'employee-trainings', 'label' => 'View Employee Trainings', 'description' => 'View employee trainings'],
            ['name' => 'create-employee-trainings', 'module' => 'employee-trainings', 'label' => 'Create Employee Trainings', 'description' => 'Can create employee trainings'],
            ['name' => 'edit-employee-trainings', 'module' => 'employee-trainings', 'label' => 'Edit Employee Trainings', 'description' => 'Can edit employee trainings'],
            ['name' => 'delete-employee-trainings', 'module' => 'employee-trainings', 'label' => 'Delete Employee Trainings', 'description' => 'Can delete employee trainings'],
            ['name' => 'assign-trainings', 'module' => 'employee-trainings', 'label' => 'Assign Trainings', 'description' => 'Can assign trainings to employees'],
            ['name' => 'manage-assessments', 'module' => 'employee-trainings', 'label' => 'Manage Assessments', 'description' => 'Can manage training assessments'],
            ['name' => 'record-assessment-results', 'module' => 'employee-trainings', 'label' => 'Record Assessment Results', 'description' => 'Can record training assessment results'],


            // New Module Permissions

            ['name' => 'manage-performance-indicator-categories', 'module' => 'performance_indicator_categories', 'label' => 'Manage Performance Indicator Categories', 'description' => 'Can manage performance indicator categories'],
            ['name' => 'manage-any-performance-indicator-categories', 'module' => 'performance_indicator_categories', 'label' => 'Manage All Performance Indicator Categories', 'description' => 'Manage Any Performance Indicator Categories'],
            ['name' => 'manage-own-performance-indicator-categories', 'module' => 'performance_indicator_categories', 'label' => 'Manage Own Performance Indicator Categories', 'description' => 'Manage Limited Performance Indicator Categories that is created by own'],
            ['name' => 'view-performance-indicator-categories', 'module' => 'performance_indicator_categories', 'label' => 'View Performance Indicator Categories', 'description' => 'View Performance Indicator Categories'],
            ['name' => 'create-performance-indicator-categories', 'module' => 'performance_indicator_categories', 'label' => 'Create Performance Indicator Categories', 'description' => 'Can create performance indicator categories'],
            ['name' => 'edit-performance-indicator-categories', 'module' => 'performance_indicator_categories', 'label' => 'Edit Performance Indicator Categories', 'description' => 'Can edit performance indicator categories'],
            ['name' => 'delete-performance-indicator-categories', 'module' => 'performance_indicator_categories', 'label' => 'Delete Performance Indicator Categories', 'description' => 'Can delete performance indicator categories'],

            // Performance Indicators
            ['name' => 'manage-performance-indicators', 'module' => 'performance_indicators', 'label' => 'Manage Performance Indicators', 'description' => 'Can manage performance indicators'],
            ['name' => 'manage-any-performance-indicators', 'module' => 'performance_indicators', 'label' => 'Manage All Performance Indicators', 'description' => 'Manage Any Performance Indicators'],
            ['name' => 'manage-own-performance-indicators', 'module' => 'performance_indicators', 'label' => 'Manage Own Performance Indicators', 'description' => 'Manage Limited Performance Indicators that is created by own'],
            ['name' => 'view-performance-indicators', 'module' => 'performance_indicators', 'label' => 'View Performance Indicators', 'description' => 'View Performance Indicators'],
            ['name' => 'create-performance-indicators', 'module' => 'performance_indicators', 'label' => 'Create Performance Indicators', 'description' => 'Can create performance indicators'],
            ['name' => 'edit-performance-indicators', 'module' => 'performance_indicators', 'label' => 'Edit Performance Indicators', 'description' => 'Can edit performance indicators'],
            ['name' => 'delete-performance-indicators', 'module' => 'performance_indicators', 'label' => 'Delete Performance Indicators', 'description' => 'Can delete performance indicators'],

            // Goal Types
            ['name' => 'manage-goal-types', 'module' => 'goal_types', 'label' => 'Manage Goal Types', 'description' => 'Can manage goal types'],
            ['name' => 'manage-any-goal-types', 'module' => 'goal_types', 'label' => 'Manage All Goal Types', 'description' => 'Manage Any Goal Types'],
            ['name' => 'manage-own-goal-types', 'module' => 'goal_types', 'label' => 'Manage Own Goal Types', 'description' => 'Manage Limited Goal Types that is created by own'],
            ['name' => 'view-goal-types', 'module' => 'goal_types', 'label' => 'View Goal Types', 'description' => 'View Goal Types'],
            ['name' => 'create-goal-types', 'module' => 'goal_types', 'label' => 'Create Goal Types', 'description' => 'Can create goal types'],
            ['name' => 'edit-goal-types', 'module' => 'goal_types', 'label' => 'Edit Goal Types', 'description' => 'Can edit goal types'],
            ['name' => 'delete-goal-types', 'module' => 'goal_types', 'label' => 'Delete Goal Types', 'description' => 'Can delete goal types'],

            // Employee Goals
            ['name' => 'manage-employee-goals', 'module' => 'employee_goals', 'label' => 'Manage Employee Goals', 'description' => 'Can manage employee goals'],
            ['name' => 'manage-any-employee-goals', 'module' => 'employee_goals', 'label' => 'Manage All Employee Goals', 'description' => 'Manage Any Employee Goals'],
            ['name' => 'manage-own-employee-goals', 'module' => 'employee_goals', 'label' => 'Manage Own Employee Goals', 'description' => 'Manage Limited Employee Goals that is created by own'],
            ['name' => 'view-employee-goals', 'module' => 'employee_goals', 'label' => 'View Employee Goals', 'description' => 'View Employee Goals'],
            ['name' => 'create-employee-goals', 'module' => 'employee_goals', 'label' => 'Create Employee Goals', 'description' => 'Can create employee goals'],
            ['name' => 'edit-employee-goals', 'module' => 'employee_goals', 'label' => 'Edit Employee Goals', 'description' => 'Can edit employee goals'],
            ['name' => 'delete-employee-goals', 'module' => 'employee_goals', 'label' => 'Delete Employee Goals', 'description' => 'Can delete employee goals'],

            // Review Cycles
            ['name' => 'manage-review-cycles', 'module' => 'review_cycles', 'label' => 'Manage Review Cycles', 'description' => 'Can manage review cycles'],
            ['name' => 'manage-any-review-cycles', 'module' => 'review_cycles', 'label' => 'Manage All Review Cycles', 'description' => 'Manage Any Review Cycles'],
            ['name' => 'manage-own-review-cycles', 'module' => 'review_cycles', 'label' => 'Manage Own Review Cycles', 'description' => 'Manage Limited Review Cycles that is created by own'],
            ['name' => 'view-review-cycles', 'module' => 'review_cycles', 'label' => 'View Review Cycles', 'description' => 'View Review Cycles'],
            ['name' => 'create-review-cycles', 'module' => 'review_cycles', 'label' => 'Create Review Cycles', 'description' => 'Can create review cycles'],
            ['name' => 'edit-review-cycles', 'module' => 'review_cycles', 'label' => 'Edit Review Cycles', 'description' => 'Can edit review cycles'],
            ['name' => 'delete-review-cycles', 'module' => 'review_cycles', 'label' => 'Delete Review Cycles', 'description' => 'Can delete review cycles'],

            // Employee Reviews
            ['name' => 'manage-employee-reviews', 'module' => 'employee_reviews', 'label' => 'Manage Employee Reviews', 'description' => 'Can manage employee reviews'],
            ['name' => 'manage-any-employee-reviews', 'module' => 'employee_reviews', 'label' => 'Manage All Employee Reviews', 'description' => 'Manage Any Employee Reviews'],
            ['name' => 'manage-own-employee-reviews', 'module' => 'employee_reviews', 'label' => 'Manage Own Employee Reviews', 'description' => 'Manage Limited Employee Reviews that is created by own'],
            ['name' => 'view-employee-reviews', 'module' => 'employee_reviews', 'label' => 'View Employee Reviews', 'description' => 'View Employee Reviews'],
            ['name' => 'create-employee-reviews', 'module' => 'employee_reviews', 'label' => 'Create Employee Reviews', 'description' => 'Can create employee reviews'],
            ['name' => 'edit-employee-reviews', 'module' => 'employee_reviews', 'label' => 'Edit Employee Reviews', 'description' => 'Can edit employee reviews'],
            ['name' => 'delete-employee-reviews', 'module' => 'employee_reviews', 'label' => 'Delete Employee Reviews', 'description' => 'Can delete employee reviews'],

            // Job Categories management
            ['name' => 'manage-job-categories', 'module' => 'job_categories', 'label' => 'Manage Job Categories', 'description' => 'Can manage job categories'],
            ['name' => 'manage-any-job-categories', 'module' => 'job_categories', 'label' => 'Manage All Job Categories', 'description' => 'Manage Any Job Categories'],
            ['name' => 'manage-own-job-categories', 'module' => 'job_categories', 'label' => 'Manage Own Job Categories', 'description' => 'Manage Limited Job Categories that is created by own'],
            ['name' => 'view-job-categories', 'module' => 'job_categories', 'label' => 'View Job Categories', 'description' => 'View Job Categories'],
            ['name' => 'create-job-categories', 'module' => 'job_categories', 'label' => 'Create Job Categories', 'description' => 'Can create job categories'],
            ['name' => 'edit-job-categories', 'module' => 'job_categories', 'label' => 'Edit Job Categories', 'description' => 'Can edit job categories'],
            ['name' => 'delete-job-categories', 'module' => 'job_categories', 'label' => 'Delete Job Categories', 'description' => 'Can delete job categories'],

            // Job Requisitions management
            ['name' => 'manage-job-requisitions', 'module' => 'job_requisitions', 'label' => 'Manage Job Requisitions', 'description' => 'Can manage job requisitions'],
            ['name' => 'manage-any-job-requisitions', 'module' => 'job_requisitions', 'label' => 'Manage All Job Requisitions', 'description' => 'Manage Any Job Requisitions'],
            ['name' => 'manage-own-job-requisitions', 'module' => 'job_requisitions', 'label' => 'Manage Own Job Requisitions', 'description' => 'Manage Limited Job Requisitions that is created by own'],
            ['name' => 'view-job-requisitions', 'module' => 'job_requisitions', 'label' => 'View Job Requisitions', 'description' => 'View Job Requisitions'],
            ['name' => 'create-job-requisitions', 'module' => 'job_requisitions', 'label' => 'Create Job Requisitions', 'description' => 'Can create job requisitions'],
            ['name' => 'edit-job-requisitions', 'module' => 'job_requisitions', 'label' => 'Edit Job Requisitions', 'description' => 'Can edit job requisitions'],
            ['name' => 'delete-job-requisitions', 'module' => 'job_requisitions', 'label' => 'Delete Job Requisitions', 'description' => 'Can delete job requisitions'],
            ['name' => 'approve-job-requisitions', 'module' => 'job_requisitions', 'label' => 'Approve Job Requisitions', 'description' => 'Can approve job requisitions'],

            // Job Types management
            ['name' => 'manage-job-types', 'module' => 'job_types', 'label' => 'Manage Job Types', 'description' => 'Can manage job types'],
            ['name' => 'manage-any-job-types', 'module' => 'job_types', 'label' => 'Manage All Job Types', 'description' => 'Manage Any Job Types'],
            ['name' => 'manage-own-job-types', 'module' => 'job_types', 'label' => 'Manage Own Job Types', 'description' => 'Manage Limited Job Types that is created by own'],
            ['name' => 'view-job-types', 'module' => 'job_types', 'label' => 'View Job Types', 'description' => 'View Job Types'],
            ['name' => 'create-job-types', 'module' => 'job_types', 'label' => 'Create Job Types', 'description' => 'Can create job types'],
            ['name' => 'edit-job-types', 'module' => 'job_types', 'label' => 'Edit Job Types', 'description' => 'Can edit job types'],
            ['name' => 'delete-job-types', 'module' => 'job_types', 'label' => 'Delete Job Types', 'description' => 'Can delete job types'],

            // Job Locations management
            ['name' => 'manage-job-locations', 'module' => 'job_locations', 'label' => 'Manage Job Locations', 'description' => 'Can manage job locations'],
            ['name' => 'manage-any-job-locations', 'module' => 'job_locations', 'label' => 'Manage All Job Locations', 'description' => 'Manage Any Job Locations'],
            ['name' => 'manage-own-job-locations', 'module' => 'job_locations', 'label' => 'Manage Own Job Locations', 'description' => 'Manage Limited Job Locations that is created by own'],
            ['name' => 'view-job-locations', 'module' => 'job_locations', 'label' => 'View Job Locations', 'description' => 'View Job Locations'],
            ['name' => 'create-job-locations', 'module' => 'job_locations', 'label' => 'Create Job Locations', 'description' => 'Can create job locations'],
            ['name' => 'edit-job-locations', 'module' => 'job_locations', 'label' => 'Edit Job Locations', 'description' => 'Can edit job locations'],
            ['name' => 'delete-job-locations', 'module' => 'job_locations', 'label' => 'Delete Job Locations', 'description' => 'Can delete job locations'],

            // Job Postings management
            ['name' => 'manage-job-postings', 'module' => 'job_postings', 'label' => 'Manage Job Postings', 'description' => 'Can manage job postings'],
            ['name' => 'manage-any-job-postings', 'module' => 'job_postings', 'label' => 'Manage All Job Postings', 'description' => 'Manage Any Job Postings'],
            ['name' => 'manage-own-job-postings', 'module' => 'job_postings', 'label' => 'Manage Own Job Postings', 'description' => 'Manage Limited Job Postings that is created by own'],
            ['name' => 'view-job-postings', 'module' => 'job_postings', 'label' => 'View Job Postings', 'description' => 'View Job Postings'],
            ['name' => 'create-job-postings', 'module' => 'job_postings', 'label' => 'Create Job Postings', 'description' => 'Can create job postings'],
            ['name' => 'edit-job-postings', 'module' => 'job_postings', 'label' => 'Edit Job Postings', 'description' => 'Can edit job postings'],
            ['name' => 'delete-job-postings', 'module' => 'job_postings', 'label' => 'Delete Job Postings', 'description' => 'Can delete job postings'],
            ['name' => 'publish-job-postings', 'module' => 'job_postings', 'label' => 'Publish Job Postings', 'description' => 'Can publish job postings'],

            // Candidate Sources management
            ['name' => 'manage-candidate-sources', 'module' => 'candidate_sources', 'label' => 'Manage Candidate Sources', 'description' => 'Can manage candidate sources'],
            ['name' => 'manage-any-candidate-sources', 'module' => 'candidate_sources', 'label' => 'Manage All Candidate Sources', 'description' => 'Manage Any Candidate Sources'],
            ['name' => 'manage-own-candidate-sources', 'module' => 'candidate_sources', 'label' => 'Manage Own Candidate Sources', 'description' => 'Manage Limited Candidate Sources that is created by own'],
            ['name' => 'view-candidate-sources', 'module' => 'candidate_sources', 'label' => 'View Candidate Sources', 'description' => 'View Candidate Sources'],
            ['name' => 'create-candidate-sources', 'module' => 'candidate_sources', 'label' => 'Create Candidate Sources', 'description' => 'Can create candidate sources'],
            ['name' => 'edit-candidate-sources', 'module' => 'candidate_sources', 'label' => 'Edit Candidate Sources', 'description' => 'Can edit candidate sources'],
            ['name' => 'delete-candidate-sources', 'module' => 'candidate_sources', 'label' => 'Delete Candidate Sources', 'description' => 'Can delete candidate sources'],

            // Candidates management
            ['name' => 'manage-candidates', 'module' => 'candidates', 'label' => 'Manage Candidates', 'description' => 'Can manage candidates'],
            ['name' => 'manage-any-candidates', 'module' => 'candidates', 'label' => 'Manage All Candidates', 'description' => 'Manage Any Candidates'],
            ['name' => 'manage-own-candidates', 'module' => 'candidates', 'label' => 'Manage Own Candidates', 'description' => 'Manage Limited Candidates that is created by own'],
            ['name' => 'view-candidates', 'module' => 'candidates', 'label' => 'View Candidates', 'description' => 'View Candidates'],
            ['name' => 'convert-to-employee', 'module' => 'candidates', 'label' => 'Convert to Employee', 'description' => 'Convert Candidate to Employee'],
            // ['name' => 'create-candidates', 'module' => 'candidates', 'label' => 'Create Candidates', 'description' => 'Can create candidates'],
            ['name' => 'edit-candidates', 'module' => 'candidates', 'label' => 'Edit Candidates', 'description' => 'Can edit candidates'],
            ['name' => 'delete-candidates', 'module' => 'candidates', 'label' => 'Delete Candidates', 'description' => 'Can delete candidates'],

            // Interview Types management
            ['name' => 'manage-interview-types', 'module' => 'interview_types', 'label' => 'Manage Interview Types', 'description' => 'Can manage interview types'],
            ['name' => 'manage-any-interview-types', 'module' => 'interview_types', 'label' => 'Manage All Interview Types', 'description' => 'Manage Any Interview Types'],
            ['name' => 'manage-own-interview-types', 'module' => 'interview_types', 'label' => 'Manage Own Interview Types', 'description' => 'Manage Limited Interview Types that is created by own'],
            ['name' => 'view-interview-types', 'module' => 'interview_types', 'label' => 'View Interview Types', 'description' => 'View Interview Types'],
            ['name' => 'create-interview-types', 'module' => 'interview_types', 'label' => 'Create Interview Types', 'description' => 'Can create interview types'],
            ['name' => 'edit-interview-types', 'module' => 'interview_types', 'label' => 'Edit Interview Types', 'description' => 'Can edit interview types'],
            ['name' => 'delete-interview-types', 'module' => 'interview_types', 'label' => 'Delete Interview Types', 'description' => 'Can delete interview types'],

            // Interview Rounds management
            ['name' => 'manage-interview-rounds', 'module' => 'interview_rounds', 'label' => 'Manage Interview Rounds', 'description' => 'Can manage interview rounds'],
            ['name' => 'manage-any-interview-rounds', 'module' => 'interview_rounds', 'label' => 'Manage All Interview Rounds', 'description' => 'Manage Any Interview Rounds'],
            ['name' => 'manage-own-interview-rounds', 'module' => 'interview_rounds', 'label' => 'Manage Own Interview Rounds', 'description' => 'Manage Limited Interview Rounds that is created by own'],
            ['name' => 'view-interview-rounds', 'module' => 'interview_rounds', 'label' => 'View Interview Rounds', 'description' => 'View Interview Rounds'],
            ['name' => 'create-interview-rounds', 'module' => 'interview_rounds', 'label' => 'Create Interview Rounds', 'description' => 'Can create interview rounds'],
            ['name' => 'edit-interview-rounds', 'module' => 'interview_rounds', 'label' => 'Edit Interview Rounds', 'description' => 'Can edit interview rounds'],
            ['name' => 'delete-interview-rounds', 'module' => 'interview_rounds', 'label' => 'Delete Interview Rounds', 'description' => 'Can delete interview rounds'],

            // Interviews management
            ['name' => 'manage-interviews', 'module' => 'interviews', 'label' => 'Manage Interviews', 'description' => 'Can manage interviews'],
            ['name' => 'manage-any-interviews', 'module' => 'interviews', 'label' => 'Manage All Interviews', 'description' => 'Manage Any Interviews'],
            ['name' => 'manage-own-interviews', 'module' => 'interviews', 'label' => 'Manage Own Interviews', 'description' => 'Manage Limited Interviews that is created by own'],
            ['name' => 'view-interviews', 'module' => 'interviews', 'label' => 'View Interviews', 'description' => 'View Interviews'],
            ['name' => 'create-interviews', 'module' => 'interviews', 'label' => 'Create Interviews', 'description' => 'Can create interviews'],
            ['name' => 'edit-interviews', 'module' => 'interviews', 'label' => 'Edit Interviews', 'description' => 'Can edit interviews'],
            ['name' => 'delete-interviews', 'module' => 'interviews', 'label' => 'Delete Interviews', 'description' => 'Can delete interviews'],

            // Interview Feedback management
            ['name' => 'manage-interview-feedback', 'module' => 'interview_feedback', 'label' => 'Manage Interview Feedback', 'description' => 'Can manage interview feedback'],
            ['name' => 'manage-any-interview-feedback', 'module' => 'interview_feedback', 'label' => 'Manage All Interview Feedback', 'description' => 'Manage Any Interview Feedback'],
            ['name' => 'manage-own-interview-feedback', 'module' => 'interview_feedback', 'label' => 'Manage Own Interview Feedback', 'description' => 'Manage Limited Interview Feedback that is created by own'],
            ['name' => 'view-interview-feedback', 'module' => 'interview_feedback', 'label' => 'View Interview Feedback', 'description' => 'View Interview Feedback'],
            ['name' => 'create-interview-feedback', 'module' => 'interview_feedback', 'label' => 'Create Interview Feedback', 'description' => 'Can create interview feedback'],
            ['name' => 'edit-interview-feedback', 'module' => 'interview_feedback', 'label' => 'Edit Interview Feedback', 'description' => 'Can edit interview feedback'],
            ['name' => 'delete-interview-feedback', 'module' => 'interview_feedback', 'label' => 'Delete Interview Feedback', 'description' => 'Can delete interview feedback'],

            // Custom Questions management
            ['name' => 'manage-custom-questions', 'module' => 'custom_questions', 'label' => 'Manage Custom Questions', 'description' => 'Can manage custom questions'],
            ['name' => 'manage-any-custom-questions', 'module' => 'custom_questions', 'label' => 'Manage All Custom Questions', 'description' => 'Manage Any Custom Questions'],
            ['name' => 'manage-own-custom-questions', 'module' => 'custom_questions', 'label' => 'Manage Own Custom Questions', 'description' => 'Manage Limited Custom Questions that is created by own'],
            ['name' => 'view-custom-questions', 'module' => 'custom_questions', 'label' => 'View Custom Questions', 'description' => 'View Custom Questions'],
            ['name' => 'create-custom-questions', 'module' => 'custom_questions', 'label' => 'Create Custom Questions', 'description' => 'Can create custom questions'],
            ['name' => 'edit-custom-questions', 'module' => 'custom_questions', 'label' => 'Edit Custom Questions', 'description' => 'Can edit custom questions'],
            ['name' => 'delete-custom-questions', 'module' => 'custom_questions', 'label' => 'Delete Custom Questions', 'description' => 'Can delete custom questions'],

            // Candidate Assessments management
            ['name' => 'manage-candidate-assessments', 'module' => 'candidate_assessments', 'label' => 'Manage Candidate Assessments', 'description' => 'Can manage candidate assessments'],
            ['name' => 'manage-any-candidate-assessments', 'module' => 'candidate_assessments', 'label' => 'Manage All Candidate Assessments', 'description' => 'Manage Any Candidate Assessments'],
            ['name' => 'manage-own-candidate-assessments', 'module' => 'candidate_assessments', 'label' => 'Manage Own Candidate Assessments', 'description' => 'Manage Limited Candidate Assessments that is created by own'],
            ['name' => 'view-candidate-assessments', 'module' => 'candidate_assessments', 'label' => 'View Candidate Assessments', 'description' => 'View Candidate Assessments'],
            ['name' => 'create-candidate-assessments', 'module' => 'candidate_assessments', 'label' => 'Create Candidate Assessments', 'description' => 'Can create candidate assessments'],
            ['name' => 'edit-candidate-assessments', 'module' => 'candidate_assessments', 'label' => 'Edit Candidate Assessments', 'description' => 'Can edit candidate assessments'],
            ['name' => 'delete-candidate-assessments', 'module' => 'candidate_assessments', 'label' => 'Delete Candidate Assessments', 'description' => 'Can delete candidate assessments'],

            // Offer Templates management
            ['name' => 'manage-offer-templates', 'module' => 'offer_templates', 'label' => 'Manage Offer Templates', 'description' => 'Can manage offer templates'],
            ['name' => 'manage-any-offer-templates', 'module' => 'offer_templates', 'label' => 'Manage All Offer Templates', 'description' => 'Manage Any Offer Templates'],
            ['name' => 'manage-own-offer-templates', 'module' => 'offer_templates', 'label' => 'Manage Own Offer Templates', 'description' => 'Manage Limited Offer Templates that is created by own'],
            ['name' => 'view-offer-templates', 'module' => 'offer_templates', 'label' => 'View Offer Templates', 'description' => 'View Offer Templates'],
            ['name' => 'create-offer-templates', 'module' => 'offer_templates', 'label' => 'Create Offer Templates', 'description' => 'Can create offer templates'],
            ['name' => 'edit-offer-templates', 'module' => 'offer_templates', 'label' => 'Edit Offer Templates', 'description' => 'Can edit offer templates'],
            ['name' => 'delete-offer-templates', 'module' => 'offer_templates', 'label' => 'Delete Offer Templates', 'description' => 'Can delete offer templates'],

            // Offers management
            ['name' => 'manage-offers', 'module' => 'offers', 'label' => 'Manage Offers', 'description' => 'Can manage offers'],
            ['name' => 'manage-any-offers', 'module' => 'offers', 'label' => 'Manage All Offers', 'description' => 'Manage Any Offers'],
            ['name' => 'manage-own-offers', 'module' => 'offers', 'label' => 'Manage Own Offers', 'description' => 'Manage Limited Offers that is created by own'],
            ['name' => 'view-offers', 'module' => 'offers', 'label' => 'View Offers', 'description' => 'View Offers'],
            ['name' => 'create-offers', 'module' => 'offers', 'label' => 'Create Offers', 'description' => 'Can create offers'],
            ['name' => 'edit-offers', 'module' => 'offers', 'label' => 'Edit Offers', 'description' => 'Can edit offers'],
            ['name' => 'delete-offers', 'module' => 'offers', 'label' => 'Delete Offers', 'description' => 'Can delete offers'],
            ['name' => 'approve-offers', 'module' => 'offers', 'label' => 'Approve Offers', 'description' => 'Can approve offers'],

            // Onboarding Checklists management
            ['name' => 'manage-onboarding-checklists', 'module' => 'onboarding_checklists', 'label' => 'Manage Onboarding Checklists', 'description' => 'Can manage onboarding checklists'],
            ['name' => 'manage-any-onboarding-checklists', 'module' => 'onboarding_checklists', 'label' => 'Manage All Onboarding Checklists', 'description' => 'Manage Any Onboarding Checklists'],
            ['name' => 'manage-own-onboarding-checklists', 'module' => 'onboarding_checklists', 'label' => 'Manage Own Onboarding Checklists', 'description' => 'Manage Limited Onboarding Checklists that is created by own'],
            ['name' => 'view-onboarding-checklists', 'module' => 'onboarding_checklists', 'label' => 'View Onboarding Checklists', 'description' => 'View Onboarding Checklists'],
            ['name' => 'create-onboarding-checklists', 'module' => 'onboarding_checklists', 'label' => 'Create Onboarding Checklists', 'description' => 'Can create onboarding checklists'],
            ['name' => 'edit-onboarding-checklists', 'module' => 'onboarding_checklists', 'label' => 'Edit Onboarding Checklists', 'description' => 'Can edit onboarding checklists'],
            ['name' => 'delete-onboarding-checklists', 'module' => 'onboarding_checklists', 'label' => 'Delete Onboarding Checklists', 'description' => 'Can delete onboarding checklists'],

            // Checklist Items management
            ['name' => 'manage-checklist-items', 'module' => 'checklist_items', 'label' => 'Manage Checklist Items', 'description' => 'Can manage checklist items'],
            ['name' => 'manage-any-checklist-items', 'module' => 'checklist_items', 'label' => 'Manage All Checklist Items', 'description' => 'Manage Any Checklist Items'],
            ['name' => 'manage-own-checklist-items', 'module' => 'checklist_items', 'label' => 'Manage Own Checklist Items', 'description' => 'Manage Limited Checklist Items that is created by own'],
            ['name' => 'view-checklist-items', 'module' => 'checklist_items', 'label' => 'View Checklist Items', 'description' => 'View Checklist Items'],
            ['name' => 'create-checklist-items', 'module' => 'checklist_items', 'label' => 'Create Checklist Items', 'description' => 'Can create checklist items'],
            ['name' => 'edit-checklist-items', 'module' => 'checklist_items', 'label' => 'Edit Checklist Items', 'description' => 'Can edit checklist items'],
            ['name' => 'delete-checklist-items', 'module' => 'checklist_items', 'label' => 'Delete Checklist Items', 'description' => 'Can delete checklist items'],

            // Candidate Onboarding management
            ['name' => 'manage-candidate-onboarding', 'module' => 'candidate_onboarding', 'label' => 'Manage Candidate Onboarding', 'description' => 'Can manage candidate onboarding'],
            ['name' => 'manage-any-candidate-onboarding', 'module' => 'candidate_onboarding', 'label' => 'Manage All Candidate Onboarding', 'description' => 'Manage Any Candidate Onboarding'],
            ['name' => 'manage-own-candidate-onboarding', 'module' => 'candidate_onboarding', 'label' => 'Manage Own Candidate Onboarding', 'description' => 'Manage Limited Candidate Onboarding that is created by own'],
            ['name' => 'view-candidate-onboarding', 'module' => 'candidate_onboarding', 'label' => 'View Candidate Onboarding', 'description' => 'View Candidate Onboarding'],
            ['name' => 'manage-candidate-onboarding-status', 'module' => 'candidate_onboarding', 'label' => 'Manage Candidate Onboarding status', 'description' => 'Manage Candidate Onboarding Status'],
            ['name' => 'create-candidate-onboarding', 'module' => 'candidate_onboarding', 'label' => 'Create Candidate Onboarding', 'description' => 'Can create candidate onboarding'],
            ['name' => 'edit-candidate-onboarding', 'module' => 'candidate_onboarding', 'label' => 'Edit Candidate Onboarding', 'description' => 'Can edit candidate onboarding'],
            ['name' => 'delete-candidate-onboarding', 'module' => 'candidate_onboarding', 'label' => 'Delete Candidate Onboarding', 'description' => 'Can delete candidate onboarding'],

            // Meeting Types management
            ['name' => 'manage-meeting-types', 'module' => 'meeting_types', 'label' => 'Manage Meeting Types', 'description' => 'Can manage meeting types'],
            ['name' => 'manage-any-meeting-types', 'module' => 'meeting_types', 'label' => 'Manage All Meeting Types', 'description' => 'Manage Any Meeting Types'],
            ['name' => 'manage-own-meeting-types', 'module' => 'meeting_types', 'label' => 'Manage Own Meeting Types', 'description' => 'Manage Limited Meeting Types that is created by own'],
            ['name' => 'view-meeting-types', 'module' => 'meeting_types', 'label' => 'View Meeting Types', 'description' => 'View Meeting Types'],
            ['name' => 'create-meeting-types', 'module' => 'meeting_types', 'label' => 'Create Meeting Types', 'description' => 'Can create meeting types'],
            ['name' => 'edit-meeting-types', 'module' => 'meeting_types', 'label' => 'Edit Meeting Types', 'description' => 'Can edit meeting types'],
            ['name' => 'delete-meeting-types', 'module' => 'meeting_types', 'label' => 'Delete Meeting Types', 'description' => 'Can delete meeting types'],

            // Meeting Rooms management
            ['name' => 'manage-meeting-rooms', 'module' => 'meeting_rooms', 'label' => 'Manage Meeting Rooms', 'description' => 'Can manage meeting rooms'],
            ['name' => 'manage-any-meeting-rooms', 'module' => 'meeting_rooms', 'label' => 'Manage All Meeting Rooms', 'description' => 'Manage Any Meeting Rooms'],
            ['name' => 'manage-own-meeting-rooms', 'module' => 'meeting_rooms', 'label' => 'Manage Own Meeting Rooms', 'description' => 'Manage Limited Meeting Rooms that is created by own'],
            ['name' => 'view-meeting-rooms', 'module' => 'meeting_rooms', 'label' => 'View Meeting Rooms', 'description' => 'View Meeting Rooms'],
            ['name' => 'create-meeting-rooms', 'module' => 'meeting_rooms', 'label' => 'Create Meeting Rooms', 'description' => 'Can create meeting rooms'],
            ['name' => 'edit-meeting-rooms', 'module' => 'meeting_rooms', 'label' => 'Edit Meeting Rooms', 'description' => 'Can edit meeting rooms'],
            ['name' => 'delete-meeting-rooms', 'module' => 'meeting_rooms', 'label' => 'Delete Meeting Rooms', 'description' => 'Can delete meeting rooms'],

            // Meetings management
            ['name' => 'manage-meetings', 'module' => 'meetings', 'label' => 'Manage Meetings', 'description' => 'Can manage meetings'],
            ['name' => 'manage-any-meetings', 'module' => 'meetings', 'label' => 'Manage All Meetings', 'description' => 'Manage Any Meetings'],
            ['name' => 'manage-own-meetings', 'module' => 'meetings', 'label' => 'Manage Own Meetings', 'description' => 'Manage Limited Meetings that is created by own'],
            ['name' => 'view-meetings', 'module' => 'meetings', 'label' => 'View Meetings', 'description' => 'View Meetings'],
            ['name' => 'create-meetings', 'module' => 'meetings', 'label' => 'Create Meetings', 'description' => 'Can create meetings'],
            ['name' => 'edit-meetings', 'module' => 'meetings', 'label' => 'Edit Meetings', 'description' => 'Can edit meetings'],
            ['name' => 'delete-meetings', 'module' => 'meetings', 'label' => 'Delete Meetings', 'description' => 'Can delete meetings'],
            ['name' => 'manage-meeting-status', 'module' => 'meetings', 'label' => 'Manage Meeting Status', 'description' => 'Can manage meeting status'],

            // Meeting Attendees management
            ['name' => 'manage-meeting-attendees', 'module' => 'meeting_attendees', 'label' => 'Manage Meeting Attendees', 'description' => 'Can manage meeting attendees'],
            ['name' => 'manage-any-meeting-attendees', 'module' => 'meeting_attendees', 'label' => 'Manage All Meeting Attendees', 'description' => 'Manage Any Meeting Attendees'],
            ['name' => 'manage-own-meeting-attendees', 'module' => 'meeting_attendees', 'label' => 'Manage Own Meeting Attendees', 'description' => 'Manage Limited Meeting Attendees that is created by own'],
            ['name' => 'view-meeting-attendees', 'module' => 'meeting_attendees', 'label' => 'View Meeting Attendees', 'description' => 'View Meeting Attendees'],
            ['name' => 'create-meeting-attendees', 'module' => 'meeting_attendees', 'label' => 'Create Meeting Attendees', 'description' => 'Can create meeting attendees'],
            ['name' => 'edit-meeting-attendees', 'module' => 'meeting_attendees', 'label' => 'Edit Meeting Attendees', 'description' => 'Can edit meeting attendees'],
            ['name' => 'delete-meeting-attendees', 'module' => 'meeting_attendees', 'label' => 'Delete Meeting Attendees', 'description' => 'Can delete meeting attendees'],
            ['name' => 'manage-meeting-rsvp-status', 'module' => 'meeting_attendees', 'label' => 'Manage Meeting RSVP Status', 'description' => 'Can manage meeting RSVP status'],
            ['name' => 'manage-meeting-attendance', 'module' => 'meeting_attendees', 'label' => 'Manage Meeting Attendance', 'description' => 'Can manage meeting attendance'],

            // Meeting Minutes management
            ['name' => 'manage-meeting-minutes', 'module' => 'meeting_minutes', 'label' => 'Manage Meeting Minutes', 'description' => 'Can manage meeting minutes'],
            ['name' => 'manage-any-meeting-minutes', 'module' => 'meeting_minutes', 'label' => 'Manage All Meeting Minutes', 'description' => 'Manage Any Meeting Minutes'],
            ['name' => 'manage-own-meeting-minutes', 'module' => 'meeting_minutes', 'label' => 'Manage Own Meeting Minutes', 'description' => 'Manage Limited Meeting Minutes that is created by own'],
            ['name' => 'view-meeting-minutes', 'module' => 'meeting_minutes', 'label' => 'View Meeting Minutes', 'description' => 'View Meeting Minutes'],
            ['name' => 'create-meeting-minutes', 'module' => 'meeting_minutes', 'label' => 'Create Meeting Minutes', 'description' => 'Can create meeting minutes'],
            ['name' => 'edit-meeting-minutes', 'module' => 'meeting_minutes', 'label' => 'Edit Meeting Minutes', 'description' => 'Can edit meeting minutes'],
            ['name' => 'delete-meeting-minutes', 'module' => 'meeting_minutes', 'label' => 'Delete Meeting Minutes', 'description' => 'Can delete meeting minutes'],

            // Action Items management
            ['name' => 'manage-action-items', 'module' => 'action_items', 'label' => 'Manage Action Items', 'description' => 'Can manage action items'],
            ['name' => 'manage-any-action-items', 'module' => 'action_items', 'label' => 'Manage All Action Items', 'description' => 'Manage Any Action Items'],
            ['name' => 'manage-own-action-items', 'module' => 'action_items', 'label' => 'Manage Own Action Items', 'description' => 'Manage Limited Action Items that is created by own'],
            ['name' => 'view-action-items', 'module' => 'action_items', 'label' => 'View Action Items', 'description' => 'View Action Items'],
            ['name' => 'create-action-items', 'module' => 'action_items', 'label' => 'Create Action Items', 'description' => 'Can create action items'],
            ['name' => 'edit-action-items', 'module' => 'action_items', 'label' => 'Edit Action Items', 'description' => 'Can edit action items'],
            ['name' => 'delete-action-items', 'module' => 'action_items', 'label' => 'Delete Action Items', 'description' => 'Can delete action items'],



            // Contract Types management
            ['name' => 'manage-contract-types', 'module' => 'contract_types', 'label' => 'Manage Contract Types', 'description' => 'Can manage contract types'],
            ['name' => 'manage-any-contract-types', 'module' => 'contract_types', 'label' => 'Manage All Contract Types', 'description' => 'Manage Any Contract Types'],
            ['name' => 'manage-own-contract-types', 'module' => 'contract_types', 'label' => 'Manage Own Contract Types', 'description' => 'Manage Limited Contract Types that is created by own'],
            ['name' => 'view-contract-types', 'module' => 'contract_types', 'label' => 'View Contract Types', 'description' => 'View Contract Types'],
            ['name' => 'create-contract-types', 'module' => 'contract_types', 'label' => 'Create Contract Types', 'description' => 'Can create contract types'],
            ['name' => 'edit-contract-types', 'module' => 'contract_types', 'label' => 'Edit Contract Types', 'description' => 'Can edit contract types'],
            ['name' => 'delete-contract-types', 'module' => 'contract_types', 'label' => 'Delete Contract Types', 'description' => 'Can delete contract types'],

            // Employee Contracts management
            ['name' => 'manage-employee-contracts', 'module' => 'employee_contracts', 'label' => 'Manage Employee Contracts', 'description' => 'Can manage employee contracts'],
            ['name' => 'manage-any-employee-contracts', 'module' => 'employee_contracts', 'label' => 'Manage All Employee Contracts', 'description' => 'Manage Any Employee Contracts'],
            ['name' => 'manage-own-employee-contracts', 'module' => 'employee_contracts', 'label' => 'Manage Own Employee Contracts', 'description' => 'Manage Limited Employee Contracts that is created by own'],
            ['name' => 'view-employee-contracts', 'module' => 'employee_contracts', 'label' => 'View Employee Contracts', 'description' => 'View Employee Contracts'],
            ['name' => 'create-employee-contracts', 'module' => 'employee_contracts', 'label' => 'Create Employee Contracts', 'description' => 'Can create employee contracts'],
            ['name' => 'edit-employee-contracts', 'module' => 'employee_contracts', 'label' => 'Edit Employee Contracts', 'description' => 'Can edit employee contracts'],
            ['name' => 'delete-employee-contracts', 'module' => 'employee_contracts', 'label' => 'Delete Employee Contracts', 'description' => 'Can delete employee contracts'],
            ['name' => 'approve-employee-contracts', 'module' => 'employee_contracts', 'label' => 'Approve Employee Contracts', 'description' => 'Can approve employee contracts'],
            ['name' => 'reject-employee-contracts', 'module' => 'employee_contracts', 'label' => 'Reject Employee Contracts', 'description' => 'Can reject employee contracts'],



            // Contract Renewals management
            ['name' => 'manage-contract-renewals', 'module' => 'contract_renewals', 'label' => 'Manage Contract Renewals', 'description' => 'Can manage contract renewals'],
            ['name' => 'manage-any-contract-renewals', 'module' => 'contract_renewals', 'label' => 'Manage All Contract Renewals', 'description' => 'Manage Any Contract Renewals'],
            ['name' => 'manage-own-contract-renewals', 'module' => 'contract_renewals', 'label' => 'Manage Own Contract Renewals', 'description' => 'Manage Limited Contract Renewals that is created by own'],
            ['name' => 'view-contract-renewals', 'module' => 'contract_renewals', 'label' => 'View Contract Renewals', 'description' => 'View Contract Renewals'],
            ['name' => 'create-contract-renewals', 'module' => 'contract_renewals', 'label' => 'Create Contract Renewals', 'description' => 'Can create contract renewals'],
            ['name' => 'edit-contract-renewals', 'module' => 'contract_renewals', 'label' => 'Edit Contract Renewals', 'description' => 'Can edit contract renewals'],
            ['name' => 'delete-contract-renewals', 'module' => 'contract_renewals', 'label' => 'Delete Contract Renewals', 'description' => 'Can delete contract renewals'],
            ['name' => 'approve-contract-renewals', 'module' => 'contract_renewals', 'label' => 'Approve Contract Renewals', 'description' => 'Can approve contract renewals'],
            ['name' => 'reject-contract-renewals', 'module' => 'contract_renewals', 'label' => 'Reject Contract Renewals', 'description' => 'Can reject contract renewals'],

            // Contract Templates management
            ['name' => 'manage-contract-templates', 'module' => 'contract_templates', 'label' => 'Manage Contract Templates', 'description' => 'Can manage contract templates'],
            ['name' => 'manage-any-contract-templates', 'module' => 'contract_templates', 'label' => 'Manage All Contract Templates', 'description' => 'Manage Any Contract Templates'],
            ['name' => 'manage-own-contract-templates', 'module' => 'contract_templates', 'label' => 'Manage Own Contract Templates', 'description' => 'Manage Limited Contract Templates that is created by own'],
            ['name' => 'view-contract-templates', 'module' => 'contract_templates', 'label' => 'View Contract Templates', 'description' => 'View Contract Templates'],
            ['name' => 'create-contract-templates', 'module' => 'contract_templates', 'label' => 'Create Contract Templates', 'description' => 'Can create contract templates'],
            ['name' => 'edit-contract-templates', 'module' => 'contract_templates', 'label' => 'Edit Contract Templates', 'description' => 'Can edit contract templates'],
            ['name' => 'delete-contract-templates', 'module' => 'contract_templates', 'label' => 'Delete Contract Templates', 'description' => 'Can delete contract templates'],

            // Document Categories management
            ['name' => 'manage-document-categories', 'module' => 'document_categories', 'label' => 'Manage Document Categories', 'description' => 'Can manage document categories'],
            ['name' => 'manage-any-document-categories', 'module' => 'document_categories', 'label' => 'Manage All Document Categories', 'description' => 'Manage Any Document Categories'],
            ['name' => 'manage-own-document-categories', 'module' => 'document_categories', 'label' => 'Manage Own Document Categories', 'description' => 'Manage Limited Document Categories that is created by own'],
            ['name' => 'view-document-categories', 'module' => 'document_categories', 'label' => 'View Document Categories', 'description' => 'View Document Categories'],
            ['name' => 'create-document-categories', 'module' => 'document_categories', 'label' => 'Create Document Categories', 'description' => 'Can create document categories'],
            ['name' => 'edit-document-categories', 'module' => 'document_categories', 'label' => 'Edit Document Categories', 'description' => 'Can edit document categories'],
            ['name' => 'delete-document-categories', 'module' => 'document_categories', 'label' => 'Delete Document Categories', 'description' => 'Can delete document categories'],

            // HR Documents management
            ['name' => 'manage-hr-documents', 'module' => 'hr_documents', 'label' => 'Manage HR Documents', 'description' => 'Can manage HR documents'],
            ['name' => 'manage-any-hr-documents', 'module' => 'hr_documents', 'label' => 'Manage All HR Documents', 'description' => 'Manage Any HR Documents'],
            ['name' => 'manage-own-hr-documents', 'module' => 'hr_documents', 'label' => 'Manage Own HR Documents', 'description' => 'Manage Limited HR Documents that is created by own'],
            ['name' => 'view-hr-documents', 'module' => 'hr_documents', 'label' => 'View HR Documents', 'description' => 'View HR Documents'],
            ['name' => 'create-hr-documents', 'module' => 'hr_documents', 'label' => 'Create HR Documents', 'description' => 'Can create HR documents'],
            ['name' => 'edit-hr-documents', 'module' => 'hr_documents', 'label' => 'Edit HR Documents', 'description' => 'Can edit HR documents'],
            ['name' => 'delete-hr-documents', 'module' => 'hr_documents', 'label' => 'Delete HR Documents', 'description' => 'Can delete HR documents'],



            // Document Acknowledgments management
            ['name' => 'manage-document-acknowledgments', 'module' => 'document_acknowledgments', 'label' => 'Manage Document Acknowledgments', 'description' => 'Can manage document acknowledgments'],
            ['name' => 'manage-any-document-acknowledgments', 'module' => 'document_acknowledgments', 'label' => 'Manage All Document Acknowledgments', 'description' => 'Manage Any Document Acknowledgments'],
            ['name' => 'manage-own-document-acknowledgments', 'module' => 'document_acknowledgments', 'label' => 'Manage Own Document Acknowledgments', 'description' => 'Manage Limited Document Acknowledgments that is created by own'],
            ['name' => 'view-document-acknowledgments', 'module' => 'document_acknowledgments', 'label' => 'View Document Acknowledgments', 'description' => 'View Document Acknowledgments'],
            ['name' => 'create-document-acknowledgments', 'module' => 'document_acknowledgments', 'label' => 'Create Document Acknowledgments', 'description' => 'Can create document acknowledgments'],
            ['name' => 'edit-document-acknowledgments', 'module' => 'document_acknowledgments', 'label' => 'Edit Document Acknowledgments', 'description' => 'Can edit document acknowledgments'],
            ['name' => 'delete-document-acknowledgments', 'module' => 'document_acknowledgments', 'label' => 'Delete Document Acknowledgments', 'description' => 'Can delete document acknowledgments'],
            ['name' => 'acknowledge-document-acknowledgments', 'module' => 'document_acknowledgments', 'label' => 'Acknowledge Document Acknowledgments', 'description' => 'Can acknowledge document acknowledgments'],

            // Document Templates management
            ['name' => 'manage-document-templates', 'module' => 'document_templates', 'label' => 'Manage Document Templates', 'description' => 'Can manage document templates'],
            ['name' => 'manage-any-document-templates', 'module' => 'document_templates', 'label' => 'Manage All Document Templates', 'description' => 'Manage Any Document Templates'],
            ['name' => 'manage-own-document-templates', 'module' => 'document_templates', 'label' => 'Manage Own Document Templates', 'description' => 'Manage Limited Document Templates that is created by own'],
            ['name' => 'view-document-templates', 'module' => 'document_templates', 'label' => 'View Document Templates', 'description' => 'View Document Templates'],
            ['name' => 'create-document-templates', 'module' => 'document_templates', 'label' => 'Create Document Templates', 'description' => 'Can create document templates'],
            ['name' => 'edit-document-templates', 'module' => 'document_templates', 'label' => 'Edit Document Templates', 'description' => 'Can edit document templates'],
            ['name' => 'delete-document-templates', 'module' => 'document_templates', 'label' => 'Delete Document Templates', 'description' => 'Can delete document templates'],

            // Leave Types management
            ['name' => 'manage-leave-types', 'module' => 'leave_types', 'label' => 'Manage Leave Types', 'description' => 'Can manage leave types'],
            ['name' => 'manage-any-leave-types', 'module' => 'leave_types', 'label' => 'Manage All Leave Types', 'description' => 'Manage Any Leave Types'],
            ['name' => 'manage-own-leave-types', 'module' => 'leave_types', 'label' => 'Manage Own Leave Types', 'description' => 'Manage Limited Leave Types that is created by own'],
            ['name' => 'view-leave-types', 'module' => 'leave_types', 'label' => 'View Leave Types', 'description' => 'View Leave Types'],
            ['name' => 'create-leave-types', 'module' => 'leave_types', 'label' => 'Create Leave Types', 'description' => 'Can create leave types'],
            ['name' => 'edit-leave-types', 'module' => 'leave_types', 'label' => 'Edit Leave Types', 'description' => 'Can edit leave types'],
            ['name' => 'delete-leave-types', 'module' => 'leave_types', 'label' => 'Delete Leave Types', 'description' => 'Can delete leave types'],

            // Leave Policies management
            ['name' => 'manage-leave-policies', 'module' => 'leave_policies', 'label' => 'Manage Leave Policies', 'description' => 'Can manage leave policies'],
            ['name' => 'manage-any-leave-policies', 'module' => 'leave_policies', 'label' => 'Manage All Leave Policies', 'description' => 'Manage Any Leave Policies'],
            ['name' => 'manage-own-leave-policies', 'module' => 'leave_policies', 'label' => 'Manage Own Leave Policies', 'description' => 'Manage Limited Leave Policies that is created by own'],
            ['name' => 'view-leave-policies', 'module' => 'leave_policies', 'label' => 'View Leave Policies', 'description' => 'View Leave Policies'],
            ['name' => 'create-leave-policies', 'module' => 'leave_policies', 'label' => 'Create Leave Policies', 'description' => 'Can create leave policies'],
            ['name' => 'edit-leave-policies', 'module' => 'leave_policies', 'label' => 'Edit Leave Policies', 'description' => 'Can edit leave policies'],
            ['name' => 'delete-leave-policies', 'module' => 'leave_policies', 'label' => 'Delete Leave Policies', 'description' => 'Can delete leave policies'],

            // Leave Applications management
            ['name' => 'manage-leave-applications', 'module' => 'leave_applications', 'label' => 'Manage Leave Applications', 'description' => 'Can manage leave applications'],
            ['name' => 'manage-any-leave-applications', 'module' => 'leave_applications', 'label' => 'Manage All Leave Applications', 'description' => 'Manage Any Leave Applications'],
            ['name' => 'manage-own-leave-applications', 'module' => 'leave_applications', 'label' => 'Manage Own Leave Applications', 'description' => 'Manage Limited Leave Applications that is created by own'],
            ['name' => 'view-leave-applications', 'module' => 'leave_applications', 'label' => 'View Leave Applications', 'description' => 'View Leave Applications'],
            ['name' => 'create-leave-applications', 'module' => 'leave_applications', 'label' => 'Create Leave Applications', 'description' => 'Can create leave applications'],
            ['name' => 'edit-leave-applications', 'module' => 'leave_applications', 'label' => 'Edit Leave Applications', 'description' => 'Can edit leave applications'],
            ['name' => 'delete-leave-applications', 'module' => 'leave_applications', 'label' => 'Delete Leave Applications', 'description' => 'Can delete leave applications'],
            ['name' => 'approve-leave-applications', 'module' => 'leave_applications', 'label' => 'Approve Leave Applications', 'description' => 'Can approve leave applications'],
            ['name' => 'reject-leave-applications', 'module' => 'leave_applications', 'label' => 'Reject Leave Applications', 'description' => 'Can reject leave applications'],

            // Leave Balances management
            ['name' => 'manage-leave-balances', 'module' => 'leave_balances', 'label' => 'Manage Leave Balances', 'description' => 'Can manage leave balances'],
            ['name' => 'manage-any-leave-balances', 'module' => 'leave_balances', 'label' => 'Manage All Leave Balances', 'description' => 'Manage Any Leave Balances'],
            ['name' => 'manage-own-leave-balances', 'module' => 'leave_balances', 'label' => 'Manage Own Leave Balances', 'description' => 'Manage Limited Leave Balances that is created by own'],
            ['name' => 'view-leave-balances', 'module' => 'leave_balances', 'label' => 'View Leave Balances', 'description' => 'View Leave Balances'],
            ['name' => 'create-leave-balances', 'module' => 'leave_balances', 'label' => 'Create Leave Balances', 'description' => 'Can create leave balances'],
            ['name' => 'edit-leave-balances', 'module' => 'leave_balances', 'label' => 'Edit Leave Balances', 'description' => 'Can edit leave balances'],
            ['name' => 'delete-leave-balances', 'module' => 'leave_balances', 'label' => 'Delete Leave Balances', 'description' => 'Can delete leave balances'],
            ['name' => 'adjust-leave-balances', 'module' => 'leave_balances', 'label' => 'Adjust Leave Balances', 'description' => 'Can make manual adjustments to leave balances'],

            // Shifts management
            ['name' => 'manage-shifts', 'module' => 'shifts', 'label' => 'Manage Shifts', 'description' => 'Can manage shifts'],
            ['name' => 'manage-any-shifts', 'module' => 'shifts', 'label' => 'Manage All Shifts', 'description' => 'Manage Any Shifts'],
            ['name' => 'manage-own-shifts', 'module' => 'shifts', 'label' => 'Manage Own Shifts', 'description' => 'Manage Limited Shifts that is created by own'],
            ['name' => 'view-shifts', 'module' => 'shifts', 'label' => 'View Shifts', 'description' => 'View Shifts'],
            ['name' => 'create-shifts', 'module' => 'shifts', 'label' => 'Create Shifts', 'description' => 'Can create shifts'],
            ['name' => 'edit-shifts', 'module' => 'shifts', 'label' => 'Edit Shifts', 'description' => 'Can edit shifts'],
            ['name' => 'delete-shifts', 'module' => 'shifts', 'label' => 'Delete Shifts', 'description' => 'Can delete shifts'],

            // Attendance Policies management
            ['name' => 'manage-attendance-policies', 'module' => 'attendance_policies', 'label' => 'Manage Attendance Policies', 'description' => 'Can manage attendance policies'],
            ['name' => 'manage-any-attendance-policies', 'module' => 'attendance_policies', 'label' => 'Manage All Attendance Policies', 'description' => 'Manage Any Attendance Policies'],
            ['name' => 'manage-own-attendance-policies', 'module' => 'attendance_policies', 'label' => 'Manage Own Attendance Policies', 'description' => 'Manage Limited Attendance Policies that is created by own'],
            ['name' => 'view-attendance-policies', 'module' => 'attendance_policies', 'label' => 'View Attendance Policies', 'description' => 'View Attendance Policies'],
            ['name' => 'create-attendance-policies', 'module' => 'attendance_policies', 'label' => 'Create Attendance Policies', 'description' => 'Can create attendance policies'],
            ['name' => 'edit-attendance-policies', 'module' => 'attendance_policies', 'label' => 'Edit Attendance Policies', 'description' => 'Can edit attendance policies'],
            ['name' => 'delete-attendance-policies', 'module' => 'attendance_policies', 'label' => 'Delete Attendance Policies', 'description' => 'Can delete attendance policies'],

            // Attendance Records management
            ['name' => 'manage-attendance-records', 'module' => 'attendance_records', 'label' => 'Manage Attendance Records', 'description' => 'Can manage attendance records'],
            ['name' => 'manage-any-attendance-records', 'module' => 'attendance_records', 'label' => 'Manage All Attendance Records', 'description' => 'Manage Any Attendance Records'],
            ['name' => 'manage-own-attendance-records', 'module' => 'attendance_records', 'label' => 'Manage Own Attendance Records', 'description' => 'Manage Limited Attendance Records that is created by own'],
            ['name' => 'view-attendance-records', 'module' => 'attendance_records', 'label' => 'View Attendance Records', 'description' => 'View Attendance Records'],
            ['name' => 'create-attendance-records', 'module' => 'attendance_records', 'label' => 'Create Attendance Records', 'description' => 'Can create attendance records'],
            ['name' => 'edit-attendance-records', 'module' => 'attendance_records', 'label' => 'Edit Attendance Records', 'description' => 'Can edit attendance records'],
            ['name' => 'delete-attendance-records', 'module' => 'attendance_records', 'label' => 'Delete Attendance Records', 'description' => 'Can delete attendance records'],
            ['name' => 'clock-in-out', 'module' => 'attendance_records', 'label' => 'Clock In/Out', 'description' => 'Can clock in and out'],

            // Attendance Regularizations management
            ['name' => 'manage-attendance-regularizations', 'module' => 'attendance_regularizations', 'label' => 'Manage Attendance Regularizations', 'description' => 'Can manage attendance regularizations'],
            ['name' => 'manage-any-attendance-regularizations', 'module' => 'attendance_regularizations', 'label' => 'Manage All Attendance Regularizations', 'description' => 'Manage Any Attendance Regularizations'],
            ['name' => 'manage-own-attendance-regularizations', 'module' => 'attendance_regularizations', 'label' => 'Manage Own Attendance Regularizations', 'description' => 'Manage Limited Attendance Regularizations that is created by own'],
            ['name' => 'view-attendance-regularizations', 'module' => 'attendance_regularizations', 'label' => 'View Attendance Regularizations', 'description' => 'View Attendance Regularizations'],
            ['name' => 'create-attendance-regularizations', 'module' => 'attendance_regularizations', 'label' => 'Create Attendance Regularizations', 'description' => 'Can create attendance regularizations'],
            ['name' => 'edit-attendance-regularizations', 'module' => 'attendance_regularizations', 'label' => 'Edit Attendance Regularizations', 'description' => 'Can edit attendance regularizations'],
            ['name' => 'delete-attendance-regularizations', 'module' => 'attendance_regularizations', 'label' => 'Delete Attendance Regularizations', 'description' => 'Can delete attendance regularizations'],
            ['name' => 'approve-attendance-regularizations', 'module' => 'attendance_regularizations', 'label' => 'Approve Attendance Regularizations', 'description' => 'Can approve attendance regularizations'],
            ['name' => 'reject-attendance-regularizations', 'module' => 'attendance_regularizations', 'label' => 'Reject Attendance Regularizations', 'description' => 'Can reject attendance regularizations'],

            // Time Entries management
            ['name' => 'manage-time-entries', 'module' => 'time_entries', 'label' => 'Manage Time Entries', 'description' => 'Can manage time entries'],
            ['name' => 'manage-any-time-entries', 'module' => 'time_entries', 'label' => 'Manage All Time Entries', 'description' => 'Manage Any Time Entries'],
            ['name' => 'manage-own-time-entries', 'module' => 'time_entries', 'label' => 'Manage Own Time Entries', 'description' => 'Manage Limited Time Entries that is created by own'],
            ['name' => 'view-time-entries', 'module' => 'time_entries', 'label' => 'View Time Entries', 'description' => 'View Time Entries'],
            ['name' => 'create-time-entries', 'module' => 'time_entries', 'label' => 'Create Time Entries', 'description' => 'Can create time entries'],
            ['name' => 'edit-time-entries', 'module' => 'time_entries', 'label' => 'Edit Time Entries', 'description' => 'Can edit time entries'],
            ['name' => 'delete-time-entries', 'module' => 'time_entries', 'label' => 'Delete Time Entries', 'description' => 'Can delete time entries'],
            ['name' => 'approve-time-entries', 'module' => 'time_entries', 'label' => 'Approve Time Entries', 'description' => 'Can approve time entries'],
            ['name' => 'reject-time-entries', 'module' => 'time_entries', 'label' => 'Reject Time Entries', 'description' => 'Can reject time entries'],

            // Salary Components management
            ['name' => 'manage-salary-components', 'module' => 'salary_components', 'label' => 'Manage Salary Components', 'description' => 'Can manage salary components'],
            ['name' => 'manage-any-salary-components', 'module' => 'salary_components', 'label' => 'Manage All Salary Components', 'description' => 'Manage Any Salary Components'],
            ['name' => 'manage-own-salary-components', 'module' => 'salary_components', 'label' => 'Manage Own Salary Components', 'description' => 'Manage Limited Salary Components that is created by own'],
            ['name' => 'view-salary-components', 'module' => 'salary_components', 'label' => 'View Salary Components', 'description' => 'View Salary Components'],
            ['name' => 'create-salary-components', 'module' => 'salary_components', 'label' => 'Create Salary Components', 'description' => 'Can create salary components'],
            ['name' => 'edit-salary-components', 'module' => 'salary_components', 'label' => 'Edit Salary Components', 'description' => 'Can edit salary components'],
            ['name' => 'delete-salary-components', 'module' => 'salary_components', 'label' => 'Delete Salary Components', 'description' => 'Can delete salary components'],

            // Employee Salaries management
            ['name' => 'manage-employee-salaries', 'module' => 'employee_salaries', 'label' => 'Manage Employee Salaries', 'description' => 'Can manage employee salaries'],
            ['name' => 'manage-any-employee-salaries', 'module' => 'employee_salaries', 'label' => 'Manage All Employee Salaries', 'description' => 'Manage Any Employee Salaries'],
            ['name' => 'manage-own-employee-salaries', 'module' => 'employee_salaries', 'label' => 'Manage Own Employee Salaries', 'description' => 'Manage Limited Employee Salaries that is created by own'],
            ['name' => 'view-employee-salaries', 'module' => 'employee_salaries', 'label' => 'View Employee Salaries', 'description' => 'View Employee Salaries'],
            ['name' => 'create-employee-salaries', 'module' => 'employee_salaries', 'label' => 'Create Employee Salaries', 'description' => 'Can create employee salaries'],
            ['name' => 'edit-employee-salaries', 'module' => 'employee_salaries', 'label' => 'Edit Employee Salaries', 'description' => 'Can edit employee salaries'],
            ['name' => 'delete-employee-salaries', 'module' => 'employee_salaries', 'label' => 'Delete Employee Salaries', 'description' => 'Can delete employee salaries'],

            // Payroll Runs management
            ['name' => 'manage-payroll-runs', 'module' => 'payroll_runs', 'label' => 'Manage Payroll Runs', 'description' => 'Can manage payroll runs'],
            ['name' => 'manage-any-payroll-runs', 'module' => 'payroll_runs', 'label' => 'Manage All Payroll Runs', 'description' => 'Manage Any Payroll Runs'],
            ['name' => 'manage-own-payroll-runs', 'module' => 'payroll_runs', 'label' => 'Manage Own Payroll Runs', 'description' => 'Manage Limited Payroll Runs that is created by own'],
            ['name' => 'view-payroll-runs', 'module' => 'payroll_runs', 'label' => 'View Payroll Runs', 'description' => 'View Payroll Runs'],
            ['name' => 'create-payroll-runs', 'module' => 'payroll_runs', 'label' => 'Create Payroll Runs', 'description' => 'Can create payroll runs'],
            ['name' => 'edit-payroll-runs', 'module' => 'payroll_runs', 'label' => 'Edit Payroll Runs', 'description' => 'Can edit payroll runs'],
            ['name' => 'delete-payroll-runs', 'module' => 'payroll_runs', 'label' => 'Delete Payroll Runs', 'description' => 'Can delete payroll runs'],
            ['name' => 'process-payroll-runs', 'module' => 'payroll_runs', 'label' => 'Process Payroll Runs', 'description' => 'Can process payroll runs'],

            // Payslips management
            ['name' => 'manage-payslips', 'module' => 'payslips', 'label' => 'Manage Payslips', 'description' => 'Can manage payslips'],
            ['name' => 'manage-any-payslips', 'module' => 'payslips', 'label' => 'Manage All Payslips', 'description' => 'Manage Any Payslips'],
            ['name' => 'manage-own-payslips', 'module' => 'payslips', 'label' => 'Manage Own Payslips', 'description' => 'Manage Limited Payslips that is created by own'],
            ['name' => 'view-payslips', 'module' => 'payslips', 'label' => 'View Payslips', 'description' => 'View Payslips'],
            ['name' => 'create-payslips', 'module' => 'payslips', 'label' => 'Create Payslips', 'description' => 'Can create payslips'],
            ['name' => 'download-payslips', 'module' => 'payslips', 'label' => 'Download Payslips', 'description' => 'Can download payslips'],
            ['name' => 'send-payslips', 'module' => 'payslips', 'label' => 'Send Payslips', 'description' => 'Can send payslips via email'],

            // Calendar permissions
            ['name' => 'manage-calendar', 'module' => 'calendar', 'label' => 'Manage Calendar', 'description' => 'Can manage calendar'],
            ['name' => 'view-calendar', 'module' => 'calendar', 'label' => 'View Calendar', 'description' => 'Can view calendar'],

            // Manage Working Days
            ['name' => 'manage-working-days-settings', 'module' => 'working_days', 'label' => 'Manage Working Days', 'description' => 'Manage Working Days'],
            ['name' => 'update-working-days-settings', 'module' => 'working_days', 'label' => 'Manage Working Days', 'description' => 'Manage Working Days'],

            // Biometric Attendance
            ['name' => 'manage-biometric-attendance', 'module' => 'biometric_attendance', 'label' => 'Manage Biometric Attendance', 'description' => 'Can manage biometric attendance data'],
            ['name' => 'manage-any-biometric-attendance', 'module' => 'biometric_attendance', 'label' => 'Manage All Biometric Attendance', 'description' => 'Manage Any Biometric Attendance'],
            ['name' => 'manage-own-biometric-attendance', 'module' => 'biometric_attendance', 'label' => 'Manage Own Biometric Attendance', 'description' => 'Manage Limited Biometric Attendance that is created by own'],
            ['name' => 'view-biometric-attendance', 'module' => 'biometric_attendance', 'label' => 'View Biometric Attendance', 'description' => 'View Biometric Attendance'],
            ['name' => 'sync-biometric-attendance', 'module' => 'biometric_attendance', 'label' => 'Sync Biometric Attendance', 'description' => 'Can sync biometric attendance data'],
            ['name' => 'manage-biomatric-attedance-settings', 'module' => 'biometric_attendance', 'label' => 'Manage Biometric Attendance Setting', 'description' => 'Manage Biometric Attendance Setting'],

            // Ip Restriction
            ['name' => 'manage-ip-restriction-settings', 'module' => 'ip_restriction', 'label' => 'Manage Ip Restriction Settings', 'description' => 'Manage Ip Restriction Settings'],
            ['name' => 'create-ip-restriction', 'module' => 'ip_restriction', 'label' => 'Create Ip Restriction', 'description' => 'Create Ip Restriction'],
            ['name' => 'edit-ip-restriction', 'module' => 'ip_restriction', 'label' => 'Edit Ip Restriction', 'description' => 'Edit Ip Restriction'],
            ['name' => 'delete-ip-restriction', 'module' => 'ip_restriction', 'label' => 'Delete Ip Restriction', 'description' => 'Delete Ip Restriction'],

            // Manage Career Page
            ['name' => 'manage-career-page', 'module' => 'career', 'label' => 'Manage Career Page', 'description' => 'Manage Career Page'],

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                [
                    'module' => $permission['module'],
                    'label' => $permission['label'],
                    'description' => $permission['description'],
                ]
            );
        }
    }
}
