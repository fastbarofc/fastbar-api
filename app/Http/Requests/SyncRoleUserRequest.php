<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRoleUserRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => 'required|array|min:1|max:1|exists:roles,id'
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'O campo roles é obrigatório',
            'roles.array'    => 'O campo roles deve ser um array',
            'roles.min'      => 'O usuário deve ter pelo menos uma role',
            'roles.max'      => 'O usuário pode ter apenas uma role',
            'roles.exists'   => 'O campo roles deve conter ids válidos'
        ];
    }
}