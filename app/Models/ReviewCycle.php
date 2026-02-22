<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewCycle extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'name',
        'frequency',
        'description',
        'status'
    ];

    /**
     * Get the company that owns this review cycle.
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the employee reviews for this review cycle.
     */
    public function reviews()
    {
        return $this->hasMany(EmployeeReview::class, 'review_cycle_id');
    }
}