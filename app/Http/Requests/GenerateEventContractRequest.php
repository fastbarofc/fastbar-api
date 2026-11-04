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
            'representative_ids' => 'nullable|array',
            'representative_ids.*' => 'integer',
        ];
    }

    public function messages(): array
    {
        return [
            'menu_modality.required' => 'A modalidade do cardápio é obrigatória',
        ];
    }
}
