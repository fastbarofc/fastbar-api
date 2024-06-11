<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{

    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => 'nullable|string|max:255',
            'event_type_id'      => 'nullable|exists:event_types,id',
            'event_place_id'     => 'nullable|exists:event_places,id',
            'lease'              => 'nullable|numeric|min:0',
            'guests'             => 'nullable|numeric',
            'min_guests'         => 'nullable|numeric|min:1',
            'date_time'          => 'nullable|date',
            'unit_value'         => 'nullable|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'unit_discount'       => 'nullable|numeric|min:0',
            'contract_body'      => 'nullable',
            'parthenon_courtesies' => 'nullable|numeric|min:0',
            'partner_courtesies'   => 'nullable|numeric|min:0',
            'materials'          => 'nullable|array',
            'materials.*'        => 'nullable|exists:materials,id',
            'office_events'      => 'nullable|array',
            'office_events.*'    => 'nullable|exists:office_events,id',
            'legal_representatives'            => 'nullable|array',
            'legal_representatives.*'          => 'nullable|exists:clients,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'               => 'O campo nome deve ser uma string',
            'name.max'                  => 'O campo nome deve ter no máximo 255 caracteres.',
            'event_type_id.exists'      => 'O campo tipo de evento deve ser um tipo válido.',
            'event_place_id.exists'     => 'O campo local do evento deve ser um local válido.',
            'lease.string'              => 'O campo aluguel deve ser uma string',
            'lease.min'                 => 'O campo aluguel não pode ser negativo',
            'guests.numeric'            => 'O campo convidados deve conter apenas números',
            'min_guests.numeric'        => 'O campo convidados mínimos deve conter apenas números',
            'min_guests.min'            => 'O campo convidados mínimos deve conter pelo menos 1 convidado',
            'date_time.date'            => 'O campo data e hora deve ser uma data valida',
            'unit_value.numeric'        => 'O campo valor unitário deve conter apenas números',
            'unit_value.min'            => 'O campo valor unitário não pode ser negativo',
            'discount.numeric'          => 'O campo desconto deve conter apenas números',
            'discount.min'              => 'O campo desconto não pode ser negativo',
            'unit_discount.numeric'     => 'O campo desconto unitário deve conter apenas números',
            'unit_discount.min'         => 'O campo desconto unitário não pode ser negativo',
            'parthenon_courtesies.numeric' => 'O campo cortesias parthenon deve conter apenas números',
            'parthenon_courtesies.min'     => 'O campo cortesias parthenon não pode ser negativo',
            'partner_courtesies.numeric'   => 'O campo cortesias parceiro deve conter apenas números',
            'partner_courtesies.min'       => 'O campo cortesias parceiro não pode ser negativo',
            'materials.*.exists'        => 'O campo ID do material deve ser um ID válido.',
            'office_events.*.exists'    => 'O campo ID função do evento deve ser um ID válido.',
            'legal_representatives.*.exists'          => 'O campo ID do representante legal deve ser um ID válido.',
        ];
    }
}
