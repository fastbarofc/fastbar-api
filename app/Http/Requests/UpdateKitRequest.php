<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKitRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kit_id'          => 'nullable|integer|exists:kits,id',
            'name'         => ['sometimes', 'required', 'string', 'max:191', Rule::unique('kits', 'name')->ignore($this->route('kit')->id)->whereNull('deleted_at')->where('kit_id', $this->kit_id)],
            'themed_table' => 'sometimes|required|boolean',
            'optional'     => 'sometimes|required|boolean',
            'checklist'    => 'sometimes|required|boolean',
            'observation'  => 'nullable|string|max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'checklist.required' => 'O campo checklist é obrigatório.',
            'optional.required' => 'O campo opcional é obrigatório.',
            'themed_table.required' => 'O campo mesa temática é obrigatório.',
            'name.required' => 'O campo nome é obrigatório.',
            'kit_id.exists' => 'O kit informado não existe.',
            'kit_id.integer'         => 'O campo ID do kit deve conter apenas números.',
            'name.string'           => 'O campo nome deve ser um texto.',
            'name.max'              => 'O campo nome deve ter no máximo 191 caracteres.',
            'name.unique'           => 'O nome informado já está em uso.',
        ];
    }
}
