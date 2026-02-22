<?php

namespace App\Http\Controllers;

use App\Models\ContractType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ContractTypeController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-contract-types')) {
            $query = ContractType::withCount('contracts')->where(function ($q) {
                if (Auth::user()->can('manage-any-contract-types')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-contract-types')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('is_renewable') && $request->is_renewable !== 'all') {
                $query->where('is_renewable', $request->is_renewable === 'true');
            }

            $query->orderBy('created_at', 'desc');
            $contractTypes = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/contracts/contract-types/index', [
                'contractTypes' => $contractTypes,
                'filters' => $request->all(['search', 'status', 'is_renewable', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_duration_months' => 'nullable|integer|min:1|max:120',
            'probation_period_months' => 'required|integer|min:0|max:12',
            'notice_period_days' => 'required|integer|min:0|max:365',
            'is_renewable' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        ContractType::create([
            'name' => $request->name,
            'description' => $request->description,
            'default_duration_months' => $request->default_duration_months,
            'probation_period_months' => $request->probation_period_months,
            'notice_period_days' => $request->notice_period_days,
            'is_renewable' => $request->boolean('is_renewable'),
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Contract type created successfully'));
    }

    public function update(Request $request, ContractType $contractType)
    {
        if (!in_array($contractType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this contract type'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_duration_months' => 'nullable|integer|min:1|max:120',
            'probation_period_months' => 'required|integer|min:0|max:12',
            'notice_period_days' => 'required|integer|min:0|max:365',
            'is_renewable' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $contractType->update([
            'name' => $request->name,
            'description' => $request->description,
            'default_duration_months' => $request->default_duration_months,
            'probation_period_months' => $request->probation_period_months,
            'notice_period_days' => $request->notice_period_days,
            'is_renewable' => $request->boolean('is_renewable'),
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Contract type updated successfully'));
    }

    public function destroy(ContractType $contractType)
    {
        if (!in_array($contractType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this contract type'));
        }

        if ($contractType->contracts()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete contract type as it is being used in contracts'));
        }

        $contractType->delete();
        return redirect()->back()->with('success', __('Contract type deleted successfully'));
    }

    public function toggleStatus(ContractType $contractType)
    {
        if (!in_array($contractType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this contract type'));
        }

        $contractType->update([
            'status' => $contractType->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Contract type status updated successfully'));
    }
}
