<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventNoteRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => 'required|string',
            'event_id' => 'required|numeric|exists:events,id',
            'checklist' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'O campo texto é obrigatório.',
            'text.string' => 'O campo texto deve ser uma string.',
            'event_id.required' => 'O campo ID do evento é obrigatório.',
            'event_id.numeric' => 'O campo ID do evento deve conter apenas números.',
            'event_id.exists' => 'O campo ID do evento deve ser um evento válido',
            'checklist.boolean' => 'O campo checklist deve ser um valor booleano',
        ];
    }
}
