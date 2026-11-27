<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
   
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')->id;

        $data = [
            'corporate_name' => 'sometimes|required|string|max:191',
            'fantasy_name' => 'sometimes|required|string|max:191',
            'cnpj' => 'sometimes|required|string|max:18',
            'state_registration' => 'sometimes|required|string|max:191',
            'phone_1' => 'sometimes|required|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'observations' => 'nullable|string|max:191',
            'user_id' => 'nullable|exists:users,id',
            'pix_keys'   => 'nullable|array',
            'bank_details' => 'nullable|array',
        ];

        return array_merge($data, [
            'address' => 'nullable|array',
            'address.street' => 'nullable|string|max:191',
            'address.district' => 'nullable|string|max:191',
            'address.complement' => 'nullable|string|max:191',
            'address.number' => 'nullable|string|max:50',
            'address.zip_code' => 'nullable|string|max:191',
            'address.reference' => 'nullable|string|max:65535',
            'address.city_id' => 'nullable|integer|exists:cities,id',
            'address.state_id' => 'nullable|integer|exists:states,id',
            'bank_details.*.id' => 'nullable|integer',
            'bank_details.*.bank_name' => 'nullable|string|max:191',
            'bank_details.*.agency' => 'nullable|string|max:191',
            'bank_details.*.account' => 'nullable|string|max:191',
            'bank_details.*.account_holder' => 'nullable|string|max:191',
            'bank_details.*.status' => 'nullable|boolean',
            'pix_keys.*.id' => 'nullable|integer',
            'pix_keys.*.type' => 'nullable|string|max:191',
            'pix_keys.*.key' => 'nullable|string|max:191',
            'removed_bank_detail_ids' => 'nullable|array',
            'removed_bank_detail_ids.*' => 'integer',
            'removed_pix_key_ids' => 'nullable|array',
            'removed_pix_key_ids.*' => 'integer',
        ]);
    }

    public function messages(): array
    {
        return [
            'corporate_name.string' => 'O campo razão social deve ser uma string.',
            'corporate_name.max' => 'O campo razão social deve ter no máximo 191 caracteres.',
            'fantasy_name.string' => 'O campo nome fantasia deve ser uma string.',
            'fantasy_name.max' => 'O campo nome fantasia deve ter no máximo 191 caracteres.',
            'cnpj.string' => 'O campo CNPJ deve ser uma string.',
            'cnpj.max' => 'O campo CNPJ deve ter no máximo 18 caracteres.',
            'state_registration.string' => 'O campo inscrição estadual deve ser uma string.',
            'state_registration.max' => 'O campo inscrição estadual deve ter no máximo 191 caracteres.',
            'phone_1.string' => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max' => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_2.string' => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max' => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'observations.string' => 'O campo observações deve ser uma string.',
            'user_id.exists' => 'O usuário informado não existe.',
            'address.city_id.exists' => 'A cidade informada não existe.',
            'address.state_id.exists' => 'O estado informado não existe.',
            'address.number.max' => 'O número do endereço deve ter no máximo 50 caracteres.',
            'address.*.max' => 'Um dos campos do endereço passou do tamanho máximo.',
            'bank_details.*.*' => 'Um dos dados bancários é inválido ou longo demais.',
            'pix_keys.*.*' => 'Uma das chaves pix é inválida ou longa demais.',
        ];
    }
}
