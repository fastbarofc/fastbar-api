<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class StoreClientRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $data = [
            'type'         => 'required|string|in:pf,pj',
            'how_met'      => 'required|string|max:255',
            'email'        => 'required|email|unique:clients,email',
            'phone_1'      => 'nullable|string|max:20|celular_com_ddd',
            'phone_2'      => 'nullable|string|max:20|celular_com_ddd',
            'name'         => 'nullable|string|max:255',
            'nickname'     => 'nullable|string|max:255',
            'birth_date'   => 'nullable|date',
            'cpf'          => 'nullable|string|max:14|cpf',
            'rg'           => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'fantasy_name' => 'nullable|string|max:255',
            'cnpj'         => 'nullable|string|max:18|cnpj',
            'ie'           => 'nullable|string|max:255',
            'im'           => 'nullable|string|max:255',
        ];

        if (!empty($this->request->all()['address'])) {
            $this->validateAddress($this->request->all()['address']);
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'type.string'             => 'O campo tipo deve ser uma string.',
            'type.in'           => 'O tipo informado de ser PF ou PJ.',
            'how_met.required'        => 'O campo como conheceu é obrigatório.',
            'how_met.string'          => 'O campo como conheceu deve ser uma string.',
            'how_met.max'             => 'O campo como conheceu deve ter no máximo 255 caracteres.',
            'email.required'           => 'O campo e-mail é obrigatório.',
            'email.email'             => 'O campo e-mail deve ser um e-mail válido.',
            'email.unique'            => 'Esse E-mail já está cadastrado.',
            'phone_1.string'          => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'             => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_1.celular_com_ddd' => 'O campo telefone 1 deve ser um telefone válido.',
            'phone_2.string'          => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'             => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'phone_2.celular_com_ddd' => 'O campo telefone 2 deve ser um telefone válido.',
            'name.string'             => 'O campo nome deve ser uma string.',
            'name.max'                => 'O campo nome deve ter no máximo 255 caracteres.',
            'nickname.string'         => 'O campo apelido deve ser uma string.',
            'nickname.max'            => 'O campo apelido deve ter no máximo 255 caracteres.',
            'birth_date.date'         => 'O campo data de nascimento deve ser uma data válida.',
            'cpf.string'              => 'O campo CPF deve ser uma string.',
            'cpf.max'                 => 'O campo CPF deve ter no máximo 14 caracteres.',
            'cpf.cpf'                 => 'O campo CPF deve ser um CPF válido.',
            'rg.string'               => 'O campo RG deve ser uma string.',
            'rg.max'                  => 'O campo RG deve ter no máximo 20 caracteres.',
            'company.string'             => 'O campo companhia deve ser uma string.',
            'company.max'                => 'O campo companhia deve ter no máximo 255 caracteres.',
            'fantasy_name.string'             => 'O campo nome fantasia deve ser uma string.',
            'fantasy_name.max'                => 'O campo nome fantasia deve ter no máximo 255 caracteres.',
            'cnpj.string'             => 'O campo CNPJ deve ser uma string.',
            'cnpj.max'                => 'O campo CNPJ deve ter no máximo 255 caracteres.',
            'cnpj.cnpj'                => 'O campo CNPJ deve ser um CNPJ válido.',
            'ie.string'             => 'O campo inscrição estadual deve ser uma string.',
            'ie.max'                => 'O campo inscrição estadual deve ter no máximo 255 caracteres.',
            'im.string'             => 'O campo inscrição municipal deve ser uma string.',
            'im.max'                => 'O campo inscrição municipal deve ter no máximo 255 caracteres.',
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
