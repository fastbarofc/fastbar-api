<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class UpdateEventPlaceRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $data = [
            'name'       => 'string|max:255|unique:event_places,name,' . $this->route('event_place')->id,
            'responsible'=> 'string|max:255',
            'description'=> 'string|max:255',
            'phone_1'    => 'string|min:10|max:20',
            'phone_2'    => 'string|min:10|max:20',
            'email'      => 'email',
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
            'name.unique'             => 'O nome informado já está em uso.',
            'responsible.string'      => 'O campo nomeresponsável deve ser uma string.',
            'responsible.max'         => 'O campo nomeresponsável deve ter no máximo 255 caracteres.',
            'description.string'      => 'O campo dercrição deve ser uma string.',
            'description.max'         => 'O campo dercrição deve ter no máximo 255 caracteres.',
            'phone_1.string'          => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'             => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_1.celular_com_ddd' => 'O campo telefone 1 deve ser um telefone válido.',
            'phone_2.string'          => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'             => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'phone_2.celular_com_ddd' => 'O campo telefone 2 deve ser um telefone válido.',
            'email.email'             => 'O campo e-mail deve ser um e-mail válido.',
            'email.unique'            => 'E-mail já cadastrado.',
        ];
    
    
    }

    public function validateAddress(array $data) {
        $validator = Validator::make($data, [
            'street'     => ['string'],
            'district'   => ['string'],
            'complement' => ['string'],
            'city_id'    => ['exists:cities,id'],
            'state_id'   => ['exists:states,id'],
            'number'     => ['integer'],
            'zip_code'   => ['formato_cep'],
            'reference'  => ['nullable', 'string'],
        ],[
            'street.string'        => 'O campo rua deve ser uma string.',
            'district.string'      => 'O campo bairro deve ser uma string.',
            'complement.string'    => 'O campo complemento deve ser uma string.',
            'city_id.exists'       => 'O campo Cidade deve ser uma Cidade valida.',
            'state_id.exists'      => 'O campo Estado deve ser um Estado valido.',
            'number.integer'       => 'O campo número é deve conter apenas números.',
            'zip_code.formato_cep' => 'O campo CEP de ser um CEP valido.',
            'referencia.string'    => 'O campo referencia deve ser uma string.',
            
    ]);

        $validator->validate();
    }
}
