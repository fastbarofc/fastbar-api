<?php

namespace App\Traits;

trait ValidatesEventPayload
{
    private const DECIMAL_10_2 = 'max:99999999.99';
    private const DECIMAL_8_2 = 'max:999999.99';
    private const INT = 'max:2147483647';

    // Barra no request o que o banco recusaria (tamanho, FK, NOT NULL) e hoje vira 500.
    protected function eventPayloadRules(): array
    {
        return [
            'client_id' => 'nullable|integer|exists:clients,id',
            'invoice_client_id' => 'nullable|integer|exists:clients,id',
            'event_id' => 'nullable|integer|exists:events,id',
            'collaborator_id' => 'nullable|integer|exists:collaborators,id',
            'event_status_id' => 'nullable|integer|exists:event_statuses,id',
            'partner_id' => 'nullable|integer|exists:partners,id',
            'decorator_id' => 'nullable|integer|exists:decorators,id',
            'event_capture_id' => 'nullable|integer|exists:event_captures,id',
            'ceremonial_id' => 'nullable|integer|exists:ceremonials,id',
            'competing_company_id' => 'nullable|integer|exists:competing_companies,id',
            'lost_reason_id' => 'nullable|integer|exists:lost_reasons,id',

            'lease' => 'nullable|numeric|min:0|' . self::DECIMAL_10_2,
            'buffet_value' => 'nullable|numeric|min:0|' . self::DECIMAL_10_2,
            'sub_total' => 'nullable|numeric|min:0|' . self::DECIMAL_10_2,
            'total_value' => 'nullable|numeric|min:0|' . self::DECIMAL_10_2,
            'guest_unit_value' => 'nullable|numeric|min:0|' . self::DECIMAL_8_2,
            'excess_unit_value' => 'nullable|numeric|min:0|' . self::DECIMAL_8_2,
            'discount' => 'nullable|numeric|min:0|' . self::DECIMAL_8_2,
            'unit_discount' => 'nullable|numeric|min:0|' . self::DECIMAL_8_2,
            'unit_insurance' => 'nullable|numeric|min:0|' . self::DECIMAL_8_2,
            'event_guests' => 'nullable|numeric|min:0|' . self::INT,
            'present_guests' => 'nullable|numeric|min:0|' . self::INT,
            'min_guests' => 'nullable|integer|min:1|' . self::INT,
            'guests_per_bartender' => 'nullable|integer|min:1|max:65535',
            'duration' => 'nullable|numeric|min:0|' . self::INT,
            'duration_after' => 'nullable|numeric|min:0|' . self::INT,
            'closed_at' => 'nullable|date',

            'post_event_consumption_report' => [
                'nullable',
                'string',
                'regex:/^data:(image\/(png|jpe?g)|application\/pdf|application\/msword|application\/vnd\.openxmlformats-officedocument\.wordprocessingml\.document);base64,/',
            ],

            'event_batches' => 'nullable|array',
            'event_batches.*.id' => 'nullable|integer',
            'event_batches.*.guests' => 'nullable|integer|min:0|' . self::INT,
            'event_batches.*.unit_value' => 'nullable|required_with:event_batches.*.guests|numeric|min:0|' . self::DECIMAL_8_2,

            'materials' => 'nullable|array',
            'materials.*.id' => 'required|integer|exists:materials,id',
            'materials.*.selected' => 'present|nullable|in:-1,0,1',
            'materials.*.quantity' => 'nullable|integer|min:0|' . self::INT,

            'office_events' => 'nullable|array',
            'office_events.*.id' => 'required|integer|exists:office_events,id',
            'office_events.*.quantity' => 'nullable|integer|min:0|' . self::INT,

            'utensils' => 'nullable|array',
            'utensils.*' => 'integer|exists:utensils,id',
            'office_budgets' => 'nullable|array',
            'office_budgets.*' => 'integer|exists:office_budgets,id',
            'related_tasting_events' => 'nullable|array',
            'related_tasting_events.*' => 'integer|exists:events,id',
            'parent_events' => 'nullable|array',
            'parent_events.*' => 'integer|exists:events,id',

            'extra_sales' => 'nullable|array',
            'extra_sales.*.id' => 'nullable|integer',
            'extra_sales.*.description' => 'required|string|max:191',
            'extra_sales.*.quantity' => 'required|integer|min:0|' . self::INT,
            'extra_sales.*.unit_value' => 'required|numeric|min:0|' . self::DECIMAL_10_2,
            'extra_sales.*.unit_id' => 'nullable|integer|exists:units,id',

            'event_notes' => 'nullable|array',
            'event_notes.*.id' => 'nullable|integer',
            'event_notes.*.text' => 'required|string',
            'event_notes.*.checklist' => 'nullable|boolean',

            'payment_plan' => 'nullable|array',
            'payment_plan.total_amount' => 'nullable|numeric|min:0|' . self::DECIMAL_10_2,
            'payment_plan.installment_amount' => 'nullable|numeric|min:0|' . self::DECIMAL_10_2,
            'payment_plan.down_payment' => 'nullable|numeric|min:0|' . self::DECIMAL_10_2,
            'payment_plan.down_payment_due_date' => 'nullable|date',
            'payment_plan.num_installments' => 'nullable|integer|min:0|max:120',
            'payment_plan.due_day' => 'nullable|integer|between:1,31',
            'payment_plan.payment_method_id' => 'nullable|integer|exists:payment_methods,id',
            'payment_plan.installments' => 'nullable|array',
            'payment_plan.installments.*.id' => 'nullable|integer',
            'payment_plan.installments.*.installment_number' => 'nullable|integer|min:1|max:120',
            'payment_plan.installments.*.amount' => 'nullable|numeric|min:0|' . self::DECIMAL_10_2,
            'payment_plan.installments.*.due_date' => 'required|date',
        ];
    }

