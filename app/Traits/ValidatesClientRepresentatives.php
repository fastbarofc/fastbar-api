<?php

namespace App\Traits;

use App\Models\Client;
use App\Rules\Cpf;
use Illuminate\Validation\Rule;

trait ValidatesClientRepresentatives
{
    private function normalizeRepresentatives(): void
    {
        $representatives = $this->input('representatives');

        if (!is_array($representatives)) {
            return;
        }

        $this->merge([
            'representatives' => array_map(function ($representative) {
                if (!is_array($representative)) {
                    return $representative;
                }

                if (array_key_exists('cpf', $representative)) {
                    $representative['cpf'] = unmaskCpf($representative['cpf']);
                }

                if (is_array($representative['address'] ?? null) && array_key_exists('zip_code', $representative['address'])) {
                    $representative['address']['zip_code'] = maskZipCode($representative['address']['zip_code']);
                }

                return $representative;
            }, $representatives),
        ]);
    }

    private function representativeRules(bool $required, ?Client $client = null): array
    {
        $rules = [
            'representatives' => [$required ? 'required' : 'nullable', 'array'],
            'representatives.*.id' => [
                'nullable',
                'integer',
                Rule::exists('client_representatives', 'id')->where('client_id', $client?->id),
            ],
            'representatives.*.name' => ['required', 'string', 'max:255'],
            'representatives.*.cpf' => [$required ? 'required' : 'nullable', 'string', new Cpf],
            'representatives.*.address' => [$required ? 'required' : 'nullable', 'array'],
        ];

        if (!$required) {
            return $rules;
        }

        return $rules + [
            'representatives.*.address.street' => ['required', 'string'],
            'representatives.*.address.district' => ['required', 'string'],
            'representatives.*.address.complement' => ['nullable', 'string'],
            'representatives.*.address.city_id' => ['required', 'exists:cities,id'],
            'representatives.*.address.state_id' => ['required', 'exists:states,id'],
            'representatives.*.address.number' => ['required', 'string', 'max:50'],
            'representatives.*.address.zip_code' => ['required', 'formato_cep'],
            'representatives.*.address.reference' => ['nullable', 'string'],
        ];
    }

    private function representativeMessages(): array
    {
        return [
            'representatives.required' => 'Cadastre pelo menos um representante para pessoa jurídica.',
            'representatives.array' => 'Os representantes devem ser uma lista.',
            'representatives.*.id.exists' => 'O :positionº representante não pertence a este cliente.',
            'representatives.*.name.required' => 'O nome do :positionº representante é obrigatório.',
            'representatives.*.name.max' => 'O nome do :positionº representante deve ter no máximo 255 caracteres.',
            'representatives.*.cpf.required' => 'O CPF do :positionº representante é obrigatório.',
            'representatives.*.address.required' => 'O endereço do :positionº representante é obrigatório.',
            'representatives.*.address.street.required' => 'A rua do :positionº representante é obrigatória.',
            'representatives.*.address.district.required' => 'O bairro do :positionº representante é obrigatório.',
            'representatives.*.address.city_id.required' => 'A cidade do :positionº representante é obrigatória.',
            'representatives.*.address.city_id.exists' => 'A cidade do :positionº representante deve ser uma cidade válida.',
            'representatives.*.address.state_id.required' => 'O estado do :positionº representante é obrigatório.',
            'representatives.*.address.state_id.exists' => 'O estado do :positionº representante deve ser um estado válido.',
            'representatives.*.address.number.required' => 'O número do endereço do :positionº representante é obrigatório.',
            'representatives.*.address.number.max' => 'O número do endereço do :positionº representante não pode ter mais de 50 caracteres.',
            'representatives.*.address.zip_code.required' => 'O CEP do :positionº representante é obrigatório.',
            'representatives.*.address.zip_code.formato_cep' => 'O CEP do :positionº representante deve ser um CEP válido.',
        ];
    }
}
