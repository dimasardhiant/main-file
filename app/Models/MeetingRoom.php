<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingRoom extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'location',
        'capacity',
        'equipment',
        'booking_url',
        'status',
        'created_by'
    ];

    protected $casts = [
        'equipment' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'room_id');
    }
}