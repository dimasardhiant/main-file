<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalary extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'basic_salary',
        'components',
        'is_active',
        'calculation_status',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'components' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the employee.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }



    /**
     * Get the user who created the salary.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get active salary for employee.
     */
    public static function getActiveSalary($employeeId)
    {
        return static::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get basic salary for employee.
     */
    public static function getBasicSalary($employeeId)
    {
        $salary = static::getActiveSalary($employeeId);
        return $salary ? $salary->basic_salary : 0;
    }

    /**
     * Parse components field and extract IDs and custom overrides.
     * Supports both old format [1, 3, 5] and new format [{"id":1,"custom_amount":300000}, ...]
     */
    public function parseComponents()
    {
        $rawComponents = $this->components ?? [];
        $componentIds = [];
        $customOverrides = []; // keyed by component ID

        foreach ($rawComponents as $entry) {
            if (is_array($entry) && isset($entry['id'])) {
                // New format: object with id and optional custom values
                $componentIds[] = $entry['id'];
                $customOverrides[$entry['id']] = [
                    'custom_amount' => $entry['custom_amount'] ?? null,
                    'custom_percentage' => $entry['custom_percentage'] ?? null,
                ];
            } else {
                // Old format: plain ID (backward compatibility)
                $componentIds[] = $entry;
            }
        }

        return [$componentIds, $customOverrides];
    }

    /**
     * Calculate salary components based on selected components.
     * Supports per-employee custom values with fallback to template defaults.
     */
    public function calculateAllComponents()
    {
        [$componentIds, $customOverrides] = $this->parseComponents();

        $components = SalaryComponent::whereIn('id', $componentIds)
            ->where('status', 'active')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->get();

        $earnings = ['Basic Salary' => $this->basic_salary];

        $deductions = [];
        $totalEarnings = $this->basic_salary;
        $totalDeductions = 0;

        foreach ($components as $component) {
            $override = $customOverrides[$component->id] ?? [];
            $customAmount = $override['custom_amount'] ?? null;
            $customPercentage = $override['custom_percentage'] ?? null;

            // Determine the amount: custom values take priority over template defaults
            if (!is_null($customAmount) && $customAmount !== '' && $customAmount !== 0) {
                $amount = (float) $customAmount;
            } elseif (!is_null($customPercentage) && $customPercentage !== '' && $customPercentage !== 0) {
                $amount = ($this->basic_salary * (float) $customPercentage) / 100;
            } else {
                // Fallback to template default
                $amount = $component->calculateAmount($this->basic_salary);
            }

            if ($component->type === 'earning') {
                $earnings[$component->name] = $amount;
                $totalEarnings += $amount;
            } else {
                $deductions[$component->name] = $amount;
                $totalDeductions += $amount;
            }
        }

        // --- Indonesian BPJS Statutory Calculations ---
        $basicSalary = $this->basic_salary;

        // Caps
        $bpjsKesehatanCap = 12000000; // 12 Juta
        $bpjsJpCap = 10042300; // Approx 10 Juta for JP

        // Basis for calculation
        $basisKesehatan = min($basicSalary, $bpjsKesehatanCap);
        $basisJp = min($basicSalary, $bpjsJpCap);

        // 1. BPJS Kesehatan (1% EE, 4% ER)
        $eeBpjsKes = $basisKesehatan * 0.01;
        $erBpjsKes = $basisKesehatan * 0.04;

        // 2. BPJS Ketenagakerjaan - JHT (Jaminan Hari Tua) (2% EE, 3.7% ER)
        $eeJht = $basicSalary * 0.02;
        $erJht = $basicSalary * 0.037;

        // 3. BPJS Ketenagakerjaan - JP (Jaminan Pensiun) (1% EE, 2% ER)
        $eeJp = $basisJp * 0.01;
        $erJp = $basisJp * 0.02;

        // 4. BPJS Ketenagakerjaan - JKK (Jaminan Kecelakaan Kerja) (0.24% ER - Defaulting to low risk)
        $erJkk = $basicSalary * 0.0024;

        // 5. BPJS Ketenagakerjaan - JKM (Jaminan Kematian) (0.3% ER)
        $erJkm = $basicSalary * 0.003;

        // Append to Employee Deductions (reduces Net Pay)
        $deductions['BPJS Kesehatan (1%)'] = $eeBpjsKes;
        $deductions['BPJS JHT (2%)'] = $eeJht;
        $deductions['BPJS JP (1%)'] = $eeJp;
        $totalDeductions += ($eeBpjsKes + $eeJht + $eeJp);

        // Store Employer Contributions (does NOT reduce Net Pay)
        $employerContributions = [
            'ER_BPJS_Kesehatan_(4%)' => $erBpjsKes,
            'ER_BPJS_JHT_(3.7%)' => $erJht,
            'ER_BPJS_JP_(2%)' => $erJp,
            'ER_BPJS_JKK_(0.24%)' => $erJkk,
            'ER_BPJS_JKM_(0.3%)' => $erJkm,
        ];

        return [
            'basic_salary' => $this->basic_salary,
            'earnings' => $earnings,
            'deductions' => $deductions,
            'employer_contributions' => $employerContributions,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'gross_salary' => $totalEarnings,
            'net_salary' => $totalEarnings - $totalDeductions,
        ];
    }
}
