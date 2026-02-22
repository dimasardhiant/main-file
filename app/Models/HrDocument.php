<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HrDocument extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'version',
        'status',
        'effective_date',
        'expiry_date',
        'requires_acknowledgment',
        'download_count',
        'uploaded_by',
        'approved_by',
        'approved_at',
        'created_by'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'approved_at' => 'datetime',
        'requires_acknowledgment' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accessControls()
    {
        return $this->hasMany(DocumentAccessControl::class, 'document_id');
    }

    public function acknowledgments()
    {
        return $this->hasMany(DocumentAcknowledgment::class, 'document_id');
    }

    public function getIsExpiredAttribute()
    {
        return $this->expiry_date && $this->expiry_date < Carbon::today();
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}