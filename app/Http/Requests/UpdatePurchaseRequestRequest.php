<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePurchaseRequestRequest extends FormRequest
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
            'sector_id' => 'nullable|integer|exists:sectors,id',
            'expected_date' => 'nullable|date|after_or_equal:today',
            'status' => 'nullable|string|in:not_confirmed,confirmed,authorized',
            'products' => 'nullable|array|min:1',
            'products.*.product_id' => 'required_with:products|integer|exists:products,id',
            'products.*.factor' => 'nullable|string|max:255',
            'products.*.reference' => 'nullable|string|max:255',
            'products.*.quantity' => 'required_with:products|integer|min:1',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $status = $this->input('status');

            if (in_array($status, ['confirmed', 'authorized'])) {
                if ($this->filled('products')) {
                    $validator->errors()->add(
                        'products',
                        'Não é permitido editar pedido de compra quando o status é confirmado ou autorizado.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_id.integer' => 'O campo empresa deve ser um número inteiro.',
            'company_id.exists' => 'A empresa selecionada não existe.',
            'sector_id.integer' => 'O campo setor deve ser um número inteiro.',
            'sector_id.exists' => 'O setor selecionado não existe.',
            'expected_date.date' => 'O campo data prevista deve ser uma data válida.',
            'expected_date.after_or_equal' => 'A data prevista deve ser hoje ou uma data futura.',
            'status.string' => 'O campo status deve ser uma string.',
            'status.in' => 'O status deve ser: não confirmado, confirmado ou autorizado.',
            'products.array' => 'Os produtos devem estar em formato de lista.',
            'products.min' => 'É obrigatório incluir pelo menos um produto.',
            'products.*.product_id.required_with' => 'O produto é obrigatório quando produtos são informados.',
            'products.*.product_id.integer' => 'O produto deve ser um número inteiro.',
            'products.*.product_id.exists' => 'O produto selecionado não existe.',
            'products.*.factor.string' => 'O fator deve ser uma string.',
            'products.*.factor.max' => 'O fator deve ter no máximo 255 caracteres.',
            'products.*.reference.string' => 'A referência deve ser uma string.',
            'products.*.reference.max' => 'A referência deve ter no máximo 255 caracteres.',
            'products.*.quantity.required_with' => 'A quantidade é obrigatória quando produtos são informados.',
            'products.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'products.*.quantity.min' => 'A quantidade deve ser pelo menos 1.',
        ];
    }
} 