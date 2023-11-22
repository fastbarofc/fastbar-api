<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Sector;
use App\Models\Office;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collaborator>
 */
class CollaboratorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $email = $this->faker->unique()->safeEmail;

        $user = User::factory()->state([
            'email' => $email,
        ])->create();

        $hasLogin = $this->faker->boolean;

        $office = Office::all()->random();

        return [
            'name'       => $this->faker->name,
            'nickname'   => $this->faker->name,
            'birth_date' => $this->faker->date(),
            'cpf'        => $this->faker->cpf(false),
            'rg'         => $this->faker->rg(false),
            'phone_1'    => $this->faker->phoneNumber,
            'phone_2'    => $this->faker->phoneNumber,
            'email'      => $email,
            'sector_id'  => $office->sector_id,
            'office_id'  => $office->id,
            'user_id'    => $hasLogin ? $user->id : null,
            'has_login'  => $hasLogin,
        ];
    }
}
