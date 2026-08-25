<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $stocks   = Stock::all();
        $orders   = PurchaseOrder::all();

        // Toda movimentação exige um documento de origem, então sem ordens de
        // compra não há entrada possível
        if ($stocks->isEmpty() || $products->isEmpty() || $orders->isEmpty()) {
            return;
        }

        foreach ($products as $product) {
            // Cada produto recebe movimentações em 1 a 2 estoques
            $stks = $stocks->random(min(rand(1, 2), $stocks->count()));
            if (!$stks instanceof \Illuminate\Support\Collection) {
                $stks = collect([$stks]);
            }

            foreach ($stks as $stock) {
                $order = $orders->random();

                // Entradas. As saídas ficam a cargo de RequisitionSeeder e
                // DevolutionSeeder, que rodam depois e têm o documento de origem
                $inCount = rand(3, 8);
                for ($i = 0; $i < $inCount; $i++) {
                    $quantity = rand(5, 100);
                    $price    = fake()->randomFloat(2, 10, 300);

                    StockMovement::create([
                        'product_id'        => $product->id,
                        'stock_id'          => $stock->id,
                        'type'              => StockMovement::TYPE_IN,
                        'quantity'          => $quantity,
                        'price'             => $price,
                        'movementable_type' => PurchaseOrder::class,
                        'movementable_id'   => $order->id,
                    ]);

                    $this->updateStockLevel($product->id, $stock->id, StockMovement::TYPE_IN, $quantity, $price);
                }
            }
        }
    }

    private function updateStockLevel(int $productId, int $stockId, string $type, int $quantity, float $price): void
    {
        $level = StockLevel::firstOrCreate(
            ['product_id' => $productId, 'stock_id' => $stockId],
            ['current_quantity' => 0, 'weighted_average_price' => 0, 'total_value' => 0],
        );

        if ($type === StockMovement::TYPE_IN) {
            $newQuantity = $level->current_quantity + $quantity;
            $newAvgPrice = $level->current_quantity > 0
                ? (($level->current_quantity * $level->weighted_average_price) + ($quantity * $price)) / $newQuantity
                : $price;

            $level->update([
                'current_quantity'       => $newQuantity,
                'weighted_average_price' => round($newAvgPrice, 2),
                'total_value'            => round($newQuantity * $newAvgPrice, 2),
            ]);
        } else {
            $newQuantity = max(0, $level->current_quantity - $quantity);

            $level->update([
                'current_quantity' => $newQuantity,
                'total_value'      => round($newQuantity * $level->weighted_average_price, 2),
            ]);
        }
    }
}
