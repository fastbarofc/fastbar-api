<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'internal_name' => 'required|string|max:255',
            'legal_name' => 'required|string|max:255',
            'trade_name' => 'required|string|max:255',
            'type' => 'required|in:head_office,branch',
        ];
    }

    public function messages(): array
    {
        return [
            'internal_name.required' => 'O campo nome interno é obrigatório.',
            'internal_name.string' => 'O campo nome interno deve ser uma string.',
            'internal_name.max' => 'O campo nome interno deve ter no máximo 255 caracteres.',
            'legal_name.required' => 'O campo razão social é obrigatório.',
            'legal_name.string' => 'O campo razão social deve ser uma string.',
            'legal_name.max' => 'O campo razão social deve ter no máximo 255 caracteres.',
            'trade_name.required' => 'O campo nome fantasia é obrigatório.',
            'trade_name.string' => 'O campo nome fantasia deve ser uma string.',
            'trade_name.max' => 'O campo nome fantasia deve ter no máximo 255 caracteres.',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O campo tipo deve ser "head_office" ou "branch".',
        ];
    }
}