<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'code' => [
                'required',
                'string',
                Rule::unique('financial_operations')->whereNull('deleted_at'),
            ],
            'description' => 'required|string',
            'account' => 'required|in:analytic,synthetic',
            'nature' => 'required|in:revenue,expense',
            'type' => 'required|in:fixed,variable',
            'dre_section' => 'nullable|in:receita_total,deducoes,custos_variaveis,despesas_operacionais,despesas_nao_operacionais,receitas_nao_operacionais,retirada_socios,fora',
            'dre_order' => 'nullable|integer',
            'active' => 'nullable|boolean',
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