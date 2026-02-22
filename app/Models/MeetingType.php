<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingType extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'color',
        'default_duration',
        'status',
        'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'type_id');
    }
}