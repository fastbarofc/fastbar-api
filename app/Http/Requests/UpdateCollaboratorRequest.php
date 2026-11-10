<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class UpdateCollaboratorRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        $collaboratorId = $this->route('collaborator')->id;

        $data = [
            'name'       => 'nullable|string|max:255',
            'nickname'   => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'cpf'        => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('collaborators', 'cpf')->ignore($collaboratorId)->whereNull('deleted_at'),
            ],
            'rg'         => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('collaborators', 'rg')->ignore($collaboratorId)->whereNull('deleted_at'),
            ],
            'email'      => [
                'nullable', 
                'email',
                Rule::unique('collaborators', 'email')->ignore($collaboratorId)->whereNull('deleted_at'),
            ],
            'phone_1'    => 'nullable|string|max:20',
            'phone_2'    => 'nullable|string|max:20',
            'sector_id'  => 'nullable|exists:sectors,id',
            'sector_office_id'  => 'nullable|exists:sector_offices,id',
            'employment_type' => 'nullable|in:clt,freelancer',
            'biometric_consent' => 'nullable|boolean',
            'has_login'  => 'nullable|boolean',
            'active'     => 'nullable|boolean',
            'user_id'    => 'nullable|exists:users,id',
            'role_id'    => 'nullable|exists:roles,id',
            'pix_keys'   => 'nullable|array',
            'bank_details' => 'nullable|array',
            'qualifications' => 'nullable|array',
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'exists:companies,id',
        ];

        // E-mail é opcional, mas continua obrigatório para quem tem acesso ao sistema (login).
        if ($this->request->get('has_login')) {
            $data['email'][0] = 'required';
        }

        /* if ($this->request->get('has_login')) {
            $data['password'] = 'nullable|string|min:8';
            $data['email']    = [
                'nullable',
                'email',
                Rule::unique('collaborators', 'email')->ignore($collaboratorId),
            ];
        } */

        if (!empty($this->request->all()['address']) && !empty($data['address']['street'])) {
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
            'cpf.unique'              => 'O CPF já está sendo utilizado',
            'rg.string'               => 'O campo RG deve ser uma string.',
            'rg.max'                  => 'O campo RG deve ter no máximo 20 caracteres.',
            'rg.unique'               => 'O RG já está sendo utilizado',
            'phone_1.string'          => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'             => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_2.string'          => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'             => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'email.email'             => 'O campo e-mail deve ser um e-mail válido.',
            'email.unique'            => 'E-mail já cadastrado.',
            'email.required'          => 'Para dar acesso ao sistema (login), informe o e-mail.',
            'sector_id.exists'        => 'O campo setor deve ser um setor válido.',
            'sector_office_id.exists'        => 'O campo cargo deve ser um cargo válido.',
            'has_login.boolean'       => 'O campo possui login deve ser um booleano.',
            'active.boolean'         => 'O campo ativo deve ser um booleano.',
            'role_id.exists'         => 'O perfil selecionado não é válido.',
            'company_ids.required'   => 'Selecione pelo menos uma empresa.',
            'company_ids.min'        => 'Selecione pelo menos uma empresa.',
            'company_ids.*.exists'   => 'A empresa selecionada não é válida.',
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
            'reference'  => ['nullable', 'string'],
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
