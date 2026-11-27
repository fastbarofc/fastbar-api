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
            'description' => 'sometimes|required|string|max:191|unique:event_gifts,description,' . $this->route('event_gift')->id,
            'event_id'    => 'nullable|numeric|exists:events,id',
            'quantity'    => 'sometimes|required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'O campo quantidade é obrigatório.',
            'description.required' => 'O campo descrição é obrigatório.',
            'description.string' => 'O campo descrição deve ser uma string.',
            'description.max'    => 'O campo descrição deve ter no máximo 191 caracteres.',
            'description.unique' => 'Essa descrição já está sendo utiliada',
            'event_id.numeric'   => 'O campo ID do evento deve conter apenas números.',
            'event_id.exists'    => 'O campo ID do evento deve ser um evento válida',
            'quantity.numeric'   => 'O campo quantidade deve ser conter apenas números.',
            'quantity.min'       => 'O campo quantidade não pode ser negativo',
        ];
    }
}
