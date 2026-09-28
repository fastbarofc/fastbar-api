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
            'name'              => 'required|string|max:255|unique:event_places',
            'responsible'       => 'required|string|max:255',
            'description'       => 'nullable|string|max:255',
            'phone_1'           => 'nullable|required_without:phone_2|string|min:10|max:20',
            'phone_2'           => 'nullable|required_without:phone_1|string|min:10|max:20',
            'email'             => 'nullable|email',
            'belongs_parthenon' => 'nullable|boolean',
        ];

        $this->validateAddress($this->input('address') ?? []);

        return $data;
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'O campo nome é obrigatório.',
            'name.string'             => 'O campo nome deve ser uma string.',
            'name.max'                => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'             => 'O nome informado já está em uso.',
            'responsible.required'    => 'O campo responsável é obrigatório.',
            'responsible.string'      => 'O campo nomeresponsável deve ser uma string.',
            'responsible.max'         => 'O campo nomeresponsável deve ter no máximo 255 caracteres.',
            'description.string'      => 'O campo descrição deve ser uma string.',
            'description.max'         => 'O campo descrição deve ter no máximo 255 caracteres.',
            'phone_1.required_without' => 'Informe o celular ou o telefone.',
            'phone_2.required_without' => 'Informe o celular ou o telefone.',
            'phone_1.string'          => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'             => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_2.string'          => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'             => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'email.email'             => 'O campo e-mail deve ser um e-mail válido.',
            'email.unique'            => 'E-mail já cadastrado.',
            'belongs_parthenon.boolean' => 'O campo pertence ao Parthenon deve ser um valor booleano',
        ];
    
    
    }

    public function validateAddress(array $data) {
        $validator = Validator::make($data, [
            'street'     => ['required', 'string'],
            'district'   => ['required', 'string'],
            'complement' => ['nullable', 'string'],
            'city_id'    => ['required', 'exists:cities,id'],
            'state_id'   => ['required', 'exists:states,id'],
            'number'     => ['required', 'string'],
            'zip_code'   => ['required', 'regex:/^\d{5}-?\d{3}$/'],
            'reference'  => ['nullable', 'string'],
        ],[
            'street.required'      => 'O campo rua é obrigatório',
            'street.string'        => 'O campo rua deve ser uma string',
            'district.required'    => 'O campo bairro é obrigatório',
            'district.string'      => 'O campo bairro deve ser uma string',
            'complement.string'    => 'O campo complemento deve ser uma string',
            'city_id.required'     => 'O campo cidade é obrigatório',
            'city_id.exists'       => 'O campo Cidade deve ser uma Cidade valida',
            'state_id.required'    => 'O campo estado é obrigatório',
            'state_id.exists'      => 'O campo Estado deve ser um Estado valido',
            'number.required'      => 'O campo número é obrigatório',
            'number.string'        => 'O campo número deve conter apenas letras e números',
            'zip_code.required'    => 'O campo CEP é obrigatório',
            'zip_code.regex'       => 'O campo CEP de ser um CEP valido',
            'referencia.string'    => 'O campo referencia deve ser uma string',
        ]);

        $validator->validate();
    }
}
