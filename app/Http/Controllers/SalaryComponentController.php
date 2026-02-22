<?php

namespace App\Http\Controllers;

use App\Models\SalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SalaryComponentController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-salary-components')) {
            $query = SalaryComponent::with(['creator'])->where(function ($q) {
                if (Auth::user()->can('manage-any-salary-components')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-salary-components')) {
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

            // Handle type filter
            if ($request->has('type') && !empty($request->type) && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            // Handle calculation type filter
            if ($request->has('calculation_type') && !empty($request->calculation_type) && $request->calculation_type !== 'all') {
                $query->where('calculation_type', $request->calculation_type);
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

            $salaryComponents = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/salary-components/index', [
                'salaryComponents' => $salaryComponents,
                'filters' => $request->all(['search', 'type', 'calculation_type', 'status', 'sort_field', 'sort_direction', 'per_page']),
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
            'type' => 'required|in:earning,deduction',
            'calculation_type' => 'required|in:fixed,percentage',
            'default_amount' => 'required_if:calculation_type,fixed|nullable|numeric|min:0',
            'percentage_of_basic' => 'required_if:calculation_type,percentage|nullable|numeric|min:0|max:100',
            'is_taxable' => 'boolean',
            'is_mandatory' => 'boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['created_by'] = creatorId();
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['is_taxable'] = $validated['is_taxable'] ?? true;
        $validated['is_mandatory'] = $validated['is_mandatory'] ?? false;

        // Set default values based on calculation type
        if ($validated['calculation_type'] === 'fixed') {
            $validated['percentage_of_basic'] = null;
        } else {
            $validated['default_amount'] = 0;
        }

        // Check if component with same name already exists
        $exists = SalaryComponent::where('name', $validated['name'])
            ->whereIn('created_by', getCompanyAndUsersId())
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', __('Salary component with this name already exists.'));
        }

        SalaryComponent::create($validated);

        return redirect()->back()->with('success', __('Salary component created successfully.'));
    }

    public function update(Request $request, $salaryComponentId)
    {
        $salaryComponent = SalaryComponent::where('id', $salaryComponentId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($salaryComponent) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'type' => 'required|in:earning,deduction',
                    'calculation_type' => 'required|in:fixed,percentage',
                    'default_amount' => 'required_if:calculation_type,fixed|nullable|numeric|min:0',
                    'percentage_of_basic' => 'required_if:calculation_type,percentage|nullable|numeric|min:0|max:100',
                    'is_taxable' => 'boolean',
                    'is_mandatory' => 'boolean',
                    'status' => 'nullable|in:active,inactive',
                ]);

                // Set default values based on calculation type
                if ($validated['calculation_type'] === 'fixed') {
                    $validated['percentage_of_basic'] = null;
                } else {
                    $validated['default_amount'] = 0;
                }

                // Check if component with same name already exists (excluding current)
                $exists = SalaryComponent::where('name', $validated['name'])
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->where('id', '!=', $salaryComponentId)
                    ->exists();

                if ($exists) {
                    return redirect()->back()->with('error', __('Salary component with this name already exists.'));
                }

                $salaryComponent->update($validated);

                return redirect()->back()->with('success', __('Salary component updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update salary component'));
            }
        } else {
            return redirect()->back()->with('error', __('Salary component Not Found.'));
        }
    }

    public function destroy($salaryComponentId)
    {
        $salaryComponent = SalaryComponent::where('id', $salaryComponentId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($salaryComponent) {
            try {
                $salaryComponent->delete();
                return redirect()->back()->with('success', __('Salary component deleted successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete salary component'));
            }
        } else {
            return redirect()->back()->with('error', __('Salary component Not Found.'));
        }
    }

    public function toggleStatus($salaryComponentId)
    {
        $salaryComponent = SalaryComponent::where('id', $salaryComponentId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($salaryComponent) {
            try {
                $salaryComponent->status = $salaryComponent->status === 'active' ? 'inactive' : 'active';
                $salaryComponent->save();

                return redirect()->back()->with('success', __('Salary component status updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update salary component status'));
            }
        } else {
            return redirect()->back()->with('error', __('Salary component Not Found.'));
        }
    }
}
