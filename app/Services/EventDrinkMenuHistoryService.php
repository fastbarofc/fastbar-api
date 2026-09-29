<?php

namespace App\Services;

use App\Models\EventHistory;
use App\Models\EventKit;
use Closure;
use Illuminate\Support\Facades\Auth;

class EventDrinkMenuHistoryService
{
    private const FIELD = 'drinks_menu';

    private const FOLDER_ATTRIBUTES = [
        'name' => 'Nome',
        'themed_table' => 'Mesa temática',
        'optional' => 'Opcional',
        'checklist' => 'Checklist',
        'observation' => 'Observação',
        'value' => 'Valor',
    ];

    private const DRINK_ATTRIBUTES = [
        'optional' => 'Opcional',
        'checklist' => 'Checklist',
        'observation' => 'Observação',
        'value' => 'Valor',
    ];

    public function record(?int $eventId, Closure $change): mixed
    {
        if (!$eventId) {
            return $change();
        }

        $before = $this->snapshot($eventId);
        $result = $change();
        $this->log($eventId, $before, $this->snapshot($eventId));

        return $result;
    }

    /** A ordenação fica de fora: reordenar grava a posição de todos os irmãos e lotaria o histórico. */
    private function snapshot(int $eventId): array
    {
        $folders = EventKit::with('products')->where('event_id', $eventId)->get()->keyBy('id');

        $paths = [];
        $pathOf = function (EventKit $folder) use (&$pathOf, &$paths, $folders): string {
            if (!isset($paths[$folder->id])) {
                $parent = $folders->get($folder->event_kit_id);
                $paths[$folder->id] = $parent ? $pathOf($parent) . ' > ' . $folder->name : $folder->name;
            }

            return $paths[$folder->id];
        };

        $snapshot = ['folders' => [], 'drinks' => []];

        foreach ($folders as $folder) {
            $parent = $folders->get($folder->event_kit_id);

            $snapshot['folders'][$folder->id] = [
                'path' => $pathOf($folder),
                'parent_id' => $parent?->id,
                'name' => $folder->name,
                'themed_table' => (bool) $folder->themed_table,
                'optional' => (bool) $folder->optional,
                'checklist' => (bool) $folder->checklist,
                'observation' => $folder->observation,
                'value' => $folder->value,
            ];

            foreach ($folder->products as $product) {
                $snapshot['drinks']["{$folder->id}:{$product->id}"] = [
                    'product_id' => $product->id,
                    'folder_id' => $folder->id,
                    'name' => $product->name,
                    'optional' => (bool) $product->pivot->optional,
                    'checklist' => (bool) $product->pivot->checklist,
                    'observation' => $product->pivot->observation,
                    'value' => $product->pivot->value,
                ];
            }
        }

        return $snapshot;
    }

    private function log(int $eventId, array $before, array $after): void
    {
        $changes = [
            ...$this->folderChanges($before, $after),
            ...$this->drinkChanges($before, $after),
        ];

        if (empty($changes)) {
            return;
        }

        EventHistory::create([
            'event_id' => $eventId,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'changes' => $changes,
        ]);
    }

    private function folderChanges(array $before, array $after): array
    {
        $changes = [];

        $added = array_diff_key($after['folders'], $before['folders']);
        foreach ($added as $id => $folder) {
            if (!isset($added[$folder['parent_id']])) {
                $changes[] = $this->change('Menu de drinks: pasta adicionada', null, $this->describeFolder($id, $after));
            }
        }

        $removed = array_diff_key($before['folders'], $after['folders']);
        foreach ($removed as $id => $folder) {
            if (!isset($removed[$folder['parent_id']])) {
                $changes[] = $this->change('Menu de drinks: pasta removida', $this->describeFolder($id, $before), null);
            }
        }

        foreach (array_intersect_key($after['folders'], $before['folders']) as $id => $folder) {
            $old = $before['folders'][$id];

            if ($old['parent_id'] !== $folder['parent_id']) {
                $changes[] = $this->change(
                    "Menu de drinks: pasta {$old['path']} - Pasta superior",
                    $before['folders'][$old['parent_id']]['path'] ?? '(raiz)',
                    $after['folders'][$folder['parent_id']]['path'] ?? '(raiz)',
                );
            }

            foreach (self::FOLDER_ATTRIBUTES as $attribute => $label) {
                if ($this->differs($old[$attribute], $folder[$attribute])) {
                    $changes[] = $this->change(
                        "Menu de drinks: pasta {$old['path']} - {$label}",
                        $this->formatValue($attribute, $old[$attribute]),
                        $this->formatValue($attribute, $folder[$attribute]),
                    );
                }
            }
        }

        return $changes;
    }

