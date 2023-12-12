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
            'date_time'          => 'nullable|date|after:now',
            'value_unit'         => 'nullable|string',
            'descont_unit'       => 'nullable|string',
            'contract_body'      => 'nullable',
            'courtesy_parthenon' => 'nullable|string|max:255',
            'courtesy_partner'   => 'nullable|string|max:255',
            'courtesies'         => 'nullable|string|max:255',
            'materials'          => 'nullable|array',
            'materials.*'        => 'nullable|exists:materials,id',
            'office_events'      => 'nullable|array',
            'office_events.*'    => 'nullable|exists:office_events,id',
            'clients'            => 'nullable|array',
            'clients.*'          => 'nullable|exists:clients,id',
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
            'date_time.after'           => 'A data e hora devem ser maiores que a data e hora atuais.',
            'value_unit.string'         => 'O campo valor unitário deve ser uma string',
            'descont_unit.string'       => 'O campo desconto unitário deve ser uma string',
            'courtesy_parthenon.string' => 'O campo cortesia parthenon deve ser uma string',
            'courtesy_parthenon.max'    => 'O campo cortesia parthenon deve ter no máximo 255 caracteres.',
            'courtesy_partner.string'   => 'O campo cortesia parceiro deve ser uma string',
            'courtesy_partner.max'      => 'O campo cortesia parceiro deve ter no máximo 255 caracteres.',
            'courtesies.string'         => 'O campo cortesias deve ser uma string',
            'courtesies.max'            => 'O campo cortesias deve ter no máximo 255 caracteres.',
            'materials.*.exists'        => 'O campo ID do material deve ser um ID válido.',
            'office_events.*.exists'    => 'O campo ID função do evento deve ser um ID válido.',
            'clients.*.exists'          => 'O campo ID do cliente deve ser um ID válido.',
        ];
    }
}
