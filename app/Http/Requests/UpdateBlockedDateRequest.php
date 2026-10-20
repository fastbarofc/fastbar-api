<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlockedDateRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        $blockedDate = $this->route('blocked_date'); 
        return [
            'date'        => ['bail', 'nullable', 'date', Rule::unique('blocked_dates', 'date')->ignore($blockedDate)->whereNull('deleted_at')],
            'description' => 'nullable|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'date.date'          => 'O campo data deve ser uma data válida.',
            'date.unique'        => 'Já existe uma data bloqueada com essa data.',
            'description.string' => 'O campo descrição deve ser uma string.',
            'description.max'    => 'O campo descrição deve ter no máximo 255 caracteres.',
        ];
    }
}