    protected function eventPayloadMessages(): array
    {
        $exists = fn (string $what) => "O {$what} informado não existe.";

        return [
            'client_id.exists' => $exists('cliente'),
            'invoice_client_id.exists' => $exists('cliente da nota fiscal'),
            'event_id.exists' => $exists('evento'),
            'collaborator_id.exists' => $exists('responsável'),
            'event_status_id.exists' => $exists('status'),
            'partner_id.exists' => $exists('parceiro'),
            'decorator_id.exists' => $exists('decorador'),
            'event_capture_id.exists' => 'A captação informada não existe.',
            'ceremonial_id.exists' => $exists('cerimonial'),
            'competing_company_id.exists' => 'A empresa concorrente informada não existe.',
            'lost_reason_id.exists' => $exists('motivo de perda'),

            'lease.max' => 'O aluguel não pode passar de 99.999.999,99.',
            'buffet_value.max' => 'O valor do buffet não pode passar de 99.999.999,99.',
            'sub_total.max' => 'O subtotal não pode passar de 99.999.999,99.',
            'total_value.max' => 'O valor total não pode passar de 99.999.999,99.',
            'guest_unit_value.max' => 'O valor por convidado não pode passar de 999.999,99.',
            'excess_unit_value.max' => 'O valor por convidado excedente não pode passar de 999.999,99.',
            'discount.max' => 'O desconto não pode passar de 999.999,99.',
            'unit_discount.max' => 'O desconto unitário não pode passar de 999.999,99.',
            'unit_insurance' => 'O seguro unitário deve ser um valor entre 0 e 999.999,99.',
            'event_guests.max' => 'O número de convidados é grande demais.',
            'present_guests.max' => 'O número de convidados presentes é grande demais.',
            'min_guests.max' => 'O mínimo de convidados é grande demais.',
            'guests_per_bartender.max' => 'O número de convidados por bartender não pode passar de 65.535.',
            'duration' => 'A duração deve ser um número de horas válido.',
            'duration_after' => 'A duração após o evento deve ser um número de horas válido.',
            'closed_at' => 'A data de fechamento é inválida.',

            'post_event_consumption_report.regex' => 'O relatório de consumo deve ser PNG, JPG, PDF, DOC ou DOCX.',

            'event_batches.*.guests' => 'Os convidados do lote devem ser um número inteiro.',
            'event_batches.*.unit_value.required_with' => 'Informe o valor por convidado do lote.',
            'event_batches.*.unit_value' => 'O valor por convidado do lote deve estar entre 0 e 999.999,99.',

            'materials.*.id' => 'Um dos materiais informados não existe.',
            'materials.*.selected' => 'A opção escolhida para um dos materiais é inválida.',
            'materials.*.quantity' => 'A quantidade de um dos materiais é inválida.',
            'office_events.*.id' => 'Uma das funções informadas não existe.',
            'office_events.*.quantity' => 'A quantidade de uma das funções é inválida.',
            'utensils.*' => 'Um dos utensílios informados não existe.',
            'office_budgets.*' => 'Um dos cargos de orçamento informados não existe.',
            'related_tasting_events.*' => 'Um dos eventos de degustação informados não existe.',
            'parent_events.*' => 'Um dos eventos principais informados não existe.',

            'extra_sales.*.description.required' => 'Informe a descrição da venda extra.',
            'extra_sales.*.description.max' => 'A descrição da venda extra deve ter no máximo 191 caracteres.',
            'extra_sales.*.quantity' => 'Informe uma quantidade válida na venda extra.',
            'extra_sales.*.unit_value' => 'Informe um valor unitário válido na venda extra.',
            'extra_sales.*.unit_id' => 'A unidade da venda extra não existe.',

            'event_notes.*.text' => 'Informe o texto da observação.',
            'event_notes.*.checklist' => 'O campo checklist da observação é inválido.',

            'payment_plan.total_amount' => 'O valor total do pagamento deve estar entre 0 e 99.999.999,99.',
            'payment_plan.installment_amount' => 'O valor da parcela deve estar entre 0 e 99.999.999,99.',
            'payment_plan.down_payment' => 'A entrada deve estar entre 0 e 99.999.999,99.',
            'payment_plan.down_payment_due_date' => 'A data de vencimento da entrada é inválida.',
            'payment_plan.num_installments' => 'O número de parcelas deve estar entre 0 e 120.',
            'payment_plan.due_day' => 'O dia de vencimento deve estar entre 1 e 31.',
            'payment_plan.payment_method_id' => 'O método de pagamento informado não existe.',
            'payment_plan.installments.*.installment_number' => 'O número de uma das parcelas é inválido.',
            'payment_plan.installments.*.amount' => 'O valor de uma das parcelas deve estar entre 0 e 99.999.999,99.',
            'payment_plan.installments.*.due_date' => 'Informe uma data de vencimento válida em todas as parcelas.',
        ];
    }
}
