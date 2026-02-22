<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Meeting;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->type === 'employee') {
            if (!$user->hasPermissionTo('view-calendar')) {
                abort(403, 'Unauthorized');
            }
        } else {
            if (!$user->hasPermissionTo('manage-calendar') && !$user->hasPermissionTo('view-calendar')) {
                abort(403, 'Unauthorized');
            }
        }

        $companyUserIds =  getCompanyAndUsersId();


        // Get meetings
        $meetings = Meeting::query()
            ->when($user->hasRole('employee'), function ($query) use ($user) {
                $query->where('organizer_id', $user->id)
                      ->orWhereHas('attendees', function ($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            }, function ($query) use ($companyUserIds) {
                $query->whereIn('created_by', $companyUserIds);
            })
            ->get()
            ->map(function ($meeting) {
                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'start' => Carbon::parse($meeting->meeting_date)->format('Y-m-d') . 'T' . Carbon::parse($meeting->start_time)->format('H:i:s'),
                    'end' => Carbon::parse($meeting->meeting_date)->format('Y-m-d') . 'T' . Carbon::parse($meeting->end_time)->format('H:i:s'),
                    'type' => 'meeting',
                    'status' => $meeting->status,
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#3b82f6'
                ];
            });

        // Get holidays
        $holidays = Holiday::whereIn('created_by', $companyUserIds)
            ->get()
            ->map(function ($holiday) {
                return [
                    'id' => $holiday->id,
                    'title' => $holiday->name,
                    'start' => $holiday->start_date,
                    'end' => $holiday->end_date ?: $holiday->start_date,
                    'type' => 'holiday',
                    'allDay' => true,
                    'backgroundColor' => '#10b77f',
                    'borderColor' => '#10b77f'
                ];
            });

        // Get leave applications
        $leaves = LeaveApplication::whereIn('created_by', $companyUserIds)
            ->where('status', 'approved')
            ->with(['employee', 'leaveType'])
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'title' => $leave->employee->name . ' - ' . $leave->leaveType->name,
                    'start' => $leave->start_date,
                    'end' => Carbon::parse($leave->end_date)->addDay()->format('Y-m-d'),
                    'type' => 'leave',
                    'allDay' => true,
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b'
                ];
            });

        $events = $meetings->concat($holidays)->concat($leaves);

        return Inertia::render('calendar/index', [
            'events' => $events,
            'canManage' => $user->hasPermissionTo('manage-calendar')
        ]);
    }
}
