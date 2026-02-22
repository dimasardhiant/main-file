<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeGoal extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'employee_id',
        'goal_type_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'target',
        'progress',
        'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress' => 'integer',
    ];

    /**
     * Get the company that owns this goal.
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the employee that this goal belongs to.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the goal type that this goal belongs to.
     */
    public function goalType()
    {
        return $this->belongsTo(GoalType::class, 'goal_type_id');
    }
}