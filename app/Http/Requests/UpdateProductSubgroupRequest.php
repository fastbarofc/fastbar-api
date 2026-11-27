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
            'product_group_id' => 'sometimes|required|numeric|exists:product_groups,id',
            'description'      => 'sometimes|required|string|max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'O campo descrição é obrigatório.',
            'product_group_id.required' => 'O campo grupo é obrigatório.',
            'product_group_id.exists' => 'O grupo informado não existe.',
            'description.max'         => 'A descrição deve ter no máximo 191 caracteres.',
        ];
    }
}
