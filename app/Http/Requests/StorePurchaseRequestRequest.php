<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
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
            'sector_id' => 'required|integer|exists:sectors,id',
            'expected_date' => 'required|date|after_or_equal:today',
            'status' => 'nullable|string|in:not_confirmed,confirmed,authorized',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.factor' => 'nullable|string|max:255',
            'products.*.observation' => 'nullable|string|max:255',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'O campo empresa é obrigatório.',
            'company_id.integer' => 'O campo empresa deve ser um número inteiro.',
            'company_id.exists' => 'A empresa selecionada não existe.',
            'sector_id.required' => 'O campo setor é obrigatório.',
            'sector_id.integer' => 'O campo setor deve ser um número inteiro.',
            'sector_id.exists' => 'O setor selecionado não existe.',
            'expected_date.required' => 'O campo data prevista é obrigatório.',
            'expected_date.date' => 'O campo data prevista deve ser uma data válida.',
            'expected_date.after_or_equal' => 'A data prevista deve ser hoje ou uma data futura.',
            'status.string' => 'O campo status deve ser uma string.',
            'status.in' => 'O status deve ser: não confirmado, confirmado ou autorizado.',
            'products.required' => 'É obrigatório incluir pelo menos um produto.',
            'products.array' => 'Os produtos devem estar em formato de lista.',
            'products.min' => 'É obrigatório incluir pelo menos um produto.',
            'products.*.product_id.required' => 'O produto é obrigatório.',
            'products.*.product_id.integer' => 'O produto deve ser um número inteiro.',
            'products.*.product_id.exists' => 'O produto selecionado não existe.',
            'products.*.factor.string' => 'O fator deve ser uma string.',
            'products.*.factor.max' => 'O fator deve ter no máximo 255 caracteres.',
            'products.*.observation.string' => 'A observação deve ser uma string.',
            'products.*.observation.max' => 'A observação deve ter no máximo 255 caracteres.',
            'products.*.quantity.required' => 'A quantidade é obrigatória.',
            'products.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'products.*.quantity.min' => 'A quantidade deve ser pelo menos 1.',
        ];
    }
} 