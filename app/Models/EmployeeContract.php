<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeContract extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'employee_id',
        'contract_type_id',
        'start_date',
        'end_date',
        'basic_salary',
        'benefits',
        'terms_conditions',
        'status',
        'approved_by',
        'approved_at',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'benefits' => 'array',
        'basic_salary' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function amendments()
    {
        return $this->hasMany(ContractAmendment::class);
    }

    public function renewals()
    {
        return $this->hasMany(ContractRenewal::class);
    }

    public function getIsExpiringAttribute()
    {
        if (!$this->end_date || $this->status !== 'Active') return false;
        return $this->end_date <= Carbon::today()->addDays(30);
    }

    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->end_date || $this->status !== 'Active') return null;
        return Carbon::today()->diffInDays($this->end_date, false);
    }

    public function getTotalCompensationAttribute()
    {
        return $this->basic_salary;
    }
}