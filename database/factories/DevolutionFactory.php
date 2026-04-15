<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Devolution;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Devolution>
 */
class DevolutionFactory extends Factory
{
    protected $model = Devolution::class;

    public function definition(): array
    {
        return [
            'requisition_id'   => Requisition::inRandomOrder()->first()?->id ?? 1,
            'department_id'    => Department::inRandomOrder()->first()?->id ?? 1,
            'registered_by_id' => User::inRandomOrder()->first()?->id ?? 1,
            'notes'            => $this->faker->optional(0.4)->sentence(),
        ];
    }
}
