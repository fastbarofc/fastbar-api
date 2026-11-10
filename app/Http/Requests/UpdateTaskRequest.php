<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'sector_id' => 'nullable|integer|exists:sectors,id',
            'event_id' => 'nullable|integer|exists:events,id',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|date_format:H:i',
            'recurrence' => ['nullable', Rule::in(Task::RECURRENCES)],
            'priority' => ['nullable', Rule::in(Task::PRIORITIES)],
            'status' => ['nullable', Rule::in(Task::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O título da tarefa é obrigatório.',
            'title.max' => 'O título pode ter no máximo 255 caracteres.',
            'user_ids.*.exists' => 'Um dos responsáveis escolhidos não existe.',
            'sector_id.exists' => 'A área escolhida não existe.',
            'due_time.date_format' => 'O horário deve estar no formato 00:00.',
            'recurrence.in' => 'Repetição inválida.',
            'event_id.exists' => 'O evento escolhido não existe.',
            'due_date.date' => 'O prazo deve ser uma data válida.',
            'priority.in' => 'Prioridade inválida.',
            'status.in' => 'Situação inválida.',
        ];
    }
}
