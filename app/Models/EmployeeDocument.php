<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'document_type_id',
        'file_path',
        'expiry_date',
        'verification_status',
        'notes',
        'created_by'
    ];

    /**
     * Get the employee that owns the document.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the document type of this document.
     */
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * Get the user who created the document.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}