<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Monta a árvore completa de pastas e drinks numa resposta só, no mesmo formato
 * de nó ({data, children}) das rotas de árvore que carregam um nível por vez.
 */
class DrinkTreeService
{
    /** Espera `products` já carregado nas pastas, para não consultar uma a uma. */
    public static function build(Collection $folders, string $parentKey): array
    {
        $byParent = $folders->groupBy(fn ($folder) => $folder->{$parentKey} ?? 'root');

        return self::nodes($byParent, 'root', $parentKey);
    }

    private static function nodes(Collection $byParent, int|string $parentId, string $parentKey): array
    {
        return $byParent->get($parentId, collect())
            ->map(fn ($folder) => [
                'data' => [
                    'id' => $folder->id,
                    $parentKey => $folder->{$parentKey},
                    'name' => $folder->name,
                    'type' => 'kit',
                    'themed_table' => (bool) $folder->themed_table,
                    'optional' => (bool) $folder->optional,
                    'checklist' => (bool) $folder->checklist,
                    'observation' => $folder->observation,
                    'value' => $folder->value,
                    'ordination' => $folder->ordination,
                ],
                'children' => [
                    ...$folder->products->map(fn ($product) => [
                        'data' => [
                            'id' => $product->id,
                            $parentKey => $folder->id,
                            'name' => $product->name,
                            'type' => 'product',
                            'optional' => (bool) $product->pivot->optional,
                            'checklist' => (bool) $product->pivot->checklist,
                            'observation' => $product->pivot->observation,
                            'value' => $product->pivot->value,
                            'ordination' => $product->pivot->ordination,
                        ],
                        'children' => [],
                    ])->all(),
                    ...self::nodes($byParent, $folder->id, $parentKey),
                ],
            ])
            ->values()
            ->all();
    }
}
