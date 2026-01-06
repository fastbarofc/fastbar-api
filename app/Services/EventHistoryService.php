<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventHistory;
use App\Models\Client;
use App\Models\Collaborator;
use App\Models\EventType;
use App\Models\EventPlace;
use App\Models\EventStatus;
use App\Models\Partner;
use App\Models\Decorator;
use App\Models\Ceremonial;
use App\Models\EventCapture;
use App\Models\CompetingCompany;
use App\Models\LostReason;
use App\Models\Material;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EventHistoryService
{
    private array $ignoredFields = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'old_system_id',
        'total_guests',
        'client',
        'invoice_client',
        'collaborator',
        'event_type',
        'type',
        'event_place',
        'event_status',
        'status',
        'partner',
        'decorator',
        'ceremonial',
        'event_capture',
        'contracts',
        'event_gifts',
        'receivables',
        'failures',
        'tasting_events',
        'parent_events',
        'clients',
        'legal_representatives',
        'post_event_consumption_report_file',
        'post_event_consumption_report',
        'post_event_consumption_report_file_name',
        'post_event_consumption_report_file_id',
        'post_event_consumption_report_file_url',
        'post_event_consumption_report_remove',
        'has_tasting',
    ];

    private array $fieldLabels = [
        'name' => 'Nome do Evento',
        'client_id' => 'Cliente',
        'invoice_client_id' => 'Cliente da Nota Fiscal',
        'collaborator_id' => 'Colaborador Responsável',
        'event_type_id' => 'Tipo de Evento',
        'event_place_id' => 'Local do Evento',
        'event_status_id' => 'Status do Evento',
        'partner_id' => 'Parceiro',
        'decorator_id' => 'Decorador',
        'event_capture_id' => 'Forma de Captura',
        'ceremonial_id' => 'Cerimonial',
        'competing_company_id' => 'Empresa Concorrente',
        'lost_reason_id' => 'Motivo da Perda',
        'lease' => 'Valor da Locação',
        'date' => 'Data do Evento',
        'time' => 'Horário',
        'duration' => 'Duração (horas)',
        'duration_after' => 'Duração Após (minutos)',
        'contract_body' => 'Corpo do Contrato',
        'parthenon_courtesies' => 'Cortesias Parthenon',
        'partner_courtesies' => 'Cortesias Parceiro',
        'unit_insurance' => 'Seguro Unitário',
        'discount' => 'Desconto',
        'unit_discount' => 'Desconto Unitário',
        'is_draft' => 'Rascunho',
        'total_value' => 'Valor Total',
        'buffet_value' => 'Valor do Buffet',
        'sub_total' => 'Subtotal',
        'event_guests' => 'Quantidade de Convidados',
        'present_guests' => 'Convidados Presentes',
        'guest_unit_value' => 'Valor Unitário por Convidado',
        'excess_unit_value' => 'Valor Unitário Excedente',
        'min_guests' => 'Mínimo de Convidados',
        'is_medicine' => 'É Medicina',
        'closed_at' => 'Data de Fechamento',
        'event_batches' => 'Lotes do Evento',
        'event_notes' => 'Anotações do Evento',
        'payment_plan' => 'Plano de Pagamento',
        'extra_sales' => 'Vendas Extras',
        'event_kits' => 'Itens do Buffet',
        'materials' => 'Materiais',
        'office_events' => 'Eventos de Escritório',
        'utensils' => 'Utensílios',
        'office_budgets' => 'Orçamentos de Escritório',
        'related_tasting_events' => 'Eventos de Degustação Relacionados',
        'parent_events' => 'Eventos Principais',
        'legal_representatives' => 'Representantes Legais',
    ];

    public function logEventCreation(Event $event): EventHistory
    {
        $newData = $this->prepareEventData($event);
        $formattedChanges = $this->formatChangesForCreation($newData);

        return EventHistory::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'changes' => $this->processChangesForStorage($formattedChanges),
        ]);
    }

    public function logEventUpdate(Event $event, array $originalData, array $requestData = []): ?EventHistory
    {
        $originalAttributes = $this->extractAttributes($originalData);
        $originalRelationships = $this->extractRelationships($originalData);

        $requestAttributes = !empty($requestData) ? $this->extractAttributes($requestData) : $event->getAttributes();
        $requestRelationships = !empty($requestData) ? $this->extractRelationships($requestData) : [];

        $oldData = $this->prepareEventData($event, $originalAttributes);

        $newData = $this->prepareEventData($event, $requestAttributes);

        if (!empty($originalRelationships) || !empty($requestRelationships)) {
            $oldData = $this->addRelationshipsData($oldData, $originalRelationships);
            $newData = $this->addRelationshipsData($newData, $requestRelationships);
        }

        $changes = $this->detectChanges($oldData, $newData, $requestData);

        if (empty($changes)) {
            return null;
        }

        return EventHistory::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'changes' => $this->processChangesForStorage($changes),
        ]);
    }

    private function extractAttributes(array $data): array
    {
        $relationships = [
            'event_batches',
            'event_notes',
            'payment_plan',
            'extra_sales',
            'event_kits',
            'materials',
            'office_events',
            'related_tasting_events',
            'parent_events',
            'legal_representatives',
        ];

        $attributes = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, $relationships) && !in_array($key, $this->ignoredFields)) {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    private function extractRelationships(array $data): array
    {
        $relationships = [
            'event_batches',
            'event_notes',
            'payment_plan',
            'extra_sales',
            'event_kits',
            'materials',
            'office_events',
            'related_tasting_events',
            'parent_events',
            'legal_representatives',
        ];

        $relationshipsData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $relationships)) {
                $relationshipsData[$key] = $value;
            }
        }

        return $relationshipsData;
    }

    private function addRelationshipsData(array $data, array $sourceData): array
    {
        $relationshipsToTrack = [
            'event_batches',
            'event_notes',
            'payment_plan',
            'extra_sales',
            'event_kits',
            'materials',
            'office_events',
            'related_tasting_events',
            'parent_events',
            'legal_representatives',
        ];

        foreach ($relationshipsToTrack as $relationship) {
            if (isset($sourceData[$relationship])) {
                $normalized = $this->normalizeRelationshipData($sourceData[$relationship]);

                if ($normalized !== null && $normalized !== []) {
                    $data[$relationship] = $normalized;
                }
            }
        }

        return $data;
    }

    private function normalizeRelationshipData($data)
    {
        if ($data === null || $data === []) {
            return null;
        }

        if (is_array($data) && !empty($data)) {
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);

            if ($isAssoc) {
                return $this->cleanRelationshipItem($data);
            }

            $normalized = array_map(function ($item) {
                if (!is_array($item)) {
                    return $item;
                }
                return $this->cleanRelationshipItem($item);
            }, $data);

            usort($normalized, function ($a, $b) {
                $aId = $a['id'] ?? 0;
                $bId = $b['id'] ?? 0;
                return $aId <=> $bId;
            });

            return $normalized;
        }

        return $data;
    }

    private function cleanRelationshipItem(array $item): array
    {
        $fieldsToRemove = [
            'created_at',
            'updated_at',
            'deleted_at',
            'pivot',
            'laravel_through_key',
            'event_id',
            'user_id',
            'collaborator_id',
            'user_name',
            'installments',
        ];

        foreach ($fieldsToRemove as $field) {
            unset($item[$field]);
        }

        $numericFields = ['total_amount', 'down_payment', 'installment_amount', 'unit_value', 'quantity'];
        foreach ($numericFields as $field) {
            if (isset($item[$field])) {
                $item[$field] = number_format((float) $item[$field], 2, '.', '');
            }
        }

        if (isset($item['selected'])) {
            $item['selected'] = (string) $item['selected'];
        }

        $item = array_filter($item, function ($value) {
            return $value !== null;
        });

        return $item;
    }

    private function prepareEventData(Event $event, ?array $originalData = null): array
    {
        $data = $originalData ?? $event->getAttributes();

        foreach ($this->ignoredFields as $field) {
            unset($data[$field]);
        }

        $data = $this->normalizeData($data);

        return $data;
    }

    private function normalizeData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, ['is_draft', 'is_medicine', 'lease']) && $value !== null) {
                $data[$key] = (bool) $value;
            }

            if ($key === 'time' && $value !== null) {
                if (is_string($value)) {
                    $data[$key] = substr($value, 0, 5);
                }
            }

            if (in_array($key, ['total_value', 'buffet_value', 'sub_total', 'unit_insurance', 'discount', 'unit_discount']) && $value !== null) {
                $data[$key] = number_format((float) $value, 2, '.', '');
            }

            if ($key === 'contract_body' && $value !== null && is_string($value)) {
                $data[$key] = $this->normalizeHtml($value);
            }

            if (is_string($value) && $value !== null && $key !== 'time') {
                $data[$key] = trim($value);
            }
        }

        return $data;
    }

    private function normalizeHtml(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        $text = strip_tags($html);

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $text = preg_replace('/\s+/', ' ', $text);

        $text = trim($text);

        return $text;
    }

    private function detectChanges(array $oldData, array $newData, array $requestData = []): array
    {
        $changes = [];

        $allKeys = array_unique(array_merge(array_keys($oldData), array_keys($newData)));

        foreach ($allKeys as $key) {
            $oldValue = $oldData[$key] ?? null;
            $newValue = $newData[$key] ?? null;

            if (!isset($oldData[$key]) && !isset($newData[$key])) {
                continue;
            }

            if ($this->valuesAreDifferent($oldValue, $newValue)) {
                $change = [
                    'field' => $key,
                    'field_label' => $this->fieldLabels[$key] ?? $key,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ];

                if ($this->isRelationshipIdField($key)) {
                    $change['old_value_display'] = $this->getRelationshipName($key, $oldValue);
                    $change['new_value_display'] = $this->getRelationshipName($key, $newValue, $requestData);
                }

                if ($this->isComplexRelationship($key)) {
                    $change['old_value_display'] = $this->formatComplexRelationship($key, $oldValue);
                    $change['new_value_display'] = $this->formatComplexRelationship($key, $newValue);
                }

                $changes[] = $change;
            }
        }

        return $changes;
    }

    private function valuesAreDifferent($oldValue, $newValue): bool
    {
        $oldValue = $oldValue === '' ? null : $oldValue;
        $newValue = $newValue === '' ? null : $newValue;

        if ($oldValue === null && $newValue === null) {
            return false;
        }

        if ($oldValue === null && is_array($newValue) && empty($newValue)) {
            return false;
        }
        if ($newValue === null && is_array($oldValue) && empty($oldValue)) {
            return false;
        }
        if (($oldValue === null && $newValue !== null) || ($oldValue !== null && $newValue === null)) {
            return true;
        }

        if (is_array($oldValue) || is_object($oldValue)) {
            $oldValue = $this->createDataHash($oldValue);
        }
        if (is_array($newValue) || is_object($newValue)) {
            $newValue = $this->createDataHash($newValue);
        }

        return $oldValue !== $newValue;
    }

    private function createDataHash($data): string
    {
        if (!is_array($data)) {
            return (string) $data;
        }

        $data = $this->removeNullValues($data);

        $data = $this->sortArrayRecursive($data);

        return md5(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function removeNullValues($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        foreach ($array as $key => $value) {
            if ($value === null) {
                unset($array[$key]);
            } elseif (is_array($value)) {
                $array[$key] = $this->removeNullValues($value);
            }
        }

        return $array;
    }

    private function sortArrayRecursive($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->sortArrayRecursive($value);
            }
        }

        $keys = array_keys($array);
        if ($keys !== range(0, count($array) - 1)) {
            ksort($array, SORT_STRING);
        }

        return $array;
    }

    private function formatChangesForCreation(array $data): array
    {
        $changes = [];

        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $changes[] = [
                    'field' => $key,
                    'field_label' => $this->fieldLabels[$key] ?? $key,
                    'old_value' => null,
                    'new_value' => $value,
                ];
            }
        }

        return $changes;
    }

    private function processChangesForStorage(array $changes): array
    {
        return array_map(function ($change) {
            $processed = [
                'field' => $change['field'] ?? '',
                'label' => $change['field_label'] ?? $change['field'] ?? '',
            ];

            if (isset($change['old_value_display'])) {
                $processed['old'] = $change['old_value_display'];
            } else {
                $processed['old'] = $this->formatValueForDisplay(
                    $change['old_value'] ?? null,
                    $change['field'] ?? null
                );
            }

            if (isset($change['new_value_display'])) {
                $processed['new'] = $change['new_value_display'];
            } else {
                $processed['new'] = $this->formatValueForDisplay(
                    $change['new_value'] ?? null,
                    $change['field'] ?? null
                );
            }

            return $processed;
        }, $changes);
    }

    public function getEventHistory(int $eventId, int $perPage = 15)
    {
        return EventHistory::where('event_id', $eventId)
            ->with(['user.collaborator.sector', 'user.collaborator.sectorOffice'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    private function isRelationshipIdField(string $field): bool
    {
        $relationshipFields = [
            'client_id',
            'invoice_client_id',
            'collaborator_id',
            'event_type_id',
            'event_place_id',
            'event_status_id',
            'partner_id',
            'decorator_id',
            'ceremonial_id',
            'event_capture_id',
            'competing_company_id',
            'lost_reason_id',
        ];

        return in_array($field, $relationshipFields);
    }

    private function isComplexRelationship(string $field): bool
    {
        $complexFields = [
            'event_batches',
            'event_notes',
            'extra_sales',
            'event_kits',
            'materials',
            'office_events',
            'payment_plan',
            'related_tasting_events',
            'parent_events',
            'legal_representatives',
        ];

        return in_array($field, $complexFields);
    }

    private function getRelationshipName(string $field, $id, array $requestData = []): string
    {
        if ($id === null || $id === '') {
            return '(nenhum)';
        }

        $name = $this->getNameFromRequestData($field, $id, $requestData);
        if ($name !== null) {
            return $name;
        }

        try {
            switch ($field) {
                case 'client_id':
                    $client = Client::find($id);
                    return $client ? ($client->name ?? $client->nickname ?? "Cliente #{$id}") : "Cliente #{$id}";

                case 'invoice_client_id':
                    $client = Client::find($id);
                    return $client ? ($client->name ?? $client->nickname ?? "Cliente #{$id}") : "Cliente #{$id}";

                case 'collaborator_id':
                    $collaborator = Collaborator::find($id);
                    return $collaborator ? ($collaborator->name ?? "Colaborador #{$id}") : "Colaborador #{$id}";

                case 'event_type_id':
                    $type = EventType::find($id);
                    return $type ? $type->name : "Tipo #{$id}";

                case 'event_place_id':
                    $place = EventPlace::find($id);
                    return $place ? $place->name : "Local #{$id}";

                case 'event_status_id':
                    $status = EventStatus::find($id);
                    return $status ? $status->name : "Status #{$id}";

                case 'partner_id':
                    $partner = Partner::find($id);
                    return $partner ? $partner->name : "Parceiro #{$id}";

                case 'decorator_id':
                    $decorator = Decorator::find($id);
                    return $decorator ? $decorator->name : "Decorador #{$id}";

                case 'ceremonial_id':
                    $ceremonial = Ceremonial::find($id);
                    return $ceremonial ? $ceremonial->name : "Cerimonial #{$id}";

                case 'event_capture_id':
                    $capture = EventCapture::find($id);
                    return $capture ? $capture->name : "Captura #{$id}";

                case 'competing_company_id':
                    $company = CompetingCompany::find($id);
                    return $company ? $company->name : "Empresa #{$id}";

                case 'lost_reason_id':
                    $reason = LostReason::find($id);
                    return $reason ? $reason->name : "Motivo #{$id}";

                default:
                    return (string) $id;
            }
        } catch (\Exception $e) {
            Log::error("Erro ao buscar nome do relacionamento {$field}: " . $e->getMessage());
            return (string) $id;
        }
    }

    private function getNameFromRequestData(string $field, $id, array $requestData): ?string
    {
        if (empty($requestData)) {
            return null;
        }

        $mapping = [
            'client_id' => 'client',
            'invoice_client_id' => 'invoice_client',
            'collaborator_id' => 'collaborator',
            'event_type_id' => 'type',
            'event_place_id' => 'event_place',
            'event_status_id' => 'status',
            'partner_id' => 'partner',
            'decorator_id' => 'decorator',
            'ceremonial_id' => 'ceremonial',
            'event_capture_id' => 'event_capture',
            'competing_company_id' => 'competing_company',
            'lost_reason_id' => 'lost_reason',
        ];

        $relationKey = $mapping[$field] ?? null;
        if (!$relationKey || !isset($requestData[$relationKey])) {
            return null;
        }

        $relation = $requestData[$relationKey];
        if (is_array($relation) && isset($relation['id']) && $relation['id'] == $id) {
            return $relation['name'] ?? $relation['nickname'] ?? null;
        }

        return null;
    }

    private function formatComplexRelationship(string $field, $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '(nenhum)';
        }

        if (!is_array($value)) {
            return (string) $value;
        }

        switch ($field) {
            case 'event_batches':
                return $this->formatEventBatches($value);

            case 'event_notes':
                return $this->formatEventNotes($value);

            case 'extra_sales':
                return $this->formatExtraSales($value);

            case 'materials':
                return $this->formatMaterials($value);

            case 'office_events':
                return $this->formatOfficeEvents($value);

            case 'payment_plan':
                return $this->formatPaymentPlan($value);

            case 'related_tasting_events':
            case 'parent_events':
                return $this->formatRelatedEvents($value);

            case 'legal_representatives':
                return $this->formatLegalRepresentatives($value);

            default:
                return count($value) . ' ' . (count($value) === 1 ? 'item' : 'itens');
        }
    }

    private function formatEventBatches(array $batches): string
    {
        if (empty($batches)) {
            return '(nenhum)';
        }

        $items = [];
        foreach ($batches as $batch) {
            $guests = $batch['guests'] ?? 0;
            $unitValue = isset($batch['unit_value']) ? 'R$ ' . number_format((float) $batch['unit_value'], 2, ',', '.') : '';
            $items[] = "{$guests} convidados ({$unitValue})";
        }

        return count($items) . ' lote(s): ' . implode('; ', array_slice($items, 0, 3)) . (count($items) > 3 ? '...' : '');
    }

    private function formatEventNotes(array $notes): string
    {
        if (empty($notes)) {
            return '(nenhuma)';
        }

        return count($notes) . ' anotação' . (count($notes) === 1 ? '' : 'ões');
    }

    private function formatExtraSales(array $sales): string
    {
        if (empty($sales)) {
            return '(nenhuma)';
        }

        $items = [];
        foreach ($sales as $sale) {
            $desc = $sale['description'] ?? 'Item';
            $qty = $sale['quantity'] ?? 0;
            $items[] = "{$desc} (qtd: {$qty})";
        }

        return count($items) . ' venda(s): ' . implode('; ', array_slice($items, 0, 2)) . (count($items) > 2 ? '...' : '');
    }

    private function formatMaterials(array $materials): string
    {
        if (empty($materials)) {
            return '(nenhum)';
        }

        $selectedCount = 0;
        $selectedItems = [];

        foreach ($materials as $material) {
            if (isset($material['selected']) && $material['selected'] == '1') {
                $selectedCount++;
                $desc = $material['description'] ?? 'Material';
                $qty = $material['quantity'] ?? 0;
                $selectedItems[] = "{$desc} (qtd: {$qty})";
            }
        }

        if ($selectedCount === 0) {
            return '(nenhum selecionado)';
        }

        return "{$selectedCount} material" . ($selectedCount === 1 ? '' : 'iais') . ' selecionado(s): ' .
            implode('; ', array_slice($selectedItems, 0, 3)) . ($selectedCount > 3 ? '...' : '');
    }

    private function formatOfficeEvents(array $events): string
    {
        if (empty($events)) {
            return '(nenhum)';
        }

        return count($events) . ' evento(s) de escritório';
    }

    private function formatPaymentPlan(array $plan): string
    {
        $hasData = false;
        foreach ($plan as $key => $value) {
            if ($value !== null && $value !== '') {
                $hasData = true;
                break;
            }
        }

        if (!$hasData) {
            return '(não configurado)';
        }

        $parts = [];

        if (isset($plan['total_amount']) && $plan['total_amount'] !== null) {
            $parts[] = 'Total: R$ ' . number_format((float) $plan['total_amount'], 2, ',', '.');
        }

        if (isset($plan['down_payment']) && $plan['down_payment'] !== null && (float) $plan['down_payment'] > 0) {
            $parts[] = 'Entrada: R$ ' . number_format((float) $plan['down_payment'], 2, ',', '.');
        }

        if (isset($plan['num_installments']) && $plan['num_installments'] !== null) {
            $parts[] = $plan['num_installments'] . ' parcela' . ($plan['num_installments'] == 1 ? '' : 's');
        }

        if (isset($plan['installment_amount']) && $plan['installment_amount'] !== null) {
            $parts[] = 'de R$ ' . number_format((float) $plan['installment_amount'], 2, ',', '.');
        }

        return empty($parts) ? '(configurado)' : implode(', ', $parts);
    }

    private function formatRelatedEvents(array $events): string
    {
        if (empty($events)) {
            return '(nenhum)';
        }

        $names = [];
        foreach ($events as $event) {
            if (is_array($event) && isset($event['name'])) {
                $names[] = $event['name'];
            } elseif (is_numeric($event)) {
                $names[] = "Evento #{$event}";
            }
        }

        if (empty($names)) {
            return count($events) . ' evento(s)';
        }

        return implode(', ', array_slice($names, 0, 3)) . (count($names) > 3 ? ' e mais ' . (count($names) - 3) : '');
    }

    private function formatLegalRepresentatives(array $representatives): string
    {
        if (empty($representatives)) {
            return '(nenhum)';
        }

        $names = [];
        foreach ($representatives as $representative) {
            if (is_array($representative) && isset($representative['name'])) {
                $names[] = $representative['name'];
            } elseif (is_array($representative) && isset($representative['id'])) {
                $names[] = "Representante #{$representative['id']}";
            } elseif (is_numeric($representative)) {
                $names[] = "Representante #{$representative}";
            }
        }

        if (empty($names)) {
            return count($representatives) . ' representante(s)';
        }

        return implode(', ', array_slice($names, 0, 3)) . (count($names) > 3 ? ' e mais ' . (count($names) - 3) : '');
    }

    public function formatValueForDisplay($value, string $field = null): string
    {
        if ($value === null || $value === '') {
            return '(vazio)';
        }

        if (in_array($field, ['date', 'closed_at'])) {
            return date('d/m/Y', strtotime($value));
        }

        if (in_array($field, ['time'])) {
            return date('H:i', strtotime($value));
        }

        if (in_array($field, ['total_value', 'buffet_value', 'sub_total', 'unit_insurance', 'lease', 'discount', 'unit_discount', 'guest_unit_value', 'excess_unit_value'])) {
            return 'R$ ' . number_format((float) $value, 2, ',', '.');
        }

        if (in_array($field, ['is_draft', 'is_medicine'])) {
            return $value ? 'Sim' : 'Não';
        }

        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        if (in_array($field, ['event_batches', 'event_notes', 'extra_sales', 'event_kits']) && is_array($value)) {
            return count($value) . ' ' . (count($value) === 1 ? 'item' : 'itens');
        }

        if ($field === 'payment_plan' && is_array($value)) {
            if (isset($value['num_installments'])) {
                return $value['num_installments'] . ' parcela' . ($value['num_installments'] === 1 ? '' : 's');
            }
            return 'Configurado';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
