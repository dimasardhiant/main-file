<?php

namespace App\Http\Controllers;

use App\Models\GoalType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class GoalTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-goal-types')) {
            $query = GoalType::where(function ($q) {
                if (Auth::user()->can('manage-any-goal-types')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-goal-types')) {
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
                $query->orderBy('id', 'desc');
            }

            $goalTypes = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/performance/goal-types/index', [
                'goalTypes' => $goalTypes,
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
        if (Auth::user()->can('create-goal-types')) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|string|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            GoalType::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? 'active',
                'created_by' => creatorId(),
            ]);

            return redirect()->back()->with('success', 'Goal type created successfully');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GoalType $goalType)
    {
        if (Auth::user()->can('edit-goal-types')) {
            // Check if goal type belongs to current company
            if (!in_array($goalType->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', 'You do not have permission to update this goal type');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|string|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $goalType->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? 'active',
            ]);

            return redirect()->back()->with('success', 'Goal type updated successfully');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GoalType $goalType)
    {
        if (Auth::user()->can('delete-goal-types')) {
            // Check if goal type belongs to current company
            if (!in_array($goalType->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', 'You do not have permission to delete this goal type');
            }

            // Check if goal type is being used in goals
            if ($goalType->goals()->count() > 0) {
                return redirect()->back()->with('error', 'Cannot delete goal type as it is being used in employee goals');
            }

            $goalType->delete();

            return redirect()->back()->with('success', 'Goal type deleted successfully');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(GoalType $goalType)
    {
        if (Auth::user()->can('edit-goal-types')) {
            // Check if goal type belongs to current company
            if (!in_array($goalType->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', 'You do not have permission to update this goal type');
            }

            $goalType->update([
                'status' => $goalType->status === 'active' ? 'inactive' : 'active',
            ]);

            return redirect()->back()->with('success', 'Goal type status updated successfully');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
