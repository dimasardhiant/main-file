<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
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
        
        // Fixed leave types for consistent data
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'description' => 'Yearly vacation leave for rest and recreation',
                'max_days_per_year' => 21,
                'is_paid' => true,
                'color' => '#10b77f',
                'status' => 'active'
            ],
            [
                'name' => 'Sick Leave',
                'description' => 'Medical leave for illness or health-related issues',
                'max_days_per_year' => 10,
                'is_paid' => true,
                'color' => '#EF4444',
                'status' => 'active'
            ],
            [
                'name' => 'Maternity Leave',
                'description' => 'Leave for new mothers during childbirth and recovery period',
                'max_days_per_year' => 90,
                'is_paid' => true,
                'color' => '#EC4899',
                'status' => 'active'
            ],
            [
                'name' => 'Paternity Leave',
                'description' => 'Leave for new fathers to support family during childbirth',
                'max_days_per_year' => 15,
                'is_paid' => true,
                'color' => '#3B82F6',
                'status' => 'active'
            ],
            [
                'name' => 'Emergency Leave',
                'description' => 'Urgent leave for unexpected personal or family emergencies',
                'max_days_per_year' => 5,
                'is_paid' => true,
                'color' => '#F59E0B',
                'status' => 'active'
            ],
            [
                'name' => 'Bereavement Leave',
                'description' => 'Compassionate leave for death of family members or close relatives',
                'max_days_per_year' => 7,
                'is_paid' => true,
                'color' => '#6B7280',
                'status' => 'active'
            ],
            [
                'name' => 'Study Leave',
                'description' => 'Educational leave for professional development and training',
                'max_days_per_year' => 10,
                'is_paid' => false,
                'color' => '#8B5CF6',
                'status' => 'active'
            ],
            [
                'name' => 'Compensatory Leave',
                'description' => 'Time off in lieu of overtime work or weekend duties',
                'max_days_per_year' => 12,
                'is_paid' => true,
                'color' => '#06B6D4',
                'status' => 'active'
            ],
            [
                'name' => 'Personal Leave',
                'description' => 'Personal time off for individual matters and commitments',
                'max_days_per_year' => 5,
                'is_paid' => false,
                'color' => '#84CC16',
                'status' => 'active'
            ],
            [
                'name' => 'Marriage Leave',
                'description' => 'Special leave for wedding ceremonies and related celebrations',
                'max_days_per_year' => 7,
                'is_paid' => true,
                'color' => '#F97316',
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($leaveTypes as $typeData) {
                // Check if leave type already exists for this company
                if (LeaveType::where('name', $typeData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    LeaveType::create([
                        'name' => $typeData['name'],
                        'description' => $typeData['description'],
                        'max_days_per_year' => $typeData['max_days_per_year'],
                        'is_paid' => $typeData['is_paid'],
                        'color' => $typeData['color'],
                        'status' => $typeData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create leave type: ' . $typeData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('LeaveType seeder completed successfully!');
    }
}