<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;


class UpdateCollaboratorRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        $data = [
            'name'       => 'string|max:255',
            'nickname'   => 'string|max:255',
            'birth_date' => 'date',
            'cpf'        => 'string|max:14|unique:collaborators,cpf,' . $this->route('collaborator')->id,
            'rg'         => 'string|max:20|unique:collaborators,rg,' . $this->route('collaborator')->id,
            'email'      => 'email',
            'phone_1'    => 'string|max:20|celular_com_ddd',
            'phone_2'    => 'string|max:20|celular_com_ddd',
            'sector_id'  => 'exists:sectors,id',
            'office_id'  => 'exists:offices,id',
            'has_login'  => 'boolean',
            'user_id'    => 'nullable|exists:users,id',
        ];

        if ($this->request->get('has_login')) {
            $data['password'] = 'required|string|min:8';
            $data['email']    = 'required|email|unique:users,email,' . $this->route('collaborator')->id;
        }

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
            'nickname.string'         => 'O campo apelido deve ser uma string.',
            'nickname.max'            => 'O campo apelido deve ter no máximo 255 caracteres.',
            'birth_date.date'         => 'O campo data de nascimento deve ser uma data válida.',
            'cpf.string'              => 'O campo CPF deve ser uma string.',
            'cpf.max'                 => 'O campo CPF deve ter no máximo 14 caracteres.',
            'cpf.cpf'                 => 'O campo CPF deve ser um CPF válido.',
            'cpf.unique'                 => 'O CPF já está sendo utiliado',
            'rg.string'               => 'O campo RG deve ser uma string.',
            'rg.max'                  => 'O campo RG deve ter no máximo 20 caracteres.',
            'rg.unique'                  => 'O RG já está sendo utiliado',
            'phone_1.string'          => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'             => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_1.celular_com_ddd' => 'O campo telefone 1 deve ser um telefone válido.',
            'phone_2.string'          => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'             => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'phone_2.celular_com_ddd' => 'O campo telefone 2 deve ser um telefone válido.',
            'email.email'             => 'O campo e-mail deve ser um e-mail válido.',
            'email.unique'            => 'E-mail já cadastrado.',
            'sector_id.exists'        => 'O campo setor deve ser um setor válido.',
            'office_id.exists'        => 'O campo cargo deve ser um cargo válido.',
            'has_login.boolean'       => 'O campo possui login deve ser um booleano.',
            'password.string'         => 'O campo senha deve ser uma string.',
            'password.min'            => 'O campo senha deve ter no mínimo 8 caracteres.'
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
