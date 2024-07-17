<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFailureRequest extends FormRequest
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
            'percentage' => 'numeric|between:0,100',
        ];
    }

    public function messages(): array
    {
        return [
            'description.max' => 'O campo descrição deve ter no maximo 255 caracteres.',
            'percentage.between' => 'O campo porcentagem deve estar entre 0 e 100',
            'percentage.numeric' => 'O campo porcentagem deve ser um valor numérico',
            'description.string' => 'O campo descrição deve ser uma string',
        ];
    }
}
