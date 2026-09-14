<?php

namespace App\Http\Requests;

use App\Services\EventProposalService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class GenerateEventProposalRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Fora da capa, tudo e opcional: o front envia apenas os blocos que o
     * usuario marcou no toggle e o PDF monta so o que chegar.
     */
    public function rules(): array
    {
        $rules = [
            'proposal_number' => 'required|string|max:30',
            'validity_days' => 'required|integer|min:1|max:365',
            'client_name' => 'required|string|max:255',
            'event_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'date' => 'required|string|max:30',
            'duration' => 'required|string|max:30',
            'guests' => 'required|string|max:30',

            'budget_details' => 'nullable|array|max:12',
            'budget_details.*' => 'required|string|max:255',
            'observations' => 'nullable|array|max:12',
            'observations.*' => 'required|string|max:255',

            'optional_drinks_note' => 'nullable|string|max:500',

            'additional_items' => 'nullable|array|max:12',
            'additional_items.*' => 'required|string|max:255',
        ];

        foreach (array_keys(EventProposalService::barSections()) as $key) {
            $rules["{$key}_items"] = 'nullable|array|max:12';
            $rules["{$key}_items.*"] = 'required|string|max:255';
            $rules["{$key}_price"] = "nullable|required_with:{$key}_items|numeric|min:0";
        }

        return $rules;
    }

    /** O resumo e a coluna da direita do PDF: nao pode sair vazia. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (EventProposalService::hasSummary($this->all())) {
                return;
            }

            $validator->errors()->add('summary', 'Selecione ao menos um item da seção Resumo.');
        });
    }

    public function messages(): array
    {
        $messages = [
            'proposal_number.required' => 'O número da proposta é obrigatório',
            'validity_days.required' => 'A validade é obrigatória',
            'validity_days.integer' => 'A validade deve ser um número de dias',
            'client_name.required' => 'O campo cliente é obrigatório',
            'event_name.required' => 'O campo evento é obrigatório',
            'location.required' => 'O campo local é obrigatório',
            'date.required' => 'O campo data é obrigatório',
            'duration.required' => 'O campo duração é obrigatório',
            'guests.required' => 'O campo número de convidados é obrigatório',
            'budget_details.max' => 'O orçamento detalhado aceita no máximo 12 itens',
            'observations.max' => 'As observações aceitam no máximo 12 itens',
            'additional_items.max' => 'Os adicionais aceitam no máximo 12 itens',
        ];

        foreach (EventProposalService::barSections() as $key => $title) {
            $label = mb_strtolower(rtrim($title, ':'));

            $messages["{$key}_items.max"] = "O bloco {$label} aceita no máximo 12 itens";
            $messages["{$key}_price.required_with"] = "O valor por pessoa do bloco {$label} é obrigatório";
            $messages["{$key}_price.numeric"] = "O valor por pessoa do bloco {$label} deve ser um número";
        }

        return $messages;
    }
}
