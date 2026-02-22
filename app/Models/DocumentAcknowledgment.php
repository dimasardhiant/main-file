<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DocumentAcknowledgment extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'user_id',
        'status',
        'acknowledged_at',
        'due_date',
        'acknowledgment_note',
        'ip_address',
        'user_agent',
        'assigned_by',
        'assigned_at',
        'created_by'
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'due_date' => 'date',
        'assigned_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(HrDocument::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'Pending' && $this->due_date && $this->due_date < Carbon::today();
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) return 0;
        return Carbon::today()->diffInDays($this->due_date);
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->status !== 'Pending' || !$this->due_date) return null;
        return Carbon::today()->diffInDays($this->due_date, false);
    }
}