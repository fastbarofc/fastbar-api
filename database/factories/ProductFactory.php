<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Unit;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $unit = Unit::all()->random();

        $tipos = [
            'Arroz', 'Feijão', 'Macarrão', 'Salada', 'Carne', 'Frango',
            'Peixe', 'Sopa', 'Suco', 'Refrigerante', 'Água', 'Pudim',
            'Mousse', 'Bolo', 'Torta', 'Legumes', 'Farofa', 'Batata',
        ];

        $preparos = [
            'Assado', 'Grelhado', 'Cozido', 'Frito', 'Ao Molho',
            'Refogado', 'Natural', 'Temperado', 'Gelado', 'Especial'
        ];

        return [
            'name' => $this->faker->randomElement($tipos) 
            . ' ' 
            . $this->faker->randomElement($preparos) 
            . ' ' 
            . $this->faker->unique()->numberBetween(1, 10000),
            'unit_id'           => $unit->id,
            'value'             => $this->faker->randomFloat(2, 20, 3000),
            'marketable'        => $this->faker->boolean,
            'display_checklist' => $this->faker->boolean,
            'own_production'    => $this->faker->boolean,
        ];
    }
}
