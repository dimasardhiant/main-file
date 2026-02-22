<?php

namespace Database\Seeders;

use App\Models\Payslip;
use App\Models\PayrollEntry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PayslipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get completed payroll entries
        $payrollEntries = PayrollEntry::whereHas('payrollRun', function ($query) {
            $query->where('status', 'completed');
        })->get();
        
        foreach ($payrollEntries as $entry) {
            // Check if payslip already exists
            $exists = Payslip::where('payroll_entry_id', $entry->id)->exists();
            
            if (!$exists) {
                $payslipNumber = Payslip::generatePayslipNumber(
                    $entry->employee_id, 
                    $entry->payrollRun->pay_date
                );
                
                Payslip::create([
                    'payroll_entry_id' => $entry->id,
                    'employee_id' => $entry->employee_id,
                    'payslip_number' => $payslipNumber,
                    'pay_period_start' => $entry->payrollRun->pay_period_start,
                    'pay_period_end' => $entry->payrollRun->pay_period_end,
                    'pay_date' => $entry->payrollRun->pay_date,
                    'status' => 'generated',
                    'created_by' => $entry->created_by,
                ]);
            }
        }
    }
}