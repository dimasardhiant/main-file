<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingAttendee extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'type',
        'rsvp_status',
        'attendance_status',
        'rsvp_date',
        'decline_reason',
        'created_by'
    ];

    protected $casts = [
        'rsvp_date' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}