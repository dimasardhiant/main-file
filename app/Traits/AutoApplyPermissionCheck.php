<?php

namespace App\Traits;

use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Illuminate\Support\Facades\Schema;

trait AutoApplyPermissionCheck
{
    /**
     * Apply permission check to a model query
     *
     * @param string $modelClass The fully qualified model class name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function queryWithPermission($modelClass)
    {
        return $modelClass::withPermissionCheck();
    }

    /**
     * Apply permission scope to the query based on user's permissions
     *https://temp-file.workdo.io/download/FjkuKXEofvEcEkCmnOQerQ0Q7YN6aD08
     * @param \Illuminate\Database\Eloquent\Builder $queryquery
     * @param string $module The module name (e.g., 'roles', 'permissions')
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyPermissionScope($query, $module)
    {
        // Skip permission check if no authenticated user (e.g., in console commands)
        if (!auth()->check()) {
            return $query;
        }

        $user = auth()->user();

        // Check if user is superadmin - they can see everything
        if ($user->hasRole(['superadmin'])) {
            return $query;
        }

        // For company users, show their records and their employees' records
        if ($user->hasRole(['company'])) {
            if (Schema::hasColumn($query->getModel()->getTable(), 'created_by')) {
                return $query->whereIn('created_by', getCompanyAndUsersId());
            }
        }

        try {
            // Check for specific permissions first (works for all roles)
            // $module = str_replace('_', '-', $module);
            // if ($user->hasPermissionTo("manage-own-{$module}")) {
            //     if (Schema::hasColumn($query->getModel()->getTable(), 'created_by')) {
            //         return $query->where('created_by', $user->id)->orWhere('employee_id', $user->id);
            //     }
            //     return $query;
            // }

            $module = str_replace('_', '-', $module);
            if ($user->hasPermissionTo("manage-own-{$module}")) {
                $table = $query->getModel()->getTable();
                return $query->where(function ($q) use ($user, $table) {
                    if (Schema::hasColumn($table, 'created_by')) {
                        $q->where('created_by', $user->id);
                    }
                    if (Schema::hasColumn($table, 'employee_id')) {
                        // Use OR only if created_by already applied
                        $q->orWhere('employee_id', $user->id);
                    }
                });
            }

            // If user has permission to list all items, return the query without filtering
            if ($user->hasPermissionTo("manage-any-{$module}")) {
                if (Schema::hasColumn($query->getModel()->getTable(), 'created_by')) {
                    return $query->whereIn('created_by', getCompanyAndUsersId());
                }
            }
        } catch (PermissionDoesNotExist $e) {
            // Permission doesn't exist, check for access to module instead
            if ($user->hasPermissionTo("access-{$module}-module")) {
                // Default to showing only own records if they have module access
                if (Schema::hasColumn($query->getModel()->getTable(), 'created_by')) {
                    return $query->where('created_by', $user->id);
                }
                return $query;
            }
        }

        // Check employee role after specific permissions
        if ($user->hasRole(['employee'])) {
            return $this->applyEmployeeRoleFiltering($query, $user, $permission = null, $module);
        }

        // Check Default manage Permission
        try {
            if ($user->hasPermissionTo("manage-{$module}")) {
                if (Schema::hasColumn($query->getModel()->getTable(), 'created_by')) {
                    return $query->whereIn('created_by', getCompanyAndUsersId());
                }
            }
        } catch (PermissionDoesNotExist $e) {
            // Permission doesn't exist, check for access to module instead
            if ($user->hasPermissionTo("access-{$module}-module")) {
                // Default to showing only own records if they have module access
                if (Schema::hasColumn($query->getModel()->getTable(), 'created_by')) {
                    return $query->where('created_by', $user->id);
                }
                return $query;
            }
        }

        return $query;
    }


    private function applyEmployeeRoleFiltering($query, $user, $permission = null, $module)
    {
        $module = str_replace('_', '-', $module);

        // Check if employee has manage permission
        $hasManagePermission = false;
        try {
            $hasManagePermission = $user->hasPermissionTo("manage-{$module}");
        } catch (PermissionDoesNotExist $e) {
            // Continue with model-specific filtering
        }

        $modelClass = get_class($query->getModel());
        switch ($modelClass) {
            case 'App\Models\User':
                return $query->where('id', $user->id);
            case 'App\Models\Award':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Promotion':
                return $query->where('employee_id', $user->id);
            case 'App\Models\EmployeeGoal':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Resignation':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Termination':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Warning':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Trip':
                return $query->where('employee_id', $user->id);
            case 'App\Models\EmployeeTransfer':
                return $query->where('employee_id', $user->id);
            case 'App\Models\EmployeeReview':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Resignation':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Termination':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Warning':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Trip':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Complaint':
                return $query->where(function ($q) use ($user) {
                    $q->where('employee_id', $user->id)
                        ->orWhere('against_employee_id', $user->id);
                });
            case 'App\Models\Asset':
                return $query->join('asset_assignments', 'assets.id', '=', 'asset_assignments.asset_id')
                    ->where('asset_assignments.employee_id', $user->id)
                    ->select('assets.*');
            case 'App\Models\TrainingSession':
                return $query->join('training_session_trainer', 'training_sessions.id', '=', 'training_session_trainer.training_session_id')
                    ->where('training_session_trainer.employee_id', $user->id)
                    ->select('training_sessions.*');
            case 'App\Models\EmployeeTraining':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Interview':
                return $query->whereJsonContains('interviewers', (string) $user->id);
            case 'App\Models\CandidateAssessment':
                return $query->where('conducted_by', $user->id);
            case 'App\Models\CandidateOnboarding':
                return $query->where('buddy_employee_id', $user->id);
            case 'App\Models\EmployeeContract':
                return $query->where('employee_id', $user->id);
            case 'App\Models\ContractAmendment':
                return $query->join('employee_contracts', 'employee_contracts.id', '=', 'contract_amendments.contract_id')
                    ->where('employee_contracts.employee_id', $user->id)
                    ->select('contract_amendments.*');
            case 'App\Models\ContractRenewal':
                return $query->join('employee_contracts', 'employee_contracts.id', '=', 'contract_renewals.contract_id')
                    ->where('employee_contracts.employee_id', $user->id)
                    ->select('contract_renewals.*');
            case 'App\Models\HrDocument':
                return $query->whereIn('created_by', getCompanyAndUsersId());
            case 'App\Models\DocumentAcknowledgment':
                return $query->where('user_id', $user->id);
            case 'App\Models\Meeting':
                return $query->where('organizer_id', $user->id);
            case 'App\Models\MeetingAttendee':
                return $query->where('user_id', $user->id);
            case 'App\Models\MeetingMinute':
                return $query->where('recorded_by', $user->id);
            case 'App\Models\ActionItem':
                return $query->where('assigned_to', $user->id);
            case 'App\Models\LeaveBalance':
                return $query->where('employee_id', $user->id);
            case 'App\Models\LeaveApplication':
                return $query->where('employee_id', $user->id);
            case 'App\Models\AttendanceRecord':
                return $query->where('employee_id', $user->id);
            case 'App\Models\AttendanceRegularization':
                return $query->where('employee_id', $user->id);
            case 'App\Models\TimeEntry':
                return $query->where('employee_id', $user->id);
            case 'App\Models\EmployeeSalary':
                return $query->where('employee_id', $user->id);
            case 'App\Models\Payslip':
                return $query->where('employee_id', $user->id);
            default:
                if ($hasManagePermission && Schema::hasColumn($query->getModel()->getTable(), 'created_by')) {
                    return $query->whereIn('created_by', getCompanyAndUsersId());
                }
                return $query;
        }
    }
}
