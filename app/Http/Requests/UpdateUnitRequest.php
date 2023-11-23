<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'string|max:255|unique:units,description,' . $this->route('unit')->id,
        ];
    }

    public function messages(): array
    {
        return [
            'description.string'          => 'O campo descrição deve ser uma string.',
            'description.max'             => 'O campo descrição deve ter no máximo 255 caracteres.',
            'description.unique'          => 'Esse descrição já está sendo utiliada',
        ];
    }
}
