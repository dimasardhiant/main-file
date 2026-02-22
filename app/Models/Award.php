<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Award extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'award_type_id',
        'award_date',
        'gift',
        'monetary_value',
        'description',
        'certificate',
        'photo',
        'created_by'
    ];

    /**
     * Get the employee that received this award.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the award type of this award.
     */
    public function awardType()
    {
        return $this->belongsTo(AwardType::class);
    }

    /**
     * Get the user who created this award.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}