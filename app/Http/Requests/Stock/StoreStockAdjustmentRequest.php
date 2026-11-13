<?php

namespace App\Http\Requests\Stock;

use App\Models\StockAdjustment;
use App\Rules\StockProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', new StockProduct()],
            'quantity' => ['required', 'numeric', 'not_in:0', 'min:-999999', 'max:999999'],
            'reason' => ['required', Rule::in(array_keys(StockAdjustment::REASONS))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Selecione o produto.',
            'quantity.required' => 'Informe a quantidade do ajuste.',
            'quantity.not_in' => 'O ajuste não pode ser zero.',
            'reason.required' => 'Informe o motivo do ajuste.',
        ];
    }
}
