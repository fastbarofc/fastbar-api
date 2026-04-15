<?php

namespace Database\Seeders;

use App\Models\Department;
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
        $departments = Department::all();

        if ($products->isEmpty() || $departments->isEmpty()) {
            return;
        }

        Requisition::factory(100)->create()->each(function (Requisition $requisition) use ($products, $departments) {
            $department = $departments->find($requisition->department_id) ?? $departments->first();
            $items      = $products->random(rand(1, 4));

            foreach ($items as $product) {
                $quantity = rand(1, 10);

                RequisitionItem::create([
                    'requisition_id' => $requisition->id,
                    'product_id'     => $product->id,
                    'quantity'       => $quantity,
                ]);

                $price = $this->getProductPrice($product->id, $department->id);

                StockMovement::create([
                    'product_id'        => $product->id,
                    'department_id'     => $department->id,
                    'type'              => StockMovement::TYPE_OUT,
                    'quantity'          => $quantity,
                    'price'             => $price,
                    'movementable_type' => Requisition::class,
                    'movementable_id'   => $requisition->id,
                ]);

                $this->updateStockLevel($product->id, $department->id, StockMovement::TYPE_OUT, $quantity, $price);
            }
        });
    }

    private function getProductPrice(int $productId, int $departmentId): float
    {
        $level = StockLevel::where('product_id', $productId)
            ->where('department_id', $departmentId)
            ->first();

        return $level?->weighted_average_price > 0
            ? (float) $level->weighted_average_price
            : fake()->randomFloat(2, 5, 200);
    }

    private function updateStockLevel(int $productId, int $departmentId, string $type, int $quantity, float $price): void
    {
        $level = StockLevel::firstOrCreate(
            ['product_id' => $productId, 'department_id' => $departmentId],
            ['current_quantity' => 0, 'weighted_average_price' => 0, 'total_value' => 0],
        );

        $newQuantity = max(0, $level->current_quantity - $quantity);

        $level->update([
            'current_quantity' => $newQuantity,
            'total_value'      => round($newQuantity * $level->weighted_average_price, 2),
        ]);
    }
}
