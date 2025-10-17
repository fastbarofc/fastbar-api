<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeCollaboratorPasswordRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required'      => 'O campo senha é obrigatório.',
            'password.string'        => 'O campo senha deve ser uma string.',
            'password.min'           => 'O campo senha deve ter no mínimo 8 caracteres.',
            'password.confirmed'     => 'A confirmação de senha não corresponde.',
            'password_confirmation.required' => 'O campo confirmação de senha é obrigatório.',
            'password_confirmation.string'   => 'O campo confirmação de senha deve ser uma string.',
            'password_confirmation.min'      => 'O campo confirmação de senha deve ter no mínimo 8 caracteres.',
        ];
    }
}

