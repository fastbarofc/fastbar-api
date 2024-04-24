<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBreakItemRequest extends FormRequest
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
            'description' => 'string|max:255',
            'unit_id' => 'exists:units,id',
            'value' => 'numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'unit_id.exists' => 'Usuário não encontrado',
            'value.numeric' => 'Valor deve ser numérico',
            'description.max' => 'Descrição deve ter no máximo 255 caracteres',
            'description.string' => 'Descrição deve ser uma string',

        ];
    }
    
}
