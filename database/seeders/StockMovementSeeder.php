<?php

namespace Database\Seeders;

use App\Models\Department;
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
        $products    = Product::all();
        $departments = Department::all();
        $orders      = PurchaseOrder::all();

        if ($departments->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach ($products as $product) {
            // Cada produto recebe movimentações em 1 a 2 departamentos
            $depts = $departments->random(min(rand(1, 2), $departments->count()));
            if (!$depts instanceof \Illuminate\Support\Collection) {
                $depts = collect([$depts]);
            }

            foreach ($depts as $department) {
                $order = $orders->isNotEmpty() ? $orders->random() : null;

                // Entradas
                $inCount = rand(3, 8);
                for ($i = 0; $i < $inCount; $i++) {
                    $quantity = rand(5, 100);
                    $price    = fake()->randomFloat(2, 10, 300);

                    StockMovement::create([
                        'product_id'        => $product->id,
                        'department_id'     => $department->id,
                        'type'              => StockMovement::TYPE_IN,
                        'quantity'          => $quantity,
                        'price'             => $price,
                        'movementable_type' => $order ? PurchaseOrder::class : null,
                        'movementable_id'   => $order?->id,
                    ]);

                    $this->updateStockLevel($product->id, $department->id, StockMovement::TYPE_IN, $quantity, $price);
                }

                // Saídas
                $outCount = rand(1, 4);
                for ($i = 0; $i < $outCount; $i++) {
                    $quantity = rand(1, 15);
                    $price    = fake()->randomFloat(2, 10, 300);

                    StockMovement::create([
                        'product_id'        => $product->id,
                        'department_id'     => $department->id,
                        'type'              => StockMovement::TYPE_OUT,
                        'quantity'          => $quantity,
                        'price'             => $price,
                        'movementable_type' => null,
                        'movementable_id'   => null,
                    ]);

                    $this->updateStockLevel($product->id, $department->id, StockMovement::TYPE_OUT, $quantity, $price);
                }
            }
        }
    }

    private function updateStockLevel(int $productId, int $departmentId, string $type, int $quantity, float $price): void
    {
        $level = StockLevel::firstOrCreate(
            ['product_id' => $productId, 'department_id' => $departmentId],
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
