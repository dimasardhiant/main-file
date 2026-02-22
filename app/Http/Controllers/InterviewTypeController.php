<?php

namespace App\Http\Controllers;

use App\Models\InterviewType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class InterviewTypeController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-interview-types')) {
            $query = InterviewType::where(function ($q) {
                if (Auth::user()->can('manage-any-interview-types')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-interview-types')) {
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

            $query->orderBy('created_at', 'desc');
            $interviewTypes = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/recruitment/interview-types/index', [
                'interviewTypes' => $interviewTypes,
                'filters' => $request->all(['search', 'status', 'per_page']),
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
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        InterviewType::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Interview type created successfully'));
    }

    public function update(Request $request, InterviewType $interviewType)
    {
        if (!in_array($interviewType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this interview type'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $interviewType->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Interview type updated successfully'));
    }

    public function destroy(InterviewType $interviewType)
    {
        if (!in_array($interviewType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this interview type'));
        }

        if ($interviewType->interviews()->count() > 0) {
            return redirect()->back()->with('error', _('Cannot delete interview type as it is being used in interviews'));
        }

        $interviewType->delete();
        return redirect()->back()->with('success', __('Interview type deleted successfully'));
    }

    public function toggleStatus(InterviewType $interviewType)
    {
        if (!in_array($interviewType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this interview type'));
        }

        $interviewType->update([
            'status' => $interviewType->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Interview type status updated successfully'));
    }
}
