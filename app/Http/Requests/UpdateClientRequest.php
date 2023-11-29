<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class UpdateClientRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $data = [
            'name'       => 'string|max:255|unique:clients,name,' . $this->route('client')->id,
            'nickname'   => 'string|max:255',
            'birth_date' => 'date',
            'cpf'        => ['string', 'max:14', 'cpf'],
            'rg'         => 'string|max:20',
            'phone_1'    => 'string|max:20|celular_com_ddd',
            'phone_2'    => 'string|max:20|celular_com_ddd',
            'email'      => 'email|unique:clients,email,' . $this->route('client')->id,
            'how_met'    => 'string|max:255'
        ];

        if ($this->request->all()['address']) {
            $this->validateAddress($this->request->all()['address']);
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'name.string'             => 'O campo nome deve ser uma string.',
            'name.max'                => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'                => 'Esse nome já está sendo utiliado',
            'nickname.string'         => 'O campo apelido deve ser uma string.',
            'nickname.max'            => 'O campo apelido deve ter no máximo 255 caracteres.',
            'birth_date.date'         => 'O campo data de nascimento deve ser uma data válida.',
            'cpf.string'              => 'O campo CPF deve ser uma string.',
            'cpf.max'                 => 'O campo CPF deve ter no máximo 14 caracteres.',
            'cpf.cpf'                 => 'O campo CPF deve ser um CPF válido.',
            'rg.string'               => 'O campo RG deve ser uma string.',
            'rg.max'                  => 'O campo RG deve ter no máximo 20 caracteres.',
            'phone_1.string'          => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'             => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_1.celular_com_ddd' => 'O campo telefone 1 deve ser um telefone válido.',
            'phone_2.string'          => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'             => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'email.email'             => 'O campo e-mail deve ser um e-mail válido.',
            'email.unique'            => 'Esse E-mail já está cadastrado.',
            'how_met.string'          => 'O campo como conheceu deve ser uma string.',
            'how_met.max'             => 'O campo como conheceu deve ter no máximo 255 caracteres.',
        ];
    }

    public function validateAddress(array $data) {
        $validator = Validator::make($data, [
            'street' => ['required', 'string'],
            'district' => ['required', 'string'],
            'complement' => ['nullable', 'string'],
            'city_id' => ['required', 'exists:cities,id'],
            'state_id' => ['required', 'exists:states,id'],
            'number' => ['required'],
            'zip_code' => ['required', 'formato_cep'],
            'reference' => ['nullable', 'string'],
        ],[
            'street.required' => 'O campo rua é obrigatório',
            'street.string' => 'O campo rua deve ser uma string',
            'district.required' => 'O campo bairro é obrigatório',
            'district.string' => 'O campo bairro deve ser uma string',
            'complement.string' => 'O campo complemento deve ser uma string',
            'city_id.required' => 'O campo city ID é obrigatório',
            'city_id.exists' => 'O campo Cidade deve ser uma Cidade valida',
            'state_id.required' => 'O campo state ID é obrigatório',
            'state_id.exists' => 'O campo Estado deve ser um Estado valido',
            'number.required' => 'O campo número é obrigatório',
            'zip_code.required' => 'O campo CEP é obrigatório',
            'zip_code.formato_cep' => 'O campo CEP de ser um CEP valido',
            'referencia.string' => 'O campo referencia deve ser uma string',
        ]);

        $validator->validate();
    }
}
