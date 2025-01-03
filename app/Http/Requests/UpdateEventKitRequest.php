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
            'event_kit_id'          => 'nullable|numeric',
            'name'         => ['nullable', 'string', 'max:255', Rule::unique('event_kits', 'name')->ignore($this->route('event_kit')->id)->whereNull('deleted_at')->where('event_kit_id', $this->event_kit_id)->where('event_id', $this->event_id)],
        ];
    }

    public function messages(): array
    {
        return [
            'event_kit_id.numeric'         => 'O campo ID do kit deve conter apenas números.',
            'name.string'           => 'O campo nome deve ser um texto.',
            'name.max'              => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'           => 'O nome informado já está em uso.',
        ];
    }
}
