<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductSubgroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_group_id' => 'nullable|numeric|exists:product_groups,id',
            'description'      => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'product_group_id.exists' => 'O grupo informado não existe.',
            'description.max'         => 'A descrição deve ter no máximo 255 caracteres.',
        ];
    }
}
