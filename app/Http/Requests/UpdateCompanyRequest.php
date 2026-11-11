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
            'cnpj' => 'nullable|string|max:18',
            'municipal_registration' => 'nullable|string|max:50',
            'city_id' => 'nullable|exists:cities,id',
            'tax_regime' => 'nullable|in:simples_nacional,mei,lucro_presumido,lucro_real',
            'nf_environment' => 'nullable|in:homologacao,producao',
            'nf_service_code' => 'nullable|string|max:30',
            'nf_municipal_tax_code' => 'nullable|string|max:30',
            'nf_iss_rate' => 'nullable|numeric|min:0|max:100',
            'nf_default_description' => 'nullable|string|max:1000',
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