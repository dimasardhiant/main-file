<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TimeEntryController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-time-entries')) {
            $query = TimeEntry::with(['employee', 'approver', 'creator'])->where(function ($q) {
                if (Auth::user()->can('manage-any-time-entries')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-time-entries')) {
                    $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id())->orWhere('approved_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('description', 'like', '%' . $request->search . '%')
                        ->orWhere('project', 'like', '%' . $request->search . '%')
                        ->orWhereHas('employee', function ($subQ) use ($request) {
                            $subQ->where('name', 'like', '%' . $request->search . '%');
                        });
                });
            }

            // Handle employee filter
            if ($request->has('employee_id') && !empty($request->employee_id) && $request->employee_id !== 'all') {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle project filter
            if ($request->has('project') && !empty($request->project) && $request->project !== 'all') {
                $query->where('project', $request->project);
            }

            // Handle date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->where('date', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('date', 'desc');
            }

            $timeEntries = $query->paginate($request->per_page ?? 10);

            // Get employees for filter dropdown
            $employees = User::where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->get(['id', 'name']);

            // Get unique projects for filter dropdown
            $projects = TimeEntry::whereIn('created_by', getCompanyAndUsersId())
                ->whereNotNull('project')
                ->distinct()
                ->pluck('project');

            return Inertia::render('hr/time-entries/index', [
                'timeEntries' => $timeEntries,
                'employees' => $this->getFilteredEmployees(),
                'projects' => $projects,
                'filters' => $request->all(['search', 'employee_id', 'status', 'project', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-time-entries') && !Auth::user()->can('manage-any-time-entries')) {
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
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.5|max:24',
            'description' => 'required|string',
            'project' => 'nullable|string|max:255',
        ]);

        $validated['created_by'] = creatorId();

        TimeEntry::create($validated);

        return redirect()->back()->with('success', __('Time entry created successfully.'));
    }

    public function update(Request $request, $timeEntryId)
    {
        $timeEntry = TimeEntry::where('id', $timeEntryId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($timeEntry) {
            try {
                $validated = $request->validate([
                    'employee_id' => 'required|exists:users,id',
                    'date' => 'required|date',
                    'hours' => 'required|numeric|min:0.5|max:24',
                    'description' => 'required|string',
                    'project' => 'nullable|string|max:255',
                ]);

                // Only allow updates if status is pending
                if ($timeEntry->status !== 'pending') {
                    return redirect()->back()->with('error', __('Cannot update processed time entry.'));
                }

                $timeEntry->update($validated);

                return redirect()->back()->with('success', __('Time entry updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update time entry'));
            }
        } else {
            return redirect()->back()->with('error', __('Time entry Not Found.'));
        }
    }

    public function destroy($timeEntryId)
    {
        $timeEntry = TimeEntry::where('id', $timeEntryId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($timeEntry) {
            try {
                // Only allow deletion if status is pending
                if ($timeEntry->status !== 'pending') {
                    return redirect()->back()->with('error', __('Cannot delete processed time entry.'));
                }

                $timeEntry->delete();
                return redirect()->back()->with('success', __('Time entry deleted successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete time entry'));
            }
        } else {
            return redirect()->back()->with('error', __('Time entry Not Found.'));
        }
    }

    public function updateStatus(Request $request, $timeEntryId)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'manager_comments' => 'nullable|string',
        ]);

        $timeEntry = TimeEntry::where('id', $timeEntryId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($timeEntry) {
            try {
                $timeEntry->update([
                    'status' => $validated['status'],
                    'manager_comments' => $validated['manager_comments'],
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                return redirect()->back()->with('success', __('Time entry status updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update time entry status'));
            }
        } else {
            return redirect()->back()->with('error', __('Time entry Not Found.'));
        }
    }
}
