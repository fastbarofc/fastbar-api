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
        ];
    }
}
