<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventGiftRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:255|unique:event_gifts',
            'event_id'    => 'required|numeric|exists:events,id',
            'quantity'    => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'O campo descrição é obrigatório.',
            'description.string'   => 'O campo descrição deve ser uma string.',
            'description.max'      => 'O campo descrição deve ter no máximo 255 caracteres.',
            'description.unique'   => 'Esse descrição já está sendo utiliada',
            'event_id.required'    => 'O campo ID do evento é obrigatório.',
            'event_id.numeric'     => 'O campo ID do evento deve conter apenas números.',
            'event_id.exists'      => 'O campo ID do evento deve ser um evento válida',
            'quantity.required'    => 'O campo quantidade é obrigatório.',
            'quantity.numeric'     => 'O campo quantidade deve ser conter apenas números.',
            'quantity.min'         => 'O campo quantidade não pode ser negativo',
        ];
    }
}
