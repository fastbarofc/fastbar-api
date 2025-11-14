<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class StoreEventRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $data = [
            'name'               => 'nullable|string|max:255',
            'event_type_id'      => 'nullable|exists:event_types,id',
            'event_place_id'     => 'nullable|exists:event_places,id',
            'lease'              => 'nullable|numeric|min:0',
            'date'          => 'nullable|date|after:now',
            'time'          => 'nullable|date_format:H:i',
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
            'legal_representatives.*'          => 'exists:clients,id',
            'min_guests' => 'nullable|integer|min:1',
            'is_medicine' => 'nullable|boolean',
            'old_system_id' => 'nullable',
            'post_event_consumption_report' => 'nullable|string',
            'post_event_consumption_report_file_id' => 'nullable|exists:files,id',
            'post_event_consumption_report_file_name' => 'nullable|string|max:255',
            'post_event_consumption_report_remove' => 'nullable|boolean',


        ];

        return $data;
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'O campo nome é obrigatório',
            'name.string'               => 'O campo nome deve ser uma string',
            'name.max'                  => 'O campo nome deve ter no máximo 255 caracteres.',
            'event_type_id.required'    => 'O campo tipo de evento é obrigatório',
            'event_type_id.exists'      => 'O campo tipo de evento deve ser um tipo válido.',
            'event_place_id.required'   => 'O campo local do evento é obrigatório',
            'event_place_id.exists'     => 'O campo local do evento deve ser um local válido.',
            'lease.required'            => 'O campo aluguel é obrigatório',
            'lease.string'              => 'O campo aluguel deve ser uma string',
            'lease.min'                 => 'O campo aluguel não pode ser negativo',
            'date.required'        => 'O campo data é obrigatório',
            'date.date'            => 'O campo data deve ser uma data valida',
            'date.after'           => 'A data deve ser posterior à data atual.',
            'time.date_format'     => 'O campo hora deve estar no formato HH:mm',
            'time>required_if'     => 'A hora é obrigatoria quando o evento estiver sendo fechado',
            'parthenon_courtesies.numeric' => 'O campo cortesia parthenon deve ser um número',
            'parthenon_courtesies.min'     => 'O campo cortesia parthenon não pode ser negativo',
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
            'discount.numeric' => 'O campo desconto deve ser um número',
            'discount.min' => 'O campo desconto não pode ser negativo',
            'unit_discount.numeric' => 'O campo desconto unitário deve ser um número',
            'unit_discount.min' => 'O campo desconto unitário não pode ser negativo',
            'partner_courtesies.numeric'   => 'O campo cortesia parceiro deve ser um número',
            'partner_courtesies.min'       => 'O campo cortesia parceiro não pode ser negativo',
            'clients.*.exists'          => 'O campo ID do cliente deve ser um ID válido.',
            'min_guests.integer' => 'O campo convidados mínimos deve conter apenas números inteiros',
            'min_guests.min' => 'O campo convidados mínimos deve conter pelo menos 1 convidado',
            'is_medicine.boolean' => 'O campo é medicina deve ser um valor booleano',
        ];
    }
}
