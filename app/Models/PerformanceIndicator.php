<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceIndicator extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'measurement_unit',
        'target_value',
        'status',
        'created_by'
    ];

    /**
     * Get the user who created this indicator.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the category that this indicator belongs to.
     */
    public function category()
    {
        return $this->belongsTo(PerformanceIndicatorCategory::class, 'category_id');
    }
}