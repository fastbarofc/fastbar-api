<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventGiftRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'string|max:255|unique:event_gifts,description,' . $this->route('event_gift')->id,
            'quantity'      => 'numeric|min:0',
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
