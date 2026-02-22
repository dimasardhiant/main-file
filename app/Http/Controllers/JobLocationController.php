<?php

namespace App\Http\Controllers;

use App\Models\JobLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class JobLocationController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-job-locations')) {
            $query = JobLocation::where(function ($q) {
                if (Auth::user()->can('manage-any-job-locations')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-job-locations')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });


            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('city', 'like', '%' . $request->search . '%')
                        ->orWhere('address', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('is_remote') && $request->is_remote !== 'all') {
                $query->where('is_remote', $request->is_remote === 'true');
            }

            $query->orderBy('id', 'desc');
            $jobLocations = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/recruitment/job-locations/index', [
                'jobLocations' => $jobLocations,
                'filters' => $request->all(['search', 'status', 'is_remote', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'is_remote' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        JobLocation::create([
            'name' => $request->name,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
            'is_remote' => $request->boolean('is_remote'),
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Job location created successfully'));
    }

    public function update(Request $request, JobLocation $jobLocation)
    {
        if (!in_array($jobLocation->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this job location');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'is_remote' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $jobLocation->update([
            'name' => $request->name,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
            'is_remote' => $request->boolean('is_remote'),
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Job location updated successfully'));
    }

    public function destroy(JobLocation $jobLocation)
    {
        if (!in_array($jobLocation->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to delete this job location');
        }

        if ($jobLocation->jobPostings()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete job location as it is being used in job postings'));
        }

        $jobLocation->delete();
        return redirect()->back()->with('success', __('Job location deleted successfully'));
    }

    public function toggleStatus(JobLocation $jobLocation)
    {
        if (!in_array($jobLocation->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this job location');
        }

        $jobLocation->update([
            'status' => $jobLocation->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Job location status updated successfully'));
    }
}
