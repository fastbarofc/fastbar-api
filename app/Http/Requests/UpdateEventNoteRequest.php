<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventNoteRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => 'sometimes|required|string',
            'event_id' => 'sometimes|required|numeric|exists:events,id',
            'checklist' => 'sometimes|required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'checklist.required' => 'O campo checklist é obrigatório.',
            'event_id.required' => 'O campo evento é obrigatório.',
            'text.required' => 'O campo texto é obrigatório.',
            'text.string' => 'O campo texto deve ser uma string.',
            'event_id.numeric' => 'O campo ID do evento deve conter apenas números.',
            'event_id.exists' => 'O campo ID do evento deve ser um evento válido',
            'checklist.boolean' => 'O campo checklist deve ser um valor booleano',
        ];
    }
}
