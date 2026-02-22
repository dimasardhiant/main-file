<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'expense_type',
        'expense_date',
        'amount',
        'currency',
        'description',
        'receipt',
        'is_reimbursable',
        'status',
        'created_by'
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'is_reimbursable' => 'boolean',
    ];

    /**
     * Get the trip that owns this expense.
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user who created this expense.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}