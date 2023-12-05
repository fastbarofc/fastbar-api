<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollaboratorIncidentRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'collaborator_id' => 'required|integer|exists:collaborators,id',
            'description'     => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'collaborator_id.required' => 'O campo ID do colaborador é obrigatório.',
            'collaborator_id.integer' => 'O campo ID do colaborador deve ser um número.',
            'collaborator_id.exists'   => 'O campo ID do colaborador deve ser um ID válido.',
            'description.required'     => 'O campo descrição é obrigatório.',
            'description.string'       => 'O campo descrição deve ser uma string.',
        ];
    }
}
