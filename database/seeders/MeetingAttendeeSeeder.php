<?php

namespace Database\Seeders;

use App\Models\MeetingAttendee;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeetingAttendeeSeeder extends Seeder
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

        // Fixed attendee patterns for consistent data
        $attendeePatterns = [
            ['type' => 'Required', 'rsvp_status' => 'Accepted', 'attendance_status' => 'Present', 'decline_reason' => null],
            ['type' => 'Required', 'rsvp_status' => 'Accepted', 'attendance_status' => 'Present', 'decline_reason' => null],
            ['type' => 'Optional', 'rsvp_status' => 'Accepted', 'attendance_status' => 'Present', 'decline_reason' => null],
            ['type' => 'Required', 'rsvp_status' => 'Declined', 'attendance_status' => 'Not Attended', 'decline_reason' => 'Conflicting meeting schedule'],
            ['type' => 'Optional', 'rsvp_status' => 'Tentative', 'attendance_status' => 'Not Attended', 'decline_reason' => null],
            ['type' => 'Required', 'rsvp_status' => 'Accepted', 'attendance_status' => 'Late', 'decline_reason' => null],
            ['type' => 'Optional', 'rsvp_status' => 'Accepted', 'attendance_status' => 'Left Early', 'decline_reason' => null],
            ['type' => 'Required', 'rsvp_status' => 'Pending', 'attendance_status' => 'Not Attended', 'decline_reason' => null]
        ];

        foreach ($companies as $company) {
            // Get meetings for this company
            $meetings = Meeting::where('created_by', $company->id)->get();

            if ($meetings->isEmpty()) {
                $this->command->warn('No meetings found for company: ' . $company->name . '. Please run MeetingSeeder first.');
                continue;
            }

            // Get employees for this company
            $employees = User::whereIn('type', ['manager', 'hr', 'employee'])
                ->where('created_by', $company->id)
                ->get();

            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name);
                continue;
            }

            foreach ($meetings as $meeting) {
                // Determine number of attendees based on meeting type
                $attendeeCount = $this->getAttendeeCount($meeting->type->name ?? 'Team Meeting');

                // Select attendees (excluding organizer to avoid duplicates)
                $availableEmployees = $employees->where('id', '!=', $meeting->organizer_id);
                $selectedAttendees = $availableEmployees->take($attendeeCount);

                foreach ($selectedAttendees as $index => $attendee) {
                    // Check if attendee already exists for this meeting
                    if (MeetingAttendee::where('meeting_id', $meeting->id)->where('user_id', $attendee->id)->exists()) {
                        continue;
                    }

                    $pattern = $attendeePatterns[$index % 8];

                    // Set RSVP date if status is not pending
                    $rsvpDate = $pattern['rsvp_status'] !== 'Pending' ?
                        date('Y-m-d H:i:s', strtotime($meeting->meeting_date . ' -1 day')) : null;

                    // For completed meetings, set appropriate attendance status
                    if ($meeting->status === 'Completed') {
                        if ($pattern['rsvp_status'] === 'Accepted') {
                            $attendanceStatus = $pattern['attendance_status'];
                        } else {
                            $attendanceStatus = 'Not Attended';
                        }
                    } else {
                        $attendanceStatus = 'Not Attended';
                    }

                    try {
                        MeetingAttendee::create([
                            'meeting_id' => $meeting->id,
                            'user_id' => $attendee->id,
                            'type' => $pattern['type'],
                            'rsvp_status' => $pattern['rsvp_status'],
                            'attendance_status' => $attendanceStatus,
                            'rsvp_date' => $rsvpDate,
                            'decline_reason' => $pattern['decline_reason'],
                            'created_by' => $company->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create meeting attendee for meeting: ' . $meeting->title . ' and user: ' . $attendee->name . ' in company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('MeetingAttendee seeder completed successfully!');
    }

    /**
     * Get attendee count based on meeting type
     */
    private function getAttendeeCount($meetingType)
    {
        switch ($meetingType) {
            case 'Team Meeting':
                return 5;
            case 'One-on-One':
                return 1;
            case 'Client Meeting':
                return 4;
            case 'Board Meeting':
                return 5;
            case 'Training Session':
                return 5;
            case 'Interview':
                return 2;
            case 'Project Review':
                return 5;
            case 'All Hands':
                return 5;
            case 'Performance Review':
                return 1;
            case 'Brainstorming':
                return 7;
            default:
                return 5;
        }
    }
}
