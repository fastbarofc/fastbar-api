<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class StoreSupplierRequest extends FormRequest
{

    protected $stopOnFirstFailure = true;
 
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $data = [
            'corporate_name' => 'required|string|max:255',
            'fantasy_name' => 'required|string|max:255',
            'cnpj' => 'required|string|max:18',
            'state_registration' => 'required|string|max:255',
            'phone_1' => 'required|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'account_holder' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'pix_keys'   => 'nullable|array',
            'bank_details' => 'nullable|array',
        ];

        if (!empty($this->request->all()['address'])) {
            $this->validateAddress($this->request->all()['address']);
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'corporate_name.required' => 'O campo razão social é obrigatório.',
            'corporate_name.string' => 'O campo razão social deve ser uma string.',
            'corporate_name.max' => 'O campo razão social deve ter no máximo 255 caracteres.',
            'fantasy_name.required' => 'O campo nome fantasia é obrigatório.',
            'fantasy_name.string' => 'O campo nome fantasia deve ser uma string.',
            'fantasy_name.max' => 'O campo nome fantasia deve ter no máximo 255 caracteres.',
            'cnpj.required' => 'O campo CNPJ é obrigatório.',
            'cnpj.string' => 'O campo CNPJ deve ser uma string.',
            'cnpj.max' => 'O campo CNPJ deve ter no máximo 18 caracteres.',
            'state_registration.required' => 'O campo inscrição estadual é obrigatório.',
            'state_registration.string' => 'O campo inscrição estadual deve ser uma string.',
            'state_registration.max' => 'O campo inscrição estadual deve ter no máximo 255 caracteres.',
            'phone_1.required' => 'O campo telefone 1 é obrigatório.',
            'phone_1.string' => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max' => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_2.string' => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max' => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'account_holder.string' => 'O campo titular da conta deve ser uma string.',
            'account_holder.max' => 'O campo titular da conta deve ter no máximo 255 caracteres.',
            'observations.string' => 'O campo observações deve ser uma string.',
            'user_id.exists' => 'O usuário informado não existe.',
        ];
    }

    public function validateAddress(array $data)
    {
        $validator = Validator::make($data, [
            'street' => ['required', 'string'],
            'district' => ['required', 'string'],
            'complement' => ['nullable', 'string'],
            'city_id' => ['required', 'exists:cities,id'],
            'state_id' => ['required', 'exists:states,id'],
            'number' => ['required', 'string', 'max:50'],
            'zip_code' => ['required', 'formato_cep'],
            'reference' => ['nullable', 'string'],
        ], [
            'street.required' => 'O campo rua é obrigatório.',
            'street.string' => 'O campo rua deve ser uma string.',
            'district.required' => 'O campo bairro é obrigatório.',
            'district.string' => 'O campo bairro deve ser uma string.',
            'complement.string' => 'O campo complemento deve ser uma string.',
            'city_id.required' => 'O campo cidade é obrigatório.',
            'city_id.exists' => 'O campo cidade deve ser uma cidade válida.',
            'state_id.required' => 'O campo estado é obrigatório.',
            'state_id.exists' => 'O campo estado deve ser um estado válido.',
            'number.required' => 'O campo número é obrigatório.',
            'number.string'        => 'O campo número deve conter apenas letras e números',
            'number.max'           => 'O campo número não pode ter mais de 50 caracteres',
            'zip_code.required' => 'O campo CEP é obrigatório.',
            'zip_code.formato_cep' => 'O campo CEP deve ser um CEP válido.',
            'reference.string' => 'O campo referência deve ser uma string.',
        ]);

        $validator->validate();
    }
}
