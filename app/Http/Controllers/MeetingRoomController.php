<?php

namespace App\Http\Controllers;

use App\Models\MeetingRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class MeetingRoomController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-meeting-rooms')) {
            $query = MeetingRoom::withCount('meetings')->where(function ($q) {
                if (Auth::user()->can('manage-any-meeting-rooms')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-meeting-rooms')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%')
                        ->orWhere('location', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('type') && !empty($request->type) && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $query->orderBy('id', 'desc');
            $meetingRooms = $query->paginate($request->per_page ?? 10);

            return Inertia::render('meetings/meeting-rooms/index', [
                'meetingRooms' => $meetingRooms,
                'filters' => $request->all(['search', 'type', 'status', 'per_page']),
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
            'type' => 'required|in:Physical,Virtual',
            'location' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1',
            'equipment' => 'nullable|array',
            'booking_url' => 'nullable',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        MeetingRoom::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'location' => $request->location,
            'capacity' => $request->capacity,
            'equipment' => $request->equipment,
            'booking_url' => $request->booking_url,
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Meeting room created successfully'));
    }

    public function update(Request $request, MeetingRoom $meetingRoom)
    {
        if (!in_array($meetingRoom->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this meeting room'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:Physical,Virtual',
            'location' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1',
            'equipment' => 'nullable|array',
            'booking_url' => 'nullable|url',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $meetingRoom->update([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'location' => $request->location,
            'capacity' => $request->capacity,
            'equipment' => $request->equipment,
            'booking_url' => $request->booking_url,
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Meeting room updated successfully'));
    }

    public function destroy(MeetingRoom $meetingRoom)
    {
        if (!in_array($meetingRoom->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this meeting room'));
        }

        if ($meetingRoom->meetings()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete meeting room as it is being used in meetings'));
        }

        $meetingRoom->delete();
        return redirect()->back()->with('success', __('Meeting room deleted successfully'));
    }

    public function toggleStatus(MeetingRoom $meetingRoom)
    {
        if (!in_array($meetingRoom->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this meeting room'));
        }

        $meetingRoom->update([
            'status' => $meetingRoom->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Meeting room status updated successfully'));
    }
}
