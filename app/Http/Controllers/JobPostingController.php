<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CustomQuestion;
use App\Models\Department;
use App\Models\JobLocation;
use App\Models\JobPosting;
use App\Models\JobType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class JobPostingController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-job-postings')) {
            $query = JobPosting::with(['requisition', 'jobType', 'location', 'department'])->withCount('candidates')->where(function ($q) {
                if (Auth::user()->can('manage-any-job-postings')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-job-postings')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && ! empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->search.'%')
                        ->orWhere('job_code', 'like', '%'.$request->search.'%');
                });
            }

            if ($request->has('status') && ! empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('is_published') && $request->is_published !== 'all') {
                $query->where('is_published', $request->is_published === 'true');
            }

            $query->orderBy('id', 'desc');
            $jobPostings = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/recruitment/job-postings/index', [
                'jobPostings' => $jobPostings,
                'filters' => $request->all(['search', 'status', 'is_published', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create()
    {
        if (! Auth::user()->can('create-job-postings')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $jobTypes = JobType::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        $locations = JobLocation::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        $branches = Branch::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        $departments = Department::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name', 'branch_id')
            ->get();

        return Inertia::render('hr/recruitment/job-postings/create', [
            'jobTypes' => $jobTypes,
            'locations' => $locations,
            'branches' => $branches,
            'departments' => $departments,
            'customQuestions' => CustomQuestion::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'question', 'required')
                ->get(),
        ]);
    }

    public function show(JobPosting $jobPosting)
    {
        if (! Auth::user()->can('view-job-postings')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        if (! in_array($jobPosting->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to view this job posting'));
        }

        $jobPosting->load(['requisition', 'jobType', 'location', 'department.branch', 'branch']);

        return Inertia::render('hr/recruitment/job-postings/show', [
            'jobPosting' => $jobPosting,
            'customQuestions' => CustomQuestion::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'question', 'required')
                ->get(),
        ]);
    }

    public function edit(JobPosting $jobPosting)
    {
        if (! Auth::user()->can('edit-job-postings')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        if (! in_array($jobPosting->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to edit this job posting'));
        }

        $jobTypes = JobType::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        $locations = JobLocation::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        $branches = Branch::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        $departments = Department::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name', 'branch_id')
            ->get();

        return Inertia::render('hr/recruitment/job-postings/edit', [
            'jobPosting' => $jobPosting,
            'jobTypes' => $jobTypes,
            'locations' => $locations,
            'branches' => $branches,
            'departments' => $departments,
            'customQuestions' => CustomQuestion::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'question', 'required')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'job_type_id' => 'required|exists:job_types,id',
            'location_id' => 'required|exists:job_locations,id',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'priority' => 'nullable|in:Low,Medium,High',
            'skills' => 'required|array',
            'positions' => 'required|integer|min:1',
            'min_experience' => 'required|numeric|min:0',
            'max_experience' => 'required|numeric|min:0',
            'min_salary' => 'required|numeric|min:0',
            'max_salary' => 'required|numeric|min:0',
            'description' => 'required|string',
            'requirements' => 'required|string',
            'benefits' => 'nullable|string',
            'start_date' => 'required|date',
            'application_deadline' => 'required|date|after:today',
            'application_type' => 'required|in:existing,custom',
            'application_url' => 'required|string',
            'applicant' => 'nullable|array',
            'visibility' => 'nullable|array',
            'custom_question' => 'nullable|array',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $jobPosting = new JobPosting;
        $jobPosting->job_code = JobPosting::generateJobCode(null);
        $jobPosting->title = $request->title;
        $jobPosting->job_type_id = $request->job_type_id;
        $jobPosting->location_id = $request->location_id;
        $jobPosting->branch_id = $request->branch_id;
        $jobPosting->department_id = $request->department_id;
        $jobPosting->priority = $request->priority ?: 'Medium';
        $jobPosting->skills = $request->skills;
        $jobPosting->positions = $request->positions;
        $jobPosting->min_experience = $request->min_experience;
        $jobPosting->max_experience = $request->max_experience;
        $jobPosting->min_salary = $request->min_salary;
        $jobPosting->max_salary = $request->max_salary;
        $jobPosting->description = $request->description;
        $jobPosting->requirements = $request->requirements;
        $jobPosting->benefits = $request->benefits;
        $jobPosting->start_date = $request->start_date;
        $jobPosting->application_deadline = $request->application_deadline;
        $jobPosting->visibility = $request->has('visibility') ? $request->visibility : null;
        $jobPosting->custom_question = $request->has('custom_question') ? $request->custom_question : null;
        $jobPosting->applicant = $request->has('applicant') ? $request->applicant : null;
        $jobPosting->code = uniqid();
        $jobPosting->application_type = $request->application_type;
        $jobPosting->application_url = $request->application_url;
        $jobPosting->is_featured = $request->boolean('is_featured');
        $jobPosting->created_by = creatorId();
        $jobPosting->save();

        return redirect()->route('hr.recruitment.job-postings.index')->with('success', __('Job posting created successfully'));
    }

    public function update(Request $request, JobPosting $jobPosting)
    {
        if (! in_array($jobPosting->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this job posting'));
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'job_type_id' => 'required|exists:job_types,id',
            'location_id' => 'required|exists:job_locations,id',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'priority' => 'required|in:Low,Medium,High',
            'status' => 'required|in:Draft,Published,Closed',
            'positions' => 'required|integer|min:1',
            'min_experience' => 'required|numeric|min:0',
            'max_experience' => 'nullable|numeric|min:0',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'education' => 'nullable|string',
            'benefits' => 'nullable|string',
            'start_date' => 'nullable|date',
            'application_deadline' => 'nullable|date',
            'application_type' => 'required|in:existing,custom',
            'application_url' => 'required|string',
            'skills' => 'required|array',
            'applicant' => 'nullable|array',
            'visibility' => 'nullable|array',
            'custom_question' => 'nullable|array',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $jobPosting->title = $request->title;
        $jobPosting->job_type_id = $request->job_type_id;
        $jobPosting->location_id = $request->location_id;
        $jobPosting->branch_id = $request->branch_id;
        $jobPosting->department_id = $request->department_id;
        $jobPosting->priority = $request->priority;
        $jobPosting->status = $request->status;
        $jobPosting->skills = $request->skills;
        $jobPosting->positions = $request->positions;
        $jobPosting->min_experience = $request->min_experience;
        $jobPosting->max_experience = $request->max_experience;
        $jobPosting->min_salary = $request->min_salary;
        $jobPosting->max_salary = $request->max_salary;
        $jobPosting->description = $request->description;
        $jobPosting->requirements = $request->requirements;
        $jobPosting->benefits = $request->benefits;
        $jobPosting->start_date = $request->start_date;
        $jobPosting->application_deadline = $request->application_deadline;
        $jobPosting->visibility = $request->has('visibility') ? $request->visibility : null;
        $jobPosting->applicant = $request->has('applicant') ? $request->applicant : null;
        $jobPosting->custom_question = $request->has('custom_question') ? $request->custom_question : null;
        $jobPosting->application_type = $request->application_type;
        $jobPosting->application_url = $request->application_url;
        $jobPosting->is_featured = $request->boolean('is_featured');
        $jobPosting->save();

        return redirect()->route('hr.recruitment.job-postings.index')->with('success', __('Job posting updated successfully'));
    }

    public function destroy(JobPosting $jobPosting)
    {
        if (! in_array($jobPosting->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this job posting'));
        }

        if ($jobPosting->candidates()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete job posting as it has associated candidates'));
        }

        $jobPosting->delete();

        return redirect()->back()->with('success', __('Job posting deleted successfully'));
    }

    public function publish(JobPosting $jobPosting)
    {
        if (! in_array($jobPosting->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to publish this job posting'));
        }

        $jobPosting->update([
            'is_published' => true,
            'publish_date' => now(),
            'status' => 'Published',
        ]);

        return redirect()->back()->with('success', __('Job posting published successfully'));
    }

    public function unpublish(JobPosting $jobPosting)
    {
        if (! in_array($jobPosting->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to unpublish this job posting'));
        }

        $jobPosting->update([
            'is_published' => false,
            'status' => 'Draft',
        ]);

        return redirect()->back()->with('success', __('Job posting unpublished successfully'));
    }
}
