<?php

namespace App\Http\Controllers;

use App\Models\CandidateOnboarding;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\OnboardingChecklist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CandidateOnboardingController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-candidate-onboarding')) {
            $query = CandidateOnboarding::with(['employee', 'checklist', 'buddyEmployee'])->where(function ($q) {
                if (Auth::user()->can('manage-any-candidate-onboarding')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-candidate-onboarding')) {
                    $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id())->orWhere('buddy_employee_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('employee_id') && !empty($request->employee_id) && $request->employee_id !== 'all') {
                $query->where('employee_id', $request->employee_id);
            }

            $query->orderBy('id', 'desc');
            $candidateOnboarding = $query->paginate($request->per_page ?? 10);

            $employees = User::with('employee')
                ->where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name')
                ->orderBy('id', 'desc')
                ->get();

            $checklists = OnboardingChecklist::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name')
                ->get();

            return Inertia::render('hr/recruitment/candidate-onboarding/index', [
                'candidateOnboarding' => $candidateOnboarding,
                'employees' => $this->getFilteredEmployees(),
                'checklists' => $checklists,
                'buddyEmployees' => $employees,
                'filters' => $request->all(['search', 'status', 'employee_id', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-candidate-onboarding') && !Auth::user()->can('manage-any-candidate-onboarding')) {
            $employeeQuery->where(function ($q) {
                $q->where('created_by', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        $employees = User::emp()
            ->with('employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee->employee_id ?? '',
                ];
            });
        return $employees;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'checklist_id' => 'required|exists:onboarding_checklists,id',
            'start_date' => 'required|date',
            'buddy_employee_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if checklist is already assigned to this employee
        $exists = CandidateOnboarding::where('employee_id', $request->employee_id)
            ->where('checklist_id', $request->checklist_id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', __('This checklist is already assigned to the selected employee'));
        }

        CandidateOnboarding::create([
            'employee_id' => $request->employee_id,
            'checklist_id' => $request->checklist_id,
            'start_date' => $request->start_date,
            'buddy_employee_id' => $request->buddy_employee_id,
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Candidate onboarding created successfully'));
    }

    public function update(Request $request, CandidateOnboarding $candidateOnboarding)
    {
        if (!in_array($candidateOnboarding->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this onboarding'));
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'checklist_id' => 'required|exists:onboarding_checklists,id',
            'start_date' => 'required|date',
            'buddy_employee_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if checklist is already assigned to this employee (excluding current record)
        $exists = CandidateOnboarding::where('employee_id', $request->employee_id)
            ->where('checklist_id', $request->checklist_id)
            ->where('id', '!=', $candidateOnboarding->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', __('This checklist is already assigned to the selected employee'));
        }

        $candidateOnboarding->update([
            'employee_id' => $request->employee_id,
            'checklist_id' => $request->checklist_id,
            'start_date' => $request->start_date,
            'buddy_employee_id' => $request->buddy_employee_id,
        ]);

        return redirect()->back()->with('success', __('Candidate onboarding updated successfully'));
    }

    public function destroy(CandidateOnboarding $candidateOnboarding)
    {
        if (!in_array($candidateOnboarding->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this onboarding'));
        }

        $candidateOnboarding->delete();
        return redirect()->back()->with('success', __('Candidate onboarding deleted successfully'));
    }

    public function show(CandidateOnboarding $candidateOnboarding)
    {
        if (!in_array($candidateOnboarding->created_by, getCompanyAndUsersId())) {
            return abort(404);
        }

        $candidateOnboarding->load([
            'employee',
            'checklist.checklistItems',
            'buddyEmployee',
            'creator'
        ]);

        return Inertia::render('hr/recruitment/candidate-onboarding/show', [
            'candidateOnboarding' => $candidateOnboarding,
        ]);
    }

    public function updateStatus(Request $request, CandidateOnboarding $candidateOnboarding)
    {
        if (!in_array($candidateOnboarding->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this onboarding'));
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Pending,In Progress,Completed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $candidateOnboarding->update(['status' => $request->status]);
        return redirect()->back()->with('success', __('Onboarding status updated successfully'));
    }
}
