<?php

namespace App\Http\Controllers;

use App\Models\MeetingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class MeetingTypeController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-meeting-types')) {
            $query = MeetingType::withCount('meetings')->where(function ($q) {
                if (Auth::user()->can('manage-any-meeting-types')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-meeting-types')) {
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

            $query->orderBy('id', 'desc');
            $meetingTypes = $query->paginate($request->per_page ?? 10);

            return Inertia::render('meetings/meeting-types/index', [
                'meetingTypes' => $meetingTypes,
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
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'default_duration' => 'required|integer|min:15|max:480',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        MeetingType::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'default_duration' => $request->default_duration,
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Meeting type created successfully'));
    }

    public function update(Request $request, MeetingType $meetingType)
    {
        if (!in_array($meetingType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this meeting type'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'default_duration' => 'required|integer|min:15|max:480',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $meetingType->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'default_duration' => $request->default_duration,
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Meeting type updated successfully'));
    }

    public function destroy(MeetingType $meetingType)
    {
        if (!in_array($meetingType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this meeting type'));
        }

        if ($meetingType->meetings()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete meeting type as it is being used in meetings'));
        }

        $meetingType->delete();
        return redirect()->back()->with('success', __('Meeting type deleted successfully'));
    }

    public function toggleStatus(MeetingType $meetingType)
    {
        if (!in_array($meetingType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this meeting type'));
        }

        $meetingType->update([
            'status' => $meetingType->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Meeting type status updated successfully'));
    }
}
