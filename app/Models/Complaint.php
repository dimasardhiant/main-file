<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'against_employee_id',
        'complaint_type',
        'subject',
        'complaint_date',
        'description',
        'status',
        'documents',
        'is_anonymous',
        'assigned_to',
        'resolution_deadline',
        'investigation_notes',
        'resolution_action',
        'resolution_date',
        'follow_up_action',
        'follow_up_date',
        'feedback',
        'created_by'
    ];

    protected $casts = [
        'complaint_date' => 'date',
        'resolution_deadline' => 'date',
        'resolution_date' => 'date',
        'follow_up_date' => 'date',
        'is_anonymous' => 'boolean',
    ];

    /**
     * Get the employee who filed the complaint.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the employee against whom the complaint was filed.
     */
    public function againstEmployee()
    {
        return $this->belongsTo(User::class, 'against_employee_id');
    }

    /**
     * Get the user who is assigned to handle this complaint.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this complaint.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}