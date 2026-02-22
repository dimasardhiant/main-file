<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'phone',
        'email',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function company()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}