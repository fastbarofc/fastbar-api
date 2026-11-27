<?php

namespace App\Http\Requests;

use App\Traits\ValidatesOptionalAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollaboratorRequest extends FormRequest
{
    use ValidatesOptionalAddress;

    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        
        $data = [
            'name'       => 'required|string|max:191',
            'nickname'   => 'nullable|string|max:191',
            'birth_date' => 'nullable|date|date_format:Y-m-d',
            'cpf'        => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('collaborators')->whereNull('deleted_at'),
            ],
            'rg'         => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('collaborators')->whereNull('deleted_at'),
            ],
            'email'      => [
                'required',
                'email',
                'max:191',
                Rule::unique('collaborators')->whereNull('deleted_at'),
            ],
            'phone_1'    => 'required|string|max:20',
            'phone_2'    => 'nullable|string|max:20',
            'sector_id'  => 'required|exists:sectors,id',
            'sector_office_id'  => 'required|exists:sector_offices,id',
            'has_login'  => 'sometimes|boolean',
            'active'     => 'boolean',
            'user_id'    => 'nullable|exists:users,id',
            'pix_keys'   => 'nullable|array',
            'bank_details' => 'nullable|array',
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'exists:companies,id',
        ];
        

        if ($this->request->get('has_login')) {
            $data['email'] = [
                'required',
                'email',
                'max:191',
                Rule::unique('users')->whereNull('deleted_at'),
            ];
            $data['role_id'] = 'required|exists:roles,id';
        }
        $needsNewUser = $this->boolean('has_login');

        return array_merge($data, $this->optionalAddressRules(), [
            'role_id' => ['nullable', 'integer', 'exists:roles,id', Rule::requiredIf($needsNewUser)],
            'goals' => 'nullable|array',
            'goals.*.id' => 'nullable|integer',
            'goals.*.type' => 'nullable|in:monthly,yearly',
            'goals.*.start_date' => 'nullable|date',
            'goals.*.end_date' => 'nullable|date',
            'goals.*.expected_contracts' => 'nullable|integer|min:0|max:2147483647',
            'qualifications' => 'nullable|array',
            'qualifications.*.id' => 'nullable|integer|exists:qualifications,id',
            'qualifications.*.points' => 'nullable|integer|min:0|max:2147483647',
            'bank_details.*.bank_name' => 'nullable|string|max:191',
            'bank_details.*.agency' => 'nullable|string|max:191',
            'bank_details.*.account' => 'nullable|string|max:191',
            'bank_details.*.account_holder' => 'nullable|string|max:191',
            'bank_details.*.status' => 'nullable|boolean',
            'pix_keys.*.type' => 'nullable|string|max:191',
            'pix_keys.*.key' => 'nullable|string|max:191',
        ]);
    }

    public function messages(): array
    {
        return array_merge($this->optionalAddressMessages(), [
            'role_id.required' => 'Escolha o grupo de usuário para liberar o acesso ao sistema.',
            'goals.*.type' => 'O tipo da meta deve ser mensal ou anual.',
            'goals.*.*' => 'Um dos campos das metas é inválido.',
            'qualifications.*.*' => 'Uma das qualificações é inválida.',
            'bank_details.*.*' => 'Um dos dados bancários é inválido ou longo demais.',
            'pix_keys.*.*' => 'Uma das chaves pix é inválida ou longa demais.',
            'name.required'          => 'O campo nome é obrigatório.',
            'name.string'            => 'O campo nome deve ser uma string.',
            'name.max'               => 'O campo nome deve ter no máximo 191 caracteres.',
            'nickname.string'        => 'O campo apelido deve ser uma string.',
            'nickname.max'           => 'O campo apelido deve ter no máximo 191 caracteres.',
            'birth_date.date'        => 'O campo data de nascimento deve ser uma data válida.',
            'birth_date.date_format' => 'O campo data de nascimento deve ser no formato Y-m-d.',
            'cpf.string'             => 'O campo CPF deve ser uma string.',
            'cpf.max'                => 'O campo CPF deve ter no máximo 14 caracteres.',
            'cpf.unique'             => 'O CPF já está sendo utilizado',
            'rg.string'              => 'O campo RG deve ser uma string.',
            'rg.max'                 => 'O campo RG deve ter no máximo 20 caracteres.',
            'rg.unique'              => 'O RG já está sendo utilizado',
            'phone_1.required'       => 'O campo telefone 1 é obrigatório.',
            'phone_1.string'         => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'            => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_2.string'         => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'            => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'email.email'            => 'O campo e-mail deve ser um e-mail válido.',
            'email.required'         => 'O campo e-mail é obrigatório.',
            'email.unique'           => 'E-mail já cadastrado.',
            'sector_id.required'     => 'O campo setor é obrigatório.',
            'sector_id.exists'       => 'O campo setor deve ser um setor válido.',
            'sector_office_id.required'     => 'O campo cargo é obrigatório.',
            'sector_office_id.exists'       => 'O campo cargo deve ser um cargo válido.',
            'has_login.boolean'      => 'O campo possui login deve ser um booleano.',
            'active.required'        => 'O campo ativo é obrigatório.',
            'active.boolean'         => 'O campo ativo deve ser um booleano.',
            'role_id.required'       => 'O campo perfil é obrigatório quando o colaborador possui login.',
            'role_id.exists'         => 'O perfil selecionado não é válido.',
            'company_ids.required'    => 'Selecione pelo menos uma empresa.',
            'company_ids.min'         => 'Selecione pelo menos uma empresa.',
            'company_ids.*.exists'    => 'A empresa selecionada não é válida.',
        ]);
    }
}