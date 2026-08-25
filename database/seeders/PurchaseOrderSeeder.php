<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PurchaseOrder::factory(100)->create()->each(function ($purchaseOrder) {
            $products = Product::inRandomOrder()->take(rand(3, 8))->get();
            $purchaseRequests = PurchaseRequest::inRandomOrder()->take(3)->get();
            
            $productsToAttach = [];
            foreach ($products as $index => $product) {
                $purchaseRequest = $purchaseRequests->get($index % $purchaseRequests->count());
                $productsToAttach[$product->id] = [
                    'purchase_request_id' => $purchaseRequest->id,
                    'factor' => fake()->optional()->word(),
                    'observation' => fake()->optional()->word(),
                    'quantity' => fake()->numberBetween(1, 15),
                    'price' => fake()->randomFloat(2, 10, 500),
                ];
            }
            
            $purchaseOrder->products()->attach($productsToAttach);
        });
    }
}
