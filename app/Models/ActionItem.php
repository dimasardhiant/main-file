<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ActionItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'title',
        'description',
        'assigned_to',
        'due_date',
        'priority',
        'status',
        'progress_percentage',
        'notes',
        'completed_date',
        'created_by'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_date' => 'date',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getIsOverdueAttribute()
    {
        return $this->status !== 'Completed' && $this->due_date < Carbon::today();
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->status === 'Completed') return null;
        return Carbon::today()->diffInDays($this->due_date, false);
    }
}