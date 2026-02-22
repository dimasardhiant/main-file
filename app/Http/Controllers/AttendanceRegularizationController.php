<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRegularization;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AttendanceRegularizationController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-attendance-regularizations')) {
            $query = AttendanceRegularization::with(['employee', 'attendanceRecord', 'approver', 'creator'])->where(function ($q) {
                if (Auth::user()->can('manage-any-attendance-regularizations')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-attendance-regularizations')) {
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
                $query->orderBy('created_at', 'desc');
            }

            $regularizations = $query->paginate($request->per_page ?? 10);

            // Get employees for filter dropdown
            $employees = User::where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->get(['id', 'name']);

            // Get attendance records for form dropdown
            $attendanceRecords = AttendanceRecord::whereIn('created_by', getCompanyAndUsersId())
                ->with('employee')
                ->orderBy('date', 'desc')
                ->take(50)
                ->get();

            return Inertia::render('hr/attendance-regularizations/index', [
                'regularizations' => $regularizations,
                'employees' => $this->getFilteredEmployees(),
                'attendanceRecords' => $attendanceRecords,
                'filters' => $request->all(['search', 'employee_id', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-attendance-regularizations') && !Auth::user()->can('manage-any-attendance-regularizations')) {
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
            'attendance_record_id' => 'required|exists:attendance_records,id',
            'requested_clock_in' => 'nullable|date_format:H:i',
            'requested_clock_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string',
        ]);

        $validated['created_by'] = creatorId();

        // Get attendance record to populate original times and date
        $attendanceRecord = AttendanceRecord::find($validated['attendance_record_id']);
        if (!$attendanceRecord) {
            return redirect()->back()->with('error', __('Attendance record not found.'));
        }

        $validated['date'] = $attendanceRecord->date;
        $validated['original_clock_in'] = $attendanceRecord->clock_in;
        $validated['original_clock_out'] = $attendanceRecord->clock_out;

        // Check if regularization already exists for this record
        $exists = AttendanceRegularization::where('attendance_record_id', $validated['attendance_record_id'])
            ->whereIn('created_by', getCompanyAndUsersId())
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', __('Regularization request already exists for this attendance record.'));
        }

        AttendanceRegularization::create($validated);

        return redirect()->back()->with('success', __('Regularization request created successfully.'));
    }

    public function update(Request $request, $regularizationId)
    {
        $regularization = AttendanceRegularization::where('id', $regularizationId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($regularization) {
            try {
                $validated = $request->validate([
                    'employee_id' => 'required|exists:users,id',
                    'attendance_record_id' => 'required|exists:attendance_records,id',
                    'requested_clock_in' => 'nullable|date_format:H:i',
                    'requested_clock_out' => 'nullable|date_format:H:i',
                    'reason' => 'required|string',
                ]);

                // Only allow updates if status is pending
                if ($regularization->status !== 'pending') {
                    return redirect()->back()->with('error', __('Cannot update processed regularization request.'));
                }

                $regularization->update($validated);

                return redirect()->back()->with('success', __('Regularization request updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update regularization request'));
            }
        } else {
            return redirect()->back()->with('error', __('Regularization request Not Found.'));
        }
    }

    public function destroy($regularizationId)
    {
        $regularization = AttendanceRegularization::where('id', $regularizationId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($regularization) {
            try {
                // Only allow deletion if status is pending
                if ($regularization->status !== 'pending') {
                    return redirect()->back()->with('error', __('Cannot delete processed regularization request.'));
                }

                $regularization->delete();
                return redirect()->back()->with('success', __('Regularization request deleted successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete regularization request'));
            }
        } else {
            return redirect()->back()->with('error', __('Regularization request Not Found.'));
        }
    }

    public function updateStatus(Request $request, $regularizationId)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'manager_comments' => 'nullable|string',
        ]);

        $regularization = AttendanceRegularization::where('id', $regularizationId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($regularization) {
            try {
                $regularization->update([
                    'status' => $validated['status'],
                    'manager_comments' => $validated['manager_comments'],
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                // Apply changes to attendance record if approved
                if ($validated['status'] === 'approved') {
                    $regularization->applyToAttendanceRecord();
                }

                return redirect()->back()->with('success', __('Regularization request status updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update regularization request status'));
            }
        } else {
            return redirect()->back()->with('error', __('Regularization request Not Found.'));
        }
    }

    public function getEmployeeAttendance($employeeId)
    {
        try {
            // Get attendance records for the last 30 days
            $attendanceRecords = AttendanceRecord::where('employee_id', $employeeId)
                ->with('employee')
                ->whereDate('date', '>=', now()->subDays(30))
                ->orderBy('date', 'desc')
                ->get([
                    'id',
                    'employee_id',
                    'date',
                    'clock_in',
                    'clock_out',
                    'status',
                    'is_late',
                    'is_early_departure'
                ]);

            $datesForDropdown = $attendanceRecords->map(function ($record) {
                return [
                    'label' => $record->date->format('d/m/Y'),
                    'value' => $record->id,
                ];
            });


            return response()->json($datesForDropdown);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
