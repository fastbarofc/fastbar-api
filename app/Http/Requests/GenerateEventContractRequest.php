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
            'menu_items' => 'required|array|min:1|max:40',
            'menu_items.*' => 'required|string|max:255',
            'bar_points_text' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'menu_modality.required' => 'A modalidade do cardápio é obrigatória',
            'menu_items.required' => 'Informe ao menos um item do cardápio',
            'menu_items.min' => 'Informe ao menos um item do cardápio',
            'menu_items.max' => 'O cardápio aceita no máximo 40 itens',
            'bar_points_text.required' => 'O texto dos pontos de bar é obrigatório',
            'bar_points_text.max' => 'O texto dos pontos de bar aceita no máximo 1000 caracteres',
        ];
    }
}
