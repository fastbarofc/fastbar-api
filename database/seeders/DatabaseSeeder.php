<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CollaboratorSeeder::class,
            EventPlaceSeeder::class,
            PartnerSeeder::class,
            MaterialSeeder::class,
            DecoratorSeeder::class,
            KitSeeder::class,
            ProductSeeder::class,
            ClientSeeder::class,
            CeremonialSeeder::class,
            EventSeeder::class,
            EventGiftSeeder::class,
            BlockedDateSeeder::class,
            UtensilSeeder::class,
            StockSeeder::class,
            ProductGroupSeeder::class,
            EventKitSeeder::class,
        ]);
    }
}
