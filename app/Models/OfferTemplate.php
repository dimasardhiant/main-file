<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferTemplate extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'template_content',
        'variables',
        'status',
        'created_by'
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


}