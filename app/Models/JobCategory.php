<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCategory extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by'
    ];

    /**
     * Get the user who created this job category.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the job requisitions for this category.
     */
    public function jobRequisitions()
    {
        return $this->hasMany(JobRequisition::class);
    }
}