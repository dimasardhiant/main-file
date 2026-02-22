<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeReview extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'employee_id',
        'reviewer_id',
        'review_cycle_id',
        'template_id',
        'review_date',
        'completion_date',
        'overall_rating',
        'comments',
        'status'
    ];

    protected $casts = [
        'review_date' => 'date',
        'completion_date' => 'date',
        'overall_rating' => 'float',
    ];

    /**
     * Get the company that owns this review.
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the employee being reviewed.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the employee conducting the review.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the review cycle for this review.
     */
    public function reviewCycle()
    {
        return $this->belongsTo(ReviewCycle::class, 'review_cycle_id');
    }

    /**
     * Get the template used for this review.
     */
    public function template()
    {
        return $this->belongsTo(ReviewTemplate::class, 'template_id');
    }

    /**
     * Get the ratings for this review.
     */
    public function ratings()
    {
        return $this->hasMany(EmployeeReviewRating::class);
    }
}