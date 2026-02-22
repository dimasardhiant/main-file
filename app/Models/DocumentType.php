<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_required',
        'description',
        'created_by',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }
}