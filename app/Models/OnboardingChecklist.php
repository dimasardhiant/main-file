<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingChecklist extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_default',
        'status',
        'created_by'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checklistItems()
    {
        return $this->hasMany(ChecklistItem::class, 'checklist_id');
    }

    public function candidateOnboardings()
    {
        return $this->hasMany(CandidateOnboarding::class, 'checklist_id');
    }


}