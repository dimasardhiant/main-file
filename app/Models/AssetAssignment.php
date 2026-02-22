<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'employee_id',
        'checkout_date',
        'expected_return_date',
        'checkin_date',
        'checkout_condition',
        'checkin_condition',
        'notes',
        'is_acknowledged',
        'acknowledged_at',
        'assigned_by',
        'received_by'
    ];

    protected $casts = [
        'checkout_date' => 'date',
        'expected_return_date' => 'date',
        'checkin_date' => 'date',
        'acknowledged_at' => 'datetime',
        'is_acknowledged' => 'boolean',
    ];

    /**
     * Get the asset that was assigned.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the employee to whom the asset was assigned.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the user who assigned the asset.
     */
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user who received the asset back.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Scope a query to only include active assignments.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('checkin_date');
    }

    /**
     * Scope a query to only include completed assignments.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('checkin_date');
    }

    /**
     * Scope a query to only include overdue assignments.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNull('checkin_date')
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now());
    }
}