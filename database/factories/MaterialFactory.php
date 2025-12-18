<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    private static $materialNames = [
        'Carrinho de Bebida',
        'Mesa Redonda 1,60m',
        'Mesa Redonda 1,80m',
        'Mesa Retangular',
        'Cadeira Estofada',
        'Cadeira Simples',
        'Toalha de Mesa',
        'Jogo de Pratos',
        'Jogo de Talheres',
        'Jogo de Copos',
        'Jogo de Xícaras',
        'Bandeja de Serviço',
        'Carrinho de Serviço',
        'Mesa de Bebidas',
        'Mesa de Doces',
        'Mesa de Salgados',
        'Suporte para Bolo',
        'Carrinho de Chá',
        'Carrinho de Café',
        'Mesa Auxiliar',
        'Carrinho de Buffet',
        'Bandeja de Prata',
        'Jarra de Água',
        'Jarra de Suco',
        'Carrinho de Sobremesas',
        'Mesa de Centro',
        'Carrinho de Aperitivos',
        'Suporte para Doces',
        'Carrinho de Bebidas Quentes',
        'Mesa de Apoio',
    ];

    private static $index = 0;

    public function definition(): array
    {
        $description = self::$materialNames[self::$index % count(self::$materialNames)];
        self::$index++;

        return [
            'description' => $description,
            'quantity' => $this->faker->numberBetween(10, 100),
        ];
    }
}
