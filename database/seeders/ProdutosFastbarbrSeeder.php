<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductSubgroup;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Importa os produtos do Fastbar em produção (fastbarbr.com), lidos em 12/11/2026.
 * Dados em database/seeders/data/produtos-fastbarbr.json (grupos, subgrupos, unidades,
 * produtos e composição dos drinks). Pode rodar de novo: acha pelo nome e atualiza.
 *
 * php artisan db:seed --class=ProdutosFastbarbrSeeder
 */
class ProdutosFastbarbrSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/data/produtos-fastbarbr.json'), true);
        $keys = $data['k'];
        $products = array_map(fn ($row) => array_combine($keys, $row), $data['p']);

        DB::transaction(function () use ($data, $products) {
            $usedGroups = array_unique(array_filter(array_column($products, 'product_group_id')));
            $usedSubgroups = array_unique(array_filter(array_column($products, 'product_subgroup_id')));

            $groupMap = [];
            foreach ($data['g'] as $g) {
                if (!in_array($g['id'], $usedGroups)) {
                    continue;
                }
                $groupMap[$g['id']] = ProductGroup::firstOrCreate(['description' => $g['d']])->id;
            }

            $subgroupMap = [];
            foreach ($data['s'] as $s) {
                if (!in_array($s['id'], $usedSubgroups)) {
                    continue;
                }
                $subgroupMap[$s['id']] = ProductSubgroup::firstOrCreate([
                    'description' => $s['d'],
                    'product_group_id' => $groupMap[$s['g']] ?? null,
                ])->id;
            }

            $unitMap = [];
            foreach ($data['u'] as $u) {
                $unitMap[$u['id']] = Unit::firstOrCreate(['description' => $u['d']])->id;
            }

            $productMap = [];
            foreach ($products as $p) {
                $attrs = [
                    'product_group_id' => $groupMap[$p['product_group_id']] ?? null,
                    'product_subgroup_id' => $subgroupMap[$p['product_subgroup_id']] ?? null,
                    'barcode' => $p['barcode'],
                    'unit_id' => $unitMap[$p['unit_id']] ?? null,
                    'weight' => $p['weight'],
                    'manufacturer' => $p['manufacturer'],
                    'value' => $p['value'],
                    'supplier' => $p['supplier'],
                    'marketable' => (bool) $p['marketable'],
                    'average_consumption' => $p['average_consumption'],
                    'display_checklist' => (bool) $p['display_checklist'],
                    'own_production' => (bool) $p['own_production'],
                    'composition_item' => (bool) $p['composition_item'],
                    'recipe_yield' => $p['recipe_yield'],
                ];

                $product = Product::where('name', $p['name'])->first();
                if ($product) {
                    $product->update($attrs);
                } else {
                    $product = new Product(['name' => $p['name']] + $attrs);
                    // Mantém o mesmo código do sistema no ar quando estiver livre.
                    if (!Product::withoutGlobalScopes()->whereKey($p['id'])->exists()) {
                        $product->id = $p['id'];
                    }
                    $product->save();
                }
                $productMap[$p['id']] = $product->id;
            }

            foreach ($data['c'] as $prodId => $items) {
                $sync = [];
                foreach ($items as [$componentId, $quantity]) {
                    $sync[$productMap[$componentId]] = ['quantity' => $quantity];
                }
                Product::find($productMap[$prodId])->components()->sync($sync);
            }
        });

        $this->command?->info(count($products) . ' produtos importados do fastbarbr.com.');
    }
}
