<?php

namespace App\Http\Controllers;

use App\Models\PerformanceIndicator;
use App\Models\PerformanceIndicatorCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PerformanceIndicatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-performance-indicators')) {
            $query = PerformanceIndicator::with('category')->where(function ($q) {
                if (Auth::user()->can('manage-any-performance-indicators')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-performance-indicators')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%')
                        ->orWhere('measurement_unit', 'like', '%' . $request->search . '%')
                        ->orWhere('target_value', 'like', '%' . $request->search . '%');
                });
            }

            // Handle category filter
            if ($request->has('category_id') && !empty($request->category_id)) {
                $query->where('category_id', $request->category_id);
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

            $indicators = $query->paginate($request->per_page ?? 10);

            // Get categories for filter dropdown
            $categories = PerformanceIndicatorCategory::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']);

            return Inertia::render('hr/performance/indicators/index', [
                'indicators' => $indicators,
                'categories' => $categories,
                'filters' => $request->all(['search', 'category_id', 'status', 'sort_field', 'sort_direction', 'per_page']),
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
        if (Auth::user()->can('create-performance-indicators')) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:performance_indicator_categories,id',
                'description' => 'nullable|string',
                'measurement_unit' => 'nullable|string|max:50',
                'target_value' => 'nullable|string|max:50',
                'status' => 'nullable|string|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Verify category belongs to current company
            $category = PerformanceIndicatorCategory::find($request->category_id);
            if (!$category || !in_array($category->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', 'Invalid category selected')->withInput();
            }

            PerformanceIndicator::create([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'measurement_unit' => $request->measurement_unit,
                'target_value' => $request->target_value,
                'status' => $request->status ?? 'active',
                'created_by' => creatorId(),
            ]);

            return redirect()->back()->with('success', 'Performance indicator created successfully');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PerformanceIndicator $indicator)
    {
        if (Auth::user()->can('edit-performance-indicators')) {
            // Check if indicator belongs to current company
            if (!in_array($indicator->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this indicator'));
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:performance_indicator_categories,id',
                'description' => 'nullable|string',
                'measurement_unit' => 'nullable|string|max:50',
                'target_value' => 'nullable|string|max:50',
                'status' => 'nullable|string|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Verify category belongs to current company
            $category = PerformanceIndicatorCategory::find($request->category_id);
            if (!$category || !in_array($category->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', 'Invalid category selected')->withInput();
            }

            $indicator->update([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'measurement_unit' => $request->measurement_unit,
                'target_value' => $request->target_value,
                'status' => $request->status ?? 'active',
            ]);

            return redirect()->back()->with('success', 'Performance indicator updated successfully');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PerformanceIndicator $indicator)
    {
        if (Auth::user()->can('delete-performance-indicators')) {
            // Check if indicator belongs to current company
            if (!in_array($indicator->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', 'You do not have permission to delete this indicator');
            }

            $indicator->delete();

            return redirect()->back()->with('success', 'Performance indicator deleted successfully');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(PerformanceIndicator $indicator)
    {
        if (Auth::user()->can('edit-performance-indicators')) {
            // Check if indicator belongs to current company
            if (!in_array($indicator->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this indicator'));
            }

            $indicator->update([
                'status' => $indicator->status === 'active' ? 'inactive' : 'active',
            ]);

            return redirect()->back()->with('success', 'Performance indicator status updated successfully');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
