<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'stock_id' => ['required', 'integer', 'exists:stocks,id'],
            'min_quantity' => ['nullable', 'integer', 'min:0'],
            'max_quantity' => ['nullable', 'integer', 'min:0', 'gte:min_quantity'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'O produto é obrigatório.',
            'product_id.exists' => 'Produto não encontrado.',
            'stock_id.required' => 'O estoque é obrigatório.',
            'stock_id.exists' => 'Estoque não encontrado.',
            'min_quantity.integer' => 'A quantidade mínima deve ser um número inteiro.',
            'min_quantity.min' => 'A quantidade mínima não pode ser negativa.',
            'max_quantity.integer' => 'A quantidade máxima deve ser um número inteiro.',
            'max_quantity.min' => 'A quantidade máxima não pode ser negativa.',
            'max_quantity.gte' => 'A quantidade máxima deve ser maior ou igual à quantidade mínima.',
        ];
    }
}
