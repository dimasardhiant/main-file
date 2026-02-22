<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateAssessment extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'assessment_name',
        'score',
        'max_score',
        'pass_fail_status',
        'comments',
        'conducted_by',
        'assessment_date',
        'created_by'
    ];

    protected $casts = [
        'assessment_date' => 'date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function conductor()
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getScorePercentageAttribute()
    {
        if (!$this->max_score || $this->max_score == 0) {
            return null;
        }
        return round(($this->score / $this->max_score) * 100, 2);
    }
}