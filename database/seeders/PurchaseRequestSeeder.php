<?php

namespace Database\Seeders;

use App\Models\PurchaseRequest;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PurchaseRequest::factory(10)->create()->each(function ($purchaseRequest) {
            $products = Product::inRandomOrder()->take(rand(1, 5))->get();

            foreach ($products as $product) {
                $purchaseRequest->purchaseRequestProducts()->create([
                    'product_id'  => $product->id,
                    'factor'      => fake()->optional()->word(),
                    'observation' => fake()->optional()->word(),
                    'quantity'    => fake()->numberBetween(1, 20),
                ]);
            }
        });
    }
}
