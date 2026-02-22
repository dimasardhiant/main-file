<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeReview;
use App\Models\EmployeeReviewRating;
use App\Models\PerformanceIndicator;
use App\Models\ReviewCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class EmployeeReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-employee-reviews')) {
            $query = EmployeeReview::with(['employee', 'reviewer', 'reviewCycle'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-employee-reviews')) {
                        $q->whereIn('created_by',  getCompanyAndUsersId());
                    } elseif (Auth::user()->can('manage-own-employee-reviews')) {
                        $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('employee', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('employee_id', 'like', '%' . $request->search . '%');
                    })
                        ->orWhereHas('reviewer', function ($q) use ($request) {
                            $q->where('name', 'like', '%' . $request->search . '%')
                                ->orWhere('employee_id', 'like', '%' . $request->search . '%');
                        });
                });
            }

            // Handle employee filter
            if ($request->has('employee_id') && !empty($request->employee_id)) {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle reviewer filter
            if ($request->has('reviewer_id') && !empty($request->reviewer_id)) {
                $query->where('reviewer_id', $request->reviewer_id);
            }

            // Handle review cycle filter
            if ($request->has('review_cycle_id') && !empty($request->review_cycle_id)) {
                $query->where('review_cycle_id', $request->review_cycle_id);
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('review_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('review_date', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('review_date', 'desc');
            }

            $reviews = $query->paginate($request->per_page ?? 10);

            // Get review cycles for filter dropdown
            $reviewCycles = ReviewCycle::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']);

            return Inertia::render('hr/performance/employee-reviews/index', [
                'reviews' => $reviews,
                'employees' => $this->getFilteredEmployees(),
                'reviewCycles' => $reviewCycles,
                'filters' => $request->all(['search', 'employee_id', 'reviewer_id', 'review_cycle_id', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-employee-reviews') && !Auth::user()->can('manage-any-employee-reviews')) {
            $employeeQuery->where(function ($q) {
                $q->where('created_by', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        $employees = User::emp()
            ->with('employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name', 'type')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee->employee_id ?? '',
                    'type' => $user->type,
                ];
            });
        return $employees;
    }
    public function create()
    {
        if (Auth::user()->can('create-employee-reviews')) {
            // Get employees for dropdown
            $employees = $this->getFilteredEmployees();
            // Get review cycles for dropdown
            $reviewCycles = ReviewCycle::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']);

            return Inertia::render('hr/performance/employee-reviews/create', [
                'employees' => $employees,
                'reviewCycles' => $reviewCycles,
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->can('create-employee-reviews')) {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:users,id',
                'reviewer_id' => 'required|exists:users,id',
                'review_cycle_id' => 'required|exists:review_cycles,id',
                'review_date' => 'required|date',
                'status' => 'nullable|string|in:scheduled,in_progress,completed',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Verify employee belongs to current company
            $employee = User::find($request->employee_id);
            if (!$employee || !in_array($employee->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('Invalid employee selected'))->withInput();
            }

            // Verify reviewer belongs to current company
            $reviewer = User::find($request->reviewer_id);
            if (!$reviewer || !in_array($reviewer->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('Invalid reviewer selected'))->withInput();
            }

            // Verify review cycle belongs to current company
            $reviewCycle = ReviewCycle::find($request->review_cycle_id);
            if (!$reviewCycle || !in_array($reviewCycle->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('Invalid review cycle selected'))->withInput();
            }

            // Create the review
            $review = EmployeeReview::create([
                'employee_id' => $request->employee_id,
                'reviewer_id' => $request->reviewer_id,
                'review_cycle_id' => $request->review_cycle_id,
                'review_date' => $request->review_date,
                'status' => $request->status ?? 'scheduled',
                'created_by' => creatorId(),
            ]);

            return redirect()->route('hr.performance.employee-reviews.index')->with('success', __('Employee review scheduled successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeReview $employeeReview)
    {
        if (Auth::user()->can('view-employee-reviews')) {
            // Check if review belongs to current company
            if (!in_array($employeeReview->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to view this review'));
            }

            $employeeReview->load([
                'employee',
                'reviewer',
                'reviewCycle',
                'ratings.indicator.category'
            ]);

            return Inertia::render('hr/performance/employee-reviews/show', [
                'review' => $employeeReview,
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for conducting a review.
     */
    public function conduct(EmployeeReview $employeeReview)
    {
        if (Auth::user()->can('edit-employee-reviews')) {
            // Check if review belongs to current company
            if (!in_array($employeeReview->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to conduct this review'));
            }

            $employeeReview->load([
                'employee',
                'reviewer',
                'reviewCycle',
                'ratings.indicator'
            ]);

            // Get all active performance indicators with their categories
            $indicators = PerformanceIndicator::with('category')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->get()
                ->map(function ($indicator) use ($employeeReview) {
                    // Check if there's an existing rating for this indicator
                    $existingRating = $employeeReview->ratings->where('performance_indicator_id', $indicator->id)->first();

                    return [
                        'id' => $indicator->id,
                        'name' => $indicator->name,
                        'description' => $indicator->description,
                        'measurement_unit' => $indicator->measurement_unit,
                        'target_value' => $indicator->target_value,
                        'category' => $indicator->category ? $indicator->category->name : 'Uncategorized',
                        'weight' => 1, // Default weight since templates are removed
                        'rating' => $existingRating ? $existingRating->rating : null,
                        'comments' => $existingRating ? $existingRating->comments : null,
                    ];
                });

            return Inertia::render('hr/performance/employee-reviews/conduct', [
                'review' => $employeeReview,
                'indicators' => $indicators,
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Submit the review ratings.
     */
    public function submitRatings(Request $request, EmployeeReview $employeeReview)
    {
        if (Auth::user()->can('edit-employee-reviews')) {
            // Check if review belongs to current company
            if (!in_array($employeeReview->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this review'));
            }

        $validator = Validator::make($request->all(), [
            'ratings' => 'required|array',
            'ratings.*.indicator_id' => 'required|exists:performance_indicators,id',
            'ratings.*.rating' => 'required|numeric|min:1|max:5',
            'ratings.*.comments' => 'nullable|string',
            'overall_comments' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            // Delete existing ratings
            $employeeReview->ratings()->delete();

            // Create new ratings
            $totalRating = 0;
            $ratingCount = 0;

            foreach ($request->ratings as $ratingData) {
                // Verify indicator belongs to current company
                $indicator = PerformanceIndicator::where('id', $ratingData['indicator_id'])
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->first();

                if (!$indicator) {
                    continue;
                }

                $totalRating += $ratingData['rating'];
                $ratingCount++;

                // Create the rating
                EmployeeReviewRating::create([
                    'employee_review_id' => $employeeReview->id,
                    'performance_indicator_id' => $ratingData['indicator_id'],
                    'rating' => $ratingData['rating'],
                    'comments' => $ratingData['comments'] ?? null,
                ]);
            }

            // Calculate overall rating
            $overallRating = $ratingCount > 0 ? round($totalRating / $ratingCount, 1) : null;

            // Update the review
            $employeeReview->update([
                'overall_rating' => $overallRating,
                'comments' => $request->overall_comments,
                'status' => 'completed',
                'completion_date' => now(),
            ]);

            DB::commit();

            return redirect()->route('hr.performance.employee-reviews.show', $employeeReview->id)
                ->with('success', __('Review completed successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('An error occurred while submitting the review: :message', ['message' => $e->getMessage()]));
        }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeReview $employeeReview)
    {
        if (Auth::user()->can('edit-employee-reviews')) {
            // Check if review belongs to current company
            if (!in_array($employeeReview->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this review'));
            }

        // Only allow updates if the review is not completed
        if ($employeeReview->status === 'completed') {
            return redirect()->back()->with('error', __('Cannot update a completed review'));
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'reviewer_id' => 'required|exists:users,id',
            'review_cycle_id' => 'required|exists:review_cycles,id',
            'review_date' => 'required|date',
            'status' => 'nullable|string|in:scheduled,in_progress,completed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Verify employee belongs to current company
        $employee = User::find($request->employee_id);
        if (!$employee || !in_array($employee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'Invalid employee selected')->withInput();
        }

        // Verify reviewer belongs to current company
        $reviewer = User::find($request->reviewer_id);
        if (!$reviewer || !in_array($reviewer->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'Invalid reviewer selected')->withInput();
        }

        // Verify review cycle belongs to current company
        $reviewCycle = ReviewCycle::find($request->review_cycle_id);
        if (!$reviewCycle || !in_array($reviewCycle->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'Invalid review cycle selected')->withInput();
        }

        // Update the review
        $employeeReview->update([
            'employee_id' => $request->employee_id,
            'reviewer_id' => $request->reviewer_id,
            'review_cycle_id' => $request->review_cycle_id,
            'review_date' => $request->review_date,
            'status' => $request->status ?? $employeeReview->status,
        ]);

        return redirect()->route('hr.performance.employee-reviews.index')->with('success', __('Employee review updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeReview $employeeReview)
    {
        if (Auth::user()->can('delete-employee-reviews')) {
            // Check if review belongs to current company
            if (!in_array($employeeReview->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to delete this review'));
            }

            // Only allow deletion if the review is not completed
            if ($employeeReview->status === 'completed') {
                return redirect()->back()->with('error', __('Cannot delete a completed review'));
            }

            // Delete the review (this will also delete the ratings due to cascade)
            $employeeReview->delete();

            return redirect()->back()->with('success', __('Employee review deleted successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, EmployeeReview $employeeReview)
    {
        if (Auth::user()->can('edit-employee-reviews')) {
            // Check if review belongs to current company
            if (!in_array($employeeReview->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this review'));
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:scheduled,in_progress,completed',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Update the status
            $employeeReview->update([
                'status' => $request->status,
                // If status is completed, set completion date
                'completion_date' => $request->status === 'completed' ? now() : $employeeReview->completion_date,
            ]);

            return redirect()->back()->with('success', __('Review status updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
