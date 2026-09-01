<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateEventProposalRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proposal_number' => 'required|string|max:30',
            'validity_days' => 'required|integer|min:1|max:365',
            'client_name' => 'required|string|max:255',
            'event_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'date' => 'required|string|max:30',
            'duration' => 'required|string|max:30',
            'guests' => 'required|string|max:30',

            'budget_details' => 'present|array|max:12',
            'budget_details.*' => 'required|string|max:255',
            'observations' => 'present|array|max:12',
            'observations.*' => 'required|string|max:255',

            'drinks_bar_items' => 'present|array|max:12',
            'drinks_bar_items.*' => 'required|string|max:255',
            'drinks_bar_price' => 'required|numeric|min:0',
            'optional_drinks_note' => 'nullable|string|max:500',

            'spirits_bar_items' => 'present|array|max:12',
            'spirits_bar_items.*' => 'required|string|max:255',
            'spirits_bar_price' => 'required|numeric|min:0',

            'additional_items' => 'present|array|max:12',
            'additional_items.*' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_number.required' => 'O número da proposta é obrigatório',
            'validity_days.required' => 'A validade é obrigatória',
            'validity_days.integer' => 'A validade deve ser um número de dias',
            'client_name.required' => 'O campo cliente é obrigatório',
            'event_name.required' => 'O campo evento é obrigatório',
            'location.required' => 'O campo local é obrigatório',
            'date.required' => 'O campo data é obrigatório',
            'duration.required' => 'O campo duração é obrigatório',
            'guests.required' => 'O campo número de convidados é obrigatório',
            'drinks_bar_price.required' => 'O valor por pessoa do bar de drinks é obrigatório',
            'drinks_bar_price.numeric' => 'O valor por pessoa do bar de drinks deve ser um número',
            'spirits_bar_price.required' => 'O valor por pessoa do bar de destilados é obrigatório',
            'spirits_bar_price.numeric' => 'O valor por pessoa do bar de destilados deve ser um número',
            'budget_details.max' => 'O orçamento detalhado aceita no máximo 12 itens',
            'observations.max' => 'As observações aceitam no máximo 12 itens',
            'drinks_bar_items.max' => 'O bar de drinks aceita no máximo 12 itens',
            'spirits_bar_items.max' => 'O bar de destilados aceita no máximo 12 itens',
            'additional_items.max' => 'Os adicionais aceitam no máximo 12 itens',
        ];
    }
}
