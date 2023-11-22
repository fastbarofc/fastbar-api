<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDecoratorRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:255',
            'email'      => 'required|email',
            'responsible'=> 'nullable|string|max:255',
            'phone_1'    => 'required|string|min:10|max:20',
            'phone_2'    => 'nullable|string|min:10|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.string'   => 'O campo nome deve ser um texto.',
            'name.max'      => 'O campo nome deve ter no máximo 255 caracteres.',
            'email.required' => 'O campo email é obrigatório.',
            'email.email'   => 'O campo email deve ser um email real.',
            'responsible.string'   => 'O campo responsável deve ser um texto.',
            'responsible.max'      => 'O campo responsável deve ter no máximo 255 caracteres.',
            'phone_1.required'        => 'O campo telefone 1 é obrigatório.',
            'phone_1.string'          => 'O campo telefone 1 deve ser uma string.',
            'phone_1.min'             => 'O campo telefone 1 deve ter no mínimo 10 caracteres.',
            'phone_1.max'             => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_2.string'          => 'O campo telefone 2 deve ser uma string.',
            'phone_2.min'             => 'O campo telefone 2 deve ter no mínimo 10 caracteres.',
            'phone_2.max'             => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
        ];
    }

}
