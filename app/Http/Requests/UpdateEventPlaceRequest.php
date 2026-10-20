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

    protected function prepareForValidation(): void
    {
        // O front envia o CEP sem máscara, mas `formato_cep` só aceita 00000-000.
        $address = $this->input('address');

        if (is_array($address) && array_key_exists('zip_code', $address)) {
            $address['zip_code'] = maskZipCode($address['zip_code']);
            $this->merge(['address' => $address]);
        }
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
            'responsible'       => 'sometimes|required|string|max:255',
            'description'       => 'nullable|string|max:255',
            'phone_1'           => 'sometimes|nullable|required_without:phone_2|string|min:10|max:20',
            'phone_2'           => 'sometimes|nullable|required_without:phone_1|string|min:10|max:20',
            'email'             => 'nullable|email',
            'belongs_parthenon' => 'boolean',
        ];

        if ($this->has('address')) {
            $this->validateAddress($this->input('address') ?? []);
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'name.string'             => 'O campo nome deve ser uma string.',
            'name.max'                => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'             => 'O nome informado já está em uso.',
            'responsible.required'    => 'O campo responsável é obrigatório.',
            'responsible.string'      => 'O campo nome do responsável deve ser uma string.',
            'responsible.max'         => 'O campo nome do responsável deve ter no máximo 255 caracteres.',
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
            'number'     => ['required', 'string', 'max:50'],
            'zip_code'   => ['required', 'formato_cep'],
            'reference'  => ['nullable', 'string'],
        ],[
            'street.required'      => 'O campo rua é obrigatório.',
            'street.string'        => 'O campo rua deve ser uma string.',
            'district.required'    => 'O campo bairro é obrigatório.',
            'district.string'      => 'O campo bairro deve ser uma string.',
            'complement.string'    => 'O campo complemento deve ser uma string.',
            'city_id.required'     => 'O campo cidade é obrigatório.',
            'city_id.exists'       => 'O campo Cidade deve ser uma Cidade valida.',
            'state_id.required'    => 'O campo estado é obrigatório.',
            'state_id.exists'      => 'O campo Estado deve ser um Estado valido.',
            'number.required'      => 'O campo número é obrigatório.',
            'number.string'        => 'O campo número deve conter apenas letras e números',
            'number.max'           => 'O campo número não pode ter mais de 50 caracteres',
            'zip_code.required'    => 'O campo CEP é obrigatório.',
            'zip_code.formato_cep' => 'O campo CEP de ser um CEP valido.',
            'referencia.string'    => 'O campo referencia deve ser uma string.',
            
    ]);

        $validator->validate();
    }
}
