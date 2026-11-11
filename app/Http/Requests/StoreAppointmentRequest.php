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
            'summary' => 'nullable|string',
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i' . ($this->filled('start_time') ? '|after_or_equal:start_time' : ''),
            'location' => 'nullable|string|max:255',
            'event_id' => 'nullable|integer|exists:events,id',
            'client_id' => 'nullable|integer|exists:clients,id',
            'partner_id' => 'nullable|integer|exists:partners,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'participants' => 'nullable|array',
            'participants.*.type' => 'required|string|in:client,partner,supplier,collaborator',
            'participants.*.id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1] ?? null;
                    $type = $this->input("participants.$index.type");
                    $class = \App\Models\AppointmentParticipant::TYPES[$type] ?? null;
                    if ($class && !$class::withoutGlobalScopes()->whereKey($value)->exists()) {
                        $fail('Um dos participantes escolhidos não existe.');
                    }
                },
            ],
            'meeting_url' => 'nullable|string|max:255',
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
            'client_id.exists' => 'O cliente escolhido não existe.',
            'partner_id.exists' => 'O parceiro escolhido não existe.',
            'supplier_id.exists' => 'O fornecedor escolhido não existe.',
            'user_ids.*.exists' => 'Um dos participantes escolhidos não existe.',
        ];
    }
}
