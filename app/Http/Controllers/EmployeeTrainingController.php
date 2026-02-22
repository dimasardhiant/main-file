<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmployeeAssessmentResult;
use App\Models\EmployeeTraining;
use App\Models\TrainingAssessment;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class EmployeeTrainingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-employee-trainings')) {
            $query = EmployeeTraining::with(['employee.employee', 'trainingProgram.trainingType'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-employee-trainings')) {
                        $q->whereIn('created_by',  getCompanyAndUsersId());
                    } elseif (Auth::user()->can('manage-own-employee-trainings')) {
                        $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('employee', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('employee_id', 'like', '%' . $request->search . '%');
                    })
                        ->orWhereHas('trainingProgram', function ($q) use ($request) {
                            $q->where('name', 'like', '%' . $request->search . '%');
                        });
                });
            }

            // Handle employee filter
            if ($request->has('employee_id') && !empty($request->employee_id)) {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle program filter
            if ($request->has('training_program_id') && !empty($request->training_program_id)) {
                $query->where('training_program_id', $request->training_program_id);
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Handle date range filter
            if ($request->has('assigned_date_from') && !empty($request->assigned_date_from)) {
                $query->whereDate('assigned_date', '>=', $request->assigned_date_from);
            }
            if ($request->has('assigned_date_to') && !empty($request->assigned_date_to)) {
                $query->whereDate('assigned_date', '<=', $request->assigned_date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                if ($request->sort_field === 'employee_name') {
                    $query->join('employees', 'employee_trainings.employee_id', '=', 'employees.id')
                        ->select('employee_trainings.*')
                        ->orderBy('employees.name', $request->sort_direction ?? 'asc');
                } elseif ($request->sort_field === 'program_name') {
                    $query->join('training_programs', 'employee_trainings.training_program_id', '=', 'training_programs.id')
                        ->select('employee_trainings.*')
                        ->orderBy('training_programs.name', $request->sort_direction ?? 'asc');
                } else {
                    $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
                }
            } else {
                $query->orderBy('id', 'desc');
            }

            // Add assessment results count
            $query->withCount(['assessmentResults']);

            $employeeTrainings = $query->paginate($request->per_page ?? 10);

            // Get employees for filter dropdown
            $employees = User::with('employee')
                ->where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name . ' (' . ($user->employee->employee_id ?? '') . ')'
                    ];
                });

            // Get training programs for filter dropdown
            $trainingPrograms = TrainingProgram::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'name')
                ->get();

            return Inertia::render('hr/training/employee-trainings/index', [
                'employeeTrainings' => $employeeTrainings,
                'employees' => $employees,
                'trainingPrograms' => $trainingPrograms,
                'filters' => $request->all(['search', 'employee_id', 'training_program_id', 'status', 'assigned_date_from', 'assigned_date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Display the dashboard view.
     */
    public function dashboard(Request $request)
    {
        // Get training statistics with proper permission check
        $totalTrainings = EmployeeTraining::withPermissionCheck()->count();
        $completedTrainings = EmployeeTraining::withPermissionCheck()->where('status', 'completed')->count();
        $inProgressTrainings = EmployeeTraining::withPermissionCheck()->where('status', 'in_progress')->count();
        $assignedTrainings = EmployeeTraining::withPermissionCheck()->where('status', 'assigned')->count();
        $failedTrainings = EmployeeTraining::withPermissionCheck()->where('status', 'failed')->count();

        // Get completion rate by program
        $programStats = TrainingProgram::whereIn('created_by', getCompanyAndUsersId())
            ->withCount([
                'employeeTrainings as total_count' => function ($q) {
                    $q->whereHas('employee', function ($q) {
                        $q->whereIn('created_by', getCompanyAndUsersId());
                    });
                },
                'employeeTrainings as completed_count' => function ($q) {
                    $q->where('status', 'completed')
                        ->whereHas('employee', function ($q) {
                            $q->whereIn('created_by', getCompanyAndUsersId());
                        });
                }
            ])
            ->having('total_count', '>', 0)
            ->get()
            ->map(function ($program) {
                return [
                    'name' => $program->name,
                    'total' => $program->total_count,
                    'completed' => $program->completed_count,
                    'completion_rate' => $program->total_count > 0
                        ? round(($program->completed_count / $program->total_count) * 100)
                        : 0
                ];
            });

        // Get recent completions
        $recentCompletions = EmployeeTraining::with(['employee', 'trainingProgram'])
            ->withPermissionCheck()
            ->where('status', 'completed')
            ->orderBy('completion_date', 'desc')
            ->take(5)
            ->get();

        // Get upcoming trainings
        $upcomingTrainings = EmployeeTraining::with(['employee', 'trainingProgram'])
            ->withPermissionCheck()
            ->where('status', 'assigned')
            ->orderBy('assigned_date', 'asc')
            ->take(5)
            ->get();

        return Inertia::render('hr/training/employee-trainings/dashboard', [
            'statistics' => [
                'totalTrainings' => $totalTrainings,
                'completedTrainings' => $completedTrainings,
                'inProgressTrainings' => $inProgressTrainings,
                'assignedTrainings' => $assignedTrainings,
                'failedTrainings' => $failedTrainings,
                'completionRate' => $totalTrainings > 0 ? round(($completedTrainings / $totalTrainings) * 100) : 0
            ],
            'programStats' => $programStats,
            'recentCompletions' => $recentCompletions,
            'upcomingTrainings' => $upcomingTrainings
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'training_program_id' => 'required|exists:training_programs,id',
            'status' => 'required|string|in:assigned,in_progress,completed,failed',
            'assigned_date' => 'required|date',
            'completion_date' => 'nullable|date|after_or_equal:assigned_date',
            'certification' => 'nullable|string',
            'score' => 'nullable|numeric|min:0|max:100',
            'is_passed' => 'nullable|boolean',
            'feedback' => 'nullable|string',
            'notes' => 'nullable|string',
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

        // Check if training program belongs to current company
        $trainingProgram = TrainingProgram::find($request->training_program_id);
        if (!$trainingProgram || !in_array($trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('Invalid training program selected'));
        }

        $trainingData = [
            'employee_id' => $request->employee_id,
            'training_program_id' => $request->training_program_id,
            'status' => $request->status,
            'assigned_date' => $request->assigned_date,
            'completion_date' => $request->completion_date,
            'score' => $request->score,
            'is_passed' => $request->is_passed,
            'feedback' => $request->feedback,
            'notes' => $request->notes,
            'assigned_by' => creatorId(),
            'created_by' => creatorId(),
        ];

        // Handle certification from media library
        if ($request->has('certification')) {
            $trainingData['certification'] = $request->certification;
        }

        EmployeeTraining::create($trainingData);

        return redirect()->back()->with('success', __('Employee training assigned successfully'));
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeTraining $employeeTraining)
    {
        // Check if employee training belongs to current company
        if (!in_array($employeeTraining->employee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to view this employee training'));
        }

        // Load relationships
        $employeeTraining->load([
            'employee.employee.department',
            'employee.employee.designation',
            'trainingProgram.trainingType',
            'assessmentResults.trainingAssessment',
            'assigner'
        ]);

        // Get available assessments for this training program
        $availableAssessments = TrainingAssessment::where('training_program_id', $employeeTraining->training_program_id)
            ->whereDoesntHave('employeeResults', function ($q) use ($employeeTraining) {
                $q->where('employee_training_id', $employeeTraining->id);
            })
            ->get();

        return Inertia::render('hr/training/employee-trainings/show', [
            'employeeTraining' => $employeeTraining,
            'availableAssessments' => $availableAssessments,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeTraining $employeeTraining)
    {
        // Check if employee training belongs to current company
        if (!in_array($employeeTraining->employee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this employee training'));
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:assigned,in_progress,completed,failed',
            'completion_date' => 'nullable|date|after_or_equal:assigned_date',
            'certification' => 'nullable|string',
            'score' => 'nullable|numeric|min:0|max:100',
            'is_passed' => 'nullable|boolean',
            'feedback' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $trainingData = [
            'status' => $request->status,
            'completion_date' => $request->completion_date,
            'score' => $request->score,
            'is_passed' => $request->is_passed,
            'feedback' => $request->feedback,
            'notes' => $request->notes,
        ];

        // Handle certification from media library
        if ($request->has('certification')) {
            $trainingData['certification'] = $request->certification;
        }

        $employeeTraining->update($trainingData);

        return redirect()->back()->with('success', __('Employee training updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeTraining $employeeTraining)
    {
        // Check if employee training belongs to current company
        if (!in_array($employeeTraining->employee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this employee training'));
        }

        // Delete certification if exists
        if ($employeeTraining->certification) {
            Storage::disk('public')->delete($employeeTraining->certification);
        }

        // Delete assessment results
        $employeeTraining->assessmentResults()->delete();

        // Delete the employee training
        $employeeTraining->delete();

        return redirect()->back()->with('success', __('Employee training deleted successfully'));
    }

    /**
     * Download certification file.
     */
    public function downloadCertification(EmployeeTraining $employeeTraining)
    {
        // Check if employee training belongs to current company
        if (!in_array($employeeTraining->employee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to access this certification'));
        }

        if (!$employeeTraining->certification) {
            return redirect()->back()->with('error', __('Certification file not found'));
        }

        $filePath = getStorageFilePath($employeeTraining->certification);

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', __('Certification file not found'));
        }

        return response()->download($filePath);
    }

    /**
     * Bulk assign training to employees.
     */
    public function bulkAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:users,id',
            'training_program_id' => 'required|exists:training_programs,id',
            'assigned_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if employees belong to current company
        $employeeIds = $request->employee_ids;
        $validEmployees = User::whereIn('id', $employeeIds)
            ->where('type', 'employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->pluck('id')
            ->toArray();

        if (count($validEmployees) !== count($employeeIds)) {
            return redirect()->back()->with('error', __('Invalid employee selection'));
        }

        // Check if training program belongs to current company
        $trainingProgram = TrainingProgram::find($request->training_program_id);
        if (!$trainingProgram || !in_array($trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('Invalid training program selected'));
        }

        // Create training assignments for each employee
        foreach ($employeeIds as $employeeId) {
            EmployeeTraining::create([
                'employee_id' => $employeeId,
                'training_program_id' => $request->training_program_id,
                'status' => 'assigned',
                'assigned_date' => $request->assigned_date,
                'notes' => $request->notes,
                'assigned_by' => auth()->id(),
            ]);
        }

        return redirect()->back()->with('success', __('Training assigned to ' . count($employeeIds) . ' employees successfully'));
    }

    /**
     * Record assessment result.
     */
    public function recordAssessment(Request $request, EmployeeTraining $employeeTraining)
    {
        // Check if employee training belongs to current company
        if (!in_array($employeeTraining->employee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to record assessment for this employee training'));
        }

        $validator = Validator::make($request->all(), [
            'training_assessment_id' => 'required|exists:training_assessments,id',
            'score' => 'required|numeric|min:0|max:100',
            'is_passed' => 'required|boolean',
            'feedback' => 'nullable|string',
            'assessment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if assessment belongs to the training program
        $assessment = TrainingAssessment::find($request->training_assessment_id);
        if (!$assessment || $assessment->training_program_id != $employeeTraining->training_program_id) {
            return redirect()->back()->with('error', __('Invalid assessment selected'));
        }

        // Create assessment result
        EmployeeAssessmentResult::create([
            'employee_training_id' => $employeeTraining->id,
            'training_assessment_id' => $request->training_assessment_id,
            'score' => $request->score,
            'is_passed' => $request->is_passed,
            'feedback' => $request->feedback,
            'assessment_date' => $request->assessment_date,
            'assessed_by' => auth()->id(),
        ]);

        // Update employee training status if needed
        if ($request->update_training_status) {
            $employeeTraining->update([
                'status' => $request->is_passed ? 'completed' : 'failed',
                'is_passed' => $request->is_passed,
                'score' => $request->score,
                'completion_date' => $request->assessment_date,
            ]);
        }

        return redirect()->back()->with('success', __('Assessment result recorded successfully'));
    }
}
