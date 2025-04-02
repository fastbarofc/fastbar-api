<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client')->id;

        $data = [
            'type'         => 'nullable|string|in:pf,pj',
            'email'        => [
                'nullable', 
                'email',
                Rule::unique('clients')->whereNull('deleted_at')->ignore($clientId),
            ],
            'phone_1'      => 'nullable|string|max:20',
            'phone_2'      => 'nullable|string|max:20',
            'name'         => 'nullable|string|max:255',
            'nickname'     => 'nullable|string|max:255',
            'birth_date'   => 'nullable|date',
            'cpf'          => 'nullable|string|max:14',
            'rg'           => 'nullable|string|max:20',
            'cnpj'         => 'nullable|string|max:18',
            'ie'           => 'nullable|string|max:255',
            'im'           => 'nullable|string|max:255',
        ];

        if (!empty($this->request->all()['address']) && !empty($data['address']['street'])) {
            $this->validateAddress($this->request->all()['address']);
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'type.string'             => 'O campo tipo deve ser uma string.',
            'type.in'                 => 'O tipo informado de ser PF ou PJ.',
            'email.email'             => 'O campo e-mail deve ser um e-mail válido.',
            'email.unique'            => 'Esse E-mail já está cadastrado.',
            'phone_1.string'          => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'             => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_2.string'          => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'             => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'name.string'             => 'O campo nome deve ser uma string.',
            'name.max'                => 'O campo nome deve ter no máximo 255 caracteres.',
            'nickname.string'         => 'O campo apelido deve ser uma string.',
            'nickname.max'            => 'O campo apelido deve ter no máximo 255 caracteres.',
            'birth_date.date'         => 'O campo data de nascimento deve ser uma data válida.',
            'cpf.string'              => 'O campo CPF deve ser uma string.',
            'cpf.max'                 => 'O campo CPF deve ter no máximo 14 caracteres.',
            'rg.string'               => 'O campo RG deve ser uma string.',
            'rg.max'                  => 'O campo RG deve ter no máximo 20 caracteres.',
            'cnpj.string'             => 'O campo CNPJ deve ser uma string.',
            'cnpj.max'                => 'O campo CNPJ deve ter no máximo 255 caracteres.',
            'ie.string'               => 'O campo inscrição estadual deve ser uma string.',
            'ie.max'                  => 'O campo inscrição estadual deve ter no máximo 255 caracteres.',
            'im.string'               => 'O campo inscrição municipal deve ser uma string.',
            'im.max'                  => 'O campo inscrição municipal deve ter no máximo 255 caracteres.',
        ];
    }

    public function validateAddress(array $data) {
        $validator = Validator::make($data, [
            'street'     => ['nullable', 'string'],
            'district'   => ['nullable', 'string'],
            'complement' => ['nullable', 'string'],
            'city_id'    => ['nullable', 'exists:cities,id'],
            'state_id'   => ['nullable', 'exists:states,id'],
            'number'     => ['nullable', 'string', 'max:50'],
            'zip_code'   => ['nullable', 'formato_cep'],
            'reference'  => ['nullable', 'nullable', 'string'],
        ],[
            'street.string'        => 'O campo rua deve ser uma string.',
            'district.string'      => 'O campo bairro deve ser uma string.',
            'complement.string'    => 'O campo complemento deve ser uma string.',
            'city_id.exists'       => 'O campo Cidade deve ser uma Cidade valida.',
            'state_id.exists'      => 'O campo Estado deve ser um Estado valido.',
            'number.string'        => 'O campo número deve conter apenas letras e números',
            'number.max'           => 'O campo número não pode ter mais de 50 caracteres',
            'zip_code.formato_cep' => 'O campo CEP de ser um CEP valido.',
            'referencia.string'    => 'O campo referencia deve ser uma string.',
        ]);

        $validator->validate();
    }
}
