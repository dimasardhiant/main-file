<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\TrainingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TrainingTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if (Auth::user()->can('manage-training-types')) {
            $query = TrainingType::with(['departments.branch', 'branch'])
                ->withCount('trainingPrograms')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-training-types')) {
                        $q->whereIn('created_by',  getCompanyAndUsersId());
                    } elseif (Auth::user()->can('manage-own-training-types')) {
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

            // Handle branch filter
            if ($request->has('branch_id') && !empty($request->branch_id)) {
                $query->whereHas('departments', function ($q) use ($request) {
                    $q->where('departments.branch_id', $request->branch_id);
                });
            }

            // Handle department filter
            if ($request->has('department_id') && !empty($request->department_id)) {
                $query->whereHas('departments', function ($q) use ($request) {
                    $q->where('departments.id', $request->department_id);
                });
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            $trainingTypes = $query->paginate($request->per_page ?? 10);

            // Get branches for filter dropdown
            $branches = Branch::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'name')
                ->get();

            // Get departments for filter dropdown
            $departments = Department::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'name', 'branch_id')
                ->get();

            return Inertia::render('hr/training/types/index', [
                'trainingTypes' => $trainingTypes,
                'branches' => $branches,
                'departments' => $departments,
                'filters' => $request->all(['search', 'branch_id', 'department_id', 'sort_field', 'sort_direction', 'per_page']),
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
            'description' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if branch belongs to current company
        $branch = Branch::find($request->branch_id);
        if (!$branch || !in_array($branch->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('Invalid branch selected'));
        }

        $trainingType = TrainingType::create([
            'name' => $request->name,
            'description' => $request->description,
            'branch_id' => $request->branch_id,
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Training type created successfully'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TrainingType $trainingType)
    {
        // Check if training type belongs to current company
        if (!in_array($trainingType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this training type'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if departments belong to current company
        if (!empty($request->department_ids)) {
            $departmentIds = $request->department_ids;
            $validDepartments = Department::whereIn('created_by', getCompanyAndUsersId())
                ->whereIn('id', $departmentIds)
                ->pluck('id')
                ->toArray();

            if (count($validDepartments) !== count($departmentIds)) {
                return redirect()->back()->with('error', __('Invalid department selection'));
            }
        }

        $trainingType->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Sync departments
        if (isset($request->department_ids)) {
            $trainingType->departments()->sync($request->department_ids);
        } else {
            $trainingType->departments()->detach();
        }

        return redirect()->back()->with('success', __('Training type updated successfully'));
    }

    /**
     * Assign departments to training type.
     */
    public function assignDepartments(Request $request, TrainingType $trainingType)
    {
        // Check if training type belongs to current company
        if (!in_array($trainingType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this training type'));
        }

        $validator = Validator::make($request->all(), [
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if departments belong to current company and the training type's branch
        if (!empty($request->department_ids)) {
            $departmentIds = $request->department_ids;
            $validDepartments = Department::whereIn('created_by', getCompanyAndUsersId())
                ->where('branch_id', $trainingType->branch_id)
                ->whereIn('id', $departmentIds)
                ->pluck('id')
                ->toArray();

            if (count($validDepartments) !== count($departmentIds)) {
                return redirect()->back()->with('error', __('Invalid department selection for this training type\'s branch'));
            }
        }

        // Sync departments
        if (isset($request->department_ids)) {
            $trainingType->departments()->sync($request->department_ids);
        } else {
            $trainingType->departments()->detach();
        }

        return redirect()->back()->with('success', __('Departments assigned successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TrainingType $trainingType)
    {
        // Check if training type belongs to current company
        if (!in_array($trainingType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this training type'));
        }

        // Check if training type is being used by any training programs
        if ($trainingType->trainingPrograms()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete training type that is being used by training programs'));
        }

        // Detach all departments
        $trainingType->departments()->detach();

        // Delete the training type
        $trainingType->delete();

        return redirect()->back()->with('success', __('Training type deleted successfully'));
    }
}
