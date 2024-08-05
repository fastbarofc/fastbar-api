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
            'name'               => 'required|string|max:255',
            'event_type_id'      => 'required|exists:event_types,id',
            'event_place_id'     => 'nullable|exists:event_places,id',
            'lease'              => 'nullable|numeric|min:0',
            'date_time'          => 'nullable|date|after:now',
            'contract_body'      => 'nullable',
            'parthenon_courtesies' => 'nullable|numeric|min:0',
            'partner_courtesies'   => 'nullable|numeric|min:0',
            'materials'          => 'nullable|array',
            'office_events'      => 'nullable|array',
            'legal_representatives'            => 'nullable|array',
            'legal_representatives.*'          => 'exists:clients,id',
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
            'date_time.required'        => 'O campo data e hora é obrigatório',
            'date_time.date'            => 'O campo data e hora deve ser uma data valida',
            'date_time.after'           => 'A data e hora devem ser maiores que a data e hora atuais.',
            'parthenon_courtesies.numeric' => 'O campo cortesia parthenon deve ser um número',
            'parthenon_courtesies.min'     => 'O campo cortesia parthenon não pode ser negativo',
            'partner_courtesies.numeric'   => 'O campo cortesia parceiro deve ser um número',
            'partner_courtesies.min'       => 'O campo cortesia parceiro não pode ser negativo',
            'clients.*.exists'          => 'O campo ID do cliente deve ser um ID válido.',
        ];
    }
}
