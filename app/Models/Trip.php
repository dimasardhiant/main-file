<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'purpose',
        'destination',
        'start_date',
        'end_date',
        'description',
        'expected_outcomes',
        'status',
        'documents',
        'advance_amount',
        'advance_status',
        'total_expenses',
        'reimbursement_status',
        'approved_by',
        'approved_at',
        'trip_report',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'advance_amount' => 'decimal:2',
        'total_expenses' => 'decimal:2',
    ];

    /**
     * Get the employee associated with this trip.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the user who approved this trip.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this trip.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the expenses for this trip.
     */
    public function expenses()
    {
        return $this->hasMany(TripExpense::class);
    }
}