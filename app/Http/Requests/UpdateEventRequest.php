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
            'date'              => 'nullable|date',
            'time'              => 'nullable|date_format:H:i',
            'contract_body'      => 'nullable',
            'parthenon_courtesies' => 'nullable|numeric|min:0',
            'partner_courtesies'   => 'nullable|numeric|min:0',
            'total_value'   => 'nullable|numeric|min:0',
            'sub_total'   => 'nullable|numeric|min:0',
            'buffet_value'   => 'nullable|numeric|min:0',
            'event_guests' => 'nullable|numeric|min:0',
            'present_guests' => 'nullable|numeric|min:0',
            'guest_unit_value' => 'nullable|numeric|min:0',
            'excess_unit_value' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'unit_discount' => 'nullable|numeric|min:0',
            'materials'          => 'nullable|array',
            'office_events'      => 'nullable|array',
            'legal_representatives'            => 'nullable|array',
            'legal_representatives.*'          => 'nullable|exists:clients,id',
            'min_guests' => 'nullable|integer|min:1',
            'is_medicine' => 'nullable|boolean',
            'old_system_id' => 'nullable|string|unique:events,old_system_id,' . $this->route('event')->id,

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
            'date.date'               => 'O campo data deve ser uma data valida',
            'time.date_format'        =>'O campo hora deve estar no formato HH:mm',
            'parthenon_courtesies.numeric' => 'O campo cortesias parthenon deve conter apenas números',
            'parthenon_courtesies.min'     => 'O campo cortesias parthenon não pode ser negativo',
            'partner_courtesies.numeric'   => 'O campo cortesias parceiro deve conter apenas números',
            'partner_courtesies.min'       => 'O campo cortesias parceiro não pode ser negativo',
            'total_value.numeric' => 'O campo valor total deve ser um número',
            'total_value.min'     => 'O campo valor total não pode ser negativo',
            'sub_total.numeric' => 'O campo valor total deve ser um número',
            'sub_total.min'     => 'O campo valor total não pode ser negativo',
            'buffet_value.numeric' => 'O campo valor total deve ser um número',
            'buffet_value.min'     => 'O campo valor total não pode ser negativo',
            'event_guests.numeric' => 'O campo convidados deve ser um número',
            'event_guests.min'     => 'O campo convidados não pode ser negativo',
            'present_guests.numeric' => 'O campo convidados presentes deve ser um número',
            'present_guests.min'     => 'O campo convidados presentes não pode ser negativo',
            'guest_unit_value.numeric' => 'O campo valor unitário deve ser um número',
            'guest_unit_value.min' => 'O campo valor unitário não pode ser negativo',
            'excess_unit_value.numeric' => 'O campo valor unitário excedente deve ser um número',
            'excess_unit_value.min' => 'O campo valor unitário excedente não pode ser negativo',
            'legal_representatives.*.exists'          => 'O campo ID do representante legal deve ser um ID válido.',
            'min_guests.integer' => 'O campo convidados mínimos deve conter apenas números inteiros',
            'min_guests.min' => 'O campo convidados mínimos deve conter pelo menos 1 convidado',
            'is_medicine.boolean' => 'O campo é medicina deve ser um valor booleano',
            'old_system_id.unique' => 'O ID do evento antigo deve ser único',
        ];
    }
}
