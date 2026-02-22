<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\AttendancePolicy;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing weekend records first
        $this->clearWeekendRecords();
        
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found.');
            return;
        }
        $currentYear = date('Y');

        foreach ($companies as $company) {
            $employees = User::where('type', 'employee')
                ->where('created_by', $company->id)
                ->get();

            if ($employees->isEmpty()) {
                continue;
            }

            // Process May to August 2025
            for ($month = 5; $month <= 8; $month++) {
                $this->processMonth($company, $employees, $currentYear, $month);
            }
        }

        $this->command->info('AttendanceRecord seeder completed successfully!');
    }

    private function clearWeekendRecords()
    {
        // Delete all weekend attendance records for 2025
        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create(2025, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();
            
            for ($day = 1; $day <= $endDate->day; $day++) {
                $currentDate = Carbon::create(2025, $month, $day);
                
                // If it's Saturday or Sunday, delete records
                if ($currentDate->dayOfWeek == 6 || $currentDate->dayOfWeek == 0) {
                    AttendanceRecord::whereDate('date', $currentDate->format('Y-m-d'))->delete();
                }
            }
        }
    }

    private function processMonth($company, $employees, $year, $month)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        for ($day = 1; $day <= $endDate->day; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
                        
            // Skip Saturday (6) and Sunday (0)
            if ($currentDate->dayOfWeek == 6 || $currentDate->dayOfWeek == 0) {
                continue;
            }

            $dateString = $currentDate->format('Y-m-d');

            // Process each employee for this date
            foreach ($employees as $employee) {
                $this->createAttendanceForEmployee($company, $employee, $dateString);
            }
        }
    }

    private function createAttendanceForEmployee($company, $employee, $dateString)
    {
        // Get employee record
        $employeeRecord = Employee::where('user_id', $employee->id)->first();

        if (!$employeeRecord || !$employeeRecord->shift_id || !$employeeRecord->attendance_policy_id) {
            return;
        }

        $shift = Shift::find($employeeRecord->shift_id);
        $attendancePolicy = AttendancePolicy::find($employeeRecord->attendance_policy_id);

        if (!$shift || !$attendancePolicy) {
            return;
        }

        // Check if employee is on leave
        $isOnLeave = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $dateString)
            ->where('end_date', '>=', $dateString)
            ->exists();

        // Check if record already exists
        $existingRecord = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $dateString)
            ->first();
            
        if ($existingRecord) {
            echo "Skipping {$dateString} for employee {$employee->id} - already exists\n";
            return;
        }
        

        if ($isOnLeave) {
            // Create leave attendance
            $record = AttendanceRecord::create([
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'attendance_policy_id' => $attendancePolicy->id,
                'date' => $dateString,
                'clock_in' => null,
                'clock_out' => null,
                'status' => 'on_leave',
                'notes' => 'Employee on approved leave',
                'created_by' => $company->id,
            ]);
        } else {
            // Create regular attendance
            $pattern = $this->getAttendancePattern($dateString, $shift);

            $record = AttendanceRecord::create([
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'attendance_policy_id' => $attendancePolicy->id,
                'date' => $dateString,
                'clock_in' => $pattern['clock_in'],
                'clock_out' => $pattern['clock_out'],
                'status' => $pattern['status'],
                'notes' => $pattern['notes'],
                'created_by' => $company->id,
            ]);
        }

        // Process attendance calculations
        $record->processAttendance();
    }

    private function getAttendancePattern($dateString, $shift)
    {
        $date = Carbon::parse($dateString);
        $dayOfMonth = $date->day;
        $shiftStart = Carbon::parse($shift->start_time);
        $shiftEnd = Carbon::parse($shift->end_time);

        // Different patterns including overtime
        $patternType = $dayOfMonth % 4;

        switch ($patternType) {
            case 0: // Late arrival
                $lateClockIn = $shiftStart->copy()->addMinutes(25);
                return [
                    'clock_in' => $lateClockIn->format('H:i:s'),
                    'clock_out' => $shiftEnd->format('H:i:s'),
                    'status' => 'present',
                    'notes' => 'Late arrival'
                ];

            case 1: // Early departure
                $earlyClockOut = $shiftEnd->copy()->subMinutes(40);
                return [
                    'clock_in' => $shiftStart->format('H:i:s'),
                    'clock_out' => $earlyClockOut->format('H:i:s'),
                    'status' => 'present',
                    'notes' => 'Early departure'
                ];

            case 2: // Overtime
                $overtimeClockOut = $shiftEnd->copy()->addHours(2);
                return [
                    'clock_in' => $shiftStart->format('H:i:s'),
                    'clock_out' => $overtimeClockOut->format('H:i:s'),
                    'status' => 'present',
                    'notes' => 'Overtime work'
                ];

            default: // Regular attendance
                return [
                    'clock_in' => $shiftStart->format('H:i:s'),
                    'clock_out' => $shiftEnd->format('H:i:s'),
                    'status' => 'present',
                    'notes' => 'Regular attendance'
                ];
        }
    }
}
