<?php

namespace Database\Seeders;

use App\Models\Devolution;
use App\Models\DevolutionItem;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class DevolutionSeeder extends Seeder
{
    public function run(): void
    {
        // Apenas requisições com status returned ou partial podem ter devolução
        $requisitions = Requisition::whereIn('status', [
            Requisition::STATUS_RETURNED,
            Requisition::STATUS_PARTIAL,
        ])->with('requisitionItems')->get();

        if ($requisitions->isEmpty()) {
            return;
        }

        foreach ($requisitions as $requisition) {
            if ($requisition->requisitionItems->isEmpty()) {
                continue;
            }

            $devolution = Devolution::factory()->create([
                'requisition_id' => $requisition->id,
                'stock_id'       => $requisition->stock_id,
            ]);

            foreach ($requisition->requisitionItems as $item) {
                $quantityReturned = rand(1, $item->quantity);

                DevolutionItem::create([
                    'devolution_id'     => $devolution->id,
                    'product_id'        => $item->product_id,
                    'quantity_returned' => $quantityReturned,
                ]);

                $price = $this->getProductPrice($item->product_id, $requisition->stock_id);

                StockMovement::create([
                    'product_id'        => $item->product_id,
                    'stock_id'          => $requisition->stock_id,
                    'type'              => StockMovement::TYPE_IN,
                    'quantity'          => $quantityReturned,
                    'price'             => $price,
                    'movementable_type' => Devolution::class,
                    'movementable_id'   => $devolution->id,
                ]);

                $this->updateStockLevel($item->product_id, $requisition->stock_id, $quantityReturned, $price);
            }
        }
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

    private function updateStockLevel(int $productId, int $stockId, int $quantity, float $price): void
    {
        $level = StockLevel::firstOrCreate(
            ['product_id' => $productId, 'stock_id' => $stockId],
            ['current_quantity' => 0, 'weighted_average_price' => 0, 'total_value' => 0],
        );

        $newQuantity = $level->current_quantity + $quantity;
        $newAvgPrice = $level->current_quantity > 0
            ? (($level->current_quantity * $level->weighted_average_price) + ($quantity * $price)) / $newQuantity
            : $price;

        $level->update([
            'current_quantity'       => $newQuantity,
            'weighted_average_price' => round($newAvgPrice, 2),
            'total_value'            => round($newQuantity * $newAvgPrice, 2),
        ]);
    }
}
