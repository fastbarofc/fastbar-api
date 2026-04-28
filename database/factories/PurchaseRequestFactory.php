<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use App\Models\Sector;
use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
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
            'requester_id' => User::inRandomOrder()->first()?->id ?? 1,
            'sector_id' => Sector::inRandomOrder()->first()?->id ?? 1,
            'expected_date' => $this->faker->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'status' => $this->faker->randomElement([
                PurchaseRequest::STATUS_NOT_CONFIRMED,
                PurchaseRequest::STATUS_CONFIRMED,
            ]),
        ];
    }
}
