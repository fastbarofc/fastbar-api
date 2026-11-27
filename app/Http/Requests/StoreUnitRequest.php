<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:191|unique:units',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required'        => 'O campo descrição é obrigatório.',
            'description.string'          => 'O campo descrição deve ser uma string.',
            'description.max'             => 'O campo descrição deve ter no máximo 191 caracteres.',
            'description.unique'          => 'Esse descrição já está sendo utiliada',
        ];
    }
}
