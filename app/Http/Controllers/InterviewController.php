<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Models\Candidate;
use App\Models\InterviewRound;
use App\Models\InterviewType;
use App\Models\User;
use App\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class InterviewController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-interviews')) {
            $query = Interview::with(['candidate', 'job', 'round', 'interviewType'])->where(function ($q) {
                if (Auth::user()->can('manage-any-interviews')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-interviews')) {
                    $q->where('created_by', Auth::id())->orwhereJsonContains('interviewers', (string) Auth::id());
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
            $interviews = $query->paginate($request->per_page ?? 10);

            $candidates = Candidate::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'first_name', 'last_name')
                ->where('status', 'Interview')
                ->get();

            $interviewTypes = InterviewType::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name')
                ->get();

            $employees = UserModel::with('employee')
                ->whereIn('type', ['manager', 'hr', 'employee'])
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name', 'type')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'type' => $user->type,
                        'employee_id' => $user->employee->employee_id ?? ''
                    ];
                });

            return Inertia::render('hr/recruitment/interviews/index', [
                'interviews' => $interviews,
                'candidates' => $candidates,
                'interviewTypes' => $interviewTypes,
                'employees' => $employees,
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
            'round_id' => 'required|exists:interview_rounds,id',
            'interview_type_id' => 'required|exists:interview_types,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:15|max:480',
            'location' => 'nullable|string|max:255',
            'meeting_link' => 'nullable|url',
            'interviewers' => 'required|array|min:1',
            'interviewers.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if interview already exists for this candidate and round
        $existingInterview = Interview::where('candidate_id', $request->candidate_id)
            ->where('round_id', $request->round_id)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($existingInterview) {
            return redirect()->back()->with('error', __('Interview already exists for this interview round'));
        }

        $candidate = Candidate::find($request->candidate_id);

        Interview::create([
            'candidate_id' => $request->candidate_id,
            'job_id' => $candidate->job_id,
            'round_id' => $request->round_id,
            'interview_type_id' => $request->interview_type_id,
            'scheduled_date' => $request->scheduled_date,
            'scheduled_time' => $request->scheduled_time,
            'duration' => $request->duration,
            'location' => $request->location,
            'meeting_link' => $request->meeting_link,
            'interviewers' => $request->interviewers,
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Interview scheduled successfully'));
    }

    public function update(Request $request, Interview $interview)
    {
        if (!in_array($interview->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this interview'));
        }

        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|exists:candidates,id',
            'round_id' => 'required|exists:interview_rounds,id',
            'interview_type_id' => 'required|exists:interview_types,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'duration' => 'required|integer|min:15|max:480',
            'location' => 'nullable|string|max:255',
            'meeting_link' => 'nullable|url',
            'interviewers' => 'required|array|min:1',
            'interviewers.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if interview already exists for this candidate and round (excluding current record)
        $existingInterview = Interview::where('candidate_id', $request->candidate_id)
            ->where('round_id', $request->round_id)
            ->where('id', '!=', $interview->id)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($existingInterview) {
            return redirect()->back()->with('error', __('Interview already exists for this interview round'));
        }

        $candidate = Candidate::find($request->candidate_id);

        $interview->update([
            'candidate_id' => $request->candidate_id,
            'job_id' => $candidate->job_id,
            'round_id' => $request->round_id,
            'interview_type_id' => $request->interview_type_id,
            'scheduled_date' => $request->scheduled_date,
            'scheduled_time' => $request->scheduled_time,
            'duration' => $request->duration,
            'location' => $request->location,
            'meeting_link' => $request->meeting_link,
            'interviewers' => $request->interviewers,
        ]);

        return redirect()->back()->with('success', __('Interview updated successfully'));
    }

    public function destroy(Interview $interview)
    {
        if (!in_array($interview->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this interview'));
        }

        $interview->delete();
        return redirect()->back()->with('success', __('Interview deleted successfully'));
    }

    public function updateStatus(Request $request, Interview $interview)
    {
        if (!in_array($interview->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this interview'));
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Scheduled,Completed,Cancelled,No-show',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $interview->update(['status' => $request->status]);
        return redirect()->back()->with('success', __('Interview status updated successfully'));
    }

    public function getRoundsByCandidate(Candidate $candidate)
    {
        if (!in_array($candidate->created_by, getCompanyAndUsersId())) {
            return response()->json([]);
        }

        $rounds = InterviewRound::where('job_id', $candidate->job_id)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        return response()->json($rounds);
    }
}
