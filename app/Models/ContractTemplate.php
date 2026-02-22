<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'contract_type_id',
        'template_content',
        'variables',
        'clauses',
        'is_default',
        'status',
        'created_by'
    ];

    protected $casts = [
        'variables' => 'array',
        'clauses' => 'array',
        'is_default' => 'boolean',
    ];

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function generateContract($variables = [])
    {
        $content = $this->template_content;
        
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        return $content;
    }
}