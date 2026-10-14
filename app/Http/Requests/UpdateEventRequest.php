<?php

namespace App\Http\Requests;

use App\Models\ContractTemplate;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\EventType;
use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

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
            'contract_sections'  => 'nullable|array:' . implode(',', ContractTemplate::SECTIONS),
            'contract_sections.*' => 'nullable|string',
            'glass_cup_hours'    => 'nullable|integer|min:0|max:24',
            'guests_per_bartender' => 'nullable|integer|min:1',
            'bar_counter_type'   => 'nullable|in:' . implode(',', array_keys(Event::BAR_COUNTER_TYPES)),
            'cup_type'           => 'nullable|in:' . implode(',', array_keys(Event::CUP_TYPES)),
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
            'old_system_id' => 'nullable',
            'post_event_consumption_report' => 'nullable|string',
            'post_event_consumption_report_file_id' => 'nullable|exists:files,id',
            'post_event_consumption_report_file_name' => 'nullable|string|max:255',
            'post_event_consumption_report_remove' => 'nullable|boolean',
            'company_ids' => 'sometimes|array',
            'company_ids.*' => 'sometimes|exists:companies,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'O campo nome deve ser uma string',
            'name.max' => 'O campo nome deve ter no máximo 255 caracteres.',
            'event_type_id.exists' => 'O campo tipo de evento deve ser um tipo válido.',
            'event_place_id.exists' => 'O campo local do evento deve ser um local válido.',
            'lease.string' => 'O campo aluguel deve ser uma string',
            'lease.min' => 'O campo aluguel não pode ser negativo',
            'date.date' => 'O campo data deve ser uma data valida',
            'time.date_format' => 'O campo hora deve estar no formato HH:mm',
            'parthenon_courtesies.numeric' => 'O campo cortesias parthenon deve conter apenas números',
            'parthenon_courtesies.min' => 'O campo cortesias parthenon não pode ser negativo',
            'partner_courtesies.numeric' => 'O campo cortesias parceiro deve conter apenas números',
            'partner_courtesies.min' => 'O campo cortesias parceiro não pode ser negativo',
            'total_value.numeric' => 'O campo valor total deve ser um número',
            'total_value.min' => 'O campo valor total não pode ser negativo',
            'sub_total.numeric' => 'O campo valor total deve ser um número',
            'sub_total.min' => 'O campo valor total não pode ser negativo',
            'buffet_value.numeric' => 'O campo valor total deve ser um número',
            'buffet_value.min' => 'O campo valor total não pode ser negativo',
            'event_guests.numeric' => 'O campo convidados deve ser um número',
            'event_guests.min' => 'O campo convidados não pode ser negativo',
            'present_guests.numeric' => 'O campo convidados presentes deve ser um número',
            'present_guests.min' => 'O campo convidados presentes não pode ser negativo',
            'guest_unit_value.numeric' => 'O campo valor unitário deve ser um número',
            'guest_unit_value.min' => 'O campo valor unitário não pode ser negativo',
            'excess_unit_value.numeric' => 'O campo valor unitário excedente deve ser um número',
            'excess_unit_value.min' => 'O campo valor unitário excedente não pode ser negativo',
            'legal_representatives.*.exists' => 'O campo ID do representante legal deve ser um ID válido.',
            'min_guests.integer' => 'O campo convidados mínimos deve conter apenas números inteiros',
            'min_guests.min' => 'O campo convidados mínimos deve conter pelo menos 1 convidado',
            'glass_cup_hours.integer' => 'As horas com copo de vidro devem ser um número inteiro',
            'glass_cup_hours.max' => 'As horas com copo de vidro não podem passar de 24',
            'guests_per_bartender.integer' => 'O número de convidados por barman deve ser um número inteiro',
            'guests_per_bartender.min' => 'O número de convidados por barman deve ser maior que zero',
            'bar_counter_type.in' => 'O tipo do balcão deve ser lateral ou 360º',
            'cup_type.in' => 'O tipo de copo selecionado é inválido',
            'is_medicine.boolean' => 'O campo é medicina deve ser um valor booleano',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->requiresFinancialValidation()) {
                $this->validateFinancialFields($validator);
            }

            $this->validateMaterials($validator);
        });
    }

    private function validateFinancialFields(Validator $validator): void
    {
        $paymentPlan = $this->input('payment_plan', []);

        $requiredFields = [
            'total_amount' => 'O valor total é obrigatório para eventos no status Contrato.',
            'num_installments' => 'O número de parcelas é obrigatório para eventos no status Contrato.',
            'installment_amount' => 'O valor das parcelas é obrigatório para eventos no status Contrato.',
            'payment_method_id' => 'O método de pagamento é obrigatório para eventos no status Contrato.',
            'due_day' => 'O dia de vencimento é obrigatório para eventos no status Contrato.',
        ];

        foreach ($requiredFields as $field => $message) {
            $value = Arr::get($paymentPlan, $field);
            if ($this->isEmptyFinancialValue($value)) {
                $validator->errors()->add("payment_plan.{$field}", $message);
            }
        }
    }

    private function validateMaterials(Validator $validator): void
    {
        $materials = $this->input('materials', []);

        if (empty($materials)) {
            return;
        }

        $event = $this->route('event');
        if (!$event instanceof Event) {
            return;
        }

        foreach ($materials as $materialData) {
            if (empty($materialData['id']) || empty($materialData['quantity']) || $materialData['quantity'] <= 0) {
                continue;
            }

            $material = Material::find($materialData['id']);
            if (!$material) {
                continue;
            }

            $requestedQuantity = (int) $materialData['quantity'];
            $totalQuantity = $material->quantity;

            $usedQuantity = $this->getUsedQuantityForMaterial($material->id, $event->id);
            $totalUsedAfterUpdate = $usedQuantity + $requestedQuantity;

            if ($totalUsedAfterUpdate > $totalQuantity) {
                $maxAllowedQuantity = $totalQuantity - $usedQuantity;

                $validator->errors()->add(
                    'materials',
                    "O material '{$material->description}' possui apenas {$maxAllowedQuantity} unidade(s) disponível(is), mas você está tentando usar {$requestedQuantity}."
                );
            }
        }
    }

    private function getUsedQuantityForMaterial(int $materialId, ?int $excludeEventId = null): int
    {
        $eventDate = $this->input('date');

        if (!$eventDate && $excludeEventId) {
            $event = Event::find($excludeEventId);
            if ($event && $event->date) {
                $eventDate = $event->date->format('Y-m-d');
            }
        }

        if (!$eventDate) {
            return 0;
        }

        $query = DB::table('event_material')
            ->join('events', 'event_material.event_id', '=', 'events.id')
            ->where('event_material.material_id', $materialId)
            ->where('events.date', $eventDate)
            ->whereNull('events.deleted_at')
            ->whereNull('event_material.deleted_at');

        if ($excludeEventId) {
            $query->where('events.id', '!=', $excludeEventId);
        }

        $usedQuantity = $query->sum('event_material.quantity');

        return (int) $usedQuantity;
    }

    private function getCurrentEventMaterialQuantity(int $eventId, int $materialId): int
    {
        $quantity = DB::table('event_material')
            ->where('event_id', $eventId)
            ->where('material_id', $materialId)
            ->whereNull('deleted_at')
            ->value('quantity');

        return (int) ($quantity ?? 0);
    }

    private function requiresFinancialValidation(): bool
    {
        $statusId = $this->input('event_status_id');

        if (!$statusId && $this->route('event') instanceof Event) {
            $statusId = $this->route('event')->event_status_id;
        }

        if (!$statusId) {
            return false;
        }

        $eventStatus = EventStatus::find($statusId);

        if (!$eventStatus || $eventStatus->name !== 'Contrato') {
            return false;
        }

        return !$this->shouldSkipFinancialValidation();
    }

    private function shouldSkipFinancialValidation(): bool
    {
        $eventTypeId = $this->input('event_type_id');

        if (!$eventTypeId && $this->route('event') instanceof Event) {
            $eventTypeId = $this->route('event')->event_type_id;
        }

        if ($eventTypeId) {
            $eventType = EventType::find($eventTypeId);
            if ($eventType && $this->isTastingByName($eventType->name)) {
                return true;
            }
        }

        $typeInput = $this->input('type');

        if (is_array($typeInput)) {
            $eventTypeName = Arr::get($typeInput, 'name');
            if (is_string($eventTypeName) && $this->isTastingByName($eventTypeName)) {
                return true;
            }
        } elseif (is_string($typeInput) && $this->isTastingByName($typeInput)) {
            return true;
        }

        return false;
    }

    private function isEmptyFinancialValue($value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed === '' || $trimmed === '0.00' || $trimmed === '0';
        }

        if (is_numeric($value)) {
            return (float) $value === 0.0;
        }

        return false;
    }

    private function isTastingByName(?string $name): bool
    {
        if (!$name) {
            return false;
        }

        $normalized = mb_strtolower(trim($name));

        return in_array($normalized, [
            'degustação',
            'degustacao',
            'aula da saudade',
        ], true);
    }
}
