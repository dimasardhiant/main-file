<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaDirectory extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'created_by',
    ];
}
