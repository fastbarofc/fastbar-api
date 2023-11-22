<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginAuthRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;


    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email',
            'password' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'O email é obrigatório.',
            'email.email'       => 'O email deve ser um endereço de email válido.',
            'password.required' => 'A senha é obrigatória.'
        ];
    }
}