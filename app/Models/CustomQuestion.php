<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomQuestion extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'question',
        'required',
        'created_by',
    ];

    protected $casts = [
        'required' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}