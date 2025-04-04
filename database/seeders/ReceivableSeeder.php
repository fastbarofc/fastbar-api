<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Receivable;
use App\Models\Event;

class ReceivableSeeder extends Seeder
{
    public function run(): void
    {
        Receivable::factory()->count(10)->create();
    }
}
