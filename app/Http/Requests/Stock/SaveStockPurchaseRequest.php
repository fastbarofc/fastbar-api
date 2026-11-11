<?php

namespace App\Http\Requests\Stock;

use App\Rules\StockProduct;
use Illuminate\Foundation\Http\FormRequest;

class SaveStockPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchased_at' => ['required', 'date'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', new StockProduct()],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'purchased_at.required' => 'Informe a data da compra.',
            'items.required' => 'Adicione ao menos um item à compra.',
            'items.min' => 'Adicione ao menos um item à compra.',
            'items.*.product_id.required' => 'Selecione o produto.',
            'items.*.product_id.distinct' => 'O mesmo produto aparece mais de uma vez na compra.',
            'items.*.quantity.required' => 'Informe a quantidade.',
            'items.*.quantity.gt' => 'A quantidade precisa ser maior que zero.',
            'items.*.unit_price.min' => 'O valor unitário não pode ser negativo.',
        ];
    }
}
