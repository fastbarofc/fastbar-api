<?php

namespace App\Services;

use App\Models\EventKit;
use App\Models\Kit;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Copia pastas e drinks do catálogo para o menu do evento. A pasta do catálogo
 * entra com o mesmo caminho de pastas que tem lá, reaproveitando as pastas de
 * mesmo nome que o evento já tiver.
 */
class EventDrinkMenuService
{
    public function addFolder(int $eventId, Kit $kit): EventKit
    {
        $eventKit = EventKit::find($this->folderFor(null, $kit, $eventId));
        $this->copyChildren($kit, $eventKit);

        return $eventKit;
    }

    public function addDrink(int $eventId, Kit $kit, Product $product): EventKit
    {
        $eventKit = EventKit::find($this->folderFor(null, $kit, $eventId));

        if ($eventKit->products()->where('products.id', $product->id)->exists()) {
            return $eventKit;
        }

        $kitProduct = $kit->products()->where('product_id', $product->id)->first();

        $product->eventKits()->attach($eventKit->id, [
            'ordination' => $this->nextProductOrdination($eventKit),
            'optional' => $kitProduct ? ($kitProduct->pivot->optional ?? false) : false,
            'checklist' => $kitProduct ? ($kitProduct->pivot->checklist ?? false) : false,
            'observation' => $kitProduct ? ($kitProduct->pivot->observation ?? null) : null,
        ]);

        return $eventKit;
    }

    /**
     * Tira os drinks das pastas do evento. Pasta que ficou sem nenhum drink por
     * causa disso sai junto; pasta que já estava vazia continua.
     *
     * @param array<array{event_kit_id: int, product_id: int}> $drinks
     */
    public function removeDrinks(int $eventId, array $drinks): void
    {
        $folders = $this->folders($eventId);
        $hadDrinks = $folders->keys()->filter(fn (int $id) => $this->subtreeHasDrinks($folders, $id))->all();

        foreach ($drinks as $drink) {
            $folders->get($drink['event_kit_id'])?->products()->detach($drink['product_id']);
        }

        $folders = $this->folders($eventId);

        foreach ($hadDrinks as $id) {
            if (!$this->subtreeHasDrinks($folders, $id)) {
                $folders->get($id)->delete();
            }
        }
    }

    /** Drinks do menu na ordem da árvore, com a pasta em que cada um está. */
    public function drinks(int $eventId): array
    {
        $folders = $this->folders($eventId);
        $byParent = $folders->groupBy(fn (EventKit $folder) => $folder->event_kit_id ?? 'root');

        $walk = function (int|string $parentId, string $path) use (&$walk, $byParent): array {
            return $byParent->get($parentId, collect())
                ->sortBy('ordination')
                ->flatMap(function (EventKit $folder) use ($walk, $path) {
                    $folderPath = $path === '' ? $folder->name : "{$path} > {$folder->name}";

                    return [
                        ...$folder->products
                            // Só o que está no menu: marcado em Básico ou como Opcional
                            ->filter(fn (Product $product) => (bool) ($product->pivot->selected ?? true) || (bool) $product->pivot->optional)
                            ->sortBy('pivot.ordination')->map(fn (Product $product) => [
                            'event_kit_id' => $folder->id,
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'folder' => $folderPath,
                        ]),
                        ...$walk($folder->id, $folderPath),
                    ];
                })
                ->values()
                ->all();
        };

        return $walk('root', '');
    }

    private function folders(int $eventId): Collection
    {
        return EventKit::with('products')->where('event_id', $eventId)->get()->keyBy('id');
    }

    private function subtreeHasDrinks(Collection $folders, int $folderId): bool
    {
        if ($folders->get($folderId)?->products->isNotEmpty()) {
            return true;
        }

        return $folders
            ->where('event_kit_id', $folderId)
            ->contains(fn (EventKit $child) => $this->subtreeHasDrinks($folders, $child->id));
    }

