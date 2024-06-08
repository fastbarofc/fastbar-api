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
            'text' => 'nullable|string',
            'event_id' => 'nullable|numeric|exists:events,id',
            'checklist' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'text.string' => 'O campo texto deve ser uma string.',
            'event_id.numeric' => 'O campo ID do evento deve conter apenas números.',
            'event_id.exists' => 'O campo ID do evento deve ser um evento válido',
            'checklist.boolean' => 'O campo checklist deve ser um valor booleano',
        ];
    }
}
