<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractType extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'default_duration_months',
        'probation_period_months',
        'notice_period_days',
        'is_renewable',
        'status',
        'created_by'
    ];

    protected $casts = [
        'is_renewable' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contracts()
    {
        return $this->hasMany(EmployeeContract::class, 'contract_type_id');
    }
}