<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\FinancialOperation;
use App\Models\CostCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::inRandomOrder()->first()?->id ?? 1,
            'price' => $this->faker->randomFloat(2, 100, 50000),
            'financial_operation_id' => FinancialOperation::inRandomOrder()->first()?->id ?? 1,
            'cost_center_id' => CostCenter::inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
