<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

class SyncOfficeCollaboratorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'eventId' => 'required|integer|exists:events,id',
            'collaborators' => 'present|array',
            'collaborators.*' => 'integer|distinct|exists:collaborators,id',
        ];
    }

    public function messages(): array
    {
        return [
            'eventId.required' => 'Informe o evento da escalação.',
            'eventId.exists' => 'O evento informado não existe.',
            'collaborators.present' => 'Informe a lista de colaboradores.',
            'collaborators.array' => 'A lista de colaboradores é inválida.',
            'collaborators.*.distinct' => 'O mesmo colaborador foi escalado duas vezes.',
            'collaborators.*.exists' => 'Um dos colaboradores informados não existe.',
        ];
    }
}
