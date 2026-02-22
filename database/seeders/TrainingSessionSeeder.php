<?php

namespace Database\Seeders;

use App\Models\TrainingSession;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainingSessionSeeder extends Seeder
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

        $currentYear = date('Y');

        // Fixed training session data for consistent results
        $sessionData = [
            ['name' => 'Morning Session - Batch A', 'location' => 'Training Room 1', 'location_type' => 'physical', 'status' => 'completed', 'start_hour' => 9, 'duration' => 4],
            ['name' => 'Afternoon Session - Batch B', 'location' => 'Training Room 2', 'location_type' => 'physical', 'status' => 'completed', 'start_hour' => 14, 'duration' => 4],
            ['name' => 'Virtual Workshop Session', 'location' => 'Online Platform', 'location_type' => 'virtual', 'status' => 'in_progress', 'start_hour' => 10, 'duration' => 3],
            ['name' => 'Weekend Intensive Session', 'location' => 'Conference Hall', 'location_type' => 'physical', 'status' => 'scheduled', 'start_hour' => 9, 'duration' => 6],
            ['name' => 'Evening Online Session', 'location' => 'Zoom Meeting', 'location_type' => 'virtual', 'status' => 'completed', 'start_hour' => 18, 'duration' => 2]
        ];

        $meetingLinks = [
            'https://zoom.us/j/123456789',
            'https://teams.microsoft.com/meeting/join',
            'https://meet.google.com/abc-defg-hij',
            'https://webex.com/meet/training',
            'https://gotomeeting.com/join/session'
        ];

        foreach ($companies as $company) {
            // Get training programs for this company
            $trainingPrograms = TrainingProgram::where('created_by', $company->id)->get();            

            if ($trainingPrograms->isEmpty()) {
                $this->command->warn('No training programs found for company: ' . $company->name . '. Please run TrainingProgramSeeder first.');
                continue;
            }

            // Get employees for trainers and attendees
            $employees = User::where('type', 'employee')->where('created_by', $company->id)->get();

            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }

            // Create sessions for first 3 training programs
            $selectedPrograms = $trainingPrograms->take(5);

            foreach ($selectedPrograms as $progIndex => $program) {
                // Create 2 sessions per program
                for ($sessionIndex = 0; $sessionIndex < 2; $sessionIndex++) {
                    $dataIndex = ($progIndex * 2) + $sessionIndex;
                    $session = $sessionData[$dataIndex % 5];

                    $startDate = $currentYear . '-' . str_pad($progIndex + 3, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($sessionIndex + 1) * 10, 2, '0', STR_PAD_LEFT);
                    $startDateTime = $startDate . ' ' . str_pad($session['start_hour'], 2, '0', STR_PAD_LEFT) . ':00:00';
                    $endDateTime = date('Y-m-d H:i:s', strtotime($startDateTime . ' +' . $session['duration'] . ' hours'));

                    try {
                        $trainingSession = TrainingSession::create([
                            'training_program_id' => $program->id,
                            'name' => $session['name'],
                            'start_date' => $startDateTime,
                            'end_date' => $endDateTime,
                            'location' => $session['location'],
                            'location_type' => $session['location_type'],
                            'meeting_link' => $session['location_type'] === 'virtual' ? $meetingLinks[$dataIndex % 5] : null,
                            'status' => $session['status'],
                            'notes' => 'Training session for ' . $program->name,
                            'is_recurring' => false,
                            'recurrence_pattern' => null,
                            'recurrence_count' => null,
                            'created_by' => $company->id,
                        ]);

                        // Create trainers for the session
                        $this->createSessionTrainers($trainingSession, $employees);

                        // Create attendance records for completed and in_progress sessions
                        if (in_array($session['status'], ['completed', 'in_progress'])) {
                            $this->createSessionAttendance($trainingSession, $employees, $session['status']);
                        }
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create training session for program: ' . $program->name . ' in company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('TrainingSession seeder completed successfully!');
    }

    /**
     * Create trainers for training session
     */
    private function createSessionTrainers($trainingSession, $employees)
    {
        // Assign 2 random trainers from 5 random employees
        $trainers = $employees->random(3);

        foreach ($trainers as $trainer) {
            try {
                DB::table('training_session_trainer')->insert([
                    'training_session_id' => $trainingSession->id,
                    'employee_id' => $trainer->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    /**
     * Create attendance records for training session
     */
    private function createSessionAttendance($trainingSession, $employees, $status)
    {
        // Create attendance for 5 random employees
        $attendees = $employees->random(5);

        // Attendance patterns based on status
        $attendancePatterns = [
            'completed' => [true, true, true, false, true], // 4 out of 5 attended
            'in_progress' => [true, true, false, false, false] // 2 out of 5 attended so far
        ];

        $pattern = $attendancePatterns[$status];

        foreach ($attendees as $index => $attendee) {
            $isPresent = $pattern[$index];

            try {
                DB::table('training_session_attendance')->insert([
                    'training_session_id' => $trainingSession->id,
                    'employee_id' => $attendee->id,
                    'is_present' => $isPresent,
                    'notes' => $isPresent ? 'Attended session' : 'Absent from session',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
