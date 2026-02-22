<?php

namespace App\Http\Controllers;

use App\Models\CandidateAssessment;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CandidateAssessmentController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-candidate-assessments')) {
            $query = CandidateAssessment::with(['candidate', 'conductor'])->where(function ($q) {
                if (Auth::user()->can('manage-any-candidate-assessments')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-candidate-assessments')) {
                    $q->where('created_by', Auth::id())->orWhere('conducted_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('assessment_name', 'like', '%' . $request->search . '%')
                        ->orWhereHas('candidate', function ($cq) use ($request) {
                            $cq->where('first_name', 'like', '%' . $request->search . '%')
                                ->orWhere('last_name', 'like', '%' . $request->search . '%');
                        });
                });
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('pass_fail_status', $request->status);
            }

            if ($request->has('candidate_id') && !empty($request->candidate_id) && $request->candidate_id !== 'all') {
                $query->where('candidate_id', $request->candidate_id);
            }

            $query->orderBy('id', 'desc');
            $assessments = $query->paginate($request->per_page ?? 10);

            $candidates = Candidate::whereIn('created_by', getCompanyAndUsersId())->where('is_employee',0)->get();

            $employees = User::with('employee')
                ->whereIn('type', ['manager', 'hr', 'employee'])
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'employee_id' => $user->employee->employee_id ?? ''
                    ];
                });

            return Inertia::render('hr/recruitment/candidate-assessments/index', [
                'assessments' => $assessments,
                'candidates' => $candidates,
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
            'assessment_name' => 'required|string|max:255',
            'score' => 'nullable|integer|min:0',
            'max_score' => 'nullable|integer|min:1',
            'pass_fail_status' => 'required|in:Pass,Fail,Pending',
            'comments' => 'nullable|string',
            'conducted_by' => 'required|exists:users,id',
            'assessment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        CandidateAssessment::create([
            'candidate_id' => $request->candidate_id,
            'assessment_name' => $request->assessment_name,
            'score' => $request->score,
            'max_score' => $request->max_score,
            'pass_fail_status' => $request->pass_fail_status,
            'comments' => $request->comments,
            'conducted_by' => $request->conducted_by,
            'assessment_date' => $request->assessment_date,
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Assessment created successfully'));
    }

    public function update(Request $request, CandidateAssessment $candidateAssessment)
    {
        if (!in_array($candidateAssessment->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this assessment'));
        }

        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|exists:candidates,id',
            'assessment_name' => 'required|string|max:255',
            'score' => 'nullable|integer|min:0',
            'max_score' => 'nullable|integer|min:1',
            'pass_fail_status' => 'required|in:Pass,Fail,Pending',
            'comments' => 'nullable|string',
            'conducted_by' => 'required|exists:users,id',
            'assessment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $candidateAssessment->update([
            'candidate_id' => $request->candidate_id,
            'assessment_name' => $request->assessment_name,
            'score' => $request->score,
            'max_score' => $request->max_score,
            'pass_fail_status' => $request->pass_fail_status,
            'comments' => $request->comments,
            'conducted_by' => $request->conducted_by,
            'assessment_date' => $request->assessment_date,
        ]);

        return redirect()->back()->with('success', __('Assessment updated successfully'));
    }

    public function destroy(CandidateAssessment $candidateAssessment)
    {
        if (!in_array($candidateAssessment->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this assessment'));
        }

        $candidateAssessment->delete();
        return redirect()->back()->with('success', __('Assessment deleted successfully'));
    }
}
