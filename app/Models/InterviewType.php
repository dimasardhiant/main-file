<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewType extends BaseModel
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

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }
}