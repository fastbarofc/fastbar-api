<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'string|max:255|unique:partners,name,' . $this->route('partner')->id,
            'responsible' => 'string|max:255',
            'email'       => 'email|unique:partners,email,' . $this->route('partner')->id,
            'phone_1'     => 'string|max:20|celular_com_ddd',
            'phone_2'     => 'string|max:20|celular_com_ddd',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'             => 'O campo nome deve ser uma string.',
            'name.max'                => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'             => 'Esse nome já está sendo utiliado',
            'responsible.string'      => 'O campo responsável deve ser uma string.',
            'responsible.max'         => 'O campo responsável deve ter no máximo 255 caracteres.',
            'email.email'             => 'O campo e-mail deve ser um e-mail válido.',
            'email.unique'            => 'Esse E-mail já está cadastrado.',
            'phone_1.string'          => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'             => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_1.celular_com_ddd' => 'O campo telefone 1 deve ser um telefone válido.',
            'phone_2.string'          => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'             => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'phone_2.celular_com_ddd' => 'O campo telefone 2 deve ser um telefone válido.',
        ];
    }
}
