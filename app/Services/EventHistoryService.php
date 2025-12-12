<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventHistory;
use Illuminate\Support\Facades\Auth;

class EventHistoryService
{
    /**
     * Campos que devem ser ignorados na comparação
     */
    private array $ignoredFields = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        // Relacionamentos carregados que não devem ser comparados diretamente
        // (serão comparados através dos dados do request)
        'client',
        'invoice_client',
        'collaborator',
        'event_type',
        'event_place',
        'event_status',
        'partner',
        'decorator',
        'ceremonial',
        'event_capture',
        'materials',
        'event_gifts',
        'contracts',
        'office_events',
        'utensils',
        'office_budgets',
        'receivables',
        'failures',
        'tasting_events',
        'parent_events',
        'clients',
        'legal_representatives',
        'post_event_consumption_report_file',
    ];

    /**
     * Mapeamento de nomes de campos para nomes amigáveis
     */
    private array $fieldLabels = [
        'name' => 'Nome',
        'client_id' => 'Cliente',
        'invoice_client_id' => 'Cliente da Nota Fiscal',
        'collaborator_id' => 'Colaborador',
        'event_type_id' => 'Tipo de Evento',
        'event_place_id' => 'Local do Evento',
        'event_status_id' => 'Status do Evento',
        'partner_id' => 'Parceiro',
        'decorator_id' => 'Decorador',
        'event_capture_id' => 'Captura do Evento',
        'ceremonial_id' => 'Cerimonial',
        'lease' => 'Locação',
        'date' => 'Data',
        'time' => 'Horário',
        'duration' => 'Duração',
        'duration_after' => 'Duração Após',
        'contract_body' => 'Corpo do Contrato',
        'parthenon_courtesies' => 'Cortesias Parthenon',
        'partner_courtesies' => 'Cortesias Parceiro',
        'unit_insurance' => 'Seguro Unitário',
        'competing_company_id' => 'Empresa Concorrente',
        'lost_reason_id' => 'Motivo da Perda',
        'is_draft' => 'Rascunho',
        'total_value' => 'Valor Total',
        'buffet_value' => 'Valor do Buffet',
        'sub_total' => 'Subtotal',
        'event_guests' => 'Convidados do Evento',
        'is_medicine' => 'É Medicina',
        'closed_at' => 'Fechado em',
        // Relacionamentos
        'event_batches' => 'Lotes do Evento',
        'event_notes' => 'Anotações do Evento',
        'payment_plan' => 'Plano de Pagamento',
        'extra_sales' => 'Vendas Extras',
        'event_kits' => 'Itens do Buffet',
    ];

    /**
     * Registra a criação de um evento
     */
    public function logEventCreation(Event $event): EventHistory
    {
        $newData = $this->prepareEventData($event);

        return EventHistory::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'old_data' => null,
            'new_data' => $newData,
            'changes' => $this->formatChangesForCreation($newData),
            'description' => 'Evento criado',
        ]);
    }

    /**
     * Registra a atualização de um evento
     */
    public function logEventUpdate(Event $event, array $originalData, array $requestData = []): ?EventHistory
    {
        $oldData = $this->prepareEventData($event, $originalData);
        $newData = $this->prepareEventData($event);

        // Adiciona dados de relacionamentos do request se houver
        if (!empty($requestData)) {
            $oldData = $this->addRelationshipsData($oldData, $originalData);
            $newData = $this->addRelationshipsData($newData, $requestData);
        }

        $changes = $this->detectChanges($oldData, $newData);

        // Se não houver mudanças, não registra
        if (empty($changes)) {
            return null;
        }

        return EventHistory::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'old_data' => $oldData,
            'new_data' => $newData,
            'changes' => $changes,
            'description' => $this->generateUpdateDescription($changes),
        ]);
    }

    /**
     * Adiciona dados de relacionamentos que foram enviados no request
     */
    private function addRelationshipsData(array $data, array $sourceData): array
    {
        $relationshipsToTrack = ['event_batches', 'event_notes', 'payment_plan', 'extra_sales', 'event_kits'];

        foreach ($relationshipsToTrack as $relationship) {
            if (isset($sourceData[$relationship])) {
                $normalized = $this->normalizeRelationshipData($sourceData[$relationship]);
                // Só adiciona se não for vazio
                if ($normalized !== null && $normalized !== []) {
                    $data[$relationship] = $normalized;
                }
            }
        }

        return $data;
    }

    /**
     * Normaliza dados de relacionamentos para comparação
     */
    private function normalizeRelationshipData($data)
    {
        if ($data === null || $data === []) {
            return null;
        }

        // Se for array de relacionamentos (hasMany)
        if (is_array($data) && !empty($data)) {
            // Verifica se é array associativo (hasOne) ou numérico (hasMany)
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);

            if ($isAssoc) {
                // hasOne/belongsTo - apenas um objeto
                return $this->cleanRelationshipItem($data);
            }

            // hasMany - array de objetos
            $normalized = array_map(function ($item) {
                if (!is_array($item)) {
                    return $item;
                }
                return $this->cleanRelationshipItem($item);
            }, $data);

            // Ordena por ID para comparação consistente
            usort($normalized, function ($a, $b) {
                $aId = $a['id'] ?? 0;
                $bId = $b['id'] ?? 0;
                return $aId <=> $bId;
            });

            return $normalized;
        }

        return $data;
    }

    /**
     * Limpa um item de relacionamento removendo campos de controle
     */
    private function cleanRelationshipItem(array $item): array
    {
        // Remove campos de controle do Laravel, pivot e foreign keys
        $fieldsToRemove = [
            'created_at',
            'updated_at',
            'deleted_at',
            'pivot',
            'laravel_through_key',
            'event_id',        // Foreign key sempre presente no banco mas não no request
            'user_id',         // ID do usuário em event_notes
            'collaborator_id', // ID do colaborador
            'user_name',       // Nome do usuário vindo do frontend
            'installments',    // Sub-relacionamento de payment_plan (não comparar aqui)
        ];

        foreach ($fieldsToRemove as $field) {
            unset($item[$field]);
        }

        // Normaliza valores numéricos em campos de valores monetários
        $numericFields = ['total_amount', 'down_payment', 'installment_amount', 'unit_value'];
        foreach ($numericFields as $field) {
            if (isset($item[$field])) {
                // Converte para float e depois para string com 2 casas decimais
                $item[$field] = number_format((float) $item[$field], 2, '.', '');
            }
        }

        // Remove valores null para comparação mais limpa
        $item = array_filter($item, function ($value) {
            return $value !== null;
        });

        return $item;
    }

    /**
     * Prepara os dados do evento para o histórico
     */
    private function prepareEventData(Event $event, ?array $originalData = null): array
    {
        // Usa getAttributes() para pegar apenas os atributos do model, sem relacionamentos
        $data = $originalData ?? $event->getAttributes();

        // Remove campos ignorados
        foreach ($this->ignoredFields as $field) {
            unset($data[$field]);
        }

        // Normaliza os dados para comparação
        $data = $this->normalizeData($data);

        return $data;
    }

    /**
     * Normaliza dados para comparação consistente
     */
    private function normalizeData(array $data): array
    {
        foreach ($data as $key => $value) {
            // Normaliza booleanos
            if (in_array($key, ['is_draft', 'is_medicine', 'lease']) && $value !== null) {
                $data[$key] = (bool) $value;
            }

            // Normaliza horários (remove segundos se existir)
            if ($key === 'time' && $value !== null) {
                if (is_string($value)) {
                    // Remove segundos: "14:32:00" -> "14:32"
                    $data[$key] = substr($value, 0, 5);
                }
            }

            // Normaliza valores decimais
            if (in_array($key, ['total_value', 'buffet_value', 'sub_total', 'unit_insurance', 'discount', 'unit_discount']) && $value !== null) {
                $data[$key] = number_format((float) $value, 2, '.', '');
            }

            // Normaliza HTML em campos de texto rico
            if ($key === 'contract_body' && $value !== null && is_string($value)) {
                $data[$key] = $this->normalizeHtml($value);
            }

            // Remove espaços em branco extras de strings (mas não aplica em time)
            if (is_string($value) && $value !== null && $key !== 'time') {
                $data[$key] = trim($value);
            }
        }

        return $data;
    }

    /**
     * Normaliza HTML para comparação (remove diferenças de formatação)
     */
    private function normalizeHtml(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Extrai apenas o texto, removendo todas as tags HTML
        $text = strip_tags($html);

        // Remove entidades HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove espaços múltiplos e quebras de linha
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        $text = trim($text);

        // Retorna o texto normalizado
        return $text;
    }

    /**
     * Detecta mudanças entre dados antigos e novos
     */
    private function detectChanges(array $oldData, array $newData): array
    {
        $changes = [];

        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;

            // Compara valores (tratando null e string vazia como equivalentes)
            if ($this->valuesAreDifferent($oldValue, $newValue)) {
                // Debug temporário
                if ($key === 'payment_plan') {
                    \Log::debug("Payment Plan STILL different after normalization", [
                        'old_normalized' => $oldValue,
                        'new_normalized' => $newValue,
                        'old_hash' => $this->createDataHash($oldValue),
                        'new_hash' => $this->createDataHash($newValue),
                    ]);
                }

                $changes[] = [
                    'field' => $key,
                    'field_label' => $this->fieldLabels[$key] ?? $key,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Verifica se dois valores são diferentes
     */
    private function valuesAreDifferent($oldValue, $newValue): bool
    {
        // Trata null e string vazia como equivalentes
        $oldValue = $oldValue === '' ? null : $oldValue;
        $newValue = $newValue === '' ? null : $newValue;

        // Se ambos são null, são iguais
        if ($oldValue === null && $newValue === null) {
            return false;
        }

        // Se apenas um é null, são diferentes (exceto se for array vazio)
        if ($oldValue === null && is_array($newValue) && empty($newValue)) {
            return false;
        }
        if ($newValue === null && is_array($oldValue) && empty($oldValue)) {
            return false;
        }
        if (($oldValue === null && $newValue !== null) || ($oldValue !== null && $newValue === null)) {
            return true;
        }

        // Para arrays e objetos, cria um hash dos dados essenciais
        if (is_array($oldValue) || is_object($oldValue)) {
            $oldValue = $this->createDataHash($oldValue);
        }
        if (is_array($newValue) || is_object($newValue)) {
            $newValue = $this->createDataHash($newValue);
        }

        // Comparação estrita
        return $oldValue !== $newValue;
    }

    /**
     * Cria um hash dos dados para comparação consistente
     */
    private function createDataHash($data): string
    {
        if (!is_array($data)) {
            return (string) $data;
        }

        // Remove valores null
        $data = $this->removeNullValues($data);

        // Ordena recursivamente
        $data = $this->sortArrayRecursive($data);

        // Gera hash MD5 do JSON
        return md5(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Remove valores null recursivamente
     */
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

    /**
     * Ordena arrays recursivamente para comparação consistente
     */
    private function sortArrayRecursive($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        // Primeiro, ordena recursivamente os valores
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->sortArrayRecursive($value);
            }
        }

        // Se for array associativo, ordena por chave
        $keys = array_keys($array);
        if ($keys !== range(0, count($array) - 1)) {
            ksort($array, SORT_STRING);
        }

        return $array;
    }

    /**
     * Formata mudanças para criação (todos os campos preenchidos são "mudanças")
     */
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

    /**
     * Gera descrição da atualização baseada nas mudanças
     */
    private function generateUpdateDescription(array $changes): string
    {
        $changedFields = array_slice(array_column($changes, 'field_label'), 0, 3);
        $description = 'Atualizado: ' . implode(', ', $changedFields);

        if (count($changes) > 3) {
            $description .= ' e mais ' . (count($changes) - 3) . ' campo(s)';
        }

        return $description;
    }

    /**
     * Obtém o histórico de um evento com paginação
     */
    public function getEventHistory(int $eventId, int $perPage = 15)
    {
        return EventHistory::where('event_id', $eventId)
            ->with(['user.collaborator.sector', 'user.collaborator.sectorOffice'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Formata valor para exibição
     */
    public function formatValueForDisplay($value, string $field = null): string
    {
        if ($value === null || $value === '') {
            return '(vazio)';
        }

        // Formatação específica por tipo de campo
        if (in_array($field, ['date', 'closed_at'])) {
            return date('d/m/Y', strtotime($value));
        }

        if (in_array($field, ['time'])) {
            return date('H:i', strtotime($value));
        }

        if (in_array($field, ['total_value', 'buffet_value', 'sub_total', 'unit_insurance'])) {
            return 'R$ ' . number_format($value, 2, ',', '.');
        }

        if (in_array($field, ['is_draft', 'is_medicine', 'lease'])) {
            return $value ? 'Sim' : 'Não';
        }

        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        // Formatação específica para relacionamentos
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
