<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialOperationRequest extends FormRequest
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
                'sometimes',
                'string',
                Rule::unique('financial_operations', 'code')->whereNull('deleted_at')->ignore($this->route('financial_operation')),
            ],
            'description' => 'sometimes|string',
            'account' => 'sometimes|in:analytic,synthetic',
            'nature' => 'sometimes|in:revenue,expense',
            'type' => 'sometimes|in:fixed,variable',
        ];
    }

    public function messages(): array
    {
        return [
            'code.string' => 'O campo código deve ser uma string.',
            'code.unique' => 'Esse código já está sendo utilizado.',
            'description.string' => 'O campo descrição deve ser uma string.',
            'account.in' => 'O campo conta deve ser "analytic" ou "synthetic".',
            'nature.in' => 'O campo natureza deve ser "revenue" ou "expense".',
            'type.in' => 'O campo tipo deve ser "fixed" ou "variable".',
        ];
    }
}