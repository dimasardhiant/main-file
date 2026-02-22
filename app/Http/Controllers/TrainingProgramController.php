<?php

namespace App\Http\Controllers;

use App\Models\TrainingProgram;
use App\Models\TrainingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TrainingProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-training-programs')) {
            $query = TrainingProgram::with(['trainingType'])->where(function ($q) {
                if (Auth::user()->can('manage-any-training-programs')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-training-programs')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            // Handle training type filter
            if ($request->has('training_type_id') && !empty($request->training_type_id)) {
                $query->where('training_type_id', $request->training_type_id);
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Handle mandatory filter
            if ($request->has('is_mandatory') && $request->is_mandatory === 'true') {
                $query->where('is_mandatory', true);
            }

            // Handle self-enrollment filter
            if ($request->has('is_self_enrollment') && $request->is_self_enrollment === 'true') {
                $query->where('is_self_enrollment', true);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            // Add counts
            $query->withCount(['sessions', 'employeeTrainings']);

            $trainingPrograms = $query->paginate($request->per_page ?? 10);

            // Get training types for filter dropdown with branch and departments
            $trainingTypes = TrainingType::with(['branch', 'departments'])
                ->whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'name', 'branch_id')
                ->get();

            return Inertia::render('hr/training/programs/index', [
                'trainingPrograms' => $trainingPrograms,
                'trainingTypes' => $trainingTypes,
                'filters' => $request->all(['search', 'training_type_id', 'status', 'is_mandatory', 'is_self_enrollment', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'training_type_id' => 'required|exists:training_types,id',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|string|in:draft,active,completed,cancelled',
            'materials' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
            'is_self_enrollment' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if training type belongs to current company
        $trainingType = TrainingType::find($request->training_type_id);
        if (!$trainingType || $trainingType->created_by != createdBy()) {
            return redirect()->back()->with('error', 'Invalid training type selected');
        }

        $programData = [
            'name' => $request->name,
            'training_type_id' => $request->training_type_id,
            'description' => $request->description,
            'duration' => $request->duration,
            'cost' => $request->cost,
            'capacity' => $request->capacity,
            'status' => $request->status,
            'prerequisites' => $request->prerequisites,
            'is_mandatory' => $request->is_mandatory ?? false,
            'is_self_enrollment' => $request->is_self_enrollment ?? false,
            'created_by' => creatorId(),
        ];

        // Handle materials from media library
        if ($request->has('materials')) {
            $programData['materials'] = $request->materials;
        }

        TrainingProgram::create($programData);

        return redirect()->back()->with('success', __('Training program created successfully'));
    }

    /**
     * Display the specified resource.
     */
    public function show(TrainingProgram $trainingProgram)
    {
        // Check if training program belongs to current company
        if (!in_array($trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to view this training program'));
        }

        // Load relationships
        $trainingProgram->load(['trainingType', 'sessions', 'employeeTrainings.employee', 'assessments']);

        // Get session statistics
        $completedSessions = $trainingProgram->sessions()->where('status', 'completed')->count();
        $totalSessions = $trainingProgram->sessions()->count();
        $sessionCompletionRate = $totalSessions > 0 ? ($completedSessions / $totalSessions) * 100 : 0;

        // Get employee statistics
        $completedTrainings = $trainingProgram->employeeTrainings()->where('status', 'completed')->count();
        $totalTrainings = $trainingProgram->employeeTrainings()->count();
        $employeeCompletionRate = $totalTrainings > 0 ? ($completedTrainings / $totalTrainings) * 100 : 0;

        return Inertia::render('hr/training/programs/show', [
            'trainingProgram' => $trainingProgram,
            'statistics' => [
                'completedSessions' => $completedSessions,
                'totalSessions' => $totalSessions,
                'sessionCompletionRate' => $sessionCompletionRate,
                'completedTrainings' => $completedTrainings,
                'totalTrainings' => $totalTrainings,
                'employeeCompletionRate' => $employeeCompletionRate,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TrainingProgram $trainingProgram)
    {
        // Check if training program belongs to current company
        if (!in_array($trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this training program'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'training_type_id' => 'required|exists:training_types,id',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|string|in:draft,active,completed,cancelled',
            'materials' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
            'is_self_enrollment' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if training type belongs to current company
        $trainingType = TrainingType::find($request->training_type_id);
        if (!$trainingType || $trainingType->created_by != createdBy()) {
            return redirect()->back()->with('error', 'Invalid training type selected');
        }

        $programData = [
            'name' => $request->name,
            'training_type_id' => $request->training_type_id,
            'description' => $request->description,
            'duration' => $request->duration,
            'cost' => $request->cost,
            'capacity' => $request->capacity,
            'status' => $request->status,
            'prerequisites' => $request->prerequisites,
            'is_mandatory' => $request->is_mandatory ?? false,
            'is_self_enrollment' => $request->is_self_enrollment ?? false,
        ];

        // Handle materials from media library
        if ($request->has('materials')) {
            $programData['materials'] = $request->materials;
        }

        $trainingProgram->update($programData);

        return redirect()->back()->with('success', __('Training program updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TrainingProgram $trainingProgram)
    {
        // Check if training program belongs to current company
        if (!in_array($trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this training program'));
        }

        // Check if training program has sessions or employee trainings
        if ($trainingProgram->sessions()->count() > 0 || $trainingProgram->employeeTrainings()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete training program that has sessions or employee assignments'));
        }

        // Delete assessments
        $trainingProgram->assessments()->delete();

        // Delete the training program
        $trainingProgram->delete();

        return redirect()->back()->with('success', __('Training program deleted successfully'));
    }

    /**
     * Download training materials.
     */
    public function downloadMaterials(TrainingProgram $trainingProgram)
    {
        // Check if training program belongs to current company
        if (!in_array($trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to access these materials'));
        }

        if (!$trainingProgram->materials) {
            return redirect()->back()->with('error', __('Training materials not found'));
        }

        // Handle cloud storage URLs (already full URLs)
        if (filter_var($trainingProgram->materials, FILTER_VALIDATE_URL)) {
            return Storage::download($trainingProgram->materials);
        }

        // Handle local storage paths
        $relativePath = str_replace('/Product/hrmgo-saas-react/storage/', '', $trainingProgram->materials);

        if (!Storage::exists($relativePath)) {
            return redirect()->back()->with('error', __('Training materials not found'));
        }

        return Storage::download($relativePath);
    }
}
