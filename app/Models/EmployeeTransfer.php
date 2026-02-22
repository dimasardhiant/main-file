<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTransfer extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'from_branch_id',
        'to_branch_id',
        'from_department_id',
        'to_department_id',
        'from_designation_id',
        'to_designation_id',
        'transfer_date',
        'effective_date',
        'reason',
        'status',
        'documents',
        'approved_by',
        'approved_at',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'effective_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the employee being transferred.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the branch the employee is being transferred from.
     */
    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    /**
     * Get the branch the employee is being transferred to.
     */
    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    /**
     * Get the department the employee is being transferred from.
     */
    public function fromDepartment()
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    /**
     * Get the department the employee is being transferred to.
     */
    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    /**
     * Get the designation the employee is being transferred from.
     */
    public function fromDesignation()
    {
        return $this->belongsTo(Designation::class, 'from_designation_id');
    }

    /**
     * Get the designation the employee is being transferred to.
     */
    public function toDesignation()
    {
        return $this->belongsTo(Designation::class, 'to_designation_id');
    }

    /**
     * Get the user who approved this transfer.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this transfer.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}