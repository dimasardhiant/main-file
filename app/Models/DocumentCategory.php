<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'color',
        'icon',
        'sort_order',
        'is_mandatory',
        'status',
        'created_by'
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents()
    {
        return $this->hasMany(HrDocument::class, 'category_id');
    }
}