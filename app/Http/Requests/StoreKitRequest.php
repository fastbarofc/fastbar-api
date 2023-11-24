<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKitRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kit_id'       => 'nullable|numeric',
            'name'         => 'required|string|max:255|unique:kits',
            'themed_table' => 'required|boolean',
            'optional'     => 'required|boolean',
            'value'        => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'kit_id.numeric'        => 'O campo ID do kit deve conter apenas números.',
            'name.required'         => 'O campo nome é obrigatório.',
            'name.string'           => 'O campo nome deve ser um texto.',
            'name.max'              => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'           => 'O nome informado já está em uso.',
            'themed_table.required' => 'O campo mesa temática é obrigatório.',
            'optional.required'     => 'O campo kit opcional é obrigatório.',
            'value.required'        => 'O campo valor é obrigatório.',
            'value.numeric'         => 'O valor precisa ser um número',
            'value.min'             => 'O valor n"ao pode ser negativo',
        ];
    }
}
