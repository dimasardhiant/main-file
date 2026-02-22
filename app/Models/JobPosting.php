<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobPosting extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'requisition_id',
        'job_code',
        'title',
        'job_type_id',
        'location_id',
        'department_id',
        'branch_id',
        'priority',
        'skills',
        'positions',
        'min_experience',
        'max_experience',
        'min_salary',
        'max_salary',
        'description',
        'requirements',
        'benefits',
        'start_date',
        'application_deadline',
        'visibility',
        'code',
        'custom_question',
        'applicant',
        'is_published',
        'publish_date',
        'is_featured',
        'status',
        'created_by',
    ];

    protected $casts = [
        'application_deadline' => 'date',
        'start_date' => 'date',
        'publish_date' => 'datetime',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'skills' => 'array',
        'custom_question' => 'array',
        'applicant' => 'array',
        'visibility' => 'array',
    ];

    public function requisition()
    {
        return $this->belongsTo(JobRequisition::class);
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class);
    }

    public function location()
    {
        return $this->belongsTo(JobLocation::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class, 'job_id');
    }

    public static function generateJobCode($jobPostingId = null)
    {
        $nextId = $jobPostingId ?: (self::max('id') + 1);
        return 'JOB-' . creatorId() . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }
}
