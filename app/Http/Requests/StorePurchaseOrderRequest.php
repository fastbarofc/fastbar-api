<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'financial_operation_id' => 'required|integer|exists:financial_operations,id',
            'cost_center_id' => 'required|integer|exists:cost_centers,id',
            'stock_id' => 'nullable|integer|exists:stocks,id',
            // status is always set to 'pending' on creation
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.purchase_request_id' => 'required|integer|exists:purchase_requests,id',
            'products.*.factor' => 'nullable|string|max:255',
            'products.*.reference' => 'nullable|string|max:255',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0|max:99999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'O campo empresa é obrigatório.',
            'company_id.integer' => 'O campo empresa deve ser um número inteiro.',
            'company_id.exists' => 'A empresa selecionada não existe.',
            'price.required' => 'O campo preço total é obrigatório.',
            'price.numeric' => 'O campo preço total deve ser um número.',
            'price.min' => 'O preço total deve ser maior ou igual a zero.',
            'price.max' => 'O preço total deve ser menor que 99.999.999,99.',
            'financial_operation_id.required' => 'O campo operação financeira é obrigatório.',
            'financial_operation_id.integer' => 'O campo operação financeira deve ser um número inteiro.',
            'financial_operation_id.exists' => 'A operação financeira selecionada não existe.',
            'cost_center_id.required' => 'O campo centro de custo é obrigatório.',
            'cost_center_id.integer' => 'O campo centro de custo deve ser um número inteiro.',
            'cost_center_id.exists' => 'O centro de custo selecionado não existe.',
            'products.required' => 'É obrigatório incluir pelo menos um produto.',
            'products.array' => 'Os produtos devem estar em formato de lista.',
            'products.min' => 'É obrigatório incluir pelo menos um produto.',
            'products.*.product_id.required' => 'O produto é obrigatório.',
            'products.*.product_id.integer' => 'O produto deve ser um número inteiro.',
            'products.*.product_id.exists' => 'O produto selecionado não existe.',
            'products.*.purchase_request_id.required' => 'O pedido de compra é obrigatório.',
            'products.*.purchase_request_id.integer' => 'O pedido de compra deve ser um número inteiro.',
            'products.*.purchase_request_id.exists' => 'O pedido de compra selecionado não existe.',
            'products.*.factor.string' => 'O fator deve ser uma string.',
            'products.*.factor.max' => 'O fator deve ter no máximo 255 caracteres.',
            'products.*.reference.string' => 'A referência deve ser uma string.',
            'products.*.reference.max' => 'A referência deve ter no máximo 255 caracteres.',
            'products.*.quantity.required' => 'A quantidade é obrigatória.',
            'products.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'products.*.quantity.min' => 'A quantidade deve ser pelo menos 1.',
            'products.*.price.required' => 'O preço unitário é obrigatório.',
            'products.*.price.numeric' => 'O preço unitário deve ser um número.',
            'products.*.price.min' => 'O preço unitário deve ser maior ou igual a zero.',
            'products.*.price.max' => 'O preço unitário deve ser menor que 99.999.999,99.',
        ];
    }
} 