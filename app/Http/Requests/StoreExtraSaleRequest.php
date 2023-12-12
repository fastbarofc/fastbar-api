<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtraSaleRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:255|unique:extra_sales,description',
            'unit_id'     => 'nullable|numeric|exists:units,id',
            'quantity'    => 'required|numeric|min:0',
            'value_unit'  => 'required|string',
            'event_id'    => 'required|numeric|exists:events,id',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'O campo descrição é obrigatório.',
            'description.string'   => 'O campo descrição deve ser uma string.',
            'description.max'      => 'O campo descrição deve ter no máximo 255 caracteres.',
            'description.unique'   => 'Essa descrição já está em uso.',
            'unit_id.numeric'      => 'O campo ID da unidade deve conter apenas números.',
            'unit_id.exists'       => 'O campo ID da unidade deve ser uma unidade válida',
            'quantity.required'    => 'O campo quantidade é obrigatório.',
            'quantity.numeric'     => 'O campo quantidade deve ser conter apenas números.',
            'quantity.min'         => 'O campo quantidade não pode ser negativo',
            'value_unit.required'  => 'O campo valor unitário é obrigatório',
            'value_unit.string'    => 'O campo valor unitário deve ser uma string',
            'event_id.required'    => 'O campo ID do evento é obrigatório.',
            'event_id.numeric'     => 'O campo ID do evento deve conter apenas números.',
            'event_id.exists'      => 'O campo ID do evento deve ser um evento válida',
        ];
    }
}
