<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQualificationRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description'      => 'sometimes|required|string|max:191|unique:qualifications,description,' . $this->qualification->id,
        ];
    }


    public function messages(): array
    {
        return [
            'description.required' => 'O campo descrição é obrigatório.',
            'description.string'  => 'O campo descrição deve ser uma string.',
            'description.max'     => 'O campo descrição deve ter no maximo 191 caracteres.',
            'description.unique'  => 'O nome informado já está em uso.',
        ];
    }
}
