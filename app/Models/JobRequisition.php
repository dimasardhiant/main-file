<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRequisition extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'requisition_code',
        'title',
        'job_category_id',
        'department_id',
        'positions_count',
        'budget_min',
        'budget_max',
        'skills_required',
        'education_required',
        'experience_required',
        'description',
        'responsibilities',
        'status',
        'approved_by',
        'approval_date',
        'priority',
        'created_by'
    ];

    protected $casts = [
        'approval_date' => 'datetime',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
    ];

    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function jobPostings()
    {
        return $this->hasMany(JobPosting::class, 'requisition_id');
    }
}