<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type_id',
        'room_id',
        'meeting_date',
        'start_time',
        'end_time',
        'duration',
        'agenda',
        'status',
        'recurrence',
        'recurrence_end_date',
        'organizer_id',
        'created_by'
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'recurrence_end_date' => 'date',
    ];

    public function type()
    {
        return $this->belongsTo(MeetingType::class);
    }

    public function room()
    {
        return $this->belongsTo(MeetingRoom::class);
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees()
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    public function minutes()
    {
        return $this->hasMany(MeetingMinute::class);
    }

    public function actionItems()
    {
        return $this->hasMany(ActionItem::class);
    }
}