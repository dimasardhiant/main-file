<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class EmployeeTransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-employee-transfers')) {
            $query = EmployeeTransfer::with([
                'employee',
                'fromBranch:id,name',
                'toBranch:id,name',
                'fromDepartment:id,name',
                'toDepartment:id,name',
                'fromDesignation:id,name',
                'toDesignation:id,name',
                'approver'
            ])->where(function ($q) {

                if (Auth::user()->can('manage-any-employee-transfers')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-employee-transfers')) {
                    $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('employee_id', 'like', '%' . $request->search . '%');
                })
                    ->orWhere('reason', 'like', '%' . $request->search . '%')
                    ->orWhere('notes', 'like', '%' . $request->search . '%');
            }

            // Handle employee filter
            if ($request->has('employee_id') && !empty($request->employee_id)) {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle branch filter
            if ($request->has('branch_id') && !empty($request->branch_id)) {
                $query->where(function ($q) use ($request) {
                    $q->where('from_branch_id', $request->branch_id)
                        ->orWhere('to_branch_id', $request->branch_id);
                });
            }

            // Handle department filter
            if ($request->has('department_id') && !empty($request->department_id)) {
                $query->where(function ($q) use ($request) {
                    $q->where('from_department_id', $request->department_id)
                        ->orWhere('to_department_id', $request->department_id);
                });
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('transfer_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('transfer_date', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            $transfers = $query->paginate($request->per_page ?? 10);

            // Get employees for filter dropdown
            $employees = User::with('employee')
                ->where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name', 'type')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'employee_id' => $user->employee->employee_id ?? '',
                        'type' => $user->type,
                    ];
                });

            // Get branches for filter dropdown
            $branches = Branch::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'name')
                ->get();

            // Get departments for filter dropdown
            $departments = Department::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'name', 'branch_id')
                ->get();

            // Get designations for form dropdown
            $designations = \App\Models\Designation::whereIn('created_by', getCompanyAndUsersId())
                ->with('department:id,name,branch_id')
                ->select('id', 'name', 'department_id')
                ->get();

            return Inertia::render('hr/transfers/index', [
                'transfers' => $transfers,
                'employees' => $this->getFilteredEmployees(),
                'branches' => $branches,
                'departments' => $departments,
                'designations' => $designations,
                'filters' => $request->all(['search', 'employee_id', 'branch_id', 'department_id', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-employee-transfers') && !Auth::user()->can('manage-any-employee-transfers')) {
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
                    'employee_id' => $user->employee->employee_id ?? ''
                ];
            });
        return $employees;
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'to_branch_id' => 'nullable|exists:branches,id',
            'to_department_id' => 'nullable|exists:departments,id',
            'to_designation_id' => 'nullable|exists:designations,id',
            'transfer_date' => 'required|date',
            'effective_date' => 'required|date|after_or_equal:transfer_date',
            'reason' => 'nullable|string',
            'documents' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if employee belongs to current company
        $employee = User::with('employee')->find($request->employee_id);
        if (!$employee || !in_array($employee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('Invalid employee selected'));
        }

        // Ensure at least one transfer destination is specified
        if (empty($request->to_branch_id) && empty($request->to_department_id) && empty($request->to_designation_id)) {
            return redirect()->back()->with('error', __('At least one transfer destination (branch, department, or designation) must be specified'));
        }

        // Get current employee details
        $currentBranchId = $employee->employee->branch_id;
        $currentDepartmentId = $employee->employee->department_id;
        $currentDesignationId = $employee->employee->designation_id;

        $transferData = [
            'employee_id' => $request->employee_id,
            'transfer_date' => $request->transfer_date,
            'effective_date' => $request->effective_date,
            'reason' => $request->reason,
            'status' => 'pending',
            'created_by' => creatorId(),
        ];

        // Set from and to branch IDs if branch transfer
        if ($request->to_branch_id) {
            $transferData['from_branch_id'] = $currentBranchId;
            $transferData['to_branch_id'] = $request->to_branch_id;
        }

        // Set from and to department IDs if department transfer
        if ($request->to_department_id) {
            $transferData['from_department_id'] = $currentDepartmentId;
            $transferData['to_department_id'] = $request->to_department_id;
        }

        // Set from and to designation IDs if designation transfer
        if ($request->to_designation_id) {
            $transferData['from_designation_id'] = $currentDesignationId;
            $transferData['to_designation_id'] = $request->to_designation_id;
        }

        // Handle document from media library
        if ($request->has('documents')) {
            $transferData['documents'] = $request->documents;
        }

        EmployeeTransfer::create($transferData);

        return redirect()->back()->with('success', __('Transfer request created successfully'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeTransfer $transfer)
    {
        // Check if transfer belongs to current company
        if (!in_array($transfer->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this transfer');
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'to_branch_id' => 'nullable|exists:branches,id',
            'to_department_id' => 'nullable|exists:departments,id',
            'to_designation_id' => 'nullable|exists:designations,id',
            'transfer_date' => 'required|date',
            'effective_date' => 'required|date|after_or_equal:transfer_date',
            'reason' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,rejected',
            'documents' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if employee belongs to current company
        $employee = User::find($request->employee_id);
        if (!$employee || !in_array($employee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('Invalid employee selected'));
        }

        // Ensure at least one transfer destination is specified
        if (empty($request->to_branch_id) && empty($request->to_department_id) && empty($request->to_designation_id)) {
            return redirect()->back()->with('error', __('At least one transfer destination (branch, department, or designation) must be specified'));
        }

        $transferData = [
            'employee_id' => $request->employee_id,
            'transfer_date' => $request->transfer_date,
            'effective_date' => $request->effective_date,
            'reason' => $request->reason,
            'status' => $request->status ?? $transfer->status,
            'notes' => $request->notes,
        ];

        // Set from and to branch IDs if branch transfer
        if ($request->to_branch_id) {
            $transferData['to_branch_id'] = $request->to_branch_id;
        }

        // Set from and to department IDs if department transfer
        if ($request->to_department_id) {
            $transferData['to_department_id'] = $request->to_department_id;
        }

        // Set from and to designation IDs if designation transfer
        if ($request->to_designation_id) {
            $transferData['to_designation_id'] = $request->to_designation_id;
        }

        // Handle document from media library
        if ($request->has('documents')) {
            $transferData['documents'] = $request->documents;
        }

        // If status is being changed to approved or rejected, set approved_by and approved_at
        if ($request->has('status') && in_array($request->status, ['approved', 'rejected']) && $transfer->status === 'pending') {
            $transferData['approved_by'] = auth()->id();
            $transferData['approved_at'] = now();

            // If approved and effective date has passed or is today, update employee details
            if ($request->status === 'approved') {
                $user = User::with('employee')->find($request->employee_id);

                if ($user && $user->employee) {
                    if (isset($transferData['to_branch_id'])) {
                        $user->employee->branch_id = $transferData['to_branch_id'];
                    }
                    if (isset($transferData['to_department_id'])) {
                        $user->employee->department_id = $transferData['to_department_id'];
                    }
                    if (isset($transferData['to_designation_id'])) {
                        $user->employee->designation_id = $transferData['to_designation_id'];
                    }

                    $user->employee->save();
                }
            }
        }

        $transfer->update($transferData);

        return redirect()->back()->with('success', __('Transfer updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeTransfer $transfer)
    {
        // Check if transfer belongs to current company
        if (!in_array($transfer->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to delete this transfer');
        }

        // Only allow deletion of pending transfers
        if ($transfer->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending transfers can be deleted');
        }

        $transfer->delete();

        return redirect()->back()->with('success', __('Transfer deleted successfully'));
    }

    /**
     * Approve the transfer.
     */
    public function approve(Request $request, EmployeeTransfer $transfer)
    {
        // Check if transfer belongs to current company
        if (!in_array($transfer->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to approve this transfer');
        }

        // Only allow approval of pending transfers
        if ($transfer->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending transfers can be approved');
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $request->notes,
        ];

        $transfer->update($updateData);

        // If effective date has passed or is today, update employee details

        $user = User::with('employee')->find($transfer->employee_id);

        if ($user && $user->employee) {
            if ($transfer->to_branch_id) {
                $user->employee->branch_id = $transfer->to_branch_id;
            }
            if ($transfer->to_department_id) {
                $user->employee->department_id = $transfer->to_department_id;
            }
            if ($transfer->to_designation_id) {
                $user->employee->designation_id = $transfer->to_designation_id;
            }

            $user->employee->save();
        }


        return redirect()->back()->with('success', __('Transfer approved successfully'));
    }

    /**
     * Reject the transfer.
     */
    public function reject(Request $request, EmployeeTransfer $transfer)
    {
        // Check if transfer belongs to current company
        if (!in_array($transfer->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to reject this transfer');
        }

        // Only allow rejection of pending transfers
        if ($transfer->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending transfers can be rejected');
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $transfer->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', __('Transfer rejected successfully'));
    }

    /**
     * Download document file.
     */
    public function downloadDocument(EmployeeTransfer $transfer)
    {
        // Check if transfer belongs to current company
        if (!in_array($transfer->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to access this document'));
        }

        if (!$transfer->documents) {
            return redirect()->back()->with('error', __('Document file not found'));
        }

        $filePath = getStorageFilePath($transfer->documents);

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', __('Document file not found'));
        }

        return response()->download($filePath);
    }

    public function getDepartment($branchId)
    {
        try {
            $branch = Branch::with('departments')->find($branchId);

            if (!$branch) {
                return response()->json(['error' => 'Branch not found'], 404);
            }

            // Map departments into dropdown-friendly format
            $departmentsForDropdown = $branch->departments->map(function ($department) {
                return [
                    'label' => $department->name,
                    'value' => $department->id,
                ];
            });

            return response()->json($departmentsForDropdown);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDesignation($departmentId)
    {
        try {
            $department = Department::with('desginations')->find($departmentId);

            if (!$department) {
                return response()->json(['error' => 'Department not found'], 404);
            }

            // Map departments into dropdown-friendly format
            $designationDropdown = $department->desginations->map(function ($designation) {
                return [
                    'label' => $designation->name,
                    'value' => $designation->id,
                ];
            });

            return response()->json($designationDropdown);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
