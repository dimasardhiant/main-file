<?php

namespace App\Http\Controllers;

use App\Models\InterviewRound;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class InterviewRoundController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-interview-rounds')) {
            $query = InterviewRound::with(['job'])->where(function ($q) {
                if (Auth::user()->can('manage-any-interview-rounds')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-interview-rounds')) {
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

            if ($request->has('job_id') && !empty($request->job_id) && $request->job_id !== 'all') {
                $query->where('job_id', $request->job_id);
            }

            $query->orderBy('job_id')->orderBy('sequence_number');
            $interviewRounds = $query->paginate($request->per_page ?? 10);

            $jobPostings = JobPosting::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'title', 'job_code')
                ->get();

            return Inertia::render('hr/recruitment/interview-rounds/index', [
                'interviewRounds' => $interviewRounds,
                'jobPostings' => $jobPostings,
                'filters' => $request->all(['search', 'status', 'job_id', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:job_postings,id',
            'name' => 'required|string|max:255',
            'sequence_number' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if interview round with same job_id and sequence_number already exists
        $existingRound = InterviewRound::where('job_id', $request->job_id)
            ->where('sequence_number', $request->sequence_number)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($existingRound) {
            return redirect()->back()->with('error', __('Interview round with sequence number :sequence already exists for this job posting', ['sequence' => $request->sequence_number]));
        }

        InterviewRound::create([
            'job_id' => $request->job_id,
            'name' => $request->name,
            'sequence_number' => $request->sequence_number,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Interview round created successfully'));
    }

    public function update(Request $request, InterviewRound $interviewRound)
    {
        if (!in_array($interviewRound->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this interview round'));
        }

        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:job_postings,id',
            'name' => 'required|string|max:255',
            'sequence_number' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if interview round with same job_id and sequence_number already exists (excluding current record)
        $existingRound = InterviewRound::where('job_id', $request->job_id)
            ->where('sequence_number', $request->sequence_number)
            ->where('id', '!=', $interviewRound->id)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($existingRound) {
            return redirect()->back()->with('error', __('Interview round with sequence number :sequence already exists for this job posting', ['sequence' => $request->sequence_number]));
        }

        $interviewRound->update([
            'job_id' => $request->job_id,
            'name' => $request->name,
            'sequence_number' => $request->sequence_number,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Interview round updated successfully'));
    }

    public function destroy(InterviewRound $interviewRound)
    {
        if (!in_array($interviewRound->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this interview round'));
        }

        if ($interviewRound->interviews()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete interview round as it has associated interviews'));
        }

        $interviewRound->delete();
        return redirect()->back()->with('success', __('Interview round deleted successfully'));
    }

    public function toggleStatus(InterviewRound $interviewRound)
    {
        if (!in_array($interviewRound->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this interview round'));
        }

        $interviewRound->update([
            'status' => $interviewRound->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Interview round status updated successfully'));
    }
}
