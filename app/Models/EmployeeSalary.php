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
        $deductions = [];
        $employerContributions = [];
        $totalEarnings = $this->basic_salary;
        $totalDeductions = 0;

        // Fetch BPJS Caps once
        $companyId = $this->creator ? $this->creator->company_id : ($this->employee ? $this->employee->company_id : auth()->id());
        $bpjsKesehatanCap = getSetting('bpjsKesehatanCap', $companyId);
        $bpjsKesehatanCap = $bpjsKesehatanCap ? (float) $bpjsKesehatanCap : 12500000;
        $bpjsJpCap = getSetting('bpjsJpCap', $companyId);
        $bpjsJpCap = $bpjsJpCap ? (float) $bpjsJpCap : 12500000;

        $basicSalary = $this->basic_salary;
        $basisKesehatan = min($basicSalary, $bpjsKesehatanCap);
        $basisJp = min($basicSalary, $bpjsJpCap);

        foreach ($components as $component) {
            $name = $component->name;
            $lowerName = strtolower($name);
            
            // Enhanced BPJS detection
            $isBpjs = str_contains($lowerName, 'bpjs') || 
                      str_contains($lowerName, 'jht') || 
                      str_contains($lowerName, 'jkk') || 
                      str_contains($lowerName, 'jkm') || 
                      str_contains($lowerName, 'kesehatan') || 
                      str_contains($lowerName, 'pensiun') || 
                      str_contains($lowerName, 'jp');

            if ($isBpjs) {
                // Determine BPJS amounts based on statutory rules, overriding any HR input
                $amount = 0; // EE deduction amount
                
                if (str_contains($lowerName, 'kesehatan')) {
                    $amount = $basisKesehatan * 0.01;
                    $employerContributions['ER_' . $name] = $basisKesehatan * 0.04;
                    $name = $name . ' (1%)';
                } elseif (str_contains($lowerName, 'jht')) {
                    $amount = $basicSalary * 0.02;
                    $employerContributions['ER_' . $name] = $basicSalary * 0.037;
                    $name = $name . ' (2%)';
                } elseif (str_contains($lowerName, 'jp') || str_contains($lowerName, 'pensiun')) {
                    $amount = $basisJp * 0.01;
                    $employerContributions['ER_' . $name] = $basisJp * 0.02;
                    $name = $name . ' (1%)';
                } elseif (str_contains($lowerName, 'jkk') || str_contains($lowerName, 'kecelakaan')) {
                    $amount = 0; // EE doesn't pay
                    $employerContributions['ER_' . $name] = $basicSalary * 0.0024;
                    $name = $name . ' (Employer Only)';
                } elseif (str_contains($lowerName, 'jkm') || str_contains($lowerName, 'kematian')) {
                    $amount = 0; // EE doesn't pay
                    $employerContributions['ER_' . $name] = $basicSalary * 0.003;
                    $name = $name . ' (Employer Only)';
                }

                // Append EE deductions if greater than 0
                if ($amount > 0) {
                    $deductions[$name] = $amount;
                    $totalDeductions += $amount;
                }

            } else {
                // --- Standard Non-BPJS Components ---
                $override = $customOverrides[$component->id] ?? [];
                $customAmount = $override['custom_amount'] ?? null;
                $customPercentage = $override['custom_percentage'] ?? null;
    
                if (!is_null($customAmount) && $customAmount !== '' && $customAmount !== 0) {
                    $amount = (float) $customAmount;
                } elseif (!is_null($customPercentage) && $customPercentage !== '' && $customPercentage !== 0) {
                    $amount = ($this->basic_salary * (float) $customPercentage) / 100;
                } else {
                    $amount = $component->calculateAmount($this->basic_salary);
                }
    
                if ($component->type === 'earning') {
                    $earnings[$name] = $amount;
                    $totalEarnings += $amount;
                } else {
                    $deductions[$name] = $amount;
                    $totalDeductions += $amount;
                }
            }
        }

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
