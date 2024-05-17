<?php

namespace Database\Seeders;

use App\Models\ProductGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['slug' => 'roast', 'description' => 'Assado'],
            ['slug' => 'drink', 'description' => 'Bebida'],
            ['slug' => 'sweet', 'description' => 'Doce'],
            ['slug' => 'fried', 'description' => 'Frito'],
            ['slug' => 'made-to-order', 'description' => 'Preparado na hora'],
        ];

        foreach ($groups as $group) {
            ProductGroup::create($group);
        }
    }
}
