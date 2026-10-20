<?php

namespace App\Http\Resources;

use App\Models\ContractTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractVersionResource extends JsonResource
{
    private const GROUP_LABELS = [
        'contractant' => 'Dados do contratante',
        'menu' => 'Cardápio',
        'sections' => 'Cláusulas',
    ];

    private const MONEY_FIELDS = ['unit_price', 'total_price', 'excess_unit_price'];

    public function toArray(Request $request): array
    {
        $properties = $this->properties ?? [];

        return [
            'id' => $this->id,
            'version' => $this->version,
            'created_at' => $this->created_at,
            'generated_by' => $this->audits->firstWhere('event', 'created')?->user?->name,
            'event_date' => $this->value('event_date', $properties['event_date'] ?? null),
            'event_guests' => $this->value('event_guests', $properties['event_guests'] ?? null),
            'total_price' => $this->value('total_price', $properties['total_price'] ?? null),
            'changes' => $this->changes($this->properties_diff ?? []),
        ];
    }

    private function changes(array $diff): array
    {
        $rows = [];

        foreach ($diff['modified'] ?? [] as $field => $change) {
            $rows[] = $this->change($field, $change['old'] ?? null, $change['new'] ?? null);
        }

        foreach ($diff['added'] ?? [] as $field => $value) {
            $rows[] = $this->change($field, null, $value);
        }

        foreach ($diff['removed'] ?? [] as $field => $value) {
            $rows[] = $this->change($field, $value, null);
        }

        $order = array_keys(ContractTemplate::VARIABLES + self::GROUP_LABELS);

        usort($rows, fn (array $a, array $b) => $this->position($order, $a['field']) <=> $this->position($order, $b['field']));

        return $rows;
    }

    private function change(string $field, $old, $new): array
    {
        return [
            'field' => $field,
            'label' => ContractTemplate::VARIABLES[$field] ?? self::GROUP_LABELS[$field] ?? $field,
            'old' => $this->value($field, $old),
            'new' => $this->value($field, $new),
        ];
    }

    /** Grupos (contratante, cardápio, cláusulas) não cabem numa linha: o histórico só diz que mudaram. */
    private function value(string $field, $value): ?string
    {
        if (!is_scalar($value) || $value === '') {
            return null;
        }

        return in_array($field, self::MONEY_FIELDS, true) ? "R$ {$value}" : (string) $value;
    }

    private function position(array $order, string $field): int
    {
        $index = array_search($field, $order, true);

        return $index === false ? count($order) : $index;
    }
}
