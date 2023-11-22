<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterAuthRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => 'required|string',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8',
            'password_confirmation' => 'required|string|same:password'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                  => 'O nome é obrigatório.',
            'email.required'                 => 'O email é obrigatório.',
            'email.email'                    => 'O email deve ser um endereço de email válido.',
            'email.unique'                   => 'O email informado já está em uso.',
            'password.required'              => 'A senha é obrigatório.',
            'password.min'                   => 'A senha deve ter no mínimo 8 caracteres.',
            'password_confirmation.required' => 'A confirmação de senha é obrigatória.',
            'password_confirmation.same'     => 'As senhas não conferem.'
        ];
    }
}