<?php

namespace App\Http\Controllers;

use App\Models\JobType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class JobTypeController extends Controller  
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-job-types')) {
            $query = JobType::where(function ($q) {
                if (Auth::user()->can('manage-any-job-types')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-job-types')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $query->orderBy('id', 'desc');
            $jobTypes = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/recruitment/job-types/index', [
                'jobTypes' => $jobTypes,
                'filters' => $request->all(['search', 'status', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

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

        JobType::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Job type created successfully'));
    }

    public function update(Request $request, JobType $jobType)
    {
        if (!in_array($jobType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this job type'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $jobType->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Job type updated successfully'));
    }

    public function destroy(JobType $jobType)
    {
        if (!in_array($jobType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this job type'));
        }

        if ($jobType->jobPostings()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete job type as it is being used in job postings'));
        }

        $jobType->delete();
        return redirect()->back()->with('success', __('Job type deleted successfully'));
    }

    public function toggleStatus(JobType $jobType)
    {
        if (!in_array($jobType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this job type'));
        }

        $jobType->update([
            'status' => $jobType->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Job type status updated successfully'));
    }
}
