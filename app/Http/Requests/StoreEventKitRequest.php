<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventKitRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id'     => 'required|integer|exists:events,id',
            'event_kit_id' => 'nullable|integer|exists:event_kits,id',
            'type'         => 'nullable|in:kit,product',
            'id'           => 'nullable|integer',
            'kit_id'       => 'nullable|required_if:type,product|integer|exists:kits,id',
            'value'        => 'nullable|numeric|min:0|max:999999.99',
            'observation'  => 'nullable|string|max:191',
            'themed_table' => 'sometimes|boolean',
            'optional'     => 'sometimes|boolean',
            'checklist'    => 'sometimes|boolean',
            'name'         => ['required', 'string', 'max:191', Rule::unique('event_kits', 'name')->whereNull('deleted_at')->where('event_kit_id', $this->event_kit_id)->where('event_id', $this->event_id)],
        ];
    }

    public function messages(): array
    {
        return [
            'event_id.required'     => 'Informe o evento.',
            'event_id.exists'       => 'O evento informado não existe.',
            'event_kit_id.*'        => 'A pasta informada não existe.',
            'type.in'               => 'O tipo deve ser kit ou produto.',
            'kit_id.*'              => 'O kit informado não existe.',
            'value.*'               => 'O valor deve estar entre 0 e 999.999,99.',
            'observation.max'       => 'A observação deve ter no máximo 191 caracteres.',
            'name.required'         => 'O campo nome é obrigatório.',
            'name.string'           => 'O campo nome deve ser um texto.',
            'name.max'              => 'O campo nome deve ter no máximo 191 caracteres.',
            'name.unique'           => 'O nome informado já está em uso.',
        ];
    }
}
