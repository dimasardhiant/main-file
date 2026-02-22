<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'checklist_id',
        'task_name',
        'description',
        'category',
        'assigned_to_role',
        'due_day',
        'is_required',
        'status',
        'created_by'
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function checklist()
    {
        return $this->belongsTo(OnboardingChecklist::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}