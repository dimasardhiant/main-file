<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
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

        // Fixed shifts for consistent data
        $shifts = [
            [
                'name' => 'Morning Shift',
                'description' => 'Standard morning shift for regular office hours',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'break_duration' => 60,
                'break_start_time' => '13:00:00',
                'break_end_time' => '14:00:00',
                'grace_period' => 15,
                'is_night_shift' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Evening Shift',
                'description' => 'Evening shift for extended business hours',
                'start_time' => '14:00:00',
                'end_time' => '23:00:00',
                'break_duration' => 60,
                'break_start_time' => '18:00:00',
                'break_end_time' => '19:00:00',
                'grace_period' => 15,
                'is_night_shift' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Night Shift',
                'description' => 'Night shift for 24/7 operations and support',
                'start_time' => '22:00:00',
                'end_time' => '07:00:00',
                'break_duration' => 60,
                'break_start_time' => '02:00:00',
                'break_end_time' => '03:00:00',
                'grace_period' => 15,
                'is_night_shift' => true,
                'status' => 'active'
            ]
        ];

        foreach ($companies as $company) {
            foreach ($shifts as $shiftData) {
                // Check if shift already exists for this company
                if (Shift::where('name', $shiftData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    Shift::create([
                        'name' => $shiftData['name'],
                        'description' => $shiftData['description'],
                        'start_time' => $shiftData['start_time'],
                        'end_time' => $shiftData['end_time'],
                        'break_duration' => $shiftData['break_duration'],
                        'break_start_time' => $shiftData['break_start_time'],
                        'break_end_time' => $shiftData['break_end_time'],
                        'grace_period' => $shiftData['grace_period'],
                        'is_night_shift' => $shiftData['is_night_shift'],
                        'status' => $shiftData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create shift: ' . $shiftData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        // Assign shifts to employees after creating shifts
        foreach ($companies as $company) {
            $companyShifts = Shift::where('created_by', $company->id)->get();
            $employeeUsers = User::where('type', 'employee')->where('created_by', $company->id)->get();

            if ($companyShifts->isNotEmpty() && $employeeUsers->isNotEmpty()) {
                // Assign Morning Shift to all employees by default
                $morningShift = $companyShifts->where('name', 'Morning Shift')->first();
                if ($morningShift) {
                    foreach ($employeeUsers as $employeeUser) {
                        // Get employee record from employee table using user_id
                        $employee = \App\Models\Employee::where('user_id', $employeeUser->id)->first();
                        if ($employee && !$employee->shift_id) {
                            $employee->update(['shift_id' => $morningShift->id]);
                        }
                    }
                }
            }
        }

        $this->command->info('Shift seeder completed successfully!');
    }
}
