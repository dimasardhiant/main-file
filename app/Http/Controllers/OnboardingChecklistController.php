<?php

namespace App\Http\Controllers;

use App\Models\OnboardingChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class OnboardingChecklistController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-onboarding-checklists')) {
            $query = OnboardingChecklist::withCount('checklistItems')->where(function ($q) {
                if (Auth::user()->can('manage-any-onboarding-checklists')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-onboarding-checklists')) {
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

            if ($request->has('is_default') && $request->is_default !== 'all') {
                $query->where('is_default', $request->is_default === 'true');
            }

            $query->orderBy('is_default', 'desc')->orderBy('id', 'desc');
            $onboardingChecklists = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/recruitment/onboarding-checklists/index', [
                'onboardingChecklists' => $onboardingChecklists,
                'filters' => $request->all(['search', 'status', 'is_default', 'per_page']),
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
            'is_default' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // If setting as default, unset other defaults
        if ($request->boolean('is_default')) {
            OnboardingChecklist::whereIn('created_by', getCompanyAndUsersId())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        OnboardingChecklist::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_default' => $request->boolean('is_default'),
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Onboarding checklist created successfully'));
    }

    public function update(Request $request, OnboardingChecklist $onboardingChecklist)
    {
        if (!in_array($onboardingChecklist->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this checklist'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // If setting as default, unset other defaults
        if ($request->boolean('is_default') && !$onboardingChecklist->is_default) {
            OnboardingChecklist::whereIn('created_by', getCompanyAndUsersId())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $onboardingChecklist->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_default' => $request->boolean('is_default'),
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Onboarding checklist updated successfully'));
    }

    public function destroy(OnboardingChecklist $onboardingChecklist)
    {
        if (!in_array($onboardingChecklist->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this checklist'));
        }

        if ($onboardingChecklist->candidateOnboardings()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete checklist as it is being used in onboarding processes'));
        }

        $onboardingChecklist->delete();
        return redirect()->back()->with('success', __('Onboarding checklist deleted successfully'));
    }

    public function toggleStatus(OnboardingChecklist $onboardingChecklist)
    {
        if (!in_array($onboardingChecklist->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this checklist'));
        }

        $onboardingChecklist->update([
            'status' => $onboardingChecklist->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Checklist status updated successfully'));
    }
}
