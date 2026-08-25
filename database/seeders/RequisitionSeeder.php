<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\Product;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class RequisitionSeeder extends Seeder
{
    public function run(): void
    {
        $products    = Product::all();
        $stocks = Stock::all();

        if ($products->isEmpty() || $stocks->isEmpty()) {
            return;
        }

        Requisition::factory(100)->create()->each(function (Requisition $requisition) use ($products, $stocks) {
            $stock = $stocks->find($requisition->stock_id) ?? $stocks->first();
            $items      = $products->random(rand(1, 4));

            foreach ($items as $product) {
                $quantity = rand(1, 10);

                RequisitionItem::create([
                    'requisition_id' => $requisition->id,
                    'product_id'     => $product->id,
                    'quantity'       => $quantity,
                ]);

                $price = $this->getProductPrice($product->id, $stock->id);

                StockMovement::create([
                    'product_id'        => $product->id,
                    'stock_id'          => $stock->id,
                    'type'              => StockMovement::TYPE_OUT,
                    'quantity'          => $quantity,
                    'price'             => $price,
                    'movementable_type' => Requisition::class,
                    'movementable_id'   => $requisition->id,
                ]);

                $this->updateStockLevel($product->id, $stock->id, StockMovement::TYPE_OUT, $quantity, $price);
            }
        });
    }

    private function getProductPrice(int $productId, int $stockId): float
    {
        $level = StockLevel::where('product_id', $productId)
            ->where('stock_id', $stockId)
            ->first();

        return $level?->weighted_average_price > 0
            ? (float) $level->weighted_average_price
            : fake()->randomFloat(2, 5, 200);
    }

    private function updateStockLevel(int $productId, int $stockId, string $type, int $quantity, float $price): void
    {
        $level = StockLevel::firstOrCreate(
            ['product_id' => $productId, 'stock_id' => $stockId],
            ['current_quantity' => 0, 'weighted_average_price' => 0, 'total_value' => 0],
        );

        $newQuantity = max(0, $level->current_quantity - $quantity);

        $level->update([
            'current_quantity' => $newQuantity,
            'total_value'      => round($newQuantity * $level->weighted_average_price, 2),
        ]);
    }
}
