<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlockedDateRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date|unique:blocked_dates,date,' . $this->blockedDate->id,
            'description' => 'required|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'O campo data é obrigatório.',
            'date.date' => 'O campo data deve ser uma data válida.',
            'date.unique' => 'Já existe uma data bloqueada com essa data.',
            'description.required' => 'O campo descrição é obrigatório.',
            'description.string' => 'O campo descrição deve ser uma string.',
            'description.max' => 'O campo descrição deve ter no máximo 255 caracteres.',
        ];
    }
}
