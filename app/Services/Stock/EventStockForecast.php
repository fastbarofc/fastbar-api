<?php

namespace App\Services\Stock;

use App\Models\Event;
use App\Models\Product;
use Illuminate\Support\Collection;

class EventStockForecast
{
    public const RELATIONS = [
        'eventBatches',
        'eventKits.products.unit',
        'eventKits.products.productGroup',
        'eventKits.products.components.unit',
        'eventKits.products.components.productGroup',
    ];

    // Drink é o produto montado na hora a partir da composição; o que vai para o estoque são os componentes dele.
    public static function isDrink(Product $product): bool
    {
        return ! $product->composition_item && $product->components->isNotEmpty();
    }

    public static function expectedQuantity(?float $consumptionPerGuest, ?float $volume, int $guests): ?float
    {
        if (! $consumptionPerGuest || ! $volume) {
            return null;
        }

        // round antes do ceil: 0,09 × 100 ÷ 1 vira 9,0000000001 em ponto flutuante e não pode virar 10.
        return (float) ceil(round($consumptionPerGuest * $guests / $volume, 6));
    }

    public function load(Event $event): Event
    {
        return $event->loadMissing(self::RELATIONS);
    }

    public function items(Event $event): Collection
    {
        $this->load($event);
        $guests = $event->totalGuests();
        $lines = [];

        foreach ($event->eventKits as $kit) {
            foreach ($kit->products as $product) {
                if (self::isDrink($product)) {
                    foreach ($product->components as $component) {
                        $this->add($lines, $component, $product->name);
                    }
                } else {
                    $this->add($lines, $product, $kit->name);
                }
            }
        }

        return collect($lines)
            ->map(fn (array $line) => $this->present($line['product'], $line['used_in'], $guests))
            ->sortBy([['group', 'asc'], ['name', 'asc']])
            ->values();
    }

    private function add(array &$lines, Product $product, string $usedIn): void
    {
        $lines[$product->id] ??= ['product' => $product, 'used_in' => []];

        if (! in_array($usedIn, $lines[$product->id]['used_in'], true)) {
            $lines[$product->id]['used_in'][] = $usedIn;
        }
    }

    private function present(Product $product, array $usedIn, int $guests): array
    {
        $missing = array_values(array_filter([
            $product->consumption_per_guest ? null : 'consumption',
            $product->volume ? null : 'volume',
        ]));

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'group' => $product->productGroup?->description ?? 'Sem grupo',
            'unit' => $product->unit?->description,
            'consumption_per_guest' => $product->consumption_per_guest,
            'volume' => $product->volume,
            'expected_quantity' => self::expectedQuantity($product->consumption_per_guest, $product->volume, $guests),
            'missing' => $missing,
            'used_in' => $usedIn,
        ];
    }
}
