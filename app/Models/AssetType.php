<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetType extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by'
    ];

    /**
     * Get the user who created this asset type.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the assets of this type.
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}