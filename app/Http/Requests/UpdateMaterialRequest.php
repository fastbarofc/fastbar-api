<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'nullable|string|max:255|unique:materials,description,' . $this->route('material')->id,
            'quantity'      => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'description.string' => 'O campo descrição deve ser uma string.',
            'description.max'    => 'O campo descrição deve ter no máximo 255 caracteres.',
            'description.unique' => 'Essa descrição já está sendo utiliada',
            'quantity.numeric'     => 'O campo quantidade deve ser conter apenas números.',
            'quantity.min'         => 'O campo quantidade não pode ser negativo',
        ];
    }
}
