<?php

namespace App\Http\Controllers;

use App\Models\ReviewCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ReviewCycleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-review-cycles')) {
            $query = ReviewCycle::where(function ($q) {
                if (Auth::user()->can('manage-any-review-cycles')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-review-cycles')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('frequency', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle frequency filter
            if ($request->has('frequency') && !empty($request->frequency) && $request->frequency !== 'all') {
                $query->where('frequency', $request->frequency);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $reviewCycles = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/performance/review-cycles/index', [
                'reviewCycles' => $reviewCycles,
                'filters' => $request->all(['search', 'status', 'frequency', 'sort_field', 'sort_direction', 'per_page']),
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
        if (Auth::user()->can('create-review-cycles')) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'frequency' => 'required|string|max:50',
                'description' => 'nullable|string',
                'status' => 'nullable|string|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            ReviewCycle::create([
                'name' => $request->name,
                'frequency' => $request->frequency,
                'description' => $request->description,
                'status' => $request->status ?? 'active',
                'created_by' => creatorId(),
            ]);

            return redirect()->back()->with('success', __('Review cycle created successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReviewCycle $reviewCycle)
    {
        if (Auth::user()->can('edit-review-cycles')) {
            // Check if review cycle belongs to current company
            if (!in_array($reviewCycle->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this review cycle'));
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'frequency' => 'required|string|max:50',
                'description' => 'nullable|string',
                'status' => 'nullable|string|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $reviewCycle->update([
                'name' => $request->name,
                'frequency' => $request->frequency,
                'description' => $request->description,
                'status' => $request->status ?? 'active',
            ]);

            return redirect()->back()->with('success', __('Review cycle updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReviewCycle $reviewCycle)
    {
        if (Auth::user()->can('delete-review-cycles')) {
            // Check if review cycle belongs to current company
            if (!in_array($reviewCycle->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to delete this review cycle'));
            }

            // Check if review cycle is being used in reviews
            if ($reviewCycle->reviews()->count() > 0) {
                return redirect()->back()->with('error', __('Cannot delete review cycle as it has associated reviews'));
            }

            $reviewCycle->delete();

            return redirect()->back()->with('success', __('Review cycle deleted successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(ReviewCycle $reviewCycle)
    {
        if (Auth::user()->can('edit-review-cycles')) {
            // Check if review cycle belongs to current company
            if (!in_array($reviewCycle->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this review cycle'));
            }

            $reviewCycle->update([
                'status' => $reviewCycle->status === 'active' ? 'inactive' : 'active',
            ]);

            return redirect()->back()->with('success', __('Review cycle status updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
