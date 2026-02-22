<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'department_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}