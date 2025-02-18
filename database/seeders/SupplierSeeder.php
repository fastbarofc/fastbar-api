<?php

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{

    public function run(): void
    {
        Supplier::factory(20)->create()->each(function ($supplier) {
            $supplier->address()->save(Address::factory()->make());
        });
    }
}
