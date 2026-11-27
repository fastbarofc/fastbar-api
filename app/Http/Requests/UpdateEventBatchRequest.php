<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventBatchRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $data = [
            'event_id' => 'sometimes|required|exists:events,id',
            'unit_value' => 'sometimes|required|numeric|min:0',
            'guests' => 'sometimes|required|numeric',
        ];

        return $data;
    }

    public function messages(): array
    {
        return [
            'guests.required' => 'O campo convidados é obrigatório.',
            'unit_value.required' => 'O campo valor unitário é obrigatório.',
            'event_id.required' => 'O campo evento é obrigatório.',
            'event_id.exists' => 'O campo evento deve ser um tipo válido.',
            'unit_value.numeric' => 'O campo valor unitário deve ser um número',
            'unit_value.min' => 'O campo valor unitário não pode ser negativo',
            'guests.numeric' => 'O campo convidados deve conter apenas números',
        ];
    }
}
