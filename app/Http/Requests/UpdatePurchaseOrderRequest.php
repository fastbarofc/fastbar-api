<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|integer|exists:companies,id',
            'price' => 'nullable|numeric|min:0|max:99999999.99',
            'financial_operation_id' => 'nullable|integer|exists:financial_operations,id',
            'cost_center_id' => 'nullable|integer|exists:cost_centers,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            // status changes are handled via dedicated endpoint
            'products' => 'nullable|array|min:1',
            'products.*.product_id' => 'required_with:products|integer|exists:products,id',
            'products.*.purchase_request_id' => 'required_with:products|integer|exists:purchase_requests,id',
            'products.*.factor' => 'nullable|string|max:255',
            'products.*.reference' => 'nullable|string|max:255',
            'products.*.quantity' => 'required_with:products|integer|min:1',
            'products.*.price' => 'required_with:products|numeric|min:0|max:99999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.integer' => 'O campo empresa deve ser um número inteiro.',
            'company_id.exists' => 'A empresa selecionada não existe.',
            'price.numeric' => 'O campo preço total deve ser um número.',
            'price.min' => 'O preço total deve ser maior ou igual a zero.',
            'price.max' => 'O preço total deve ser menor que 99.999.999,99.',
            'financial_operation_id.integer' => 'O campo operação financeira deve ser um número inteiro.',
            'financial_operation_id.exists' => 'A operação financeira selecionada não existe.',
            'cost_center_id.integer' => 'O campo centro de custo deve ser um número inteiro.',
            'cost_center_id.exists' => 'O centro de custo selecionado não existe.',
            'products.array' => 'Os produtos devem estar em formato de lista.',
            'products.min' => 'É obrigatório incluir pelo menos um produto.',
            'products.*.product_id.required_with' => 'O produto é obrigatório quando produtos são informados.',
            'products.*.product_id.integer' => 'O produto deve ser um número inteiro.',
            'products.*.product_id.exists' => 'O produto selecionado não existe.',
            'products.*.purchase_request_id.required_with' => 'O pedido de compra é obrigatório quando produtos são informados.',
            'products.*.purchase_request_id.integer' => 'O pedido de compra deve ser um número inteiro.',
            'products.*.purchase_request_id.exists' => 'O pedido de compra selecionado não existe.',
            'products.*.factor.string' => 'O fator deve ser uma string.',
            'products.*.factor.max' => 'O fator deve ter no máximo 255 caracteres.',
            'products.*.reference.string' => 'A referência deve ser uma string.',
            'products.*.reference.max' => 'A referência deve ter no máximo 255 caracteres.',
            'products.*.quantity.required_with' => 'A quantidade é obrigatória quando produtos são informados.',
            'products.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'products.*.quantity.min' => 'A quantidade deve ser pelo menos 1.',
            'products.*.price.required_with' => 'O preço unitário é obrigatório quando produtos são informados.',
            'products.*.price.numeric' => 'O preço unitário deve ser um número.',
            'products.*.price.min' => 'O preço unitário deve ser maior ou igual a zero.',
            'products.*.price.max' => 'O preço unitário deve ser menor que 99.999.999,99.',
        ];
    }
} 