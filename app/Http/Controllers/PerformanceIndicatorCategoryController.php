<?php

namespace App\Http\Controllers;

use App\Models\PerformanceIndicatorCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PerformanceIndicatorCategoryController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-performance-indicator-categories')) {
            $query = PerformanceIndicatorCategory::where(function ($q) {
                if (Auth::user()->can('manage-any-performance-indicator-categories')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-performance-indicator-categories')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $categories = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/performance/indicator-categories/index', [
                'categories' => $categories,
                'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
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
        if (Auth::user()->can('create-performance-indicator-categories')) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|string|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            PerformanceIndicatorCategory::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? 'active',
                'created_by' => creatorId(),
            ]);

            return redirect()->back()->with('success', __('Performance indicator category created successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PerformanceIndicatorCategory $indicatorCategory)
    {
        if (Auth::user()->can('edit-performance-indicator-categories')) {
            // Check if category belongs to current company
            if (!in_array($indicatorCategory->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this category'));
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|string|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $indicatorCategory->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? 'active',
            ]);

            return redirect()->back()->with('success', __('Performance indicator category updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PerformanceIndicatorCategory $indicatorCategory)
    {
        if (Auth::user()->can('delete-performance-indicator-categories')) {
            // Check if category belongs to current company
            if (!in_array($indicatorCategory->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to delete this category'));
            }

            // Check if category is being used in indicators
            if ($indicatorCategory->indicators()->count() > 0) {
                return redirect()->back()->with('error', __('Cannot delete category as it is being used in indicators'));
            }

            $indicatorCategory->delete();

            return redirect()->back()->with('success', __('Performance indicator category deleted successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(PerformanceIndicatorCategory $indicatorCategory)
    {
        if (Auth::user()->can('edit-performance-indicator-categories')) {
            // Check if category belongs to current company
            if (!in_array($indicatorCategory->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this category'));
            }

            $indicatorCategory->update([
                'status' => $indicatorCategory->status === 'active' ? 'inactive' : 'active',
            ]);

            return redirect()->back()->with('success', __('Performance indicator category status updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
