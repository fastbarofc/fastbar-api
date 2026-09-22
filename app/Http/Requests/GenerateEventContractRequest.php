<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateEventContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_modality' => 'required|string|max:255',
            'bar_points_text' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'menu_modality.required' => 'A modalidade do cardápio é obrigatória',
            'bar_points_text.required' => 'O texto dos pontos de bar é obrigatório',
            'bar_points_text.max' => 'O texto dos pontos de bar aceita no máximo 1000 caracteres',
        ];
    }
}
