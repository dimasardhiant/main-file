<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MeetingAttendee;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class MeetingAttendeeController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-meeting-attendees')) {
            $query = MeetingAttendee::with(['meeting.type', 'user'])->where(function ($q) {
                if (Auth::user()->can('manage-any-meeting-attendees')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-meeting-attendees')) {
                    $q->where('created_by', Auth::id())->orWhere('user_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%');
                })->orWhereHas('meeting', function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('rsvp_status') && !empty($request->rsvp_status) && $request->rsvp_status !== 'all') {
                $query->where('rsvp_status', $request->rsvp_status);
            }

            if ($request->has('attendance_status') && !empty($request->attendance_status) && $request->attendance_status !== 'all') {
                $query->where('attendance_status', $request->attendance_status);
            }

            if ($request->has('meeting_id') && !empty($request->meeting_id) && $request->meeting_id !== 'all') {
                $query->where('meeting_id', $request->meeting_id);
            }

            $query->orderBy('id', 'desc');
            $meetingAttendees = $query->paginate($request->per_page ?? 10);

            $meetings = Meeting::whereIn('created_by', getCompanyAndUsersId())
                ->select('id', 'title', 'meeting_date')
                ->orderBy('meeting_date', 'desc')
                ->get();

            $employees = User::whereIn('created_by', getCompanyAndUsersId())
                ->where('type', 'employee')
                ->select('id', 'name')
                ->get();

            return Inertia::render('meetings/meeting-attendees/index', [
                'meetingAttendees' => $meetingAttendees,
                'meetings' => $meetings,
                'employees' => $this->getFilteredEmployees(),
                'filters' => $request->all(['search', 'rsvp_status', 'attendance_status', 'meeting_id', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-meeting-attendees') && !Auth::user()->can('manage-any-meeting-attendees')) {
            $employeeQuery->where(function ($q) {
                $q->where('created_by', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        $employees = User::emp()
            ->with('employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee->employee_id ?? '',
                ];
            });
        return $employees;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meeting_id' => 'required|exists:meetings,id',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:Required,Optional',
            'rsvp_status' => 'nullable|in:Pending,Accepted,Declined,Tentative',
            'attendance_status' => 'nullable|in:Not Attended,Present,Late,Left Early',
            'decline_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if attendee already exists
        $exists = MeetingAttendee::where('meeting_id', $request->meeting_id)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', __('User is already added to this meeting'));
        }

        MeetingAttendee::create([
            'meeting_id' => $request->meeting_id,
            'user_id' => $request->user_id,
            'type' => $request->type,
            'rsvp_status' => $request->rsvp_status ?? 'Pending',
            'attendance_status' => $request->attendance_status ?? 'Not Attended',
            'rsvp_date' => $request->rsvp_status && $request->rsvp_status !== 'Pending' ? now() : null,
            'decline_reason' => $request->decline_reason,
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Meeting attendee added successfully'));
    }

    public function update(Request $request, MeetingAttendee $meetingAttendee)
    {
        if (!in_array($meetingAttendee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this attendee'));
        }

        $validator = Validator::make($request->all(), [
            'meeting_id' => 'required|exists:meetings,id',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:Required,Optional',
            'rsvp_status' => 'nullable|in:Pending,Accepted,Declined,Tentative',
            'attendance_status' => 'nullable|in:Not Attended,Present,Late,Left Early',
            'decline_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $meetingAttendee->update([
            'meeting_id' => $request->meeting_id,
            'user_id' => $request->user_id,
            'type' => $request->type,
            'rsvp_status' => $request->rsvp_status ?? 'Pending',
            'attendance_status' => $request->attendance_status ?? 'Not Attended',
            'rsvp_date' => $request->rsvp_status && $request->rsvp_status !== 'Pending' ? now() : null,
            'decline_reason' => $request->decline_reason,
        ]);

        return redirect()->back()->with('success', __('Meeting attendee updated successfully'));
    }

    public function destroy(MeetingAttendee $meetingAttendee)
    {
        if (!in_array($meetingAttendee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this attendee'));
        }

        $meetingAttendee->delete();
        return redirect()->back()->with('success', __('Meeting attendee removed successfully'));
    }

    public function updateRsvp(Request $request, MeetingAttendee $meetingAttendee)
    {
        if (!in_array($meetingAttendee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this RSVP'));
        }

        $validator = Validator::make($request->all(), [
            'rsvp_status' => 'required|in:Pending,Accepted,Declined,Tentative',
            'decline_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $meetingAttendee->update([
            'rsvp_status' => $request->rsvp_status,
            'rsvp_date' => now(),
            'decline_reason' => $request->decline_reason,
        ]);

        return redirect()->back()->with('success', __('RSVP updated successfully'));
    }

    public function updateAttendance(Request $request, MeetingAttendee $meetingAttendee)
    {
        if (!in_array($meetingAttendee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update attendance'));
        }

        $validator = Validator::make($request->all(), [
            'attendance_status' => 'required|in:Not Attended,Present,Late,Left Early',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $meetingAttendee->update([
            'attendance_status' => $request->attendance_status,
        ]);

        return redirect()->back()->with('success', __('Attendance updated successfully'));
    }

    public function updateMeetingRsvp(Request $request, MeetingAttendee $meetingAttendee)
    {
        if (!in_array($meetingAttendee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this RSVP'));
        }

        $validator = Validator::make($request->all(), [
            'rsvp_status' => 'required|in:Pending,Accepted,Declined,Tentative',
            'decline_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $meetingAttendee->update([
            'rsvp_status' => $request->rsvp_status,
            'rsvp_date' => now(),
            'decline_reason' => $request->decline_reason,
        ]);

        return redirect()->back()->with('success', __('RSVP updated successfully'));
    }

    public function updateMeetingAttendance(Request $request, MeetingAttendee $meetingAttendee)
    {
        if (!in_array($meetingAttendee->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update attendance'));
        }

        $validator = Validator::make($request->all(), [
            'attendance_status' => 'required|in:Not Attended,Present,Late,Left Early',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $meetingAttendee->update([
            'attendance_status' => $request->attendance_status,
        ]);

        return redirect()->back()->with('success', __('Attendance updated successfully'));
    }
}
