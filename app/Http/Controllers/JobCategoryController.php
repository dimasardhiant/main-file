<?php

namespace App\Http\Controllers;

use App\Models\JobCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class JobCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-job-categories')) {
            $query = JobCategory::where(function ($q) {
                if (Auth::user()->can('manage-any-job-categories')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-job-categories')) {
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

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            $jobCategories = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/recruitment/job-categories/index', [
                'jobCategories' => $jobCategories,
                'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
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
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        JobCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Job category created successfully'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JobCategory $jobCategory)
    {
        // Check if job category belongs to current company
        if (!in_array($jobCategory->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this job category'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $jobCategory->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Job category updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JobCategory $jobCategory)
    {
        // Check if job category belongs to current company
        if (!in_array($jobCategory->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this job category'));
        }

        // Check if job category is being used in job requisitions
        if ($jobCategory->jobRequisitions()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete job category as it is being used in job requisitions'));
        }

        $jobCategory->delete();

        return redirect()->back()->with('success', __('Job category deleted successfully'));
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(JobCategory $jobCategory)
    {
        // Check if job category belongs to current company
        if (!in_array($jobCategory->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this job category'));
        }

        $jobCategory->update([
            'status' => $jobCategory->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Job category status updated successfully'));
    }
}
