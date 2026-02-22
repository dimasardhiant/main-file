<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\MeetingType;
use App\Models\MeetingRoom;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MeetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }

        // Fixed meeting data for consistent results
        $meetingData = [
            [
                'title' => 'Weekly Team Standup',
                'description' => 'Weekly team standup meeting to discuss progress, blockers, and upcoming tasks',
                'meeting_type' => 'Team Meeting',
                'room_name' => 'Conference Room A',
                'days_from_now' => 1,
                'start_time' => '09:00:00',
                'duration' => 60,
                'agenda' => 'Progress updates from team members, Discussion of current blockers, Planning for upcoming week, Q&A session',
                'status' => 'Completed',
                'recurrence' => 'Weekly',
                'recurrence_end_days' => 90
            ],
            [
                'title' => 'Client Presentation - Q4 Results',
                'description' => 'Quarterly business review presentation for key client stakeholders',
                'meeting_type' => 'Client Meeting',
                'room_name' => 'Boardroom',
                'days_from_now' => 3,
                'start_time' => '14:00:00',
                'duration' => 120,
                'agenda' => 'Q4 performance overview, Key achievements and milestones, Challenges and solutions, Q1 roadmap presentation',
                'status' => 'Completed',
                'recurrence' => 'None',
                'recurrence_end_days' => null
            ],
            [
                'title' => 'Performance Review Session',
                'description' => 'Monthly performance review meetings with team members',
                'meeting_type' => 'Performance Review',
                'room_name' => 'Meeting Room B',
                'days_from_now' => 5,
                'start_time' => '11:00:00',
                'duration' => 60,
                'agenda' => 'Review of monthly objectives, Performance assessment, Career development discussion, Goal setting',
                'status' => 'Scheduled',
                'recurrence' => 'Monthly',
                'recurrence_end_days' => 180
            ],
            [
                'title' => 'Daily Scrum Meeting',
                'description' => 'Daily scrum meeting for agile development team',
                'meeting_type' => 'Team Meeting',
                'room_name' => 'Huddle Space 1',
                'days_from_now' => 0,
                'start_time' => '10:00:00',
                'duration' => 30,
                'agenda' => 'Yesterday progress, Today plans, Blockers discussion',
                'status' => 'Scheduled',
                'recurrence' => 'Daily',
                'recurrence_end_days' => 30
            ],
            [
                'title' => 'Project Alpha Review',
                'description' => 'Monthly review meeting for Project Alpha to assess progress and next steps',
                'meeting_type' => 'Project Review',
                'room_name' => 'Conference Room A',
                'days_from_now' => 7,
                'start_time' => '15:30:00',
                'duration' => 90,
                'agenda' => 'Project milestone review, Budget and timeline assessment, Risk analysis, Next phase planning',
                'status' => 'Scheduled',
                'recurrence' => 'Monthly',
                'recurrence_end_days' => 180
            ],
            [
                'title' => 'All Hands Company Meeting',
                'description' => 'Quarterly all-hands meeting for company updates and announcements',
                'meeting_type' => 'All Hands',
                'room_name' => 'Webinar Room',
                'days_from_now' => 10,
                'start_time' => '16:00:00',
                'duration' => 90,
                'agenda' => 'CEO address and company updates, Financial performance overview, New initiatives and projects, Employee recognition',
                'status' => 'Scheduled',
                'recurrence' => 'None',
                'recurrence_end_days' => null
            ]
        ];

        foreach ($companies as $company) {
            // Get meeting types for this company
            $meetingTypes = MeetingType::where('created_by', $company->id)->get();

            if ($meetingTypes->isEmpty()) {
                $this->command->warn('No meeting types found for company: ' . $company->name . '. Please run MeetingTypeSeeder first.');
                continue;
            }

            // Get meeting rooms for this company
            $meetingRooms = MeetingRoom::where('created_by', $company->id)->get();

            if ($meetingRooms->isEmpty()) {
                $this->command->warn('No meeting rooms found for company: ' . $company->name . '. Please run MeetingRoomSeeder first.');
                continue;
            }

            // Get employees for organizers
            $employees = User::whereIn('type', ['manager', 'hr', 'employee'])
                ->where('created_by', $company->id)
                ->get();

            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name);
                continue;
            }

            foreach ($meetingData as $index => $meeting) {
                // Find matching meeting type
                $meetingType = $meetingTypes->where('name', $meeting['meeting_type'])->first();
                if (!$meetingType) $meetingType = $meetingTypes->first();

                // Find matching meeting room
                $meetingRoom = $meetingRooms->where('name', $meeting['room_name'])->first();
                if (!$meetingRoom) $meetingRoom = $meetingRooms->first();

                // Select organizer from first 5 employees
                $selectedEmployees = $employees->take(5);
                $organizer = $selectedEmployees->skip($index % 5)->first() ?: $selectedEmployees->first();

                $startDate = Carbon::now()->addDays($meeting['days_from_now']);
                $endTime = Carbon::createFromFormat('H:i:s', $meeting['start_time'])->addMinutes($meeting['duration'])->format('H:i:s');

                // Create recurring meetings
                $this->createRecurringMeetings(
                    $meeting,
                    $meetingType,
                    $meetingRoom,
                    $organizer,
                    $company,
                    $startDate,
                    $endTime
                );
            }
        }

        $this->command->info('Meeting seeder completed successfully!');
    }

    /**
     * Create recurring meetings based on recurrence pattern
     */
    private function createRecurringMeetings($meeting, $meetingType, $meetingRoom, $organizer, $company, $startDate, $endTime)
    {
        $currentDate = $startDate->copy();
        $recurrenceEndDate = $meeting['recurrence_end_days'] ?
            $startDate->copy()->addDays($meeting['recurrence_end_days']) :
            $startDate->copy()->addDays(30); // Default 30 days if no end date
            

        $meetingCount = 0;
        $maxMeetings = 50; // Safety limit to prevent infinite loops

        while ($currentDate->lte($recurrenceEndDate) && $meetingCount < $maxMeetings) {
            try {
                Meeting::create([
                    'title' => $meeting['title'],
                    'description' => $meeting['description'],
                    'type_id' => $meetingType->id,
                    'room_id' => $meetingRoom->id,
                    'meeting_date' => $currentDate->format('Y-m-d'),
                    'start_time' => $meeting['start_time'],
                    'end_time' => $endTime,
                    'duration' => $meeting['duration'],
                    'agenda' => $meeting['agenda'],
                    'status' => $currentDate->isPast() ? 'Completed' : $meeting['status'],
                    'recurrence' => $meeting['recurrence'],
                    'recurrence_end_date' => $meeting['recurrence_end_days'] ? $recurrenceEndDate->format('Y-m-d') : null,
                    'organizer_id' => $organizer->id,
                    'created_by' => $company->id,
                ]);

                $meetingCount++;
            } catch (\Exception $e) {
                $this->command->error('Failed to create meeting: ' . $meeting['title'] . ' on ' . $currentDate->format('Y-m-d') . ' for company: ' . $company->name);
            }

            // Calculate next occurrence based on recurrence pattern
            switch ($meeting['recurrence']) {
                case 'Daily':
                    $currentDate->addDay();
                    break;
                case 'Weekly':
                    $currentDate->addWeek();
                    break;
                case 'Monthly':
                    $currentDate->addMonth();
                    break;
                case 'None':
                default:
                    // For non-recurring meetings, break after first iteration
                    break 2;
            }
        }
    }
}
