<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'job_id',
        'round_id',
        'interview_type_id',
        'scheduled_date',
        'scheduled_time',
        'duration',
        'location',
        'meeting_link',
        'interviewers',
        'status',
        'feedback_submitted',
        'created_by'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'interviewers' => 'array',
        'feedback_submitted' => 'boolean',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job()
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function round()
    {
        return $this->belongsTo(InterviewRound::class);
    }

    public function interviewType()
    {
        return $this->belongsTo(InterviewType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function feedback()
    {
        return $this->hasMany(InterviewFeedback::class);
    }
}