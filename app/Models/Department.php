<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'branch_id',
        'description',
        'status',
        'created_by'
    ];

    /**
     * Get the branch that owns the department.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the department.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the employees assigned to this department.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function desginations()
    {
        return $this->hasMany(Designation::class,'department_id','id');
    }
}