<?php

namespace Database\Seeders;

use App\Models\ParthenonBankAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ParthenonBankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ParthenonBankAccount::factory(5)->create();
    }
}
