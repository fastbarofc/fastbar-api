<?php

namespace Database\Seeders;

use App\Models\Kit;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Importa a lista de Drinks (pastas e produtos) do Fastbar em produção (fastbarbr.com),
 * lida em 12/11/2026. Rodar depois do ProdutosFastbarbrSeeder. Pode rodar de novo:
 * acha a pasta pelo nome dentro da mesma pasta-mãe e atualiza.
 *
 * php artisan db:seed --class=DrinksFastbarbrSeeder
 */
class DrinksFastbarbrSeeder extends Seeder
{
    public function run(): void
    {
        $kits = json_decode(file_get_contents(__DIR__ . '/data/drinks-fastbarbr.json'), true);
        $products = json_decode(file_get_contents(__DIR__ . '/data/produtos-fastbarbr.json'), true);
        $productNames = array_column($products['p'], 1, 0); // id do site => nome

        DB::transaction(function () use ($kits, $productNames) {
            $kitMap = [];
            // As pastas vêm em ordem de árvore (mãe antes das filhas).
            foreach ($kits['k'] as [$id, $parentId, $name, $themed, $optional, $checklist, $observation, $ordination]) {
                $parent = $parentId ? $kitMap[$parentId] : null;
                $kit = Kit::firstOrNew(['kit_id' => $parent, 'name' => $name]);
                $kit->fill([
                    'themed_table' => (bool) $themed,
                    'optional' => (bool) $optional,
                    'checklist' => (bool) $checklist,
                    'observation' => $observation,
                    'ordination' => $ordination,
                ])->save();
                $kitMap[$id] = $kit->id;
            }

            $productIds = [];
            foreach ($kits['p'] as [$kitId, $productId, $optional, $checklist, $ordination]) {
                $localProductId = $productIds[$productId] ??= Product::where('name', $productNames[$productId])->value('id');
                if (!$localProductId) {
                    $this->command?->warn("Produto {$productNames[$productId]} não encontrado, rode o ProdutosFastbarbrSeeder antes.");
                    continue;
                }
                DB::table('kit_product')->updateOrInsert(
                    ['kit_id' => $kitMap[$kitId], 'product_id' => $localProductId, 'deleted_at' => null],
                    ['optional' => $optional, 'checklist' => $checklist, 'ordination' => $ordination, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        });

        $this->command?->info(count($kits['k']) . ' pastas e ' . count($kits['p']) . ' produtos de Drinks importados do fastbarbr.com.');
    }
}
