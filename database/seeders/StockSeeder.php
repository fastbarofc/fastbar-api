<?php

namespace Database\Seeders;

use App\Models\Stock;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $stocks = ['Bebidas', 'Produção', 'Suprimentos'];

        foreach ($stocks as $name) {
            Stock::firstOrCreate(['name' => $name]);
        }
    }
}
