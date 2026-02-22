<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\Resignation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ResignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-resignations')) {
            $query = Resignation::with(['employee', 'approver'])->where(function ($q) {
                if (Auth::user()->can('manage-any-resignations')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-resignations')) {
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
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            }

            // Handle employee filter
            if ($request->has('employee_id') && !empty($request->employee_id)) {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('resignation_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('resignation_date', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $resignations = $query->paginate($request->per_page ?? 10);



            return Inertia::render('hr/resignations/index', [
                'resignations' => $resignations,
                'employees' => $this->getFilteredEmployees(),
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

        if (Auth::user()->can('manage-own-resignations') && !Auth::user()->can('manage-any-resignations')) {
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
        if (Auth::user()->can('create-resignations')) {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:users,id',
                'resignation_date' => 'required|date',
                'last_working_day' => 'required|date|after_or_equal:resignation_date',
                'notice_period' => 'nullable|string|max:255',
                'reason' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'documents' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Check if employee belongs to current company
            $user = User::where('id', $request->employee_id)
                ->where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();
            if (!$user) {
                return redirect()->back()->with('error', __('Invalid employee selected'));
            }

            $resignationData = [
                'employee_id' => $request->employee_id,
                'resignation_date' => $request->resignation_date,
                'last_working_day' => $request->last_working_day,
                'notice_period' => $request->notice_period,
                'reason' => $request->reason,
                'description' => $request->description,
                'status' => 'pending',
                'created_by' => creatorId(),
            ];

            // Handle document from media library
            if ($request->has('documents')) {
                $resignationData['documents'] = $request->documents;
            }

            Resignation::create($resignationData);

            return redirect()->back()->with('success', __('Resignation created successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Resignation $resignation)
    {
        if (Auth::user()->can('edit-resignations')) {
            // Check if resignation belongs to current company
            if (!in_array($resignation->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this resignation'));
            }

        // Convert checkbox values to proper booleans before validation
        if ($request->has('exit_interview_conducted')) {
            $request->merge([
                'exit_interview_conducted' => $request->exit_interview_conducted === 'true' ||
                    $request->exit_interview_conducted === '1' ||
                    $request->exit_interview_conducted === 1 ||
                    $request->exit_interview_conducted === true
            ]);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'resignation_date' => 'required|date',
            'last_working_day' => 'required|date|after_or_equal:resignation_date',
            'notice_period' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,rejected,completed',
            'documents' => 'nullable|string',
            'exit_feedback' => 'nullable|string',
            'exit_interview_conducted' => 'nullable|boolean',
            'exit_interview_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if employee belongs to current company
        $user = User::where('id', $request->employee_id)
            ->where('type', 'employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();
        if (!$user) {
            return redirect()->back()->with('error', __('Invalid employee selected'));
        }

        $resignationData = [
            'employee_id' => $request->employee_id,
            'resignation_date' => $request->resignation_date,
            'last_working_day' => $request->last_working_day,
            'notice_period' => $request->notice_period,
            'reason' => $request->reason,
            'description' => $request->description,
            'exit_feedback' => $request->exit_feedback,
            'exit_interview_conducted' => $request->exit_interview_conducted ?? false,
            'exit_interview_date' => $request->exit_interview_date,
        ];

        // Update status if provided and different from current
        if ($request->has('status') && $request->status !== $resignation->status) {
            $resignationData['status'] = $request->status;

            // If status is being set to approved or completed, set approved_by and approved_at
            if (in_array($request->status, ['approved', 'completed']) && !$resignation->approved_by) {
                $resignationData['approved_by'] = auth()->id();
                $resignationData['approved_at'] = now();
            }
        }

        // Handle document from media library
        if ($request->has('documents')) {
            $resignationData['documents'] = $request->documents;
        }

        $resignation->update($resignationData);

        return redirect()->back()->with('success', __('Resignation updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resignation $resignation)
    {
        if (Auth::user()->can('delete-resignations')) {
            // Check if resignation belongs to current company
            if (!in_array($resignation->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to delete this resignation'));
            }

            // Delete associated files
            if ($resignation->documents) {
                Storage::disk('public')->delete($resignation->documents);
            }

            $resignation->delete();

            return redirect()->back()->with('success', __('Resignation deleted successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Change the status of the resignation.
     */
    public function changeStatus(Request $request, Resignation $resignation)
    {
        if (Auth::user()->can('approve-resignations') || Auth::user()->can('reject-resignations') || Auth::user()->can('edit-resignations')) {
            // Check if resignation belongs to current company
            if (!in_array($resignation->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this resignation'));
            }

        // Convert checkbox values to proper booleans before validation
        if ($request->has('exit_interview_conducted')) {
            $request->merge([
                'exit_interview_conducted' => $request->exit_interview_conducted === 'true' ||
                    $request->exit_interview_conducted === '1' ||
                    $request->exit_interview_conducted === 1 ||
                    $request->exit_interview_conducted === true
            ]);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,approved,rejected,completed',
            'exit_feedback' => 'nullable|string|required_if:status,completed',
            'exit_interview_conducted' => 'nullable|boolean',
            'exit_interview_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'status' => $request->status,
        ];

        // If status is being set to approved or completed, set approved_by and approved_at
        if (in_array($request->status, ['approved', 'completed']) && !$resignation->approved_by) {
            $updateData['approved_by'] = auth()->id();
            $updateData['approved_at'] = now();
        }

        // If status is completed, update exit interview details
        if ($request->status === 'completed') {
            $updateData['exit_feedback'] = $request->exit_feedback;
            $updateData['exit_interview_conducted'] = $request->exit_interview_conducted ?? false;
            $updateData['exit_interview_date'] = $request->exit_interview_date;
        }

        $resignation->update($updateData);

        return redirect()->back()->with('success', __('Resignation status updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Download document file.
     */
    public function downloadDocument(Resignation $resignation)
    {
        if (Auth::user()->can('view-resignations')) {
            // Check if resignation belongs to current company
            if (!in_array($resignation->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to access this document'));
            }

            if (!$resignation->documents) {
                return redirect()->back()->with('error', __('Document file not found'));
            }

            $filePath = getStorageFilePath($resignation->documents);

            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', __('Certificate file not found'));
            }

            return response()->download($filePath);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
