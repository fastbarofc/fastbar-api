<?php

namespace Database\Factories;

use App\Models\Stock;
use App\Models\Event;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Requisition>
 */
class RequisitionFactory extends Factory
{
    protected $model = Requisition::class;

    public function definition(): array
    {
        $stocks = Stock::inRandomOrder()->take(2)->get();

        return [
            'event_id'             => Event::inRandomOrder()->first()?->id ?? 1,
            'stock_id'             => $stocks->first()?->id ?? 1,
            'destination_stock_id' => $stocks->last()?->id ?? 1,
            'requester_id'         => User::inRandomOrder()->first()?->id ?? 1,
            'notes'                => $this->faker->optional(0.4)->sentence(),
            'status'               => $this->faker->randomElement([
                Requisition::STATUS_OPEN,
                Requisition::STATUS_PARTIAL,
                Requisition::STATUS_RETURNED,
            ]),
        ];
    }
}
