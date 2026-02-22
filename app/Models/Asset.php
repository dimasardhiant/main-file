<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'asset_type_id',
        'serial_number',
        'asset_code',
        'purchase_date',
        'purchase_cost',
        'status',
        'condition',
        'description',
        'location',
        'supplier',
        'warranty_info',
        'warranty_expiry_date',
        'images',
        'documents',
        'qr_code',
        'created_by'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry_date' => 'date',
        'purchase_cost' => 'decimal:2',
    ];

    /**
     * Get the asset type of this asset.
     */
    public function assetType()
    {
        return $this->belongsTo(AssetType::class);
    }

    /**
     * Get the user who created this asset.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the assignments for this asset.
     */
    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    /**
     * Get the current assignment for this asset.
     */
    public function currentAssignment()
    {
        return $this->hasOne(AssetAssignment::class)->whereNull('checkin_date')->latest();
    }

    /**
     * Get the maintenances for this asset.
     */
    public function maintenances()
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    /**
     * Get the depreciation for this asset.
     */
    public function depreciation()
    {
        return $this->hasOne(AssetDepreciation::class);
    }

    /**
     * Scope a query to only include available assets.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope a query to only include assigned assets.
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    /**
     * Scope a query to only include assets under maintenance.
     */
    public function scopeUnderMaintenance($query)
    {
        return $query->where('status', 'under_maintenance');
    }

    /**
     * Scope a query to only include disposed assets.
     */
    public function scopeDisposed($query)
    {
        return $query->where('status', 'disposed');
    }
}