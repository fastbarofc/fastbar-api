<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

class DetachOfficeCollaboratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => 'required|integer|exists:events,id',
            'collaborator_id' => 'required|integer|exists:collaborators,id',
        ];
    }

    public function messages(): array
    {
        return [
            'event_id.required' => 'Informe o evento da escalação.',
            'event_id.exists' => 'O evento informado não existe.',
            'collaborator_id.required' => 'Informe o colaborador.',
            'collaborator_id.exists' => 'O colaborador informado não existe.',
        ];
    }
}
