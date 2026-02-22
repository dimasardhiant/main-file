<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewRound extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'name',
        'sequence_number',
        'description',
        'status',
        'created_by'
    ];

    public function job()
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class, 'round_id');
    }
}