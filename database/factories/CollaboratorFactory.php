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
    public function definition(): array
    {
        $email = $this->faker->unique()->safeEmail;

        $hasLogin = $this->faker->boolean;

        if($hasLogin) {
            $user = User::factory()->state([
                'email' => $email,
            ])->create();
        }


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
