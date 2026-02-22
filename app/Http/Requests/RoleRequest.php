<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $this->validateSystemRole($value, $fail);
                }
            ],
            'description' => 'nullable|string',
            'permissions' => 'required|array'
        ];
    }


    private function validatePermissionAccess($permissionName, $fail)
    {
        $user = Auth::user();
        $userType = $user->type ?? 'company';

        // Superadmin can assign any permission
        if ($userType === 'superadmin' || $userType === 'super admin') {
            return;
        }

        // Get allowed modules for current user role
        $allowedModules = config('role-permissions.' . $userType, config('role-permissions.company'));

        // Check if permission belongs to allowed module
        $permission = Permission::where('name', $permissionName)->first();

        if (!$permission) {
            $fail('Permission does not exist.');
            return;
        }

        // Skip validation if permission module is not in allowed modules (commented out)
        if (!in_array($permission->module, $allowedModules)) {
            return; // Allow but will be filtered out by controller
        }

        // For company users, validate settings permissions
        if ($userType === 'company' && $permission->module === 'settings') {
            $allowedSettingsPermissions = [
                'manage-email-settings',
                'manage-system-settings',
                'manage-brand-settings'
            ];

            if (!in_array($permissionName, $allowedSettingsPermissions)) {
                $fail('You are not authorized to assign this permission.');
            }
        }
    }


    private function validateSystemRole($label, $fail)
    {
        $user = Auth::user();
        $userType = $user->type ?? 'company';

        // Superadmin can create/edit any role
        if ($userType === 'superadmin' || $userType === 'super admin') {
            return;
        }

        $systemRoles = ['superadmin', 'super admin', 'company'];
        $slug = \Illuminate\Support\Str::slug($label);

        if (
            in_array(strtolower($label), array_map('strtolower', $systemRoles)) ||
            in_array($slug, $systemRoles)
        ) {
            $fail('This role name is reserved for system use. Please choose a different name.');
        }
    }
}
