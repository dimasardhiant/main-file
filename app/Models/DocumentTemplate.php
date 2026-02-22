<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'template_content',
        'placeholders',
        'default_values',
        'is_default',
        'file_format',
        'status',
        'created_by'
    ];

    protected $casts = [
        'placeholders' => 'array',
        'default_values' => 'array',
        'is_default' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function generateDocument($values = [])
    {
        $content = $this->template_content;
        
        // Merge default values with provided values
        $allValues = array_merge($this->default_values ?? [], $values);
        
        // Replace placeholders
        foreach ($allValues as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        return $content;
    }

    public function getPlaceholderList()
    {
        return $this->placeholders ?? [];
    }
}