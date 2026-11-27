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
            'description' => 'sometimes|required|string|max:191|unique:office_events,description,' . $this->route('office_event')->id,
            'daily'       => 'sometimes|required|boolean',
            'coordinator' => 'sometimes|required|boolean',
            'date'        => 'sometimes|required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'O campo data é obrigatório.',
            'coordinator.required' => 'O campo coordenador é obrigatório.',
            'daily.required' => 'O campo diária é obrigatório.',
            'description.required' => 'O campo descrição é obrigatório.',
            'description.string'  => 'O campo descrição deve ser uma string.',
            'description.max'     => 'O campo descrição deve ter no máximo 191 caracteres.',
            'description.unique'  => 'Essa descrição já está em uso.',
            'daily.boolean'       => 'O campo diario precisa ser booleano.',
            'coordinator.boolean' => 'O campo cordanador precisa ser booleano.',
            'date.boolean'        => 'O campo data precisa ser booleano.',
        ];
    }
}
