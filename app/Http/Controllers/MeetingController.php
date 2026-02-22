<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Meeting;
use App\Models\MeetingType;
use App\Models\MeetingRoom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MeetingController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-meetings')) {
            $query = Meeting::with(['type', 'room', 'organizer'])->where(function ($q) {
                if (Auth::user()->can('manage-any-meetings')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-meetings')) {
                    $q->where('created_by', Auth::id())->orWhere('organizer_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });


            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('type_id') && !empty($request->type_id) && $request->type_id !== 'all') {
                $query->where('type_id', $request->type_id);
            }

            if ($request->has('organizer_id') && !empty($request->organizer_id) && $request->organizer_id !== 'all') {
                $query->where('organizer_id', $request->organizer_id);
            }

            $query->orderBy('id', 'desc');
            $meetings = $query->paginate($request->per_page ?? 10);

            $meetingTypes = MeetingType::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name')
                ->get();

            $meetingRooms = MeetingRoom::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name', 'type')
                ->get();

            $employees = User::whereIn('created_by', getCompanyAndUsersId())
                ->where('type', 'employee')
                ->select('id', 'name')
                ->get();

            return Inertia::render('meetings/meetings/index', [
                'meetings' => $meetings,
                'meetingTypes' => $meetingTypes,
                'meetingRooms' => $meetingRooms,
                'employees' => $this->getFilteredEmployees(),
                'filters' => $request->all(['search', 'status', 'type_id', 'organizer_id', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-meetings') && !Auth::user()->can('manage-any-meetings')) {
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_id' => 'required|exists:meeting_types,id',
            'room_id' => 'nullable|exists:meeting_rooms,id',
            'meeting_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'agenda' => 'nullable|string',
            'recurrence' => 'required|in:None,Daily,Weekly,Monthly',
            'recurrence_end_date' => 'nullable|date|after:meeting_date',
            'organizer_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $startTime = Carbon::createFromFormat('H:i', $request->start_time);
        $endTime = Carbon::createFromFormat('H:i', $request->end_time);
        $duration = $endTime->diffInMinutes($startTime);

        $meetingData = [
            'title' => $request->title,
            'description' => $request->description,
            'type_id' => $request->type_id,
            'room_id' => $request->room_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration' => $duration,
            'agenda' => $request->agenda,
            'recurrence' => $request->recurrence,
            'recurrence_end_date' => $request->recurrence_end_date,
            'organizer_id' => $request->organizer_id,
            'created_by' => creatorId(),
        ];

        // Create meetings based on recurrence
        $this->createRecurringMeetings($meetingData, $request->meeting_date, $request->recurrence, $request->recurrence_end_date);

        return redirect()->back()->with('success', __('Meeting created successfully'));
    }

    public function update(Request $request, Meeting $meeting)
    {
        if (!in_array($meeting->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this meeting'));
        }

        // Convert time format if needed
        if ($request->start_time) {
            // Handle different time formats (HH:MM:SS to HH:MM)
            if (strlen($request->start_time) === 8) {
                $request->merge(['start_time' => substr($request->start_time, 0, 5)]);
            }
        }
        if ($request->end_time) {
            // Handle different time formats (HH:MM:SS to HH:MM)
            if (strlen($request->end_time) === 8) {
                $request->merge(['end_time' => substr($request->end_time, 0, 5)]);
            }
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_id' => 'required|exists:meeting_types,id',
            'room_id' => 'nullable|exists:meeting_rooms,id',
            'meeting_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'agenda' => 'nullable|string',
            'recurrence' => 'required|in:None,Daily,Weekly,Monthly',
            'recurrence_end_date' => 'nullable|date|after_or_equal:meeting_date',
            'organizer_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $startTime = Carbon::createFromFormat('H:i', $request->start_time);
        $endTime = Carbon::createFromFormat('H:i', $request->end_time);
        $duration = $endTime->diffInMinutes($startTime);

        $meeting->update([
            'title' => $request->title,
            'description' => $request->description,
            'type_id' => $request->type_id,
            'room_id' => $request->room_id,
            'meeting_date' => $request->meeting_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration' => $duration,
            'agenda' => $request->agenda,
            'recurrence' => $request->recurrence,
            'recurrence_end_date' => $request->recurrence_end_date,
            'organizer_id' => $request->organizer_id,
        ]);

        return redirect()->back()->with('success', __('Meeting updated successfully'));
    }

    public function destroy(Meeting $meeting)
    {
        if (!in_array($meeting->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this meeting'));
        }

        $meeting->delete();
        return redirect()->back()->with('success', __('Meeting deleted successfully'));
    }

    public function updateStatus(Request $request, Meeting $meeting)
    {
        if (!in_array($meeting->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this meeting'));
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Scheduled,In Progress,Completed,Cancelled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $meeting->update(['status' => $request->status]);
        return redirect()->back()->with('success', __('Meeting status updated successfully'));
    }

    public function updateMeetingStatus(Request $request, Meeting $meeting)
    {
        if (!in_array($meeting->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this meeting status'));
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Scheduled,In Progress,Completed,Cancelled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $meeting->update(['status' => $request->status]);
        return redirect()->back()->with('success', __('Meeting status updated successfully'));
    }

    private function createRecurringMeetings($meetingData, $startDate, $recurrence, $endDate)
    {
        $currentDate = Carbon::parse($startDate);
        $endDate = $endDate ? Carbon::parse($endDate) : null;
        $meetings = [];

        // Create first meeting
        $meetingData['meeting_date'] = $currentDate->format('Y-m-d');
        $meetings[] = Meeting::create($meetingData);

        // Create recurring meetings if not 'None'
        if ($recurrence !== 'None' && $endDate) {
            while ($currentDate->lt($endDate)) {
                switch ($recurrence) {
                    case 'Daily':
                        $currentDate->addDay();
                        break;
                    case 'Weekly':
                        $currentDate->addWeek();
                        break;
                    case 'Monthly':
                        $currentDate->addMonth();
                        break;
                }

                if ($currentDate->lte($endDate)) {
                    $meetingData['meeting_date'] = $currentDate->format('Y-m-d');
                    $meetings[] = Meeting::create($meetingData);
                }
            }
        }

        return $meetings;
    }
}
