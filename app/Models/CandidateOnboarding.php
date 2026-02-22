<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateOnboarding extends BaseModel
{
    use HasFactory;

    protected $table = 'candidate_onboarding';

    protected $fillable = [
        'candidate_id',
        'employee_id',
        'checklist_id',
        'start_date',
        'buddy_employee_id',
        'status',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function checklist()
    {
        return $this->belongsTo(OnboardingChecklist::class);
    }

    public function buddyEmployee()
    {
        return $this->belongsTo(User::class, 'buddy_employee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}