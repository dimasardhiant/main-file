<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        
        if ($this->isMethod('POST')) {
            return $user->hasPermissionTo('create-branch');
        }
        
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user->hasPermissionTo('edit-branch');
        }
        
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = Auth::user();
        $createdBy = $user->type === 'company' ? $user->id : $user->created_by;
        
        $rules = [
            'name' => 'required|string|max:255',
            'location' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'branch_head' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ];
        
        // For create request
        if ($this->isMethod('POST')) {
            $rules['code'] = [
                'required',
                'string',
                'max:50',
                Rule::unique('branches')->where(function ($query) use ($createdBy) {
                    return $query->where('created_by', $createdBy);
                })
            ];
        }
        
        // For update request
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['code'] = [
                'required',
                'string',
                'max:50',
                Rule::unique('branches')->where(function ($query) use ($createdBy) {
                    return $query->where('created_by', $createdBy);
                })->ignore($this->branch->id)
            ];
        }
        
        return $rules;
    }
}