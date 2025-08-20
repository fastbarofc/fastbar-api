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
        // Criar 5 ordens de compra
        PurchaseOrder::factory(5)->create()->each(function ($purchaseOrder) {
            // Para cada ordem de compra, anexar de 1 a 3 produtos
            $products = Product::inRandomOrder()->take(rand(1, 3))->get();
            $purchaseRequests = PurchaseRequest::inRandomOrder()->take(3)->get();
            
            $productsToAttach = [];
            foreach ($products as $index => $product) {
                $purchaseRequest = $purchaseRequests->get($index % $purchaseRequests->count());
                $productsToAttach[$product->id] = [
                    'purchase_request_id' => $purchaseRequest->id,
                    'factor' => fake()->optional()->word(),
                    'reference' => fake()->optional()->word(),
                    'quantity' => fake()->numberBetween(1, 15),
                    'price' => fake()->randomFloat(2, 10, 500),
                ];
            }
            
            $purchaseOrder->products()->attach($productsToAttach);
        });
    }
}
