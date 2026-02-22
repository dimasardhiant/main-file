<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'job_id',
        'offer_date',
        'position',
        'department_id',
        'salary',
        'bonus',
        'equity',
        'benefits',
        'start_date',
        'expiration_date',
        'offer_letter_path',
        'status',
        'response_date',
        'decline_reason',
        'created_by',
        'approved_by'
    ];

    protected $casts = [
        'offer_date' => 'date',
        'start_date' => 'date',
        'expiration_date' => 'date',
        'response_date' => 'date',
        'salary' => 'decimal:2',
        'bonus' => 'decimal:2',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job()
    {
        return $this->belongsTo(JobPosting::class);
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
}