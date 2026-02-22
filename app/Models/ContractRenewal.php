<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractRenewal extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'renewal_number',
        'current_end_date',
        'new_start_date',
        'new_end_date',
        'new_basic_salary',
        'new_allowances',
        'new_benefits',
        'new_terms_conditions',
        'changes_summary',
        'status',
        'reason',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'created_by'
    ];

    protected $casts = [
        'current_end_date' => 'date',
        'new_start_date' => 'date',
        'new_end_date' => 'date',
        'approved_at' => 'datetime',
        'new_allowances' => 'array',
        'new_benefits' => 'array',
        'new_basic_salary' => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(EmployeeContract::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNewTotalCompensationAttribute()
    {
        $total = $this->new_basic_salary;
        if ($this->new_allowances && is_array($this->new_allowances)) {
            foreach ($this->new_allowances as $allowance) {
                $total += $allowance['amount'] ?? 0;
            }
        }
        return $total;
    }
}