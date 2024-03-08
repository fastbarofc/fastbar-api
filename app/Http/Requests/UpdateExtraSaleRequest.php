<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExtraSaleRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;
    
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'nullable|string|max:255|unique:extra_sales,description,' . $this->route('extra_sale')->id,
            'unit_id'     => 'nullable|numeric|exists:units,id',
            'quantity'    => 'nullable|numeric|min:0',
            'unit_value'  => 'nullable|string',
            'event_id'    => 'nullable|numeric|exists:events,id',
        ];
    }

    public function messages(): array
    {
        return [
            'description.string'   => 'O campo descrição deve ser uma string.',
            'description.max'      => 'O campo descrição deve ter no máximo 255 caracteres.',
            'description.unique'   => 'Essa descrição já está em uso.',
            'unit_id.numeric'      => 'O campo ID da unidade deve conter apenas números.',
            'unit_id.exists'      => 'O campo ID da unidade deve ser uma unidade válida',
            'quantity.numeric'     => 'O campo quantidade deve ser conter apenas números.',
            'quantity.min'         => 'O campo quantidade não pode ser negativo',
            'unit_value.string'    => 'O campo valor unitário deve ser uma string',
            'event_id.numeric'      => 'O campo ID do evento deve conter apenas números.',
            'event_id.exists'      => 'O campo ID do evento deve ser um evento válida',
        ];
    }
}
