<?php

namespace App\Http\Controllers;

use App\Models\TrainingAssessment;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TrainingAssessmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TrainingAssessment::with(['trainingProgram'])
            ->whereHas('trainingProgram', function ($q) {
                $q->where('created_by', createdBy());
            });

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhereHas('trainingProgram', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Handle program filter
        if ($request->has('training_program_id') && !empty($request->training_program_id)) {
            $query->where('training_program_id', $request->training_program_id);
        }

        // Handle type filter
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Handle sorting
        if ($request->has('sort_field') && !empty($request->sort_field)) {
            if ($request->sort_field === 'program_name') {
                $query->join('training_programs', 'training_assessments.training_program_id', '=', 'training_programs.id')
                    ->select('training_assessments.*')
                    ->orderBy('training_programs.name', $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            }
        } else {
            $query->orderBy('name', 'asc');
        }

        // Add employee results count
        $query->withCount(['employeeResults']);

        $trainingAssessments = $query->paginate($request->per_page ?? 10);

        // Get training programs for filter dropdown
        $trainingPrograms = TrainingProgram::where('created_by', createdBy())
            ->select('id', 'name')
            ->get();

        return Inertia::render('hr/training/assessments/index', [
            'trainingAssessments' => $trainingAssessments,
            'trainingPrograms' => $trainingPrograms,
            'filters' => $request->all(['search', 'training_program_id', 'type', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'training_program_id' => 'required|exists:training_programs,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:quiz,practical,presentation',
            'passing_score' => 'required|numeric|min:0|max:100',
            'criteria' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if training program belongs to current company
        $trainingProgram = TrainingProgram::find($request->training_program_id);
        if (!$trainingProgram || $trainingProgram->created_by != createdBy()) {
            return redirect()->back()->with('error', 'Invalid training program selected');
        }

        TrainingAssessment::create([
            'training_program_id' => $request->training_program_id,
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'passing_score' => $request->passing_score,
            'criteria' => $request->criteria,
            'created_by' => createdBy(),
        ]);

        return redirect()->back()->with('success', 'Training assessment created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(TrainingAssessment $trainingAssessment)
    {
        // Check if training assessment belongs to current company
        if ($trainingAssessment->trainingProgram->created_by != createdBy()) {
            return redirect()->back()->with('error', 'You do not have permission to view this training assessment');
        }

        // Load relationships
        $trainingAssessment->load(['trainingProgram', 'employeeResults.employeeTraining.employee']);

        // Calculate statistics
        $totalResults = $trainingAssessment->employeeResults->count();
        $passedResults = $trainingAssessment->employeeResults->where('is_passed', true)->count();
        $failedResults = $totalResults - $passedResults;
        $passRate = $totalResults > 0 ? ($passedResults / $totalResults) * 100 : 0;
        $averageScore = $totalResults > 0 ? $trainingAssessment->employeeResults->avg('score') : 0;

        return Inertia::render('hr/training/assessments/show', [
            'trainingAssessment' => $trainingAssessment,
            'statistics' => [
                'totalResults' => $totalResults,
                'passedResults' => $passedResults,
                'failedResults' => $failedResults,
                'passRate' => $passRate,
                'averageScore' => $averageScore,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TrainingAssessment $trainingAssessment)
    {
        // Check if training assessment belongs to current company
        if ($trainingAssessment->trainingProgram->created_by != createdBy()) {
            return redirect()->back()->with('error', 'You do not have permission to update this training assessment');
        }

        $validator = Validator::make($request->all(), [
            'training_program_id' => 'required|exists:training_programs,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:quiz,practical,presentation',
            'passing_score' => 'required|numeric|min:0|max:100',
            'criteria' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if training program belongs to current company
        $trainingProgram = TrainingProgram::find($request->training_program_id);
        if (!$trainingProgram || $trainingProgram->created_by != createdBy()) {
            return redirect()->back()->with('error', 'Invalid training program selected');
        }

        $trainingAssessment->update([
            'training_program_id' => $request->training_program_id,
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'passing_score' => $request->passing_score,
            'criteria' => $request->criteria,
        ]);

        return redirect()->back()->with('success', 'Training assessment updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TrainingAssessment $trainingAssessment)
    {
        // Check if training assessment belongs to current company
        if ($trainingAssessment->trainingProgram->created_by != createdBy()) {
            return redirect()->back()->with('error', 'You do not have permission to delete this training assessment');
        }

        // Check if assessment has results
        if ($trainingAssessment->employeeResults()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete assessment that has employee results');
        }

        // Delete the training assessment
        $trainingAssessment->delete();

        return redirect()->back()->with('success', 'Training assessment deleted successfully');
    }
}