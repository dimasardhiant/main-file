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

        return [
            'basic_salary' => $this->basic_salary,
            'earnings' => $earnings,
            'deductions' => $deductions,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'gross_salary' => $totalEarnings,
            'net_salary' => $totalEarnings - $totalDeductions,
        ];
    }
}
