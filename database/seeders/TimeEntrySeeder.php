<?php

namespace Database\Seeders;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TimeEntrySeeder extends Seeder
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
                ->get();

            if ($employees->isEmpty()) {
                continue;
            }

            // Process May to August 2025
            for ($month = 5; $month <= 8; $month++) {
                $this->processMonth($company, $employees,          $currentYear, $month);
            }
        }

        $this->command->info('TimeEntry seeder completed successfully!');
    }

    private function processMonth($company, $employees, $year, $month)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        foreach ($employees as $employee) {
            // Create 8-10 time entries per employee per month
            $entriesCount = 8 + ($employee->id % 3); // 8, 9, or 10 entries

            for ($i = 0; $i < $entriesCount; $i++) {
                $entryDate = $startDate->copy()->addDays($i * 3 + ($employee->id % 3));

                // Skip if date exceeds month end
                if ($entryDate->gt($endDate)) {
                    break;
                }

                // Skip weekends
                if ($entryDate->dayOfWeek == 6 || $entryDate->dayOfWeek == 0) {
                    continue;
                }

                $this->createTimeEntry($company, $employee, $entryDate, $i);
            }
        }
    }

    private function createTimeEntry($company, $employee, $date, $index)
    {
        $dateString = $date->format('Y-m-d');

        // Check if time entry already exists
        $existingEntry = TimeEntry::where('employee_id', $employee->id)
            ->where('date', $dateString)
            ->first();

        if ($existingEntry) {
            return;
        }

        // Get time entry pattern based on index
        $pattern = $this->getTimeEntryPattern($index);

        TimeEntry::create([
            'employee_id' => $employee->id,
            'date' => $dateString,
            'hours' => $pattern['hours'],
            'description' => $pattern['description'],
            'project' => $pattern['project'],
            'status' => 'pending',
            'manager_comments' => null,
            'approved_by' => null,
            'approved_at' => null,
            'created_by' => $employee->id,
        ]);
    }

    private function getTimeEntryPattern($index)
    {
        $projects = [
            'Website Development',
            'Mobile App',
            'Database Optimization',
            'API Integration',
            'Bug Fixes',
            'Code Review',
            'Documentation',
            'Testing'
        ];

        $descriptions = [
            'Worked on frontend components and user interface improvements',
            'Implemented new features and functionality as per requirements',
            'Fixed critical bugs and performance issues in the system',
            'Conducted code review and provided feedback to team members',
            'Updated project documentation and technical specifications',
            'Performed testing and quality assurance activities',
            'Attended team meetings and project planning sessions',
            'Researched new technologies and best practices'
        ];

        $hours = [6.5, 7.0, 7.5, 8.0, 8.5];

        $patternIndex = $index % count($projects);

        return [
            'hours' => $hours[$index % count($hours)],
            'description' => $descriptions[$patternIndex],
            'project' => $projects[$patternIndex]
        ];
    }
}
