<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    private static $paymentMethodNames = [
        'Dinheiro',
        'PIX',
        'Cartão de Crédito',
        'Cartão de Débito',
        'Boleto Bancário',
        'Transferência Bancária',
        'Cheque',
    ];

    private static $index = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = self::$paymentMethodNames[self::$index % count(self::$paymentMethodNames)];
        self::$index++;

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
        ];
    }
}
