<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateEventPlaceRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventPlaceId = $this->route('event_place')->id;

        $data = [
            'name'       => [
                'nullable', 
                'string',
                'max:255',
                Rule::unique('event_places', 'name')->ignore($eventPlaceId),
            ],
            'nullable|string|max:255|unique:event_places,name,' . $this->route('event_place')->id,
            'responsible'       => 'nullable|string|max:255',
            'description'       => 'nullable|string|max:255',
            'phone_1'           => 'nullable|string|min:10|max:20',
            'phone_2'           => 'nullable|string|min:10|max:20',
            'email'             => 'nullable|email',
            'belongs_parthenon' => 'boolean',
        ];

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
            'name.unique'             => 'O nome informado já está em uso.',
            'responsible.string'      => 'O campo nome do responsável deve ser uma string.',
            'responsible.max'         => 'O campo nome do responsável deve ter no máximo 255 caracteres.',
            'description.string'      => 'O campo descrição deve ser uma string.',
            'description.max'         => 'O campo descrição deve ter no máximo 255 caracteres.',
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
