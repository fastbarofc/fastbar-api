<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:191|unique:roles,name,' . ($this->route('role')?->id ?? $this->id)
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'   => 'O campo nome deve ser uma string',
            'name.max'      => 'O campo nome deve ter no máximo 191 caracteres',
            'name.unique'   => 'O nome informado já está em uso'
        ];
    }
}