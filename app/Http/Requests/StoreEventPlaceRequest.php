<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;


class StoreEventPlaceRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $data = [
            'name'        => 'required|string|max:255|unique:event_places',
            'responsible' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'phone_1'     => 'required|string|min:10|max:20',
            'phone_2'     => 'nullable|string|min:10|max:20',
            'email'       => 'required|email',
        ];

        if (!empty($this->request->all()['address'])) {
            $this->validateAddress($this->request->all()['address']);
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'O campo nome é obrigatório.',
            'name.string'             => 'O campo nome deve ser uma string.',
            'name.max'                => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'             => 'O nome informado já está em uso.',
            'responsible.required'    => 'O campo nomeresponsável é obrigatório.',
            'responsible.string'      => 'O campo nomeresponsável deve ser uma string.',
            'responsible.max'         => 'O campo nomeresponsável deve ter no máximo 255 caracteres.',
            'description.required'    => 'O campo descrição é obrigatório.',
            'description.string'      => 'O campo descrição deve ser uma string.',
            'description.max'         => 'O campo descrição deve ter no máximo 255 caracteres.',
            'phone_1.required'        => 'O campo telefone 1 é obrigatório.',
            'phone_1.string'          => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'             => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_1.celular_com_ddd' => 'O campo telefone 1 deve ser um telefone válido.',
            'phone_2.string'          => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'             => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'phone_2.celular_com_ddd' => 'O campo telefone 2 deve ser um telefone válido.',
            'email.email'             => 'O campo e-mail deve ser um e-mail válido.',
            'email.required'          => 'O campo e-mail é obrigatório.',
            'email.unique'            => 'E-mail já cadastrado.',
        ];
    
    
    }

    public function validateAddress(array $data) {
        $validator = Validator::make($data, [
            'street'     => ['required', 'string'],
            'district'   => ['required', 'string'],
            'complement' => ['nullable', 'string'],
            'city_id'    => ['required', 'exists:cities,id'],
            'state_id'   => ['required', 'exists:states,id'],
            'number'     => ['required'],
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
            'zip_code.required'    => 'O campo CEP é obrigatório',
            'zip_code.formato_cep' => 'O campo CEP de ser um CEP valido',
            'referencia.string'    => 'O campo referencia deve ser uma string',
            
    ]);

        $validator->validate();
    }
}
