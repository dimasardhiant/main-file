<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'calculation_type',
        'default_amount',
        'percentage_of_basic',
        'is_taxable',
        'is_mandatory',
        'status',
        'created_by'
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'percentage_of_basic' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_mandatory' => 'boolean',
    ];

    /**
     * Get the user who created the component.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate component amount based on basic salary.
     */
    public function calculateAmount($basicSalary = 0)
    {
        if ($this->calculation_type === 'percentage' && $this->percentage_of_basic) {
            return ($basicSalary * $this->percentage_of_basic) / 100;
        }
        
        return $this->default_amount;
    }

    /**
     * Get earnings components.
     */
    public static function getEarnings()
    {
        return static::where('type', 'earning')
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get deductions components.
     */
    public static function getDeductions()
    {
        return static::where('type', 'deduction')
            ->where('status', 'active')
            ->get();
    }
}