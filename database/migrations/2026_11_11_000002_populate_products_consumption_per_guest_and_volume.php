<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    // Fator da aba BASE da planilha de estoque (evento 21877): consumo por convidado na unidade do produto.
    private const CATEGORY_FACTORS = [
        'vodka ' => 0.09,
        'gin ' => 0.03,
        'whisk' => 0.05,
        'cachaca ' => 0.01,
        'rum ' => 0.02,
        'tequila ' => 0.03,
        'vermouth ' => 0.015,
    ];

    private const PRODUCT_FACTORS = [
        [0.001, 0.2, ['Angostura 0,2 L']],
        [0.01, 1, ['Cachaça Guaraciaba 1L']],
        [0.04, 1, ['Campari', 'CAMPARI 0,998L']],
        [0.06, 0.66, ['Espumante Del Sole 0,66L']],
        [0.03, 0.75, ['Gin Tanqueray 0,750L']],
        [0.03, 0.7, ['Licor Pêssego Fórmula 0,7L']],
        [0.02, 1, ['Rum Montilla Carta Cristal 1L']],
        [0.03, 0.75, ['Tequila José Cuervo 0,75L', 'Tequila José Cuervo']],
        [0.015, 1, ['Vermouth Cinzano 1L']],
        [0.05, 1, ['Whisky Jonnie Walker Red Label 1L']],
        [0.06, 0.75, ['Licor 43 0,7 L']],
        [0.09, 1, ['Vodka Absolut 1L']],
        [0.01, 0.1, ['Adoçante 0,1L']],
        [0.1, 1.5, ['Água com gás 1,5L']],
        [0.05, 1, ['Água tônica 1L']],
        [0.01, 0.2, ['Creme de leite 0,2 KG']],
        [0.05, 0.35, ['Leite condensado 0,395 KG']],
        [0.009, 0.2, ['Leite de coco 0,2L']],
        [0.05, 1.5, ['H2OH Limoneto', 'H2OH Limoneto 1,5L']],
        [0.04, 1, ['Concentrado Maracujá 1L']],
        [0.02, 1, ['Promix Morango 0,25 KG']],
        [0.02, 1, ['Promix Abacaxi 0,25KG']],
        [0.001, 1, ['Emulsificante 1 KG']],
        [0.1, 10, ['Xarope Comum']],
        [0.045, 10, ['Xarope de Gengibre']],
        [0.015, 0.7, ['Xarope Fórmula Frutas Vermelhas 0,7L']],
        [0.015, 0.7, ['Xarope Fórmula Maçã-Verde 0,7L', 'Xarope Monin Maçã-Verde 0,7L']],
        [0.025, 0.7, ['Xarope Fórmula Tangerina 0,7L', 'Xarope Monin Tangerina 0,7L']],
        [0.015, 0.7, ['Xarope Fórmula Maracujá 0,7L']],
        [0.00125, 1, ['Pimenta Rosa', 'Canela', 'Anis Estrelado', 'Zimbro']],
        [0.0015, 1, ['Coco Ralado 1 kg']],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            // Drinks e frutas tinham 1,00 ou 0,00 do formulário antigo, sem sentido como consumo por convidado.
            DB::table('products')->whereIn('consumption_per_guest', [0, 1])->update(['consumption_per_guest' => null]);

            $products = $this->products();

            foreach ($products as $product) {
                $values = array_filter([
                    'consumption_per_guest' => $this->categoryFactor($product),
                    'volume' => $this->volumeFromName($product->name),
                ], fn ($value) => $value !== null);

                $this->update($product->id, $values);
            }

            foreach (self::PRODUCT_FACTORS as [$factor, $volume, $names]) {
                $normalizedNames = array_map(fn ($name) => $this->normalize($name), $names);

                $products->filter(fn ($product) => in_array($product->normalized, $normalizedNames, true))
                    ->each(fn ($product) => $this->update($product->id, [
                        'consumption_per_guest' => $factor,
                        'volume' => $volume,
                    ]));
            }
        });
    }

    public function down(): void
    {
    }

    private function products(): Collection
    {
        $composedIds = DB::table('product_compositions')->distinct()->pluck('product_id')->all();

        return DB::table('products')
            ->whereNull('deleted_at')
            ->get(['id', 'name'])
            ->map(function ($product) use ($composedIds) {
                $product->normalized = $this->normalize($product->name);
                $product->composed = in_array($product->id, $composedIds);

                return $product;
            });
    }

    private function categoryFactor(object $product): ?float
    {
        if ($product->composed) {
            return null;
        }

        foreach (self::CATEGORY_FACTORS as $prefix => $factor) {
            if (str_starts_with($product->normalized, $prefix)) {
                return $factor;
            }
        }

        return null;
    }

    private function volumeFromName(string $name): ?float
    {
        if (! preg_match_all('/(\d+(?:,\d+)?)\s*(ml|kg|l|g)\b/', $this->normalize($name), $matches, PREG_SET_ORDER)) {
            return null;
        }

        [, $number, $unit] = end($matches);
        $value = (float) str_replace(',', '.', $number);

        return in_array($unit, ['ml', 'g'], true) ? $value / 1000 : $value;
    }

    private function normalize(string $name): string
    {
        return rtrim(Str::squish(Str::lower(Str::ascii($name))), '.');
    }

    private function update(int $id, array $values): void
    {
        if ($values) {
            DB::table('products')->where('id', $id)->update($values);
        }
    }
};
