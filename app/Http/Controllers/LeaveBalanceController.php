<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-leave-balances')) {
            $query = LeaveBalance::with(['employee', 'leaveType', 'leavePolicy', 'creator'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-leave-balances')) {
                        $q->whereIn('created_by',  getCompanyAndUsersId());
                    } elseif (Auth::user()->can('manage-own-leave-balances')) {
                        $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('employee', function ($subQ) use ($request) {
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

            // Handle year filter
            if ($request->has('year') && !empty($request->year) && $request->year !== 'all') {
                $query->where('year', $request->year);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $leaveBalances = $query->paginate($request->per_page ?? 10);

            // Get employees for filter dropdown
            $employees = User::where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->get(['id', 'name']);

            // Get leave types for filter dropdown
            $leaveTypes = LeaveType::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->get(['id', 'name', 'color']);

            // Get years for filter
            $years = LeaveBalance::whereIn('created_by', getCompanyAndUsersId())
                ->distinct()
                ->pluck('year')
                ->sort()
                ->values();

            return Inertia::render('hr/leave-balances/index', [
                'leaveBalances' => $leaveBalances,
                'employees' => $this->getFilteredEmployees(),
                'leaveTypes' => $leaveTypes,
                'years' => $years,
                'filters' => $request->all(['search', 'employee_id', 'leave_type_id', 'year', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-leave-balances') && !Auth::user()->can('manage-any-leave-balances')) {
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
            'year' => 'required|integer|min:2020|max:2030',
            'allocated_days' => 'required|numeric|min:0',
            'carried_forward' => 'nullable|numeric|min:0',
            'manual_adjustment' => 'nullable|numeric',
            'adjustment_reason' => 'nullable|string',
        ]);

        $validated['created_by'] = creatorId();
        $validated['carried_forward'] = $validated['carried_forward'] ?? 0;
        $validated['manual_adjustment'] = $validated['manual_adjustment'] ?? 0;

        // Get leave policy for this leave type
        $leavePolicy = LeavePolicy::where('leave_type_id', $validated['leave_type_id'])
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->first();

        if (!$leavePolicy) {
            return redirect()->back()->with('error', __('No active policy found for selected leave type.'));
        }

        $validated['leave_policy_id'] = $leavePolicy->id;

        // Check if balance already exists
        $exists = LeaveBalance::where('employee_id', $validated['employee_id'])
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $validated['year'])
            ->whereIn('created_by', getCompanyAndUsersId())
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', __('Leave balance already exists for this employee, leave type, and year.'));
        }

        // Calculate remaining days
        $validated['used_days'] = 0;
        $validated['remaining_days'] = ($validated['allocated_days'] + $validated['carried_forward'] + $validated['manual_adjustment']) - $validated['used_days'];

        LeaveBalance::create($validated);

        return redirect()->back()->with('success', __('Leave balance created successfully.'));
    }

    public function update(Request $request, $leaveBalanceId)
    {
        $leaveBalance = LeaveBalance::where('id', $leaveBalanceId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveBalance) {
            try {
                $validated = $request->validate([
                    'employee_id' => 'required|exists:users,id',
                    'leave_type_id' => 'required|exists:leave_types,id',
                    'year' => 'required|integer|min:2020|max:2030',
                    'allocated_days' => 'required|numeric|min:0',
                    'carried_forward' => 'nullable|numeric|min:0',
                    'manual_adjustment' => 'nullable|numeric',
                    'adjustment_reason' => 'nullable|string',
                ]);

                $validated['carried_forward'] = $validated['carried_forward'] ?? 0;
                $validated['manual_adjustment'] = $validated['manual_adjustment'] ?? 0;

                // Get leave policy
                $leavePolicy = LeavePolicy::where('leave_type_id', $validated['leave_type_id'])
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->where('status', 'active')
                    ->first();

                if (!$leavePolicy) {
                    return redirect()->back()->with('error', __('No active policy found for selected leave type.'));
                }

                $validated['leave_policy_id'] = $leavePolicy->id;

                // Recalculate remaining days
                $validated['remaining_days'] = ($validated['allocated_days'] + $validated['carried_forward'] + $validated['manual_adjustment']) - $leaveBalance->used_days;

                $leaveBalance->update($validated);

                return redirect()->back()->with('success', __('Leave balance updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update leave balance'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave balance Not Found.'));
        }
    }

    public function destroy($leaveBalanceId)
    {
        $leaveBalance = LeaveBalance::where('id', $leaveBalanceId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveBalance) {
            try {
                $leaveBalance->delete();
                return redirect()->back()->with('success', __('Leave balance deleted successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete leave balance'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave balance Not Found.'));
        }
    }

    public function adjust(Request $request, $leaveBalanceId)
    {
        $validated = $request->validate([
            'manual_adjustment' => 'required|numeric',
            'adjustment_reason' => 'required|string',
        ]);

        $leaveBalance = LeaveBalance::where('id', $leaveBalanceId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveBalance) {
            try {
                $leaveBalance->update([
                    'manual_adjustment' => $validated['manual_adjustment'],
                    'adjustment_reason' => $validated['adjustment_reason'],
                ]);

                // Recalculate remaining days
                $leaveBalance->calculateRemainingDays();
                $leaveBalance->save();

                return redirect()->back()->with('success', __('Leave balance adjusted successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to adjust leave balance'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave balance Not Found.'));
        }
    }
}
