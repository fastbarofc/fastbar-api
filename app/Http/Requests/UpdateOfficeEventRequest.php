<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfficeEventRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'nullable|string|max:255|unique:office_events,description,' . $this->route('office_event')->id,
            'daily'       => 'nullable|boolean',
            'coordinator' => 'nullable|boolean',
            'date'        => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'description.string'  => 'O campo descrição deve ser uma string.',
            'description.max'     => 'O campo descrição deve ter no máximo 255 caracteres.',
            'description.unique'  => 'Essa descrição já está em uso.',
            'daily.boolean'       => 'O campo diario precisa ser booleano.',
            'coordinator.boolean' => 'O campo cordanador precisa ser booleano.',
            'date.boolean'        => 'O campo data precisa ser booleano.',
        ];
    }
}
