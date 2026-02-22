<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class OfferController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-offers')) {
            $query = Offer::with(['candidate', 'job', 'department', 'approver'])->where(function ($q) {
                if (Auth::user()->can('manage-any-offers')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-offers')) {
                    $q->where('created_by', Auth::id())->orWhere('approved_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->whereHas('candidate', function ($q) use ($request) {
                    $q->where('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('last_name', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('candidate_id') && !empty($request->candidate_id) && $request->candidate_id !== 'all') {
                $query->where('candidate_id', $request->candidate_id);
            }

            $query->orderBy('id', 'desc');
            $offers = $query->paginate($request->per_page ?? 10);

            $candidates = Candidate::with('job')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'Offer')
                ->select('id', 'first_name', 'last_name', 'job_id')
                ->get();

            $departments = Department::with('branch')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name', 'branch_id')
                ->get();

            $employees = User::whereIn('created_by', getCompanyAndUsersId())
                ->whereIn('type', ['manager', 'hr'])
                ->select('id', 'name')
                ->get();

            $jobPostings = JobPosting::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'Published')
                ->select('id', 'title', 'job_code')
                ->get();

            // Add current user to employees list
            $currentUser = auth()->user();
            if ($currentUser && !$employees->contains('id', $currentUser->id)) {
                $employees->push($currentUser);
            }

            return Inertia::render('hr/recruitment/offers/index', [
                'offers' => $offers,
                'candidates' => $candidates,
                'departments' => $departments,
                'employees' => $employees,
                'jobPostings' => $jobPostings,
                'currentUser' => auth()->user(),
                'filters' => $request->all(['search', 'status', 'candidate_id', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|exists:candidates,id',
            'position' => 'required',
            'department_id' => 'nullable|exists:departments,id',
            'salary' => 'required|numeric|min:0',
            'benefits' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'expiration_date' => 'required|date|after_or_equal:today',
            'approved_by' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $candidate = Candidate::find($request->candidate_id);

        // Check if offer already exists for this candidate and job
        $existingOffer = Offer::where('candidate_id', $request->candidate_id)
            ->where('job_id', $candidate->job_id)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->exists();

        if ($existingOffer) {
            return redirect()->back()->with('error', __('An offer already exists for this candidate and position.'));
        }

        Offer::create([
            'candidate_id' => $request->candidate_id,
            'job_id' => $candidate->job_id,
            'offer_date' => now(),
            'position' => $request->position,
            'department_id' => $request->department_id,
            'salary' => $request->salary,
            'benefits' => $request->benefits,
            'start_date' => $request->start_date,
            'expiration_date' => $request->expiration_date,
            'approved_by' => $request->approved_by,
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Offer created successfully'));
    }

    public function update(Request $request, Offer $offer)
    {
        if (!in_array($offer->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this offer'));
        }

        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|exists:candidates,id',
            'position' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'salary' => 'required|numeric|min:0',
            'benefits' => 'nullable|string',
            'start_date' => 'required|date',
            'expiration_date' => 'required|date',
            'approved_by' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $candidate = Candidate::find($request->candidate_id);

        // Check if offer already exists for this candidate and job (excluding current offer)
        $existingOffer = Offer::where('candidate_id', $request->candidate_id)
            ->where('job_id', $candidate->job_id)
            ->where('id', '!=', $offer->id)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->exists();

        if ($existingOffer) {
            return redirect()->back()->with('error', __('An offer already exists for this candidate and position.'));
        }

        $offer->update([
            'candidate_id' => $request->candidate_id,
            'job_id' => $candidate->job_id,
            'position' => $request->position,
            'department_id' => $request->department_id,
            'salary' => $request->salary,
            'benefits' => $request->benefits,
            'start_date' => $request->start_date,
            'expiration_date' => $request->expiration_date,
            'approved_by' => $request->approved_by,
        ]);

        return redirect()->back()->with('success', __('Offer updated successfully'));
    }

    public function show(Offer $offer)
    {
        if (!in_array($offer->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to view this offer'));
        }

        $offer->load(['candidate', 'job', 'department', 'approver']);

        return Inertia::render('hr/recruitment/offers/show', [
            'offer' => $offer,
        ]);
    }

    public function destroy(Offer $offer)
    {
        if (!in_array($offer->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this offer'));
        }

        $offer->delete();
        return redirect()->back()->with('success', __('Offer deleted successfully'));
    }

    public function updateStatus(Request $request, Offer $offer)
    {
        if (!in_array($offer->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this offer'));
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Draft,Sent,Accepted,Negotiating,Declined,Expired',
            'decline_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $updateData = ['status' => $request->status];

        if ($request->status === 'Declined' && $request->decline_reason) {
            $updateData['decline_reason'] = $request->decline_reason;
        }

        if (in_array($request->status, ['Accepted', 'Declined'])) {
            $updateData['response_date'] = now();
        }

        $offer->update($updateData);

        // Update candidate status based on offer status
        if ($request->status === 'Accepted') {
            $candidate = Candidate::find($offer->candidate_id);
            if ($candidate) {
                $candidate->update([
                    'status' => 'Hired',
                    'final_salary' => $offer->salary,
                ]);
            }
        } elseif ($request->status === 'Declined') {
            $candidate = Candidate::find($offer->candidate_id);
            if ($candidate) {
                $candidate->update([
                    'status' => 'Rejected',
                ]);
            }
        }

        return redirect()->back()->with('success', __('Offer status updated successfully'));
    }

    public function getCandidateJob($candidateId)
    {
        $candidate = Candidate::with('job')->find($candidateId);

        if (!$candidate || !in_array($candidate->created_by, getCompanyAndUsersId())) {
            return response()->json(['error' => 'Candidate not found'], 404);
        }

        $response = [];

        // Job data
        if ($candidate->job) {
            return response()->json([
                [
                    'label' => $candidate->job->job_code . ' - ' . $candidate->job->title,
                    'value' => $candidate->job->id
                ]
            ]);
        }

        return response()->json([]);
    }

    public function getJobDepartments($jobId)
    {
        $job = JobPosting::with('department')->find($jobId);
        if (!$job || !in_array($job->created_by, getCompanyAndUsersId())) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        if ($job->department_id && $job->department) {
            return response()->json([
                [
                    'label' => $job->department->name,
                    'value' => $job->department->id
                ]
            ]);
        }

        return response()->json([]);
    }
}
