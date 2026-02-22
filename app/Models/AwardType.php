<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AwardType extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by'
    ];

    /**
     * Get the awards for this award type.
     */
    public function awards()
    {
        return $this->hasMany(Award::class);
    }

    /**
     * Get the user who created this award type.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}