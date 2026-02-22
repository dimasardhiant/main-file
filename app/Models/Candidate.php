<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'source_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'branch_id',
        'department_id',
        'date_of_birth',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'current_company',
        'current_position',
        'experience_years',
        'current_salary',
        'expected_salary',
        'final_salary',
        'notice_period',
        'resume_path',
        'cover_letter_path',
        'coverletter_message',
        'skills',
        'education',
        'portfolio_url',
        'linkedin_url',
        'referral_employee_id',
        'status',
        'application_date',
        'rating',
        'is_archive',
        'is_employee',
        'custom_question',
        'terms_condition_check',
        'created_by'
    ];

    protected $casts = [
        'application_date' => 'date',
        'date_of_birth' => 'date',
        'current_salary' => 'decimal:2',
        'expected_salary' => 'decimal:2',
        'custom_question' => 'array',
        'is_archive' => 'boolean',
        'is_employee' => 'boolean',
    ];

    public function job()
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function source()
    {
        return $this->belongsTo(CandidateSource::class);
    }

    public function referralEmployee()
    {
        return $this->belongsTo(User::class, 'referral_employee_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function location()
    {
        return $this->belongsTo(JobLocation::class, 'location_id');
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class, 'job_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    public function assessments()
    {
        return $this->hasMany(CandidateAssessment::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}