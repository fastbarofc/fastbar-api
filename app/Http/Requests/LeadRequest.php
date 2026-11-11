<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => 'required|string|max:255',
            'phone'                => 'nullable|string|max:30',
            'email'                => 'nullable|email|max:255',
            'instagram'            => 'nullable|string|max:255',
            'city'                 => 'nullable|string|max:255',
            'served_city_id'       => 'nullable|exists:served_cities,id',
            'source'               => ['nullable', Rule::in(Lead::SOURCES)],
            'temperature'          => ['nullable', Rule::in(Lead::TEMPERATURES)],
            'tags'                 => 'nullable|array',
            'tags.*'               => 'string|max:40',
            'partner_id'           => 'nullable|exists:partners,id',
            'event_type_id'        => 'nullable|exists:event_types,id',
            'event_date'           => 'nullable|date',
            'guests'               => 'nullable|integer|min:0',
            'estimated_value'      => 'nullable|numeric|min:0',
            'expected_viability'   => 'nullable|numeric|min:0|max:100',
            'real_viability'       => 'nullable|numeric|min:0|max:100',
            'meeting_date'         => 'nullable|date',
            'meeting_result'       => ['nullable', Rule::in(Lead::MEETING_RESULTS)],
            'meeting_notes'        => 'nullable|string',
            'proposal_sent_at'     => 'nullable|date',
            'proposal_valid_until' => 'nullable|date',
            'final_value'          => 'nullable|numeric|min:0',
            'discount_percent'     => 'nullable|numeric|min:0|max:100',
            'deposit_value'        => 'nullable|numeric|min:0',
            'deposit_date'         => 'nullable|date',
            'tasting'              => 'nullable|boolean',
            'tasting_date'         => 'nullable|date',
            'tasting_result'       => ['nullable', Rule::in(Lead::TASTING_RESULTS)],
            'tasting_participants' => 'nullable|string|max:255',
            'stage'                => ['required', Rule::in(Lead::STAGES)],
            'user_id'              => 'nullable|exists:users,id',
            'lost_reason_id'       => ['nullable', 'required_if:stage,perdido', 'exists:lost_reasons,id'],
            'lost_note'            => 'nullable|string',
            'competitor'           => 'nullable|string|max:255',
            'competing_company_id' => 'nullable|exists:competing_companies,id',
            'client_id'            => 'nullable|exists:clients,id',
            'event_id'             => 'nullable|exists:events,id',
            'next_contact_date'    => 'nullable|date',
            'next_action'          => 'nullable|string|max:255',
            'observation'          => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'O nome é obrigatório.',
            'email.email'                => 'O e-mail não é válido.',
            'source.in'                  => 'O canal informado não é válido.',
            'temperature.in'             => 'A temperatura deve ser quente, morno ou frio.',
            'stage.required'             => 'A etapa é obrigatória.',
            'stage.in'                   => 'A etapa informada não é válida.',
            'lost_reason_id.required_if' => 'Informe o motivo da perda.',
            'event_date.date'            => 'A data do evento não é válida.',
            'next_contact_date.date'     => 'A data do próximo contato não é válida.',
            'expected_viability.max'     => 'A viabilidade é um percentual de 0 a 100.',
            'real_viability.max'         => 'A viabilidade é um percentual de 0 a 100.',
            'discount_percent.max'       => 'O desconto é um percentual de 0 a 100.',
        ];
    }
}
