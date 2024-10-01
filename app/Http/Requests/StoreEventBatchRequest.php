<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventBatchRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $data = [
            'event_id' => 'required|exists:events,id',
            'unit_value' => 'required|numeric|min:0',
            'guests' => 'required|numeric',
            'discount' => 'nullable|numeric|min:0',
            'unit_discount' => 'nullable|numeric|min:0',
        ];

        return $data;
    }

    public function messages(): array
    {
        return [
            'event_id.required' => 'O campo evento é obrigatório',
            'event_id.exists' => 'O campo evento deve ser um tipo válido.',
            'unit_value.required' => 'O campo valor unitário é obrigatório',
            'unit_value.numeric' => 'O campo valor unitário deve ser um número',
            'unit_value.min' => 'O campo valor unitário não pode ser negativo',
            'discount.numeric' => 'O campo desconto deve ser um número',
            'discount.min' => 'O campo desconto não pode ser negativo',
            'unit_discount.numeric' => 'O campo desconto unitário deve ser um número',
            'unit_discount.min' => 'O campo desconto unitário não pode ser negativo',
            'guests.required' => 'O campo convidados é obrigatório',
            'guests.numeric' => 'O campo convidados deve conter apenas números',
        ];
    }
}
