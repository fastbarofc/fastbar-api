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
            'name'               => 'string|max:255|unique:events,name,' . $this->event->id,
            'responsible'        => 'string|max:255',
            'event_type_id'      => 'exists:event_types,id',
            'event_place_id'     => 'exists:event_places,id',
            'lease'              => 'string|max:255',
            'guests'             => 'numeric',
            'min_guests'         => 'numeric|min:1',
            'date'               => 'date',
            'time'               => 'date|date_format:HH:mm',
            'value_unit'         => 'string',
            'descont_unit'       => 'string',
            'courtesy_parthenon' => 'string|max:255',
            'courtesy_partner'   => 'string|max:255',
            'courtesies'         => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'               => 'O campo nome deve ser uma string',
            'name.max'                  => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'               => 'O nome informado já está em uso',
            'responsible.required'      => 'O campo responsável é obrigatório',
            'responsible.string'        => 'O campo responsável deve ser uma string',
            'responsible.max'           => 'O campo responsável deve ter no máximo 255 caracteres.',
            'event_type_id.exists'      => 'O campo tipo de evento deve ser um tipo válido.',
            'event_place_id.exists'     => 'O campo local do evento deve ser um local válido.',
            'lease.string'              => 'O campo aluguel deve ser uma string',
            'lease.max'                 => 'O campo aluguel deve ter no máximo 255 caracteres.',
            'guests.numeric'            => 'O campo convidados deve conter apenas números',
            'min_guests.numeric'        => 'O campo convidados mínimos deve conter apenas números',
            'min_guests.min'            => 'O campo convidados mínimos deve conter pelo menos 1 convidado',
            'date.date'                 => 'O campo data é deve ser uma data valida',
            'time.date_format'          => 'O campo hora é deve estar no formato válido',
            'value_unit.string'         => 'O campo valor unitário deve ser uma string',
            'descont_unit.string'       => 'O campo desconto unitário deve ser uma string',
            'courtesy_parthenon.string' => 'O campo cortesia parthenon deve ser uma string',
            'courtesy_parthenon.max'    => 'O campo cortesia parthenon deve ter no máximo 255 caracteres.',
            'courtesy_partner.string'   => 'O campo cortesia parceiro deve ser uma string',
            'courtesy_partner.max'      => 'O campo cortesia parceiro deve ter no máximo 255 caracteres.',
            'courtesies.string'         => 'O campo cortesias deve ser uma string',
            'courtesies.max'            => 'O campo cortesias deve ter no máximo 255 caracteres.',
        ];
    }
}
