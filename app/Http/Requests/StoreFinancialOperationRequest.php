<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialOperationRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|unique:financial_operations,code',
            'description' => 'required|string',
            'account' => 'required|in:analytic,synthetic',
            'nature' => 'required|in:revenue,expense',
            'type' => 'required|in:fixed,variable',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'O campo código é obrigatório.',
            'code.string' => 'O campo código deve ser uma string.',
            'code.unique' => 'Esse código já está sendo utilizado.',
            'description.required' => 'O campo descrição é obrigatório.',
            'description.string' => 'O campo descrição deve ser uma string.',
            'account.required' => 'O campo conta é obrigatório.',
            'account.in' => 'O campo conta deve ser "analytic" ou "synthetic".',
            'nature.required' => 'O campo natureza é obrigatório.',
            'nature.in' => 'O campo natureza deve ser "revenue" ou "expense".',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O campo tipo deve ser "fixed" ou "variable".',
        ];
    }
}