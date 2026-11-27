<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBreakItemRequest extends FormRequest
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
            'description' => 'required|string|max:191',
            'unit_id' => 'required|exists:units,id',
            'value' => 'required|numeric|max:999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'unit_id.exists' => 'Unidade não encontrado',
            'unit_id.required' => 'Unidade é obrigatório',
            'description.required' => 'Descrição é obrigatória',
            'value.required' => 'Valor é obrigatório',
            'value.numeric' => 'Valor deve ser numérico',
            'description.max' => 'Descrição deve ter no máximo 191 caracteres',
            'description.string' => 'Descrição deve ser uma string',
            'description.required' => 'Descrição é obrigatória',

        ];
    }
}
