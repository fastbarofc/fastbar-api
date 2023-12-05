<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollaboratorIncidentRequest extends FormRequest
{    
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'collaborator_id' => 'integer|exists:collaborators,id,' . $this->route('collaborator')->id,
            'description'     => 'string',
        ];
    }

    public function messages()
    {
        return [
            'collaborator_id.integer' => 'O campo ID do colaborador deve ser um número.',
            'collaborator_id.exists'   => 'O campo ID do colaborador deve ser um ID válido.',
            'description.string'       => 'O campo descrição deve ser uma string.',
        ];
    }
}
