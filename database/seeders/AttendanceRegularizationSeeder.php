<?php

namespace Database\Seeders;

use App\Models\AttendanceRegularization;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceRegularizationSeeder extends Seeder
{
    public function run(): void
    {
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found.');
            return;
        }
        $currentYear = date('Y');
        foreach ($companies as $company) {
            $employees = User::where('type', 'employee')
                ->where('created_by', $company->id)
                ->limit(4)
                ->get();

            if ($employees->isEmpty()) {
                continue;
            }

            // Process May to August 2025
            for ($month = 5; $month <= 8; $month++) {
                $this->processMonth($company, $employees, $currentYear, $month);
            }
        }

        $this->command->info('AttendanceRegularization seeder completed successfully!');
    }

    private function processMonth($company, $employees, $year, $month)
    {
        foreach ($employees as $employee) {
            // Get attendance records for this employee in this month
            $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->where('status', '!=', 'on_leave')
                ->get();

            if ($attendanceRecords->isEmpty()) {
                continue;
            }

            // Create 2-3 regularization requests per employee per month
            $recordsToRegularize = $attendanceRecords->take(3);

            foreach ($recordsToRegularize as $index => $record) {
                $this->createRegularization($company, $employee, $record, $index);
            }
        }
    }

    private function createRegularization($company, $employee, $attendanceRecord, $index)
    {
        // Check if regularization already exists
        $existingRegularization = AttendanceRegularization::where('employee_id', $employee->id)
            ->where('attendance_record_id', $attendanceRecord->id)
            ->first();

        if ($existingRegularization) {
            return;
        }

        // Get regularization pattern based on index
        $pattern = $this->getRegularizationPattern($attendanceRecord, $index);

        // Get manager for approval
        $manager = User::where('type', 'employee')
            ->where('created_by', $company->id)
            ->where('id', '!=', $employee->id)
            ->first() ?? $company;

        AttendanceRegularization::create([
            'employee_id' => $employee->id,
            'attendance_record_id' => $attendanceRecord->id,
            'date' => $attendanceRecord->date,
            'requested_clock_in' => $pattern['requested_clock_in'],
            'requested_clock_out' => $pattern['requested_clock_out'],
            'original_clock_in' => $attendanceRecord->clock_in,
            'original_clock_out' => $attendanceRecord->clock_out,
            'reason' => $pattern['reason'],
            'status' => $pattern['status'],
            'manager_comments' => $pattern['manager_comments'],
            'approved_by' => $pattern['approved_by'] ? $manager->id : null,
            'approved_at' => $pattern['approved_at'],
            'created_by' => $employee->id,
        ]);
    }

    private function getRegularizationPattern($attendanceRecord, $index)
    {
        $originalClockIn = $attendanceRecord->clock_in ? Carbon::parse($attendanceRecord->clock_in) : null;
        $originalClockOut = $attendanceRecord->clock_out ? Carbon::parse($attendanceRecord->clock_out) : null;

        switch ($index % 3) {
            case 0: // Pending - Late arrival correction
                $requestedClockIn = $originalClockIn ? $originalClockIn->copy()->subMinutes(30) : Carbon::parse('09:00:00');
                return [
                    'requested_clock_in' => $requestedClockIn->format('H:i:s'),
                    'requested_clock_out' => $originalClockOut ? $originalClockOut->format('H:i:s') : null,
                    'reason' => 'Traffic jam caused delay, requesting time correction',
                    'status' => 'pending',
                    'manager_comments' => null,
                    'approved_by' => false,
                    'approved_at' => null,
                ];

            case 1: // Pending - Early departure correction
                $requestedClockOut = $originalClockOut ? $originalClockOut->copy()->addMinutes(45) : Carbon::parse('18:00:00');
                return [
                    'requested_clock_in' => $originalClockIn ? $originalClockIn->format('H:i:s') : null,
                    'requested_clock_out' => $requestedClockOut->format('H:i:s'),
                    'reason' => 'Had to leave early for medical appointment, worked from home later',
                    'status' => 'pending',
                    'manager_comments' => null,
                    'approved_by' => false,
                    'approved_at' => null,
                ];

            default: // Pending - Missing punch correction
                return [
                    'requested_clock_in' => '09:00:00',
                    'requested_clock_out' => '18:00:00',
                    'reason' => 'Forgot to punch in/out, was present full day',
                    'status' => 'pending',
                    'manager_comments' => null,
                    'approved_by' => false,
                    'approved_at' => null,
                ];
        }
    }
}
