<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ActionItemController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-action-items')) {
            $query = ActionItem::with(['meeting.type', 'assignee'])->where(function ($q) {
                if (Auth::user()->can('manage-any-action-items')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-action-items')) {
                    $q->where('created_by', Auth::id())->orWhere('assigned_to', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });


            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%')
                        ->orWhereHas('assignee', function ($aq) use ($request) {
                            $aq->where('name', 'like', '%' . $request->search . '%');
                        });
                });
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('priority') && !empty($request->priority) && $request->priority !== 'all') {
                $query->where('priority', $request->priority);
            }

            if ($request->has('assigned_to') && !empty($request->assigned_to) && $request->assigned_to !== 'all') {
                $query->where('assigned_to', $request->assigned_to);
            }

            if ($request->has('meeting_id') && !empty($request->meeting_id) && $request->meeting_id !== 'all') {
                $query->where('meeting_id', $request->meeting_id);
            }

            // Auto-update overdue items
            ActionItem::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', '!=', 'Completed')
                ->where('due_date', '<', Carbon::today())
                ->update(['status' => 'Overdue']);

            $query->orderBy('id', 'desc');
            $actionItems = $query->paginate($request->per_page ?? 10);

            $meetings = Meeting::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'title', 'meeting_date')
                ->orderBy('meeting_date', 'desc')
                ->get();

            $employees = User::whereIn('created_by', getCompanyAndUsersId())
                ->where('type', 'employee')
                ->select('id', 'name')
                ->get();

            return Inertia::render('meetings/action-items/index', [
                'actionItems' => $actionItems,
                'meetings' => $meetings,
                'employees' => $employees,
                'filters' => $request->all(['search', 'status', 'priority', 'assigned_to', 'meeting_id', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meeting_id' => 'required|exists:meetings,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'required|date|after_or_equal:today',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $status = 'Not Started';
        $progress = $request->progress_percentage ?? 0;

        if ($progress > 0 && $progress < 100) {
            $status = 'In Progress';
        } elseif ($progress == 100) {
            $status = 'Completed';
        }

        ActionItem::create([
            'meeting_id' => $request->meeting_id,
            'title' => $request->title,
            'description' => $request->description,
            'assigned_to' => $request->assigned_to,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'status' => $status,
            'progress_percentage' => $progress,
            'notes' => $request->notes,
            'completed_date' => $status === 'Completed' ? now() : null,
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Action item created successfully'));
    }

    public function update(Request $request, ActionItem $actionItem)
    {
        if (!in_array($actionItem->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this action item'));
        }

        $validator = Validator::make($request->all(), [
            'meeting_id' => 'required|exists:meetings,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'required|date',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $progress = $request->progress_percentage ?? $actionItem->progress_percentage;
        $status = $actionItem->status;
        $completedDate = $actionItem->completed_date;

        if ($progress == 0) {
            $status = 'Not Started';
            $completedDate = null;
        } elseif ($progress > 0 && $progress < 100) {
            $status = 'In Progress';
            $completedDate = null;
        } elseif ($progress == 100) {
            $status = 'Completed';
            $completedDate = $completedDate ?? now();
        }

        // Check if overdue
        if ($status !== 'Completed' && Carbon::parse($request->due_date) < Carbon::today()) {
            $status = 'Overdue';
        }

        $actionItem->update([
            'meeting_id' => $request->meeting_id,
            'title' => $request->title,
            'description' => $request->description,
            'assigned_to' => $request->assigned_to,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'status' => $status,
            'progress_percentage' => $progress,
            'notes' => $request->notes,
            'completed_date' => $completedDate,
        ]);

        return redirect()->back()->with('success', __('Action item updated successfully'));
    }

    public function destroy(ActionItem $actionItem)
    {
        if (!in_array($actionItem->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this action item'));
        }

        $actionItem->delete();
        return redirect()->back()->with('success', __('Action item deleted successfully'));
    }

    public function updateProgress(Request $request, ActionItem $actionItem)
    {
        if (!in_array($actionItem->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this action item'));
        }

        $validator = Validator::make($request->all(), [
            'progress_percentage' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $progress = $request->progress_percentage;
        $status = $actionItem->status;
        $completedDate = $actionItem->completed_date;

        if ($progress == 0) {
            $status = 'Not Started';
            $completedDate = null;
        } elseif ($progress > 0 && $progress < 100) {
            $status = 'In Progress';
            $completedDate = null;
        } elseif ($progress == 100) {
            $status = 'Completed';
            $completedDate = now();
        }

        $actionItem->update([
            'progress_percentage' => $progress,
            'status' => $status,
            'completed_date' => $completedDate,
            'notes' => $request->notes ?? $actionItem->notes,
        ]);

        return redirect()->back()->with('success', __('Progress updated successfully'));
    }
}
