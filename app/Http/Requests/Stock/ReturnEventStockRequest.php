<?php

namespace App\Http\Requests\Stock;

use App\Rules\StockProduct;
use Illuminate\Foundation\Http\FormRequest;

class ReturnEventStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'finalize' => ['required', 'boolean'],
            'items' => ['present', 'array'],
            'items.*.product_id' => ['required', 'integer', 'distinct', new StockProduct()],
            'items.*.returned_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'items.*.extra_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.returned_quantity.min' => 'O retorno não pode ser negativo.',
            'items.*.extra_quantity.min' => 'O consumo extra não pode ser negativo.',
        ];
    }
}
