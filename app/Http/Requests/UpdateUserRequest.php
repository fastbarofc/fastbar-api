<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $data = [
            'name'  => 'filled|string|max:255',
            'email' => 'filled|email|max:255|unique:users,email,' . $this->id,
            'active' => 'nullable|boolean',
        ];

        if ($this->has('password')) {
            $data['password'] = 'filled|min:8';
            $data['password_confirmation'] = 'string|same:password|required_with:password';
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'name.max'                            => 'O nome deve ter no máximo 255 caracteres.',
            'name.filled'                         => 'O nome é obrigatório.',
            'email.email'                         => 'O email deve ser um endereço de email válido.',
            'email.max'                           => 'O email deve ter no máximo 255 caracteres.',
            'email.unique'                        => 'O email informado já está em uso.',
            'email.filled'                        => 'O email é obrigatório.',
            'password.min'                        => 'A senha deve ter no mínimo 8 caracteres.',
            'password.filled'                     => 'A senha é obrigatória.',
            'password_confirmation.min'           => 'A confirmação de senha deve ter no mínimo 8 caracteres.',
            'password_confirmation.same'          => 'As senhas não conferem.',
            'password_confirmation.required_with' => 'A confirmação de senha é obrigatória.',
            'active.boolean'                      => 'O campo ativo deve ser um booleano.',
        ];
    }
}