<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventTypeRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255|unique:event_types,name,' . $this->route('event_type')->id,
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'   => 'O campo nome deve ser um texto.',
            'name.max'      => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'   => 'O nome informado já está em uso.',
        ];
    }
}
