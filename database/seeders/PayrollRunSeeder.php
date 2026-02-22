<?php

namespace Database\Seeders;

use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PayrollRunSeeder extends Seeder
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

        foreach ($companies as $company) {

            // Create payroll runs for May, June, July, August 2025
            for ($month = 4; $month <= 8; $month++) {
                $this->createPayrollRun($company, $currentYear, $month);
            }
        }

        $this->command->info('PayrollRun seeder completed successfully!');
    }

    /**
     * Create payroll run for specific month
     */
    private function createPayrollRun($company, $year, $month)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $payDate = $endDate->copy()->addDays(5); // Pay 5 days after month end

        $monthName = $startDate->format('F');
        $title = "{$monthName} {$year} Payroll";

        // Check if payroll run already exists
        if (PayrollRun::where('title', $title)->where('created_by', $company->id)->exists()) {
            return;
        }

        try {
            PayrollRun::create([
                'title' => $title,
                'payroll_frequency' => 'monthly',
                'pay_period_start' => $startDate->format('Y-m-d'),
                'pay_period_end' => $endDate->format('Y-m-d'),
                'pay_date' => $payDate->format('Y-m-d'),
                'total_gross_pay' => 0.00,
                'total_deductions' => 0.00,
                'total_net_pay' => 0.00,
                'status' => 'draft',
                'notes' => "Monthly payroll for {$monthName} {$year}",
                'created_by' => $company->id,
            ]);
        } catch (\Exception $e) {
            $this->command->error('Failed to create payroll run: ' . $title . ' for company: ' . $company->name);
        }
    }
}
