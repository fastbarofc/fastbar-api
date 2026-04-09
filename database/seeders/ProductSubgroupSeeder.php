<?php

namespace Database\Seeders;

use App\Models\ProductGroup;
use App\Models\ProductSubgroup;
use Illuminate\Database\Seeder;

class ProductSubgroupSeeder extends Seeder
{
    public function run(): void
    {
        $subgroups = [
            'roast' => [
                ['slug' => 'roast-chicken', 'description' => 'Frango'],
                ['slug' => 'roast-beef',    'description' => 'Boi'],
                ['slug' => 'roast-pork',    'description' => 'Porco'],
            ],
            'drink' => [
                ['slug' => 'drink-alcoholic',     'description' => 'Alcoólica'],
                ['slug' => 'drink-non-alcoholic', 'description' => 'Não Alcoólica'],
            ],
        ];

        foreach ($subgroups as $groupSlug => $items) {
            $group = ProductGroup::where('slug', $groupSlug)->first();

            if (!$group) {
                continue;
            }

            foreach ($items as $item) {
                ProductSubgroup::firstOrCreate(
                    ['slug' => $item['slug']],
                    [
                        'description'      => $item['description'],
                        'product_group_id' => $group->id,
                    ]
                );
            }
        }
    }
}