    /** Drinks de pastas inteiras adicionadas ou removidas já aparecem na descrição da pasta. */
    private function drinkChanges(array $before, array $after): array
    {
        $changes = [];

        $added = array_filter(
            array_diff_key($after['drinks'], $before['drinks']),
            fn (array $drink) => isset($before['folders'][$drink['folder_id']]),
        );
        $removed = array_filter(
            array_diff_key($before['drinks'], $after['drinks']),
            fn (array $drink) => isset($after['folders'][$drink['folder_id']]),
        );

        foreach ($removed as $removedKey => $drink) {
            $movedKey = collect($added)->search(fn (array $candidate) => $candidate['product_id'] === $drink['product_id']);

            if ($movedKey === false) {
                $changes[] = $this->change('Menu de drinks: drink removido', $this->describeDrink($drink, $before), null);
                continue;
            }

            $changes[] = $this->change(
                'Menu de drinks: drink movido',
                $this->describeDrink($drink, $before),
                $this->describeDrink($added[$movedKey], $after),
            );
            unset($added[$movedKey]);
        }

        foreach ($added as $drink) {
            $changes[] = $this->change('Menu de drinks: drink adicionado', null, $this->describeDrink($drink, $after));
        }

        foreach (array_intersect_key($after['drinks'], $before['drinks']) as $key => $drink) {
            $old = $before['drinks'][$key];

            foreach (self::DRINK_ATTRIBUTES as $attribute => $label) {
                if ($this->differs($old[$attribute], $drink[$attribute])) {
                    $changes[] = $this->change(
                        "Menu de drinks: drink {$this->describeDrink($old, $before)} - {$label}",
                        $this->formatValue($attribute, $old[$attribute]),
                        $this->formatValue($attribute, $drink[$attribute]),
                    );
                }
            }
        }

        return $changes;
    }

    private function describeFolder(int $folderId, array $snapshot): string
    {
        $drinks = collect($snapshot['drinks'])
            ->filter(fn (array $drink) => $this->isInside($drink['folder_id'], $folderId, $snapshot))
            ->pluck('name')
            ->unique()
            ->values();

        $path = $snapshot['folders'][$folderId]['path'];

        if ($drinks->isEmpty()) {
            return $path;
        }

        $count = $drinks->count() . ' ' . ($drinks->count() === 1 ? 'drink' : 'drinks');

        return "{$path} ({$count}: {$drinks->implode(', ')})";
    }

    private function isInside(?int $folderId, int $ancestorId, array $snapshot): bool
    {
        while ($folderId !== null) {
            if ($folderId === $ancestorId) {
                return true;
            }
            $folderId = $snapshot['folders'][$folderId]['parent_id'] ?? null;
        }

        return false;
    }

    private function describeDrink(array $drink, array $snapshot): string
    {
        return "{$drink['name']} (em {$snapshot['folders'][$drink['folder_id']]['path']})";
    }

    private function differs($old, $new): bool
    {
        $old = $old === '' ? null : $old;
        $new = $new === '' ? null : $new;

        if (is_numeric($old) && is_numeric($new)) {
            return (float) $old !== (float) $new;
        }

        return $old !== $new;
    }

    private function formatValue(string $attribute, $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        if ($value === null || $value === '') {
            return '(vazio)';
        }

        if ($attribute === 'value') {
            return 'R$ ' . number_format((float) $value, 2, ',', '.');
        }

        return (string) $value;
    }

    private function change(string $label, ?string $old, ?string $new): array
    {
        return [
            'field' => self::FIELD,
            'label' => $label,
            'old' => $old ?? '(nenhum)',
            'new' => $new ?? '(nenhum)',
        ];
    }
}
