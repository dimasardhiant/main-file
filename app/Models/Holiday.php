<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'category',
        'description',
        'is_recurring',
        'is_paid',
        'is_half_day',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_recurring' => 'boolean',
        'is_paid' => 'boolean',
        'is_half_day' => 'boolean',
    ];

    /**
     * Get the user who created this holiday.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the branches associated with this holiday.
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'holiday_branch');
    }

    /**
     * Check if the holiday is a multi-day holiday.
     */
    public function isMultiDay()
    {
        return $this->end_date && $this->end_date->ne($this->start_date);
    }

    /**
     * Get the duration of the holiday in days.
     */
    public function getDurationInDays()
    {
        if (!$this->end_date || $this->end_date->eq($this->start_date)) {
            return 1;
        }

        return $this->start_date->diffInDays($this->end_date) + 1;
    }
}