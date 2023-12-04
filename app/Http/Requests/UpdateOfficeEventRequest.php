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
            'description' => 'string|max:255|unique:office_events,description,' . $this->route('office_event')->id,
            'daily'       => 'boolean',
            'coordinator' => 'boolean',
            'date'        => 'boolean',
        ];
    }

    public function messaghes(): array
    {
        return [
            'description.string'  => 'O campo dercrição deve ser uma string.',
            'description.max'     => 'O campo dercrição deve ter no máximo 255 caracteres.',
            'description.unique'  => 'Essa descrição já está em uso.',
            'daily.boolean'       => 'O campo diario precisa ser booleano.',
            'coordinator.boolean' => 'O campo cordanador precisa ser booleano.',
            'date.boolean'        => 'O campo data precisa ser booleano.',
        ];
    }
}
