<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i' . ($this->filled('start_time') ? '|after_or_equal:start_time' : ''),
            'location' => 'nullable|string|max:255',
            'event_id' => 'nullable|integer|exists:events,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O título do compromisso é obrigatório.',
            'title.max' => 'O título pode ter no máximo 255 caracteres.',
            'date.required' => 'Informe a data do compromisso.',
            'date.date' => 'A data deve ser válida.',
            'start_time.date_format' => 'O horário de início deve estar no formato 00:00.',
            'end_time.date_format' => 'O horário de fim deve estar no formato 00:00.',
            'end_time.after_or_equal' => 'O horário de fim deve ser depois do início.',
            'event_id.exists' => 'O evento escolhido não existe.',
            'user_ids.*.exists' => 'Um dos participantes escolhidos não existe.',
        ];
    }
}
