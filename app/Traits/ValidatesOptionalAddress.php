<?php

namespace App\Traits;

trait ValidatesOptionalAddress
{
    // Endereço parcial é aceito (as colunas são nulas); só barra o que o banco recusaria.
    protected function optionalAddressRules(): array
    {
        return [
            'address' => 'nullable|array',
            'address.street' => 'nullable|string|max:191',
            'address.district' => 'nullable|string|max:191',
            'address.complement' => 'nullable|string|max:191',
            'address.number' => 'nullable|string|max:50',
            'address.zip_code' => 'nullable|string|max:191',
            'address.reference' => 'nullable|string|max:65535',
            'address.city_id' => 'nullable|integer|exists:cities,id',
            'address.state_id' => 'nullable|integer|exists:states,id',
        ];
    }

    protected function optionalAddressMessages(): array
    {
        return [
            'address.array' => 'O endereço é inválido.',
            'address.number.max' => 'O número do endereço deve ter no máximo 50 caracteres.',
            'address.*.max' => 'Um dos campos do endereço passou do tamanho máximo.',
            'address.city_id.exists' => 'A cidade informada não existe.',
            'address.state_id.exists' => 'O estado informado não existe.',
        ];
    }
}
