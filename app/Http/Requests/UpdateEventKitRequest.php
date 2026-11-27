<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventKitRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_kit_id' => 'nullable|integer|exists:event_kits,id',
            'value'        => 'nullable|numeric|min:0|max:999999.99',
            'observation'  => 'nullable|string|max:191',
            'themed_table' => 'sometimes|required|boolean',
            'optional'     => 'sometimes|required|boolean',
            'checklist'    => 'sometimes|required|boolean',
            'name'         => ['sometimes', 'required', 'string', 'max:191', Rule::unique('event_kits', 'name')->ignore($this->route('event_kit')->id)->whereNull('deleted_at')->where('event_kit_id', $this->event_kit_id)->where('event_id', $this->event_id)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'event_kit_id.*'        => 'A pasta informada não existe.',
            'value.*'               => 'O valor deve estar entre 0 e 999.999,99.',
            'observation.max'       => 'A observação deve ter no máximo 191 caracteres.',
            'name.string'           => 'O campo nome deve ser um texto.',
            'name.max'              => 'O campo nome deve ter no máximo 191 caracteres.',
            'name.unique'           => 'O nome informado já está em uso.',
        ];
    }
}
