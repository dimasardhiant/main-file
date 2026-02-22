<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingType extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'branch_id',
        'created_by'
    ];

    /**
     * Get the user who created this training type.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the branch this training type belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the departments associated with this training type.
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'training_type_department');
    }

    /**
     * Get the training programs of this type.
     */
    public function trainingPrograms()
    {
        return $this->hasMany(TrainingProgram::class);
    }
}