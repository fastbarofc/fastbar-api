<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
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
            'corporate_name' => 'nullable|string|max:255',
            'fantasy_name' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'state_registration' => 'nullable|string|max:255',
            'phone_1' => 'nullable|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'bank' => 'nullable|string|max:255',
            'agency' => 'nullable|string|max:20',
            'account' => 'nullable|string|max:20',
            'pix_key' => 'nullable|string|max:255',
            'account_holder' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ];

        if (!empty($this->request->all()['address'])) {
            $this->validateAddress($this->request->all()['address']);
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'corporate_name.string' => 'O campo razão social deve ser uma string.',
            'corporate_name.max' => 'O campo razão social deve ter no máximo 255 caracteres.',
            'fantasy_name.string' => 'O campo nome fantasia deve ser uma string.',
            'fantasy_name.max' => 'O campo nome fantasia deve ter no máximo 255 caracteres.',
            'cnpj.string' => 'O campo CNPJ deve ser uma string.',
            'cnpj.max' => 'O campo CNPJ deve ter no máximo 18 caracteres.',
            'state_registration.string' => 'O campo inscrição estadual deve ser uma string.',
            'state_registration.max' => 'O campo inscrição estadual deve ter no máximo 255 caracteres.',
            'phone_1.string' => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max' => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_2.string' => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max' => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'bank.string' => 'O campo banco deve ser uma string.',
            'bank.max' => 'O campo banco deve ter no máximo 255 caracteres.',
            'agency.string' => 'O campo agência deve ser uma string.',
            'agency.max' => 'O campo agência deve ter no máximo 20 caracteres.',
            'account.string' => 'O campo conta deve ser uma string.',
            'account.max' => 'O campo conta deve ter no máximo 20 caracteres.',
            'pix_key.string' => 'O campo chave Pix deve ser uma string.',
            'pix_key.max' => 'O campo chave Pix deve ter no máximo 255 caracteres.',
            'account_holder.string' => 'O campo titular da conta deve ser uma string.',
            'account_holder.max' => 'O campo titular da conta deve ter no máximo 255 caracteres.',
            'observations.string' => 'O campo observações deve ser uma string.',
            'user_id.exists' => 'O usuário informado não existe.',
        ];
    }

    public function validateAddress(array $data)
    {
        $validator = Validator::make($data, [
            'street' => ['nullable', 'string'],
            'district' => ['nullable', 'string'],
            'complement' => ['nullable', 'string'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'state_id' => ['nullable', 'exists:states,id'],
            'number' => ['nullable', 'string', 'max:50'],
            'zip_code' => ['nullable', 'formato_cep'],
            'reference' => ['nullable', 'string'],
        ], [
            'street.string' => 'O campo rua deve ser uma string.',
            'district.string' => 'O campo bairro deve ser uma string.',
            'complement.string' => 'O campo complemento deve ser uma string.',
            'city_id.exists' => 'O campo cidade deve ser uma cidade válida.',
            'state_id.exists' => 'O campo estado deve ser um estado válido.',
            'number.string'        => 'O campo número deve conter apenas letras e números',
            'number.max'           => 'O campo número não pode ter mais de 50 caracteres',
            'zip_code.formato_cep' => 'O campo CEP deve ser um CEP válido.',
            'reference.string' => 'O campo referência deve ser uma string.',
        ]);

        $validator->validate();
    }
}
