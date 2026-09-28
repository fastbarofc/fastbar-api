<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:partners',
            'responsible' => 'nullable|string|max:255',
            'email'       => 'nullable|email|unique:partners',
            'phone_1'     => 'required|string|max:20',
            'phone_2'     => 'nullable|string|max:20',
        ];
    }

    public function messages(): array 
    {
        return [
            'name.required'           => 'O campo nome é obrigatório.',
            'name.string'             => 'O campo nome deve ser uma string.',
            'name.max'                => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'                => 'Esse nome já está sendo utiliado',
            'responsible.string'             => 'O campo responsável deve ser uma string.',
            'responsible.max'                => 'O campo responsável deve ter no máximo 255 caracteres.',
            'email.email'             => 'O campo e-mail deve ser um e-mail válido.',
            'email.unique'            => 'Esse E-mail já está cadastrado.',
            'phone_1.required'        => 'O campo telefone 1 é obrigatório.',
            'phone_1.string'          => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'             => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_2.string'          => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'             => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
        ];
    }
}
