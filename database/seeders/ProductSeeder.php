<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Kit;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::factory(100)->create();

        $kits = Kit::all();

        foreach ($kits as $kit) {
            $productsToAttach = $products->random(2);
            $kit->products()->attach($productsToAttach);
        }
    }
}
