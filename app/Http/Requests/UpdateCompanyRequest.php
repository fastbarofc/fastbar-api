<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'internal_name' => 'sometimes|string|max:255',
            'legal_name' => 'sometimes|string|max:255',
            'trade_name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:head_office,branch',
        ];
    }

    public function messages(): array
    {
        return [
            'internal_name.string' => 'O campo nome interno deve ser uma string.',
            'internal_name.max' => 'O campo nome interno deve ter no máximo 255 caracteres.',
            'legal_name.string' => 'O campo razão social deve ser uma string.',
            'legal_name.max' => 'O campo razão social deve ter no máximo 255 caracteres.',
            'trade_name.string' => 'O campo nome fantasia deve ser uma string.',
            'trade_name.max' => 'O campo nome fantasia deve ter no máximo 255 caracteres.',
            'type.in' => 'O campo tipo deve ser "head_office" ou "branch".',
        ];
    }
}