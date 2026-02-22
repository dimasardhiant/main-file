<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'description',
        'content',
        'start_date',
        'end_date',
        'attachments',
        'is_featured',
        'is_high_priority',
        'is_company_wide',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_featured' => 'boolean',
        'is_high_priority' => 'boolean',
        'is_company_wide' => 'boolean',
    ];

    /**
     * Get the user who created this announcement.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the departments this announcement is targeted to.
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'announcement_department');
    }

    /**
     * Get the branches this announcement is targeted to.
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'announcement_branch');
    }

    /**
     * Get the employees who have viewed this announcement.
     */
    public function viewedBy()
    {
        return $this->belongsToMany(User::class, 'announcement_views', 'announcement_id', 'employee_id')
            ->withPivot('viewed_at')
            ->withTimestamps();
    }

    /**
     * Check if the announcement is active based on start and end dates.
     */
    public function isActive()
    {
        $today = now()->startOfDay();
        
        if ($this->end_date) {
            return $this->start_date->lte($today) && $this->end_date->gte($today);
        }
        
        return $this->start_date->lte($today);
    }

    /**
     * Scope a query to only include active announcements.
     */
    public function scopeActive($query)
    {
        $today = now()->format('Y-m-d');
        
        return $query->where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            });
    }

    /**
     * Scope a query to only include featured announcements.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include high priority announcements.
     */
    public function scopeHighPriority($query)
    {
        return $query->where('is_high_priority', true);
    }
}