    private function folderFor($parentId, Kit $kit, int $eventId): int
    {
        if (!empty($kit->kit_id)) {
            $parentKit = Kit::find($kit->kit_id);
            if ($parentKit) {
                $parentId = $this->folderFor($parentId, $parentKit, $eventId);
            }
        }

        $eventKit = EventKit::where('event_kit_id', $parentId)->where('event_id', $eventId)->where('name', $kit->name)->first();

        if (empty($eventKit)) {
            $eventKit = EventKit::create([
                'name' => $kit->name,
                'event_id' => $eventId,
                'event_kit_id' => $parentId,
                'ordination' => EventKit::where('event_kit_id', $parentId)->where('event_id', $eventId)->count() + 1,
                'themed_table' => $kit->themed_table ?? false,
                'optional' => $kit->optional ?? false,
                'checklist' => $kit->checklist ?? false,
                'observation' => $kit->observation ?? null,
            ]);
        }

        return $eventKit->id;
    }

    private function copyChildren(?Kit $kit, EventKit $eventKit): void
    {
        if (!$kit || !$kit->has_children) {
            return;
        }

        foreach ($kit->children as $child) {
            if ($child['data']['type'] == 'product') {
                $product = Product::find($child['data']['id']);

                if ($eventKit->products()->where('products.id', $product->id)->exists()) {
                    continue;
                }

                $kitProduct = $kit->products()->where('product_id', $child['data']['id'])->first();

                $product->eventKits()->attach($eventKit->id, [
                    'ordination' => $this->nextProductOrdination($eventKit),
                    'optional' => $kitProduct ? ($kitProduct->pivot->optional ?? false) : ($child['data']['optional'] ?? false),
                    'checklist' => $kitProduct ? ($kitProduct->pivot->checklist ?? false) : ($child['data']['checklist'] ?? false),
                    'observation' => $kitProduct ? ($kitProduct->pivot->observation ?? null) : ($child['data']['observation'] ?? null),
                    // Entra desmarcado: no evento a pessoa marca em "Básico" só os drinks que quer (igual aos itens de buffet do Parthenon)
                    'selected' => false,
                ]);
                continue;
            }

            $data = $child['data'];
            $data['event_id'] = $eventKit->event_id;
            $data['event_kit_id'] = $eventKit->id;

            $originalKit = Kit::find($child['data']['id']);
            if ($originalKit) {
                $data['themed_table'] = $originalKit->themed_table ?? false;
                $data['optional'] = $originalKit->optional ?? false;
                $data['checklist'] = $originalKit->checklist ?? false;
                $data['observation'] = $originalKit->observation ?? null;
            } else {
                $data['themed_table'] = $child['data']['themed_table'] ?? false;
                $data['optional'] = $child['data']['optional'] ?? false;
                $data['checklist'] = $child['data']['checklist'] ?? false;
                $data['observation'] = $child['data']['observation'] ?? null;
            }

            // Pasta adicionada de novo: reaproveita a subpasta que o evento já tem (não duplica)
            $newEventKit = EventKit::where('event_kit_id', $data['event_kit_id'])->where('event_id', $data['event_id'])->where('name', $data['name'])->first();
            if (!$newEventKit) {
                $data['ordination'] = EventKit::where('event_kit_id', $data['event_kit_id'])->where('event_id', $data['event_id'])->get()->count() + 1;
                // Subpasta entra desmarcada; marcar um drink dentro dela marca a pasta
                $data['selected'] = false;
                unset($data['id'], $data['type'], $data['custom_item']);
                $newEventKit = EventKit::create($data);
            }

            if ($originalKit && $originalKit->has_children) {
                $this->copyChildren($originalKit, $newEventKit);
            }
        }
    }

    private function nextProductOrdination(EventKit $eventKit): int
    {
        $missing = $eventKit->products()->wherePivot('ordination', null)->get();
        $ordered = $eventKit->products()->wherePivot('ordination', '!=', null)->count();

        foreach ($missing as $index => $product) {
            $product->pivot->update(['ordination' => $ordered + $index + 1]);
        }

        return $eventKit->products()->count() + 1;
    }
}
