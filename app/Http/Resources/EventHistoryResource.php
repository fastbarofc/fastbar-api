<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'action' => $this->action,
            'action_label' => $this->getActionLabel($this->action),
            'changes' => $this->formatChanges($this->changes ?? []),
            'changes_summary' => $this->getChangesSummary($this->changes ?? []),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'collaborator' => [
                    'id' => $this->user?->collaborator?->id,
                    'name' => $this->user?->collaborator?->name,
                    'sector' => [
                        'id' => $this->user?->collaborator?->sector?->id,
                        'name' => $this->user?->collaborator?->sector?->name,
                    ],
                    'sector_office' => [
                        'id' => $this->user?->collaborator?->sectorOffice?->id,
                        'name' => $this->user?->collaborator?->sectorOffice?->name,
                    ],
                ],
            ],
        ];
    }

    /**
     * Formata as mudanças para exibição
     */
    private function formatChanges(array $changes): array
    {
        if (empty($changes)) {
            return [];
        }

        return array_map(function ($change) {
            // Garante que os valores sejam strings
            $old = isset($change['old']) ? (string) $change['old'] : '';
            $new = isset($change['new']) ? (string) $change['new'] : '';

            return [
                'field' => $change['field'] ?? '',
                'label' => $change['label'] ?? '',
                'old_value' => $old,
                'new_value' => $new,
                'description' => $this->getChangeDescription([
                    'label' => $change['label'] ?? '',
                    'old' => $old,
                    'new' => $new,
                ]),
            ];
        }, $changes);
    }

    /**
     * Gera descrição amigável de uma mudança
     */
    private function getChangeDescription(array $change): string
    {
        $label = $change['label'] ?? $change['field'] ?? '';
        $oldValue = $change['old'] ?? '';
        $newValue = $change['new'] ?? '';

        if ($oldValue === '(vazio)' || $oldValue === '(nenhum)' || $oldValue === '(não configurado)') {
            return "{$label} foi definido como: {$newValue}";
        }

        if ($newValue === '(vazio)' || $newValue === '(nenhum)' || $newValue === '(não configurado)') {
            return "{$label} foi removido (era: {$oldValue})";
        }

        return "{$label} foi alterado de \"{$oldValue}\" para \"{$newValue}\"";
    }

    /**
     * Obtém resumo das mudanças
     */
    private function getChangesSummary(array $changes): string
    {
        if (empty($changes)) {
            return 'Nenhuma mudança';
        }

        $count = count($changes);
        $labels = array_map(function ($change) {
            return $change['label'] ?? $change['field'] ?? '';
        }, $changes);

        $fields = array_slice($labels, 0, 3);
        $summary = implode(', ', array_filter($fields));

        if ($count > 3) {
            $summary .= " e mais " . ($count - 3) . " campo" . ($count - 3 === 1 ? '' : 's');
        }

        return $summary;
    }

    /**
     * Traduz o tipo de ação
     */
    private function getActionLabel(string $action): string
    {
        $labels = [
            'created' => 'Criado',
            'updated' => 'Atualizado',
            'deleted' => 'Excluído',
        ];

        return $labels[$action] ?? $action;
    }
}
