<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfficeEventRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;
    
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:255|unique:office_events,description',
            'daily'       => 'required|boolean',
            'coordinator' => 'required|boolean',
            'date'        => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'O campo dercrição é obrigatório.',
            'description.string'   => 'O campo dercrição deve ser uma string.',
            'description.max'      => 'O campo dercrição deve ter no máximo 255 caracteres.',
            'description.unique'   => 'Essa descrição já está em uso.',
            'daily.required'       => 'O campo diária é obrigatório.',
            'coordinator.required' => 'O campo cordenador é obrigatório.',
            'date.required'        => 'O campo data é obrigatório.',
        ];
    }
}
