<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKitRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kit_id'          => 'nullable|numeric',
            'name'         => 'string|max:255|unique:kits,name,' . $this->route('kit')->id,
            'themed_table' => 'boolean',
            'optional'     => 'boolean',
            'value'        => 'numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'kit_id.numeric'         => 'O campo ID do kit deve conter apenas números.',
            'name.string'           => 'O campo nome deve ser um texto.',
            'name.max'              => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'           => 'O nome informado já está em uso.',
            'value.numeric'         => 'O valor precisa ser um número',
            'value.min'             => 'O valor não pode ser negativo',
        ];
    }
}
