<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-complaints')) {
            $query = Complaint::with(['employee', 'againstEmployee', 'assignedUser'])->where(function ($q) {
                if (Auth::user()->can('manage-any-complaints')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-complaints')) {
                    $q->where('created_by', Auth::id())->orWhere('against_employee_id', Auth::id())->orWhere('assigned_to', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('subject', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%')
                        ->orWhere('complaint_type', 'like', '%' . $request->search . '%')
                        ->orWhereHas('employee', function ($q) use ($request) {
                            $q->where('name', 'like', '%' . $request->search . '%')
                                ->orWhere('employee_id', 'like', '%' . $request->search . '%');
                        })
                        ->orWhereHas('againstEmployee', function ($q) use ($request) {
                            $q->where('name', 'like', '%' . $request->search . '%')
                                ->orWhere('employee_id', 'like', '%' . $request->search . '%');
                        });
                });
            }

            // Handle employee filter
            if ($request->has('employee_id') && !empty($request->employee_id)) {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle against employee filter
            if ($request->has('against_employee_id') && !empty($request->against_employee_id)) {
                $query->where('against_employee_id', $request->against_employee_id);
            }

            // Handle complaint type filter
            if ($request->has('complaint_type') && !empty($request->complaint_type)) {
                $query->where('complaint_type', $request->complaint_type);
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('complaint_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('complaint_date', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $complaints = $query->paginate($request->per_page ?? 10);

            // Get employees for complainant dropdown
            $complainants = User::with('employee')
                ->where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'employee_id' => $user->employee->employee_id ?? $user->id
                    ];
                });

            // Get employees for against dropdown
            $againstEmployees = User::emp()->with('employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'employee_id' => $user->employee->employee_id ?? $user->id,
                        'type' => $user->type,
                    ];
                });

            // Get HR personnel for assignment dropdown
            $hrPersonnel = User::whereIn('created_by', getCompanyAndUsersId())
                ->whereIn('type', ['hr', 'manager', 'company'])   // <-- Add this line
                ->select('id', 'name', 'type')
                ->get();

            // Get complaint types for filter dropdown
            $complaintTypes = Complaint::whereIn('created_by', getCompanyAndUsersId())
                ->select('complaint_type')
                ->distinct()
                ->pluck('complaint_type')
                ->toArray();

            return Inertia::render('hr/complaints/index', [
                'complaints' => $complaints,
                'complainants' => $this->getFilteredEmployees(),
                'againstEmployees' => $againstEmployees,
                'hrPersonnel' => $hrPersonnel,
                'complaintTypes' => $complaintTypes,
                'filters' => $request->all(['search', 'employee_id', 'against_employee_id', 'complaint_type', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-complaints') && !Auth::user()->can('manage-any-complaints')) {
            $employeeQuery->where(function ($q) {
                $q->where('created_by', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        $employees = User::emp()
            ->with('employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->whereIn('id', $employeeQuery->pluck('user_id'))
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
        return $employees;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->can('create-complaints')) {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:users,id',
                'against_employee_id' => 'nullable|exists:users,id|different:employee_id',
                'complaint_type' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'complaint_date' => 'required|date',
                'description' => 'nullable|string',
                'documents' => 'nullable|string',
                'is_anonymous' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Check if employee belongs to current company
            $employee = User::find($request->employee_id);
            if (!$employee || !in_array($employee->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('Invalid employee selected'));
            }

            // Check if against_employee belongs to current company
            if ($request->against_employee_id) {
                $againstEmployee = User::find($request->against_employee_id);
                if (!$againstEmployee || !in_array($againstEmployee->created_by, getCompanyAndUsersId())) {
                    return redirect()->back()->with('error', __('Invalid employee selected for complaint against'));
                }
            }

            $complaintData = [
                'employee_id' => $request->employee_id,
                'against_employee_id' => $request->against_employee_id,
                'complaint_type' => $request->complaint_type,
                'subject' => $request->subject,
                'complaint_date' => $request->complaint_date,
                'description' => $request->description,
                'status' => 'submitted',
                'is_anonymous' => $request->is_anonymous ?? false,
                'created_by' => creatorId(),
            ];

            // Handle document from media library
            if ($request->has('documents')) {
                $complaintData['documents'] = $request->documents;
            }

            Complaint::create($complaintData);

            return redirect()->back()->with('success', __('Complaint created successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Complaint $complaint)
    {
        if (Auth::user()->can('edit-complaints')) {
            // Check if complaint belongs to current company
            if (!in_array($complaint->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this complaint'));
            }

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:users,id',
                'against_employee_id' => 'nullable|exists:users,id|different:employee_id',
                'complaint_type' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'complaint_date' => 'required|date',
                'description' => 'nullable|string',
                'status' => 'nullable|string|in:submitted,under investigation,resolved,dismissed',
                'documents' => 'nullable|string',
                'is_anonymous' => 'nullable|boolean',
                'assigned_to' => 'nullable|exists:users,id',
                'resolution_deadline' => 'nullable|date|after_or_equal:complaint_date',
                'investigation_notes' => 'nullable|string',
                'resolution_action' => 'nullable|string',
                'resolution_date' => 'nullable|date|after_or_equal:complaint_date',
                'follow_up_action' => 'nullable|string',
                'follow_up_date' => 'nullable|date|after_or_equal:resolution_date',
                'feedback' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Check if employee belongs to current company
            $employee = User::find($request->employee_id);
            if (!$employee || !in_array($employee->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('Invalid employee selected'));
            }

            // Check if against_employee belongs to current company
            if ($request->against_employee_id) {
                $againstEmployee = User::find($request->against_employee_id);
                if (!$againstEmployee || !in_array($againstEmployee->created_by, getCompanyAndUsersId())) {
                    return redirect()->back()->with('error', __('Invalid employee selected for complaint against'));
                }
            }

            // Check if assigned user belongs to current company
            if ($request->assigned_to) {
                $assignedUser = User::find($request->assigned_to);
                if (!$assignedUser || (!in_array($assignedUser->created_by, getCompanyAndUsersId()) && !in_array($assignedUser->id, getCompanyAndUsersId()))) {
                    return redirect()->back()->with('error', __('Invalid user selected for assignment'));
                }
            }

            $complaintData = [
                'employee_id' => $request->employee_id,
                'against_employee_id' => $request->against_employee_id,
                'complaint_type' => $request->complaint_type,
                'subject' => $request->subject,
                'complaint_date' => $request->complaint_date,
                'description' => $request->description,
                'status' => $request->status ?? $complaint->status,
                'is_anonymous' => $request->is_anonymous ?? $complaint->is_anonymous,
                'assigned_to' => $request->assigned_to,
                'resolution_deadline' => $request->resolution_deadline,
                'investigation_notes' => $request->investigation_notes,
                'resolution_action' => $request->resolution_action,
                'resolution_date' => $request->resolution_date,
                'follow_up_action' => $request->follow_up_action,
                'follow_up_date' => $request->follow_up_date,
                'feedback' => $request->feedback,
            ];

            // Handle document from media library
            if ($request->has('documents')) {
                $complaintData['documents'] = $request->documents;
            }

            $complaint->update($complaintData);

            return redirect()->back()->with('success', __('Complaint updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Complaint $complaint)
    {
        if (Auth::user()->can('delete-complaints')) {
            // Check if complaint belongs to current company
            if (!in_array($complaint->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to delete this complaint'));
            }

            $complaint->delete();

            return redirect()->back()->with('success', __('Complaint deleted successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Change the status of the complaint.
     */
    public function changeStatus(Request $request, Complaint $complaint)
    {
        if (Auth::user()->can('edit-complaints')) {
            // Check if complaint belongs to current company
            if (!in_array($complaint->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this complaint'));
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:submitted,under investigation,resolved,dismissed',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $complaint->update([
                'status' => $request->status,
            ]);

            return redirect()->back()->with('success', __('Complaint status updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Assign the complaint to an HR personnel.
     */
    public function assignComplaint(Request $request, Complaint $complaint)
    {
        if (Auth::user()->can('assign-complaints')) {
            // Check if complaint belongs to current company
            if (!in_array($complaint->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this complaint'));
            }

            $validator = Validator::make($request->all(), [
                'assigned_to' => 'required|exists:users,id',
                'resolution_deadline' => 'nullable|date|after_or_equal:today',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Check if assigned user belongs to current company
            $assignedUser = User::find($request->assigned_to);
            if (!$assignedUser || (!in_array($assignedUser->created_by, getCompanyAndUsersId()) && !in_array($assignedUser->id, getCompanyAndUsersId()))) {
                return redirect()->back()->with('error', __('Invalid user selected for assignment'));
            }

            $updateData = [
                'assigned_to' => $request->assigned_to,
                'resolution_deadline' => $request->resolution_deadline,
            ];

            // If complaint is in submitted status, change to under investigation
            if ($complaint->status === 'submitted') {
                $updateData['status'] = 'under investigation';
            }

            $complaint->update($updateData);

            return redirect()->back()->with('success', __('Complaint assigned successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Resolve the complaint.
     */
    public function resolveComplaint(Request $request, Complaint $complaint)
    {
        if (Auth::user()->can('resolve-complaints')) {
            // Check if complaint belongs to current company
            if (!in_array($complaint->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this complaint'));
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:resolved,dismissed',
                'investigation_notes' => 'required|string',
                'resolution_action' => 'required|string',
                'resolution_date' => 'required|date',
                'follow_up_action' => 'nullable|string',
                'follow_up_date' => 'nullable|date|after_or_equal:resolution_date',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $complaint->update([
                'status' => $request->status,
                'investigation_notes' => $request->investigation_notes,
                'resolution_action' => $request->resolution_action,
                'resolution_date' => $request->resolution_date,
                'follow_up_action' => $request->follow_up_action,
                'follow_up_date' => $request->follow_up_date,
            ]);

            return redirect()->back()->with('success', __('Complaint resolved successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update follow-up information.
     */
    public function updateFollowUp(Request $request, Complaint $complaint)
    {
        if (Auth::user()->can('resolve-complaints')) {
            // Check if complaint belongs to current company
            if (!in_array($complaint->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this complaint'));
            }

            $validator = Validator::make($request->all(), [
                'follow_up_action' => 'required|string',
                'follow_up_date' => 'required|date',
                'feedback' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $complaint->update([
                'follow_up_action' => $request->follow_up_action,
                'follow_up_date' => $request->follow_up_date,
                'feedback' => $request->feedback,
            ]);

            return redirect()->back()->with('success', __('Follow-up information updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Download document file.
     */
    public function downloadDocument(Complaint $complaint)
    {
        if (Auth::user()->can('view-complaints')) {
            // Check if complaint belongs to current company
            if (!in_array($complaint->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to access this document'));
            }

            if (!$complaint->documents) {
                return redirect()->back()->with('error', __('Document file not found'));
            }

            $filePath = getStorageFilePath($complaint->documents);

            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', __('Document file not found'));
            }

            return response()->download($filePath);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
