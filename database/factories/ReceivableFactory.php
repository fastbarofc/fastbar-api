<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Receivable;
use App\Models\PaymentMethod;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Receivable>
 */
class ReceivableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $paymentMethod = PaymentMethod::factory()->create();

        return [
            'description' => $this->faker->sentence,
            'amount' => $this->faker->randomFloat(2, 10, 10000),
            'due_date' => $this->faker->date,
            'paid_at' => $this->faker->optional()->dateTime,
            'status' => $this->faker->randomElement(['pending', 'paid', 'overdue']),
            'payment_method_id' => $paymentMethod->id,
            'receivableable_id' => $paymentMethod->id,
            'receivableable_type' => PaymentMethod::class,
        ];
    }
}
