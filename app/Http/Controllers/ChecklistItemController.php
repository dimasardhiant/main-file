<?php

namespace App\Http\Controllers;

use App\Models\ChecklistItem;
use App\Models\OnboardingChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ChecklistItemController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-checklist-items')) {
            $query = ChecklistItem::with(['checklist'])->where(function ($q) {
                if (Auth::user()->can('manage-any-checklist-items')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-checklist-items')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('task_name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('category') && !empty($request->category) && $request->category !== 'all') {
                $query->where('category', $request->category);
            }

            if ($request->has('checklist_id') && !empty($request->checklist_id) && $request->checklist_id !== 'all') {
                $query->where('checklist_id', $request->checklist_id);
            }

            if ($request->has('is_required') && $request->is_required !== 'all') {
                $query->where('is_required', $request->is_required === 'true');
            }

            $query->orderBy('id', 'desc');
            $checklistItems = $query->paginate($request->per_page ?? 10);

            $checklists = OnboardingChecklist::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name')
                ->get();

            return Inertia::render('hr/recruitment/checklist-items/index', [
                'checklistItems' => $checklistItems,
                'checklists' => $checklists,
                'filters' => $request->all(['search', 'category', 'checklist_id', 'is_required', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checklist_id' => 'required|exists:onboarding_checklists,id',
            'task_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:Documentation,IT Setup,Training,HR,Facilities,Other',
            'assigned_to_role' => 'nullable|string|max:255',
            'due_day' => 'nullable|integer|min:1',
            'is_required' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        ChecklistItem::create([
            'checklist_id' => $request->checklist_id,
            'task_name' => $request->task_name,
            'description' => $request->description,
            'category' => $request->category,
            'assigned_to_role' => $request->assigned_to_role,
            'due_day' => $request->due_day ?? 0,
            'is_required' => $request->boolean('is_required'),
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Checklist item created successfully'));
    }

    public function update(Request $request, ChecklistItem $checklistItem)
    {
        if (!in_array($checklistItem->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this item'));
        }

        $validator = Validator::make($request->all(), [
            'checklist_id' => 'required|exists:onboarding_checklists,id',
            'task_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:Documentation,IT Setup,Training,HR,Facilities,Other',
            'assigned_to_role' => 'nullable|string|max:255',
            'due_day' => 'nullable|integer|min:1',
            'is_required' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $checklistItem->update([
            'checklist_id' => $request->checklist_id,
            'task_name' => $request->task_name,
            'description' => $request->description,
            'category' => $request->category,
            'assigned_to_role' => $request->assigned_to_role,
            'due_day' => $request->due_day ?? 0,
            'is_required' => $request->boolean('is_required'),
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Checklist item updated successfully'));
    }

    public function destroy(ChecklistItem $checklistItem)
    {
        if (!in_array($checklistItem->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this item'));
        }

        $checklistItem->delete();
        return redirect()->back()->with('success', __('Checklist item deleted successfully'));
    }

    public function toggleStatus(ChecklistItem $checklistItem)
    {
        if (!in_array($checklistItem->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this item'));
        }

        $checklistItem->update([
            'status' => $checklistItem->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Item status updated successfully'));
    }
}
