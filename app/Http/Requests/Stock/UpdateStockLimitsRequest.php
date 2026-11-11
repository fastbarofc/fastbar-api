<?php

namespace App\Http\Requests\Stock;

use App\Rules\StockProduct;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStockLimitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'distinct', new StockProduct()],
            'items.*.min_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'items.*.max_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('items', []) as $index => $item) {
                $min = $item['min_quantity'] ?? null;
                $max = $item['max_quantity'] ?? null;

                if (is_numeric($min) && is_numeric($max) && (float) $max < (float) $min) {
                    $validator->errors()->add("items.$index.max_quantity", 'O máximo não pode ser menor que o mínimo.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.*.min_quantity.min' => 'O mínimo não pode ser negativo.',
        ];
    }
}
