<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnouncementView extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id',
        'employee_id',
        'viewed_at'
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the announcement that was viewed.
     */
    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    /**
     * Get the employee who viewed the announcement.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}