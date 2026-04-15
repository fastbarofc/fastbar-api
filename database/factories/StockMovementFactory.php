<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        $movementable = PurchaseOrder::inRandomOrder()->first();

        return [
            'product_id'        => Product::inRandomOrder()->first()?->id ?? 1,
            'department_id'     => Department::inRandomOrder()->first()?->id ?? 1,
            'type'              => $this->faker->randomElement([StockMovement::TYPE_IN, StockMovement::TYPE_OUT]),
            'quantity'          => $this->faker->numberBetween(1, 50),
            'price'             => $this->faker->randomFloat(2, 5, 500),
            'movementable_type' => $movementable ? PurchaseOrder::class : null,
            'movementable_id'   => $movementable?->id,
        ];
    }
}
