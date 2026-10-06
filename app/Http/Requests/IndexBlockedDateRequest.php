<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexBlockedDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
        ];
    }

    public function messages(): array
    {
        return [
            'start.date' => 'O campo início deve ser uma data válida.',
            'end.date' => 'O campo fim deve ser uma data válida.',
            'end.after_or_equal' => 'O campo fim deve ser uma data igual ou posterior ao início.',
        ];
    }
}
