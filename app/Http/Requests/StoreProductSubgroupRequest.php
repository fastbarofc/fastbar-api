<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductSubgroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_group_id' => 'required|numeric|exists:product_groups,id',
            'description'      => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'product_group_id.required' => 'O grupo é obrigatório.',
            'product_group_id.exists'   => 'O grupo informado não existe.',
            'description.required'      => 'A descrição é obrigatória.',
            'description.max'           => 'A descrição deve ter no máximo 255 caracteres.',
        ];
    }
}
