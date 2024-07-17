<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFailureRequest extends FormRequest
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
            'description' => 'required|string|max:255',
            'percentage' => 'required|numeric|between:0,100',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'O campo descrição é obrigatório',
            'description.string' => 'O campo descrição deve ser uma string',
            'description.max' => 'O campo descrição deve ter no máximo 255 caracteres',
            'percentage.required' => 'O campo porcentagem é obrigatório',
            'percentage.numeric' => 'O campo porcentagem deve ser um valor numérico',
            'percentage.between' => 'O campo porcentagem deve estar entre 0 e 100',
        ];
    }
}
