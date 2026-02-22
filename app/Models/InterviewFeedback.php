<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewFeedback extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'interview_id',
        'interviewer_id',
        'technical_rating',
        'communication_rating',
        'cultural_fit_rating',
        'overall_rating',
        'strengths',
        'weaknesses',
        'comments',
        'recommendation',
        'created_by'
    ];

    public function interview()
    {
        return $this->belongsTo(Interview::class);
    }

    public function getInterviewerNamesAttribute()
    {
        if (!$this->interviewer_id) {
            return '';
        }
        
        $interviewerIds = explode(',', $this->interviewer_id);
        $interviewers = \App\Models\User::whereIn('id', $interviewerIds)
            ->pluck('name')
            ->toArray();
        
        return implode(', ', $interviewers);
    }

    public function getInterviewerIdsArrayAttribute()
    {
        if (!$this->interviewer_id) {
            return [];
        }
        
        return explode(',', $this->interviewer_id);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}