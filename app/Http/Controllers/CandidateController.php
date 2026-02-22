<?php

namespace App\Http\Controllers;

use App\Models\AttendancePolicy;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\CandidateSource;
use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\Offer;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-candidates')) {
            $query = Candidate::with(['job', 'source', 'referralEmployee'])->where(function ($q) {
                if (Auth::user()->can('manage-any-candidates')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-candidates')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('job_id') && !empty($request->job_id) && $request->job_id !== 'all') {
                $query->where('job_id', $request->job_id);
            }

            if ($request->has('source_id') && !empty($request->source_id) && $request->source_id !== 'all') {
                $query->where('source_id', $request->source_id);
            }

            $query->orderBy('created_at', 'desc');
            $candidates = $query->paginate($request->per_page ?? 10);

            $jobPostings = JobPosting::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'title', 'job_code')
                ->get();

            $sources = CandidateSource::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name')
                ->get();

            $employees = User::with('employee')
                ->where('type', 'employee')
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

            return Inertia::render('hr/recruitment/candidates/index', [
                'candidates' => $candidates,
                'jobPostings' => $jobPostings,
                'sources' => $sources,
                'employees' => $employees,
                'filters' => $request->all(['search', 'status', 'job_id', 'source_id', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:job_postings,id',
            'source_id' => 'required|exists:candidate_sources,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'current_company' => 'nullable|string|max:255',
            'current_position' => 'nullable|string|max:255',
            'experience_years' => 'required|integer|min:0',
            'current_salary' => 'nullable|numeric|min:0',
            'expected_salary' => 'nullable|numeric|min:0',
            'notice_period' => 'nullable|string|max:255',
            'skills' => 'nullable|string',
            'education' => 'nullable|string',
            'portfolio_url' => 'nullable|string',
            'linkedin_url' => 'nullable|string',
            'referral_employee_id' => 'nullable|exists:users,id',
            'application_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Candidate::create([
            'job_id' => $request->job_id,
            'source_id' => $request->source_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'current_company' => $request->current_company,
            'current_position' => $request->current_position,
            'experience_years' => $request->experience_years,
            'current_salary' => $request->current_salary,
            'expected_salary' => $request->expected_salary,
            'notice_period' => $request->notice_period,
            'skills' => $request->skills,
            'education' => $request->education,
            'portfolio_url' => $request->portfolio_url ?: null,
            'linkedin_url' => $request->linkedin_url ?: null,
            'referral_employee_id' => $request->referral_employee_id ?: null,
            'application_date' => $request->application_date,
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Candidate created successfully'));
    }

    public function update(Request $request, Candidate $candidate)
    {
        if (!in_array($candidate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this candidate');
        }

        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:job_postings,id',
            'source_id' => 'required|exists:candidate_sources,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'current_company' => 'nullable|string|max:255',
            'current_position' => 'nullable|string|max:255',
            'experience_years' => 'required|integer|min:0',
            'current_salary' => 'nullable|numeric|min:0',
            'expected_salary' => 'nullable|numeric|min:0',
            'notice_period' => 'nullable|string|max:255',
            'skills' => 'nullable|string',
            'education' => 'nullable|string',
            'portfolio_url' => 'nullable|string',
            'linkedin_url' => 'nullable|string',
            'referral_employee_id' => 'nullable|exists:users,id',
            'application_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $candidate->update($request->only([
            'job_id',
            'source_id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'current_company',
            'current_position',
            'experience_years',
            'current_salary',
            'expected_salary',
            'notice_period',
            'skills',
            'education',
            'portfolio_url',
            'linkedin_url',
            'referral_employee_id',
            'application_date'
        ]));

        return redirect()->back()->with('success', __('Candidate updated successfully'));
    }

    public function destroy(Candidate $candidate)
    {
        if (!in_array($candidate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to delete this candidate');
        }

        $candidate->delete();
        return redirect()->back()->with('success', __('Candidate deleted successfully'));
    }

    public function show(Candidate $candidate)
    {
        if (!in_array($candidate->created_by, getCompanyAndUsersId())) {
            return abort(404);
        }

        $candidate->load([
            'job.location',
            'job.jobType',
            'source',
            'referralEmployee',
            'branch',
            'department'
        ]);

        return Inertia::render('hr/recruitment/candidates/show', [
            'candidate' => $candidate,
        ]);
    }
    public function updateStatus(Request $request, Candidate $candidate)
    {
        if (!in_array($candidate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this candidate');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:New,Screening,Interview,Offer,Hired,Rejected',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $candidate->update(['status' => $request->status]);
        return redirect()->back()->with('success', __('Candidate status updated successfully'));
    }

    public function convertToEmployee(Candidate $candidate)
    {
        try {
            if (Auth::user()->can('convert-to-employee')) {
                if (!in_array($candidate->created_by, getCompanyAndUsersId())) {
                    return redirect()->back()->with('error', __('You do not have permission to convert this candidate'));
                }

                if ($candidate->status !== 'Hired') {
                    return redirect()->back()->with('error', __('Only hired candidates can be converted to employees'));
                }

                if ($candidate->is_employee) {
                    return redirect()->back()->with('error', __('This candidate has already been converted to an employee'));
                }

                // Check if candidate has an accepted offer
                $acceptedOffer = Offer::where('candidate_id', $candidate->id)
                    ->where('status', 'Accepted')
                    ->first();

                if (!$acceptedOffer) {
                    return redirect()->back()->with('error', __('Candidate must have an accepted offer before conversion to employee'));
                }

                // Get data needed for employee creation form
                $branches = Branch::whereIn('created_by', getCompanyAndUsersId())
                    ->where('status', 'active')
                    ->get(['id', 'name']);

                $departments = Department::with('branch')
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->where('status', 'active')
                    ->get(['id', 'name', 'branch_id']);

                $designations = Designation::with('department')
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->where('status', 'active')
                    ->get(['id', 'name', 'department_id']);

                $documentTypes = DocumentType::whereIn('created_by', getCompanyAndUsersId())
                    ->get(['id', 'name', 'is_required']);

                $shifts = Shift::whereIn('created_by', getCompanyAndUsersId())
                    ->where('status', 'active')
                    ->get(['id', 'name', 'start_time', 'end_time']);

                $attendancePolicies = AttendancePolicy::whereIn('created_by', getCompanyAndUsersId())
                    ->where('status', 'active')
                    ->get(['id', 'name']);

                return Inertia::render('hr/recruitment/candidates/convert-to-employee', [
                    'candidate' => $candidate->load(['job', 'source']),
                    'branches' => $branches,
                    'departments' => $departments,
                    'designations' => $designations,
                    'documentTypes' => $documentTypes,
                    'shifts' => $shifts,
                    'attendancePolicies' => $attendancePolicies,
                    'generatedEmployeeId' => Employee::generateEmployeeId(),
                ]);
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } catch (\Exception $e) {
            \Log::error('Convert to employee page load failed: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Failed to load conversion page: :message', ['message' => $e->getMessage()]));
        }
    }

    public function storeEmployee(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'candidate_id' => 'required|exists:candidates,id',
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
                'phone' => 'required|string|max:20',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'branch_id' => 'required|exists:branches,id',
                'department_id' => 'required|exists:departments,id',
                'designation_id' => 'required|exists:designations,id',
                'date_of_joining' => 'required|date',
                'employment_type' => 'required|string|max:50',
                'address_line_1' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'country' => 'required|string|max:100',
                'postal_code' => 'required|string|max:20',
                'emergency_contact_name' => 'required|string|max:255',
                'emergency_contact_relationship' => 'required|string|max:100',
                'emergency_contact_number' => 'required|string|max:20',
                'bank_name' => 'required|string|max:255',
                'account_holder_name' => 'required|string|max:255',
                'account_number' => 'required|string|max:50',
                'salary' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Get candidate
            $candidate = \App\Models\Candidate::findOrFail($request->candidate_id);

            // Check permissions and status
            if (!in_array($candidate->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', 'Permission denied');
            }

            if ($candidate->status !== 'Hired' || $candidate->is_employee) {
                return redirect()->back()->with('error', 'Invalid candidate status for conversion');
            }

            // Create User
            $user = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                'type' => 'employee',
                'lang' => 'en',
                'avatar' => $request->profile_image,
                'created_by' => creatorId(),
            ]);

            // Assign Employee role
            if (isSaas()) {
                $employeeRole = \Spatie\Permission\Models\Role::where('created_by', createdBy())->where('name', 'employee')->first();
            } else {
                $employeeRole = \Spatie\Permission\Models\Role::where('name', 'employee')->first();
            }
            if ($employeeRole) {
                $user->assignRole($employeeRole);
            }

            // Create Employee
            $employee = \App\Models\Employee::create([
                'user_id' => $user->id,
                'employee_id' => \App\Models\Employee::generateEmployeeId(),
                'biometric_emp_id' => $request->biometric_emp_id,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'branch_id' => $request->branch_id,
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'shift_id' => $request->shift_id,
                'attendance_policy_id' => $request->attendance_policy_id,
                'date_of_joining' => $request->date_of_joining,
                'employment_type' => $request->employment_type,
                'employee_status' => $request->employee_status ?? 'active',
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'postal_code' => $request->postal_code,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_relationship' => $request->emergency_contact_relationship,
                'emergency_contact_number' => $request->emergency_contact_number,
                'bank_name' => $request->bank_name,
                'account_holder_name' => $request->account_holder_name,
                'account_number' => $request->account_number,
                'bank_identifier_code' => $request->bank_identifier_code,
                'bank_branch' => $request->bank_branch,
                'tax_payer_id' => $request->tax_payer_id,
                'created_by' => creatorId(),
            ]);

            // Handle documents
            if ($request->has('documents') && is_array($request->documents)) {
                foreach ($request->documents as $document) {
                    if (isset($document['file_path']) && !empty($document['file_path'])) {
                        \App\Models\EmployeeDocument::create([
                            'employee_id' => $employee->user_id,
                            'document_type_id' => $document['document_type_id'],
                            'file_path' => $document['file_path'],
                            'expiry_date' => $document['expiry_date'] ?? null,
                            'verification_status' => 'pending',
                            'created_by' => creatorId(),
                        ]);
                    }
                }
            }

            // Mark candidate as converted
            $candidate->update(['is_employee' => true]);

            return redirect()->route('hr.employees.index')->with('success', __('Candidate converted to employee successfully'));

        } catch (\Exception $e) {
            \Log::error('Candidate to employee conversion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Failed to convert candidate: :message', ['message' => $e->getMessage()]))->withInput();
        }
    }
}
