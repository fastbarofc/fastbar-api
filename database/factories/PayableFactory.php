<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Payable;
use App\Models\FinancialOperation;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payable>
 */
class PayableFactory extends Factory
{

    protected $model = Payable::class;

    public function definition(): array
    {

        $financialOperation = FinancialOperation::factory()->create();

        return [
            'description' => $this->faker->sentence,
            'amount' => $this->faker->randomFloat(2, 10, 10000),
            'due_date' => $this->faker->date,
            'paid_at' => $this->faker->optional()->dateTime,
            'status' => $this->faker->randomElement(['pending', 'paid', 'overdue']),
            'financial_operation_id' => $financialOperation->id,
            'payableable_id' => $financialOperation->id,
            'payableable_type' => FinancialOperation::class,
        ];
    }
}
