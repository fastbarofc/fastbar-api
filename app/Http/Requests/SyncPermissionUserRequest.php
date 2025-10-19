<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncPermissionUserRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;


    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => 'required|array|exists:permissions,id'
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'O campo permissões é obrigatório',
            'permissions.array'    => 'O campo permissões deve ser um array',
            'permissions.exists'   => 'O campo permissões deve conter ids válidos'
        ];
    }
}