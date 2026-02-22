<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'max_days_per_year',
        'is_paid',
        'color',
        'status',
        'created_by'
    ];

    protected $casts = [
        'is_paid' => 'boolean',
    ];

    /**
     * Get the user who created the leave type.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}