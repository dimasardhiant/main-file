<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingMinute extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'topic',
        'content',
        'type',
        'recorded_by',
        'recorded_at',
        'created_by'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}