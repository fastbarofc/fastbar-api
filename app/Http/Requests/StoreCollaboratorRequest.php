<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class StoreCollaboratorRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        
        $data = [
            'name'       => 'required|string|max:255',
            'nickname'   => 'nullable|string|max:255',
            'birth_date' => 'nullable|date|date_format:Y-m-d',
            'cpf'        => 'nullable|string|max:14|unique:collaborators',
            'rg'         => 'nullable|string|max:20|unique:collaborators',
            'email'      => 'required|email|unique:collaborators,email',
            'phone_1'    => 'required|string|max:20',
            'phone_2'    => 'nullable|string|max:20',
            'sector_id'  => 'required|exists:sectors,id',
            'sector_office_id'  => 'required|exists:sector_offices,id',
            'has_login'  => 'nullable|boolean',
            'active'     => 'boolean',
            'user_id'    => 'nullable|exists:users,id',
            'pix_keys'   => 'nullable|array',
            'bank_details' => 'nullable|array',
        ];
        

         if ($this->request->get('has_login')) {
            $data['email']    = 'required|email|unique:users,email';
        } 

        if (!empty($this->request->all()['address']) && !empty($data['address']['street'])) {
            $this->validateAddress($this->request->all()['address']);
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'O campo nome é obrigatório.',
            'name.string'            => 'O campo nome deve ser uma string.',
            'name.max'               => 'O campo nome deve ter no máximo 255 caracteres.',
            'nickname.string'        => 'O campo apelido deve ser uma string.',
            'nickname.max'           => 'O campo apelido deve ter no máximo 255 caracteres.',
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
        ];
    }

    public function validateAddress(array $data) {
        $validator = Validator::make($data, [
            'street'     => ['required', 'string'],
            'district'   => ['required', 'string'],
            'complement' => ['nullable', 'string'],
            'city_id'    => ['required', 'exists:cities,id'],
            'state_id'   => ['required', 'exists:states,id'],
            'number'     => ['required', 'string', 'max:50'],
            'zip_code'   => ['required', 'formato_cep'],
            'reference'  => ['nullable', 'string'],
        ],[
            'street.required'      => 'O campo rua é obrigatório',
            'street.string'        => 'O campo rua deve ser uma string',
            'district.required'    => 'O campo bairro é obrigatório',
            'district.string'      => 'O campo bairro deve ser uma string',
            'complement.string'    => 'O campo complemento deve ser uma string',
            'city_id.required'     => 'O campo city ID é obrigatório',
            'city_id.exists'       => 'O campo Cidade deve ser uma Cidade valida',
            'state_id.required'    => 'O campo state ID é obrigatório',
            'state_id.exists'      => 'O campo Estado deve ser um Estado valido',
            'number.required'      => 'O campo número é obrigatório',
            'number.string'        => 'O campo número deve conter apenas letras e números',
            'number.max'           => 'O campo número não pode ter mais de 50 caracteres',
            'zip_code.required'    => 'O campo CEP é obrigatório',
            'zip_code.formato_cep' => 'O campo CEP de ser um CEP valido',
            'referencia.string'    => 'O campo referencia deve ser uma string',
        ]);

        $validator->validate();
    }
}