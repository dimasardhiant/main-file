<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class LeaveApplicationController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-leave-applications')) {
            $query = LeaveApplication::with(['employee', 'leaveType', 'leavePolicy', 'approver', 'creator'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-leave-applications')) {
                        $q->whereIn('created_by',  getCompanyAndUsersId());
                    } elseif (Auth::user()->can('manage-own-leave-applications')) {
                        $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id())->orWhere('approved_by', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });


            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('reason', 'like', '%' . $request->search . '%')
                        ->orWhereHas('employee', function ($subQ) use ($request) {
                            $subQ->where('name', 'like', '%' . $request->search . '%');
                        })
                        ->orWhereHas('leaveType', function ($subQ) use ($request) {
                            $subQ->where('name', 'like', '%' . $request->search . '%');
                        });
                });
            }

            // Handle employee filter
            if ($request->has('employee_id') && !empty($request->employee_id) && $request->employee_id !== 'all') {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle leave type filter
            if ($request->has('leave_type_id') && !empty($request->leave_type_id) && $request->leave_type_id !== 'all') {
                $query->where('leave_type_id', $request->leave_type_id);
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            $leaveApplications = $query->paginate($request->per_page ?? 10);

            // Get employees for filter dropdown
            $employees = User::where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->get(['id', 'name']);

            // Get leave types for filter dropdown
            $leaveTypes = LeaveType::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->get(['id', 'name', 'color']);

            return Inertia::render('hr/leave-applications/index', [
                'leaveApplications' => $leaveApplications,
                'employees' => $this->getFilteredEmployees(),
                'leaveTypes' => $leaveTypes,
                'filters' => $request->all(['search', 'employee_id', 'leave_type_id', 'status', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-leave-applications') && !Auth::user()->can('manage-any-leave-applications')) {
            $employeeQuery->where(function ($q) {
                $q->where('created_by', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        $employees = User::emp()
            ->with('employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee->employee_id ?? '',
                ];
            });
        return $employees;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment' => 'nullable|string',
        ]);

        $validated['created_by'] = creatorId();

        // Calculate total days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $validated['total_days'] = $startDate->diffInDays($endDate) + 1;

        // Get leave policy for this leave type
        $leavePolicy = LeavePolicy::where('leave_type_id', $validated['leave_type_id'])
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->first();

        if (!$leavePolicy) {
            return redirect()->back()->with('error', __('No active policy found for selected leave type.'));
        }

        $validated['leave_policy_id'] = $leavePolicy->id;

        // Validate days per application
        if (
            $validated['total_days'] < $leavePolicy->min_days_per_application ||
            $validated['total_days'] > $leavePolicy->max_days_per_application
        ) {
            return redirect()->back()->with(
                'error',
                __('Leave days must be between :min and :max days.', [
                    'min' => $leavePolicy->min_days_per_application,
                    'max' => $leavePolicy->max_days_per_application
                ])
            );
        }

        // Check if employee has enough leave balance
        $currentYear = now()->year;
        $leaveBalance = \App\Models\LeaveBalance::where('employee_id', $validated['employee_id'])
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $currentYear)
            ->first();

        if (!$leaveBalance) {
            // Create initial balance if doesn't exist
            $leaveBalance = \App\Models\LeaveBalance::create([
                'employee_id' => $validated['employee_id'],
                'leave_type_id' => $validated['leave_type_id'],
                'leave_policy_id' => $leavePolicy->id,
                'year' => $currentYear,
                'allocated_days' => $leavePolicy->max_days_per_year ?? 10,
                'used_days' => 0,
                'remaining_days' => $leavePolicy->max_days_per_year ?? 10,
                'created_by' => creatorId(),
            ]);
        }

        // Check if enough balance available
        if ($leaveBalance->remaining_days < $validated['total_days']) {
            return redirect()->back()->with(
                'error',
                __('Insufficient leave balance. Available: :available days, Requested: :requested days', [
                    'available' => $leaveBalance->remaining_days,
                    'requested' => $validated['total_days']
                ])
            );
        }

        // Handle attachment from media library
        if ($request->has('attachment')) {
            $validated['attachment'] = $request->attachment;
        }

        // Set status based on policy
        $validated['status'] = $leavePolicy->requires_approval ? 'pending' : 'approved';

        $leaveApplication = LeaveApplication::create($validated);

        // Create attendance records if auto-approved
        if ($validated['status'] === 'approved') {
            $leaveApplication->createAttendanceRecords();
        }

        return redirect()->back()->with('success', __('Leave application created successfully.'));
    }

    public function update(Request $request, $leaveApplicationId)
    {
        $leaveApplication = LeaveApplication::where('id', $leaveApplicationId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveApplication) {
            try {
                $validated = $request->validate([
                    'employee_id' => 'required|exists:users,id',
                    'leave_type_id' => 'required|exists:leave_types,id',
                    'start_date' => 'required|date',
                    'end_date' => 'required|date|after_or_equal:start_date',
                    'reason' => 'required|string',
                    'attachment' => 'nullable|string',
                ]);

                // Calculate total days
                $startDate = Carbon::parse($validated['start_date']);
                $endDate = Carbon::parse($validated['end_date']);
                $validated['total_days'] = $startDate->diffInDays($endDate) + 1;

                // Get leave policy
                $leavePolicy = LeavePolicy::where('leave_type_id', $validated['leave_type_id'])
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->where('status', 'active')
                    ->first();

                if (!$leavePolicy) {
                    return redirect()->back()->with('error', __('No active policy found for selected leave type.'));
                }

                $validated['leave_policy_id'] = $leavePolicy->id;

                // Handle attachment from media library
                if ($request->has('attachment')) {
                    $validated['attachment'] = $request->attachment;
                }

                $leaveApplication->update($validated);

                return redirect()->back()->with('success', __('Leave application updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update leave application'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave application Not Found.'));
        }
    }

    public function destroy($leaveApplicationId)
    {
        $leaveApplication = LeaveApplication::where('id', $leaveApplicationId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveApplication) {
            try {
                $leaveApplication->delete();
                return redirect()->back()->with('success', __('Leave application deleted successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete leave application'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave application Not Found.'));
        }
    }

    public function updateStatus(Request $request, $leaveApplicationId)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'manager_comments' => 'nullable|string',
        ]);

        $leaveApplication = LeaveApplication::where('id', $leaveApplicationId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveApplication) {
            try {
                $leaveApplication->update([
                    'status' => $validated['status'],
                    'manager_comments' => $validated['manager_comments'],
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                // Create attendance records if approved
                if ($validated['status'] === 'approved') {
                    // Double-check balance before final approval
                    $currentYear = now()->year;
                    $leaveBalance = \App\Models\LeaveBalance::where('employee_id', $leaveApplication->employee_id)
                        ->where('leave_type_id', $leaveApplication->leave_type_id)
                        ->where('year', $currentYear)
                        ->first();

                    if ($leaveBalance && $leaveBalance->remaining_days < $leaveApplication->total_days) {
                        return redirect()->back()->with(
                            'error',
                            __('Cannot approve: Insufficient leave balance. Available: :available days, Required: :required days', [
                                'available' => $leaveBalance->remaining_days,
                                'required' => $leaveApplication->total_days
                            ])
                        );
                    }

                    $leaveApplication->createAttendanceRecords();
                }

                return redirect()->back()->with('success', __('Leave application status updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update leave application status'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave application Not Found.'));
        }
    }
}
