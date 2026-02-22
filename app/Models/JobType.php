<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobType extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jobPostings()
    {
        return $this->hasMany(JobPosting::class);
    }
}