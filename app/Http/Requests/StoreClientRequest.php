<?php

namespace App\Http\Requests;

use App\Rules\Cnpj;
use App\Rules\Cpf;
use App\Traits\ValidatesClientRepresentatives;
use App\Traits\ValidatesOptionalAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    use ValidatesOptionalAddress, ValidatesClientRepresentatives;

    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Os documentos são normalizados antes da validação para que cheguem ao banco sem máscara.
     */
    protected function prepareForValidation(): void
    {
        $documents = [];

        if ($this->has('cpf')) {
            $documents['cpf'] = unmaskCpf($this->input('cpf'));
        }

        if ($this->has('cnpj')) {
            $documents['cnpj'] = unmaskCnpj($this->input('cnpj'));
        }

        if (!empty($documents)) {
            $this->merge($documents);
        }

        // O front envia o CEP sem máscara, mas `formato_cep` só aceita 00000-000.
        $address = $this->input('address');

        if (is_array($address) && array_key_exists('zip_code', $address)) {
            $address['zip_code'] = maskZipCode($address['zip_code']);
            $this->merge(['address' => $address]);
        }

        $this->normalizeRepresentatives();
    }

    public function rules(): array
    {
        $data = [
            'type'         => 'nullable|string|in:pf,pj',
            'email' => [
                'required_if:has_login,true',
                'nullable',
                'email',
                Rule::unique('clients')->whereNull('deleted_at'),
            ],
            'phone_1'      => 'required|string|max:20',
            'phone_2'      => 'nullable|string|max:20',
            'name'         => 'required|string|max:191',
            'nickname'     => 'nullable|string|max:191',
            'birth_date'   => 'nullable|date_format:Y-m-d',
            'cpf'          => ['nullable', 'string', new Cpf],
            'rg'           => 'nullable|string|max:20',
            'cnpj'         => ['nullable', 'string', new Cnpj],
            'ie'           => 'nullable|string|max:191',
            'im'           => 'nullable|string|max:191',
            'has_login'    => 'nullable|boolean',
            'quick_registration'  => 'nullable|boolean',
        ] + $this->representativeRules($this->input('type') === 'pj' && !$this->boolean('quick_registration'));

        if ($this->boolean('quick_registration')) {
            $data['phone_1'] = 'nullable|string|max:20';
        }

        return array_merge($data, $this->optionalAddressRules());
    }

    public function messages(): array
    {
        return array_merge($this->optionalAddressMessages(), [
            'type.string'         => 'O campo tipo deve ser uma string.',
            'type.in'             => 'O tipo informado de ser PF ou PJ.',
            'email.email'         => 'O campo e-mail deve ser um e-mail válido.',
            'email.unique'        => 'Esse E-mail já está cadastrado.',
            'phone_1.required'    => 'O campo telefone 1 é obrigatório.',
            'phone_1.string'      => 'O campo telefone 1 deve ser uma string.',
            'phone_1.max'         => 'O campo telefone 1 deve ter no máximo 20 caracteres.',
            'phone_2.string'      => 'O campo telefone 2 deve ser uma string.',
            'phone_2.max'         => 'O campo telefone 2 deve ter no máximo 20 caracteres.',
            'name.required'       => 'O campo nome é obrigatório.',
            'name.string'         => 'O campo nome deve ser uma string.',
            'name.max'            => 'O campo nome deve ter no máximo 191 caracteres.',
            'nickname.string'     => 'O campo apelido deve ser uma string.',
            'nickname.max'        => 'O campo apelido deve ter no máximo 191 caracteres.',
            'birth_date.date'     => 'O campo data de nascimento deve ser uma data válida.',
            'cpf.string'          => 'O campo CPF deve ser uma string.',
            'rg.string'           => 'O campo RG deve ser uma string.',
            'rg.max'              => 'O campo RG deve ter no máximo 20 caracteres.',
            'cnpj.string'         => 'O campo CNPJ deve ser uma string.',
            'ie.string'           => 'O campo inscrição estadual deve ser uma string.',
            'ie.max'              => 'O campo inscrição estadual deve ter no máximo 191 caracteres.',
            'im.string'           => 'O campo inscrição municipal deve ser uma string.',
            'im.max'              => 'O campo inscrição municipal deve ter no máximo 191 caracteres.',
        ]) + $this->representativeMessages();
    }
}
