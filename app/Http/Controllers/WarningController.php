<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User as UserModel;
use App\Models\Warning;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class WarningController extends Controller
{

    public function index(Request $request)
    {
        if (Auth::user()->can('manage-warnings')) {
            $query = Warning::with(['employee', 'issuer', 'approver'])->where(function ($q) {
                if (Auth::user()->can('manage-any-warnings')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-warnings')) {
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
                    ->orWhere('subject', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            }

            // Handle employee filter
            if ($request->has('employee_id') && !empty($request->employee_id)) {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle warning type filter
            if ($request->has('warning_type') && !empty($request->warning_type)) {
                $query->where('warning_type', $request->warning_type);
            }

            // Handle severity filter
            if ($request->has('severity') && !empty($request->severity)) {
                $query->where('severity', $request->severity);
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('warning_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('warning_date', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $warnings = $query->paginate($request->per_page ?? 10);

            // Get managers for issuer dropdown
            $managers = User::whereIn('created_by', getCompanyAndUsersId())
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'like', '%Manager%')
                      ->orWhere('name', 'like', '%HR%');
                })
                ->select('id', 'name')
                ->get();


            // Get warning types for filter dropdown
            $warningTypes = Warning::whereIn('created_by', getCompanyAndUsersId())
                ->select('warning_type')
                ->distinct()
                ->pluck('warning_type')
                ->toArray();

            return Inertia::render('hr/warnings/index', [
                'warnings' => $warnings,
                'employees' => $this->getFilteredEmployees(),
                'managers' => $managers,
                'warningTypes' => $warningTypes,
                'filters' => $request->all(['search', 'employee_id', 'warning_type', 'severity', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-warnings') && !Auth::user()->can('manage-any-warnings')) {
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
        if (Auth::user()->can('create-warnings')) {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:users,id',
                'warning_by' => 'required|exists:users,id',
                'warning_type' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'severity' => 'required|string|in:verbal,written,final',
                'warning_date' => 'required|date',
                'description' => 'nullable|string',
                'documents' => 'nullable|string',
                'expiry_date' => 'nullable|date|after:warning_date',
                'has_improvement_plan' => 'nullable|boolean',
                'improvement_plan_goals' => 'nullable|string|required_if:has_improvement_plan,true',
                'improvement_plan_start_date' => 'nullable|date|required_if:has_improvement_plan,true',
                'improvement_plan_end_date' => 'nullable|date|after:improvement_plan_start_date|required_if:has_improvement_plan,true',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Check if employee belongs to current company
            $user = UserModel::where('id', $request->employee_id)
                ->where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();
            if (!$user) {
                return redirect()->back()->with('error', __('Invalid employee selected'));
            }

            // Check if warning issuer belongs to current company
            $issuer = User::find($request->warning_by);
            if (!$issuer || (!in_array($issuer->created_by, getCompanyAndUsersId()) && !in_array($issuer->id, getCompanyAndUsersId()))) {
                return redirect()->back()->with('error', __('Invalid warning issuer selected'));
            }

            $warningData = [
                'employee_id' => $request->employee_id,
                'warning_by' => $request->warning_by,
                'warning_type' => $request->warning_type,
                'subject' => $request->subject,
                'severity' => $request->severity,
                'warning_date' => $request->warning_date,
                'description' => $request->description,
                'status' => 'draft',
                'expiry_date' => $request->expiry_date,
                'has_improvement_plan' => $request->has_improvement_plan ?? false,
                'improvement_plan_goals' => $request->improvement_plan_goals,
                'improvement_plan_start_date' => $request->improvement_plan_start_date,
                'improvement_plan_end_date' => $request->improvement_plan_end_date,
                'created_by' => creatorId(),
            ];

            // Handle document from media library
            if ($request->has('documents')) {
                $warningData['documents'] = $request->documents;
            }

            Warning::create($warningData);

            return redirect()->back()->with('success', __('Warning created successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Warning $warning)
    {
        if (Auth::user()->can('edit-warnings')) {
            // Check if warning belongs to current company
            if (!in_array($warning->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', 'You do not have permission to update this warning');
            }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'warning_by' => 'required|exists:users,id',
            'warning_type' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'severity' => 'required|string|in:verbal,written,final',
            'warning_date' => 'required|date',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:draft,issued,acknowledged,expired',
            'documents' => 'nullable|string',
            'acknowledgment_date' => 'nullable|date|after_or_equal:warning_date',
            'employee_response' => 'nullable|string',
            'expiry_date' => 'nullable|date|after:warning_date',
            'has_improvement_plan' => 'nullable|boolean',
            'improvement_plan_goals' => 'nullable|string|required_if:has_improvement_plan,true',
            'improvement_plan_start_date' => 'nullable|date|required_if:has_improvement_plan,true',
            'improvement_plan_end_date' => 'nullable|date|after:improvement_plan_start_date|required_if:has_improvement_plan,true',
            'improvement_plan_progress' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if employee belongs to current company
        $user = UserModel::where('id', $request->employee_id)
            ->where('type', 'employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();
        if (!$user) {
            return redirect()->back()->with('error', 'Invalid employee selected');
        }

        // Check if warning issuer belongs to current company
        $issuer = User::find($request->warning_by);
        if (!$issuer || (!in_array($issuer->created_by, getCompanyAndUsersId()) && !in_array($issuer->id, getCompanyAndUsersId()))) {
            return redirect()->back()->with('error', 'Invalid warning issuer selected');
        }

        $warningData = [
            'employee_id' => $request->employee_id,
            'warning_by' => $request->warning_by,
            'warning_type' => $request->warning_type,
            'subject' => $request->subject,
            'severity' => $request->severity,
            'warning_date' => $request->warning_date,
            'description' => $request->description,
            'acknowledgment_date' => $request->acknowledgment_date,
            'employee_response' => $request->employee_response,
            'expiry_date' => $request->expiry_date,
            'has_improvement_plan' => $request->has_improvement_plan ?? false,
            'improvement_plan_goals' => $request->improvement_plan_goals,
            'improvement_plan_start_date' => $request->improvement_plan_start_date,
            'improvement_plan_end_date' => $request->improvement_plan_end_date,
            'improvement_plan_progress' => $request->improvement_plan_progress,
        ];

        // Update status if provided and different from current
        if ($request->has('status') && $request->status !== $warning->status) {
            $warningData['status'] = $request->status;

            // If status is being set to issued, acknowledged, or expired, set approved_by and approved_at
            if (in_array($request->status, ['issued', 'acknowledged', 'expired']) && !$warning->approved_by) {
                $warningData['approved_by'] = auth()->id();
                $warningData['approved_at'] = now();
            }
        }

        // Handle document from media library
        if ($request->has('documents')) {
            $warningData['documents'] = $request->documents;
        }

        $warning->update($warningData);

        return redirect()->back()->with('success', __('Warning updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warning $warning)
    {
        if (Auth::user()->can('delete-warnings')) {
            // Check if warning belongs to current company
            if (!in_array($warning->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to delete this warning'));
            }
            $warning->delete();

            return redirect()->back()->with('success', __('Warning deleted successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Change the status of the warning.
     */
    public function changeStatus(Request $request, Warning $warning)
    {
        if (Auth::user()->can('approve-warnings') || Auth::user()->can('acknowledge-warnings') || Auth::user()->can('edit-warnings')) {
            // Check if warning belongs to current company
            if (!in_array($warning->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this warning'));
            }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:draft,issued,acknowledged,expired',
            'acknowledgment_date' => 'nullable|date|required_if:status,acknowledged',
            'employee_response' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'status' => $request->status,
            'acknowledgment_date' => $request->acknowledgment_date,
            'employee_response' => $request->employee_response,
        ];

        // If status is being set to issued, acknowledged, or expired, set approved_by and approved_at
        if (in_array($request->status, ['issued', 'acknowledged', 'expired']) && !$warning->approved_by) {
            $updateData['approved_by'] = auth()->id();
            $updateData['approved_at'] = now();
        }

        $warning->update($updateData);

        return redirect()->back()->with('success', __('Warning status updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the improvement plan for a warning.
     */
    public function updateImprovementPlan(Request $request, Warning $warning)
    {
        if (Auth::user()->can('edit-warnings')) {
            // Check if warning belongs to current company
            if (!in_array($warning->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this warning'));
            }

        $validator = Validator::make($request->all(), [
            'has_improvement_plan' => 'required|boolean',
            'improvement_plan_goals' => 'nullable|string|required_if:has_improvement_plan,true',
            'improvement_plan_start_date' => 'nullable|date|required_if:has_improvement_plan,true',
            'improvement_plan_end_date' => 'nullable|date|after:improvement_plan_start_date|required_if:has_improvement_plan,true',
            'improvement_plan_progress' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'has_improvement_plan' => $request->has_improvement_plan,
            'improvement_plan_goals' => $request->improvement_plan_goals,
            'improvement_plan_start_date' => $request->improvement_plan_start_date,
            'improvement_plan_end_date' => $request->improvement_plan_end_date,
            'improvement_plan_progress' => $request->improvement_plan_progress,
        ];

        $warning->update($updateData);

        return redirect()->back()->with('success', __('Improvement plan updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Download document file.
     */
    public function downloadDocument(Warning $warning)
    {
        if (Auth::user()->can('view-warnings')) {
            // Check if warning belongs to current company
            if (!in_array($warning->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to access this document'));
            }

            if (!$warning->documents) {
                return redirect()->back()->with('error', __('Document file not found'));
            }

            $filePath = getStorageFilePath($warning->documents);

            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', __('Document file not found'));
            }

            return response()->download($filePath);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
