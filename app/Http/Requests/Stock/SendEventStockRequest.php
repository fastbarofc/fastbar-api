<?php

namespace App\Http\Requests\Stock;

use App\Rules\StockProduct;
use Illuminate\Foundation\Http\FormRequest;

class SendEventStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['present', 'array'],
            'items.*.product_id' => ['required', 'integer', 'distinct', new StockProduct()],
            'items.*.sent_quantity' => ['required', 'numeric', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.sent_quantity.required' => 'Informe a quantidade enviada.',
            'items.*.sent_quantity.min' => 'A quantidade enviada não pode ser negativa.',
        ];
    }
}
