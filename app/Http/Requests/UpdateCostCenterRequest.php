<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCostCenterRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|unique:cost_centers,code,' . $this->costCenter->id,
            'description' => 'sometimes|string',
            'account' => 'sometimes|in:analytic,synthetic',
        ];
    }

    public function messages(): array
    {
        return [
            'code.string' => 'O campo código deve ser uma string.',
            'code.unique' => 'Esse código já está sendo utilizado.',
            'description.string' => 'O campo descrição deve ser uma string.',
            'account.in' => 'O campo conta deve ser "analytic" ou "synthetic".',
        ];
    }
}