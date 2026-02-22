<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-branches')) {
            $query = Branch::where(function ($q) {
                if (Auth::user()->can('manage-any-branches')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-branches')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            $branches = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/branches/index', [
                'branches' => $branches,
                'filters' => $request->all(['search', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create-branches')) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'address' => 'nullable|string',
                    'city' => 'nullable|string|max:100',
                    'state' => 'nullable|string|max:100',
                    'country' => 'nullable|string|max:100',
                    'zip_code' => 'nullable|string|max:20',
                    'phone' => 'nullable|string|max:20',
                    'email' => 'nullable|email|max:255',
                    'status' => 'nullable|in:active,inactive',
                ]);

                $validated['created_by'] = creatorId();
                $validated['status'] = $validated['status'] ?? 'active';

                // Check if branch with same name already exists
                $exists = Branch::where('name', $validated['name'])
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->exists();

                if ($exists) {
                    return redirect()->back()->with('error', __('Branch with this name already exists.'));
                }

                Branch::create($validated);

                return redirect()->back()->with('success', __('Branch created successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to create branch'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function update(Request $request, $branchId)
    {
        if (Auth::user()->can('edit-branches')) {
            $branch = Branch::where('id', $branchId)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();

            if ($branch) {
                try {
                    $validated = $request->validate([
                        'name' => 'required|string|max:255',
                        'address' => 'nullable|string',
                        'city' => 'nullable|string|max:100',
                        'state' => 'nullable|string|max:100',
                        'country' => 'nullable|string|max:100',
                        'zip_code' => 'nullable|string|max:20',
                        'phone' => 'nullable|string|max:20',
                        'email' => 'nullable|email|max:255',
                        'status' => 'nullable|in:active,inactive',
                    ]);

                    // Check if branch with same name already exists (excluding current branch)
                    $exists = Branch::where('name', $validated['name'])
                        ->whereIn('created_by', getCompanyAndUsersId())
                        ->where('id', '!=', $branchId)
                        ->exists();

                    if ($exists) {
                        return redirect()->back()->with('error', __('Branch with this name already exists.'));
                    }

                    $branch->update($validated);

                    return redirect()->back()->with('success', __('Branch updated successfully'));
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update branch'));
                }
            } else {
                return redirect()->back()->with('error', __('Branch not found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function destroy($branchId)
    {
        if (Auth::user()->can('delete-branches')) {
            $branch = Branch::where('id', $branchId)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();

            if ($branch) {
                try {
                    // Check if branch has departments
                    if (class_exists('App\\Models\\Department')) {
                        $departmentCount = \App\Models\Department::where('branch_id', $branchId)->count();
                        if ($departmentCount > 0) {
                            return redirect()->back()->with('error', __('Cannot delete branch with assigned departments'));
                        }
                    }

                    $branch->delete();
                    return redirect()->back()->with('success', __('Branch deleted successfully'));
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete branch'));
                }
            } else {
                return redirect()->back()->with('error', __('Branch not found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function toggleStatus($branchId)
    {
        if (Auth::user()->can('toggle-status-branches')) {
            $branch = Branch::where('id', $branchId)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();

            if ($branch) {
                try {
                    $branch->status = $branch->status === 'active' ? 'inactive' : 'active';
                    $branch->save();

                    return redirect()->back()->with('success', __('Branch status updated successfully'));
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update branch status'));
                }
            } else {
                return redirect()->back()->with('error', __('Branch not found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
