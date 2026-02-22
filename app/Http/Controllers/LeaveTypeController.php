<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-leave-types')) {
            $query = LeaveType::with(['creator'])->where(function ($q) {
                if (Auth::user()->can('manage-any-leave-types')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-leave-types')) {
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

            $leaveTypes = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/leave-types/index', [
                'leaveTypes' => $leaveTypes,
                'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_days_per_year' => 'required|integer|min:0',
            'is_paid' => 'boolean',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['created_by'] = creatorId();
        $validated['status'] = $validated['status'] ?? 'active';

        // Check if leave type with same name already exists
        $exists = LeaveType::where('name', $validated['name'])
            ->whereIn('created_by', getCompanyAndUsersId())
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', __('Leave type with this name already exists.'));
        }

        LeaveType::create($validated);

        return redirect()->back()->with('success', __('Leave type created successfully.'));
    }

    public function update(Request $request, $leaveTypeId)
    {
        $leaveType = LeaveType::where('id', $leaveTypeId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveType) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'max_days_per_year' => 'required|integer|min:0',
                    'is_paid' => 'boolean',
                    'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                    'status' => 'nullable|in:active,inactive',
                ]);

                // Check if leave type with same name already exists (excluding current)
                $exists = LeaveType::where('name', $validated['name'])
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->where('id', '!=', $leaveTypeId)
                    ->exists();

                if ($exists) {
                    return redirect()->back()->with('error', __('Leave type with this name already exists.'));
                }

                $leaveType->update($validated);

                return redirect()->back()->with('success', __('Leave type updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update leave type'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave type Not Found.'));
        }
    }

    public function destroy($leaveTypeId)
    {
        $leaveType = LeaveType::where('id', $leaveTypeId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveType) {
            try {
                $leaveType->delete();
                return redirect()->back()->with('success', __('Leave type deleted successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete leave type'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave type Not Found.'));
        }
    }

    public function toggleStatus($leaveTypeId)
    {
        $leaveType = LeaveType::where('id', $leaveTypeId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveType) {
            try {
                $leaveType->status = $leaveType->status === 'active' ? 'inactive' : 'active';
                $leaveType->save();

                return redirect()->back()->with('success', __('Leave type status updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update leave type status'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave type Not Found.'));
        }
    }
}
