<?php

namespace Database\Factories;

use App\Models\Department;
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
        $departments = Department::inRandomOrder()->take(2)->get();

        return [
            'event_id'                 => Event::inRandomOrder()->first()?->id ?? 1,
            'department_id'            => $departments->first()?->id ?? 1,
            'destination_department_id'=> $departments->last()?->id ?? 1,
            'requester_id'             => User::inRandomOrder()->first()?->id ?? 1,
            'notes'                    => $this->faker->optional(0.4)->sentence(),
            'status'                   => $this->faker->randomElement([
                Requisition::STATUS_OPEN,
                Requisition::STATUS_PARTIAL,
                Requisition::STATUS_RETURNED,
            ]),
        ];
    }
}
