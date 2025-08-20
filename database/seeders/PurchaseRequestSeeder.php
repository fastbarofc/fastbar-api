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
        // Criar 10 pedidos de compra
        PurchaseRequest::factory(10)->create()->each(function ($purchaseRequest) {
            // Para cada pedido de compra, anexar de 1 a 5 produtos
            $products = Product::inRandomOrder()->take(rand(1, 5))->get();
            
            $productsToAttach = [];
            foreach ($products as $product) {
                $productsToAttach[$product->id] = [
                    'factor' => fake()->optional()->word(),
                    'reference' => fake()->optional()->word(),
                    'quantity' => fake()->numberBetween(1, 20),
                ];
            }
            
            $purchaseRequest->products()->attach($productsToAttach);
        });
    }
}
