<?php

namespace App\Http\Controllers;

use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\TrainingSessionAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TrainingSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TrainingSession::with(['trainingProgram', 'trainers'])
            ->withPermissionCheck();

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('location', 'like', '%' . $request->search . '%')
                    ->orWhere('notes', 'like', '%' . $request->search . '%')
                    ->orWhereHas('trainingProgram', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Handle program filter
        if ($request->has('training_program_id') && !empty($request->training_program_id)) {
            $query->where('training_program_id', $request->training_program_id);
        }

        // Handle status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Handle location type filter
        if ($request->has('location_type') && !empty($request->location_type)) {
            $query->where('location_type', $request->location_type);
        }

        // Handle date range filter
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('start_date', '<=', $request->date_to);
        }

        // Handle sorting
        if ($request->has('sort_field') && !empty($request->sort_field)) {
            $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
        } else {
            $query->orderBy('id', 'desc');
        }

        // Add attendance count and trainers count
        $query->withCount(['attendance', 'trainers']);

        $trainingSessions = $query->paginate($request->per_page ?? 10);

        // Get training programs for filter dropdown
        $trainingPrograms = TrainingProgram::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        // Get employees for trainer dropdown
        $employees = User::with('employee')
            ->where('type', 'employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => isset($user->employee) ?  $user->employee->employee_id  : '-'
                ];
            });

        return Inertia::render('hr/training/sessions/index', [
            'trainingSessions' => $trainingSessions,
            'trainingPrograms' => $trainingPrograms,
            'employees' => $employees,
            'filters' => $request->all(['search', 'training_program_id', 'status', 'location_type', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    /**
     * Display the calendar view.
     */
    public function calendar(Request $request)
    {
        $query = TrainingSession::with(['trainingProgram'])
            ->withPermissionCheck();

        // Handle program filter
        if ($request->has('training_program_id') && !empty($request->training_program_id)) {
            $query->where('training_program_id', $request->training_program_id);
        }

        // Handle status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Get all sessions for calendar
        $trainingSessions = $query->get();

        // Format sessions for calendar
        $calendarEvents = $trainingSessions->map(function ($session) {
            $statusColors = [
                'scheduled' => '#3788d8',
                'in_progress' => '#f59e0b',
                'completed' => '#10b77f',
                'cancelled' => '#ef4444',
            ];

            return [
                'id' => $session->id,
                'title' => $session->name ?? $session->trainingProgram->name,
                'start' => $session->start_date,
                'end' => $session->end_date,
                'backgroundColor' => $statusColors[$session->status] ?? '#6b7280',
                'borderColor' => $statusColors[$session->status] ?? '#6b7280',
                'url' => route('hr.training-sessions.show', $session->id),
                'extendedProps' => [
                    'program' => $session->trainingProgram->name,
                    'location' => $session->location,
                    'status' => $session->status,
                ],
            ];
        });

        // Get training programs for filter dropdown
        $trainingPrograms = TrainingProgram::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        return Inertia::render('hr/training/sessions/calendar', [
            'calendarEvents' => $calendarEvents,
            'trainingPrograms' => $trainingPrograms,
            'filters' => $request->all(['training_program_id', 'status']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'training_program_id' => 'required|exists:training_programs,id',
            'name' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'location_type' => 'required|string|in:physical,virtual',
            'meeting_link' => 'nullable|string|max:255|required_if:location_type,virtual',
            'status' => 'required|string|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'recurrence_pattern' => 'nullable|string|in:daily,weekly,monthly|required_if:is_recurring,true',
            'recurrence_count' => 'nullable|integer|min:1|required_if:is_recurring,true',
            'trainer_ids' => 'nullable|array',
            'trainer_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if training program belongs to current company
        $trainingProgram = TrainingProgram::find($request->training_program_id);
        if (!$trainingProgram || !in_array($trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'Invalid training program selected');
        }

        // Check if trainers belong to current company
        if (!empty($request->trainer_ids)) {
            $trainerIds = $request->trainer_ids;
            $validTrainers = User::whereIn('id', $trainerIds)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->pluck('id')
                ->toArray();

            if (count($validTrainers) !== count($trainerIds)) {
                return redirect()->back()->with('error', 'Invalid trainer selection');
            }
        }

        $sessionData = [
            'training_program_id' => $request->training_program_id,
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location' => $request->location,
            'location_type' => $request->location_type,
            'meeting_link' => $request->meeting_link,
            'status' => $request->status,
            'notes' => $request->notes,
            'is_recurring' => $request->is_recurring ?? false,
            'recurrence_pattern' => $request->recurrence_pattern,
            'recurrence_count' => $request->recurrence_count,
            'created_by' => creatorId(),
        ];

        $session = TrainingSession::create($sessionData);

        // Attach trainers if provided
        if (!empty($request->trainer_ids)) {
            $session->trainers()->attach($request->trainer_ids);
        }

        // Create recurring sessions if needed
        if ($request->is_recurring && $request->recurrence_count > 0) {
            $this->createRecurringSessions($session, $request->trainer_ids ?? []);
        }

        return redirect()->back()->with('success', __('Training session created successfully'));
    }

    /**
     * Display the specified resource.
     */
    public function show(TrainingSession $trainingSession)
    {
        // Check if training session belongs to current company
        if (!in_array($trainingSession->trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to view this training session'));
        }

        // Load relationships
        $trainingSession->load(['trainingProgram', 'trainers', 'attendance.employee']);

        // Format attendance data for all employees with attendance records
        $attendanceData = $trainingSession->attendance->map(function ($attendance) {
            return [
                'employee_id' => $attendance->employee_id,
                'name' => $attendance->employee->name,
                'employee_id_display' => $attendance->employee->employee->employee_id ?? '-',
                'is_present' => $attendance->is_present,
                'notes' => $attendance->notes,
            ];
        });

        return Inertia::render('hr/training/sessions/show', [
            'trainingSession' => $trainingSession,
            'attendanceData' => $attendanceData,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TrainingSession $trainingSession)
    {
        // Check if training session belongs to current company
        if (!in_array($trainingSession->trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this training session');
        }

        $validator = Validator::make($request->all(), [
            'training_program_id' => 'required|exists:training_programs,id',
            'name' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'location_type' => 'required|string|in:physical,virtual',
            'meeting_link' => 'nullable|string|max:255|required_if:location_type,virtual',
            'status' => 'required|string|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
            'trainer_ids' => 'nullable|array',
            'trainer_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if training program belongs to current company
        $trainingProgram = TrainingProgram::find($request->training_program_id);
        if (!$trainingProgram || !in_array($trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'Invalid training program selected');
        }

        // Check if trainers belong to current company
        if (!empty($request->trainer_ids)) {
            $trainerIds = $request->trainer_ids;
            $validTrainers = User::whereIn('id', $trainerIds)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->pluck('id')
                ->toArray();

            if (count($validTrainers) !== count($trainerIds)) {
                return redirect()->back()->with('error', 'Invalid trainer selection');
            }
        }

        $sessionData = [
            'training_program_id' => $request->training_program_id,
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location' => $request->location,
            'location_type' => $request->location_type,
            'meeting_link' => $request->meeting_link,
            'status' => $request->status,
            'notes' => $request->notes,
        ];

        $trainingSession->update($sessionData);

        // Sync trainers
        if (isset($request->trainer_ids)) {
            $trainingSession->trainers()->sync($request->trainer_ids);
        } else {
            $trainingSession->trainers()->detach();
        }

        return redirect()->back()->with('success', __('Training session updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TrainingSession $trainingSession)
    {
        // Check if training session belongs to current company
        if (!in_array($trainingSession->trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to delete this training session');
        }

        // Delete attendance records
        $trainingSession->attendance()->delete();
        
        // Detach trainers
        $trainingSession->trainers()->detach();
        
        // Delete the training session
        $trainingSession->delete();

        return redirect()->back()->with('success', __('Training session deleted successfully'));
    }

    /**
     * Update attendance for a training session.
     */
    public function updateAttendance(Request $request, TrainingSession $trainingSession)
    {
        // Check if training session belongs to current company
        if (!in_array($trainingSession->trainingProgram->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update attendance for this training session');
        }

        $validator = Validator::make($request->all(), [
            'attendance' => 'required|array',
            'attendance.*.employee_id' => 'required|exists:users,id',
            'attendance.*.is_present' => 'required|boolean',
            'attendance.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if employees belong to current company
        $employeeIds = collect($request->attendance)->pluck('employee_id')->toArray();
        $validEmployees = User::whereIn('id', $employeeIds)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->pluck('id')
            ->toArray();

        if (count($validEmployees) !== count($employeeIds)) {
            return redirect()->back()->with('error', 'Invalid employee selection');
        }

        // Delete existing attendance records
        $trainingSession->attendance()->delete();

        // Create new attendance records
        foreach ($request->attendance as $attendanceData) {
            TrainingSessionAttendance::create([
                'training_session_id' => $trainingSession->id,
                'employee_id' => $attendanceData['employee_id'],
                'is_present' => $attendanceData['is_present'],
                'notes' => $attendanceData['notes'],
            ]);
        }

        return redirect()->back()->with('success', __('Attendance updated successfully'));
    }

    /**
     * Create recurring sessions based on the pattern.
     */
    private function createRecurringSessions($originalSession, $trainerIds = [])
    {
        $startDate = $originalSession->start_date;
        $endDate = $originalSession->end_date;
        $duration = $startDate->diffInSeconds($endDate);
        
        for ($i = 1; $i <= $originalSession->recurrence_count; $i++) {
            // Calculate new dates based on recurrence pattern
            switch ($originalSession->recurrence_pattern) {
                case 'daily':
                    $newStartDate = $startDate->copy()->addDays($i);
                    break;
                case 'weekly':
                    $newStartDate = $startDate->copy()->addWeeks($i);
                    break;
                case 'monthly':
                    $newStartDate = $startDate->copy()->addMonths($i);
                    break;
                default:
                    continue 2; // Skip this iteration if pattern is invalid
            }
            
            $newEndDate = $newStartDate->copy()->addSeconds($duration);
            
            // Create new session
            $newSession = TrainingSession::create([
                'training_program_id' => $originalSession->training_program_id,
                'name' => $originalSession->name,
                'start_date' => $newStartDate,
                'end_date' => $newEndDate,
                'location' => $originalSession->location,
                'location_type' => $originalSession->location_type,
                'meeting_link' => $originalSession->meeting_link,
                'status' => 'scheduled',
                'notes' => $originalSession->notes,
                'is_recurring' => false, // Child sessions are not recurring
                'created_by' => $originalSession->created_by,
            ]);
            
            // Attach trainers
            if (!empty($trainerIds)) {
                $newSession->trainers()->attach($trainerIds);
            }
        }
    }
}