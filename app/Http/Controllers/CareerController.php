<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateSource;
use App\Models\JobLocation;
use App\Models\JobPosting;
use App\Models\JobType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CareerController extends Controller
{
    public function index(Request $request, $userSlug = null)
    {
        // Access shared data from middleware
        $companyId = $request->get('companyId');
        $companySettings = $request->get('companySettings');
        $userSlug = $request->get('userSlug');

        $query = JobPosting::with(['jobType', 'location', 'branch', 'department'])
            ->where('is_published', true)
            ->where('status', 'Published');

        if ($companyId) {
            $query->whereIn('created_by', getCompanyUsers($companyId));
        }

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('job_type') && !empty($request->job_type)) {
            $jobTypeIds = explode(',', $request->job_type);
            $query->whereIn('job_type_id', $jobTypeIds);
        }

        if ($request->has('location') && !empty($request->location)) {
            $query->where('location_id', $request->location);
        }

        if ($request->has('salary_range') && !empty($request->salary_range)) {
            switch ($request->salary_range) {
                case '0-50k':
                    $query->where('max_salary', '<=', 50000);
                    break;
                case '50k-100k':
                    $query->whereBetween('min_salary', [50000, 100000]);
                    break;
                case '100k+':
                    $query->where('min_salary', '>=', 100000);
                    break;
            }
        }

        if ($request->has('vacancies') && !empty($request->vacancies)) {
            $vacancyRanges = explode(',', $request->vacancies);
            $query->where(function ($q) use ($vacancyRanges) {
                foreach ($vacancyRanges as $range) {
                    switch ($range) {
                        case '1-5':
                            $q->orWhereBetween('positions', [1, 5]);
                            break;
                        case '6-15':
                            $q->orWhereBetween('positions', [6, 15]);
                            break;
                        case '16-25':
                            $q->orWhereBetween('positions', [16, 25]);
                            break;
                        case '25+':
                            $q->orWhere('positions', '>', 25);
                            break;
                    }
                }
            });
        }

        // $query = $query->orderBy('is_featured', 'desc');

        if ($request->has('sort') && !empty($request->sort)) {
            switch ($request->sort) {
                case 'oldest':
                    $query = $query->orderBy('created_at', 'asc');
                    break;
                case 'salary-high':
                    $query = $query->orderBy('max_salary', 'desc');
                    break;
                case 'salary-low':
                    $query = $query->orderBy('min_salary', 'asc');
                    break;
                default: // newest
                    $query = $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query = $query->orderBy('created_at', 'desc');
        }

        $jobPostings = $query->paginate(6);

        $jobTypes = JobType::where('status', 'active')->whereIn('created_by', getCompanyUsers($companyId))->get();
        $locations = JobLocation::where('status', 'active')->whereIn('created_by', getCompanyUsers($companyId))->get();

        $vacancyRanges = [
            ['value' => '1-5', 'label' => '1-5'],
            ['value' => '6-15', 'label' => '6-15'],
            ['value' => '16-25', 'label' => '16-25'],
            ['value' => '25+', 'label' => '25+'],
        ];

        return Inertia::render('career/index', [
            'jobPostings' => $jobPostings,
            'jobTypes' => $jobTypes,
            'locations' => $locations,
            'companyId' => $companyId,
            'userSlug' => $userSlug,
            'companySettings' => $companySettings,
            'vacancyRanges' => $vacancyRanges,
            'filters' => $request->all(keys: ['search', 'job_type', 'location', 'salary_range', 'vacancies', 'sort']),
        ]);
    }

    public function show(Request $request, $userSlug, $jobCode)
    {
        try {
            // Get company data from middleware
            $companyId = $request->get('companyId');
            $companySettings = $request->get('companySettings');

            $query = JobPosting::with(['jobType', 'location', 'branch', 'department'])
                ->where('code', $jobCode)
                ->whereIn('created_by', getCompanyUsers($companyId))
                ->where('is_published', true)
                ->where('status', 'Published');

            $jobPosting = $query->firstOrFail();

            $relatedQuery = JobPosting::with(['jobType', 'location', 'branch', 'department'])
                ->where('code', '!=', $jobCode)
                ->where('is_published', true)
                ->where('status', 'Published');

            if ($companyId) {
                $relatedQuery->whereIn('created_by', getCompanyUsers($companyId));
            }

            $relatedJobs = $relatedQuery->inRandomOrder()->limit(4)->get();

            $companySettings = null;
            if ($companyId) {
                $companySettings = settings($companyId);
                $companyUser = User::find($companyId);
                if ($companyUser) {
                    $companySettings = array_merge($companySettings, [
                        'company_name' => $companyUser->name,
                        'company_email' => $companyUser->email,
                    ]);
                }
            }

            return Inertia::render('career/job-details', [
                'jobPosting' => $jobPosting,
                'relatedJobs' => $relatedJobs,
                'companyId' => $companyId,
                'userSlug' => $userSlug,
                'companySettings' => $companySettings,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('career.index', $userSlug)
                ->with('error', 'Job not found or no longer available.');
        }
    }

    public function showApplicationForm(Request $request, $userSlug, $jobCode)
    {
        try {
            // Get company data from middleware
            $companyId = $request->get('companyId');
            $companySettings = $request->get('companySettings');

            $jobPosting = JobPosting::with(['jobType', 'location', 'branch', 'department'])
                ->where('code', $jobCode)
                ->whereIn('created_by', getCompanyUsers($companyId))
                ->where('is_published', true)
                ->where('status', 'Published')
                ->firstOrFail();

            // Get custom questions based on IDs stored in job posting
            $customQuestions = [];
            if ($jobPosting->custom_question && is_array($jobPosting->custom_question)) {
                $customQuestions = \App\Models\CustomQuestion::whereIn('id', $jobPosting->custom_question)
                    ->get();
            }

            // Get candidate sources
            $candidateSources = CandidateSource::where('status', 'active')
                ->whereIn('created_by', getCompanyUsers($companyId))
                ->get();

            return Inertia::render('career/apply', [
                'jobPosting' => $jobPosting,
                'customQuestions' => $customQuestions,
                'candidateSources' => $candidateSources,
                'applicantFields' => $jobPosting->applicant ?? [],
                'visibilityFields' => $jobPosting->visibility ?? [],
                'companyId' => $companyId,
                'userSlug' => $userSlug,
                'companySettings' => $companySettings,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('career.index', $userSlug)
                ->with('error', 'Job not found or no longer available.');
        }
    }

    public function submitApplication(Request $request, $userSlug, $jobCode)
    {
        try {
            // Get company data from middleware
            $companyId = $request->get('companyId');

            // Find the job posting
            $jobPosting = JobPosting::where('code', $jobCode)
                ->whereIn('created_by', getCompanyUsers($companyId))
                ->where('is_published', true)
                ->where('status', 'Published')
                ->firstOrFail();

            // Base validation rules
            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'zip_code' => 'required',
                'country' => 'required',
                'current_position' => 'required',
                'current_company' => 'required',
                'experience_years' => 'required|numeric|min:0|max:50',
                'current_salary' => 'required|numeric|min:0',
                'expected_salary' => 'required|numeric|min:0',
                'source_id' => 'required|exists:candidate_sources,id',
                'custom_question' => 'nullable|json',
                'resume' => 'required',
            ];

            // Check job posting applicant fields for conditional validation
            $applicantFields = $jobPosting->applicant ?? [];
            $visibilityFields = $jobPosting->visibility ?? [];

            // Add conditional validation for gender
            if (in_array('gender', $applicantFields)) {
                $rules['gender'] = 'required|in:male,female,other';
            } else {
                $rules['gender'] = 'nullable|in:male,female,other';
            }

            // Add conditional validation for date_of_birth
            if (in_array('date_of_birth', $applicantFields)) {
                $rules['date_of_birth'] = 'required|date';
            } else {
                $rules['date_of_birth'] = 'nullable|date';
            }

            // Add conditional validation for cover letter fields
            if (in_array('cover_letter', $visibilityFields)) {
                $rules['coverletter_message'] = 'required|string|max:2000';
                $rules['cover_letter_file'] = 'required';
            } else {
                $rules['coverletter_message'] = 'nullable|string|max:2000';
                $rules['cover_letter_file'] = 'nullable|file';
            }

            // Add conditional validation for terms and conditions
            if (in_array('terms_and_conditions', $visibilityFields)) {
                $rules['terms_condition_check'] = 'required|in:on,off,1,0';
            } else {
                // If terms not required, accept any value or make it optional
                $rules['terms_condition_check'] = 'sometimes|in:on,off,1,0';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Check if candidate already applied for this job
            $existingCandidate = Candidate::where('email', $request->email)
                ->where('job_id', $jobPosting->id)
                ->first();

            if ($existingCandidate) {
                return redirect()->back()
                    ->withErrors(['email' => 'You have already applied for this position.'])
                    ->withInput();
            }

            // Handle file uploads
            $resumePath = null;
            $coverLetterPath = null;

            if (!empty($request->resume) && $request->hasFile('resume')) {
                $filenameWithExt = $request->file('resume')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('resume')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'resume', $fileNameToStore, 'candidates/candidate_resumes');
                if ($upload['status'] == true) {
                    $resumePath = $upload['url'];
                } else {
                    return redirect()->back()
                        ->withErrors(['resume' => $upload['msg']])
                        ->withInput();
                }
            }

            if (!empty($request->cover_letter_file) && $request->hasFile('cover_letter_file')) {
                $filenameWithExt = $request->file('cover_letter_file')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('cover_letter_file')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'cover_letter_file', $fileNameToStore, 'candidates/candidate_cover_letters');
                if ($upload['status'] == true) {
                    $coverLetterPath = $upload['url'];
                } else {
                    return redirect()->back()
                        ->withErrors(['cover_letter_file' => $upload['msg']])
                        ->withInput();
                }
            }

            // Convert terms_condition_check from 1/0 to on/off
            if ($request->has('terms_condition_check')) {
                $termsValue = $request->terms_condition_check;
                $request->merge([
                    'terms_condition_check' => ($termsValue == '1' || $termsValue === true) ? 'on' : 'off',
                ]);
            }

            // Get custom questions for processing answers
            $customQuestions = [];
            if ($jobPosting->custom_question && is_array($jobPosting->custom_question)) {
                $customQuestions = \App\Models\CustomQuestion::whereIn('id', $jobPosting->custom_question)
                    ->get();
            }

            // Process custom questions into question-answer format
            $customQuestionData = [];
            if ($customQuestions && count($customQuestions) > 0) {
                foreach ($customQuestions as $question) {
                    $fieldName = 'custom_question_' . $question->id;
                    if ($request->has($fieldName) && !empty($request->input($fieldName))) {
                        $customQuestionData[$question->question] = $request->input($fieldName);
                    }
                }
            }

            // Create candidate record
            $candidate = new Candidate;
            $candidate->job_id = $jobPosting->id;
            $candidate->source_id = $request->source_id;
            $candidate->branch_id = $jobPosting->branch_id;
            $candidate->department_id = $jobPosting->department_id;
            $candidate->first_name = $request->first_name;
            $candidate->last_name = $request->last_name;
            $candidate->email = $request->email;
            $candidate->phone = $request->phone;
            $candidate->gender = $request->gender;
            $candidate->date_of_birth = $request->date_of_birth;
            $candidate->address = $request->address;
            $candidate->city = $request->city;
            $candidate->state = $request->state;
            $candidate->zip_code = $request->zip_code;
            $candidate->country = $request->country;
            $candidate->current_company = $request->current_company;
            $candidate->current_position = $request->current_position;
            $candidate->experience_years = $request->experience_years ?: 0;
            $candidate->current_salary = $request->current_salary ? str_replace(',', '', $request->current_salary) : null;
            $candidate->expected_salary = $request->expected_salary ? str_replace(',', '', $request->expected_salary) : null;
            $candidate->resume_path = $resumePath;
            $candidate->cover_letter_path = $coverLetterPath;
            $candidate->coverletter_message = $request->coverletter_message;
            $candidate->custom_question = $customQuestionData;
            $candidate->terms_condition_check = $request->terms_condition_check;
            $candidate->application_date = now()->toDateString();
            $candidate->created_by = $companyId;

            $candidate->save();

            return redirect()->back()
                ->with('success', 'Your application has been submitted successfully! We will review it and get back to you soon.');

        } catch (\Exception $e) {
            // Clean up uploaded files if candidate creation fails
            if (isset($resumePath) && Storage::disk('public')->exists($resumePath)) {
                Storage::disk('public')->delete($resumePath);
            }
            if (isset($coverLetterPath) && Storage::disk('public')->exists($coverLetterPath)) {
                Storage::disk('public')->delete($coverLetterPath);
            }

            return redirect()->back()
                ->withErrors(['error' => 'An error occurred while submitting your application. Please try again.'])
                ->withInput();
        }
    }
}
