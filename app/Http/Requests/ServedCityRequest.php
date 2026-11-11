<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServedCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                         => 'required|string|max:255',
            'state'                        => 'nullable|string|size:2',
            'served'                       => 'nullable|boolean',
            'min_guests'                   => 'nullable|integer|min:0',
            'user_id'                      => 'nullable|exists:users,id',
            'segment_rules'                => 'nullable|array',
            'segment_rules.*.event_type_id' => 'required|exists:event_types,id',
            'segment_rules.*.user_id'      => 'required|exists:users,id',
            'notes'                        => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da cidade é obrigatório.',
            'state.size'    => 'Informe a sigla do estado com 2 letras (ex.: MG).',
            'segment_rules.*.event_type_id.required' => 'Escolha o tipo de evento da regra.',
            'segment_rules.*.user_id.required' => 'Escolha o consultor da regra.',
        ];
    }
}
