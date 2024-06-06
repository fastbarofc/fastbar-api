<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfficeBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'name' => 'nullable|string|max:255|unique:office_events,description,' . $this->route('office_budget')->id,
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'O nome informado já está em uso',
            'name.max' => 'O campo nome deve ter no maximo 255 caracteres',
            'name.string' => 'O campo nome deve ser uma string',

        ];
    }
}
